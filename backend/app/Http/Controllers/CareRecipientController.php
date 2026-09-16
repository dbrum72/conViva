<?php

namespace App\Http\Controllers;

use App\Http\Requests\CareRecipientRequest;
use App\Models\CareRecipient;
use App\Models\Organization;
use App\Services\Care\AccessControl;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CareRecipientController extends Controller
{
    public function __construct(private AccessControl $access) {}

    public function index(Request $r)
    {
        return $this->access->visible($r->user())->orderBy('name')->get()->map(fn ($p) => [...$p->toArray(), 'capabilities' => $this->access->capabilities($r->user(), $p), 'can_manage_profile' => (int) $p->created_by === (int) $r->user()->id && count($this->access->responsibleIds($p)) === 1]);
    }

    public function store(CareRecipientRequest $r)
    {
        abort_unless($r->user()->can('recipients.create'), 403);
        $recipient = DB::transaction(function () use ($r) {
            $group = Organization::whereKey(app(CurrentOrganization::class)->id())->lockForUpdate()->firstOrFail();
            abort_if($group->recipients()->exists(), 409, 'Este grupo já possui um assistido, mesmo que arquivado. Crie outro grupo para cuidar de outra pessoa ou pet.');
            $p = CareRecipient::create([...$r->validated(), 'created_by' => $r->user()->id]);
            $p->accesses()->create(['user_id' => $r->user()->id, 'areas' => AccessControl::AREAS, 'can_edit' => true]);

            return $p;
        });

        return response()->json($recipient, 201);
    }

    public function show(Request $r, CareRecipient $recipient)
    {
        $this->access->authorize($r->user(), $recipient);

        return [...$recipient->toArray(), 'capabilities' => $this->access->capabilities($r->user(), $recipient), 'can_manage_access' => $this->access->responsible($r->user(), $recipient)];
    }

    public function update(CareRecipientRequest $r, CareRecipient $recipient)
    {
        $this->access->authorize($r->user(), $recipient, 'routine', true);
        abort_unless((int) $recipient->created_by === (int) $r->user()->id, 403, 'Somente o autor pode alterar este cadastro.');
        abort_if(count($this->access->responsibleIds($recipient)) > 1, 409, 'O cadastro compartilhado não pode ser alterado unilateralmente.');
        $recipient->update($r->validated());

        return $recipient;
    }

    public function destroy(Request $r, CareRecipient $recipient)
    {
        abort_unless($this->access->responsible($r->user(), $recipient), 403);
        abort_unless((int) $recipient->created_by === (int) $r->user()->id, 403);
        abort_if(count($this->access->responsibleIds($recipient)) > 1, 409, 'Não é permitido arquivar unilateralmente um assistido compartilhado.');
        $recipient->update(['status' => 'archived']);

        return response()->noContent();
    }

    public function accesses(Request $r, CareRecipient $recipient)
    {
        abort_unless($this->access->responsible($r->user(), $recipient), 403);

        return $recipient->accesses()->with('user:id,name,email')->get();
    }

    public function grant(Request $r, CareRecipient $recipient)
    {
        abort_unless($this->access->responsible($r->user(), $recipient), 403);
        $data = $r->validate(['user_id' => 'required|integer', 'areas' => 'required|array|min:1', 'areas.*' => 'required|in:routine,health,documents,finance', 'can_edit' => 'required|boolean', 'expires_at' => 'nullable|date|after:now']);
        abort_unless($recipient->organization->users()->whereKey($data['user_id'])->wherePivot('status', 'active')->exists(), 422, 'Membro não pertence ao grupo.');
        $target = $recipient->organization->users()->whereKey($data['user_id'])->firstOrFail();
        abort_if($this->access->responsible($target, $recipient), 409, 'Os acessos de outro responsável não podem ser reduzidos ou substituídos unilateralmente.');
        if ($target->hasRole('responsavel')) {
            $data['areas'] = AccessControl::AREAS;
            $data['can_edit'] = true;
            $data['expires_at'] = null;
        }
        if ($target->hasRole('observador')) {
            $data['can_edit'] = false;
        }
        $data['areas'] = array_values(array_unique($data['areas']));

        return $recipient->accesses()->updateOrCreate(['user_id' => $data['user_id']], $data);
    }

    public function revoke(Request $r, CareRecipient $recipient, int $user)
    {
        abort_unless($this->access->responsible($r->user(), $recipient), 403);
        $target = $recipient->organization->users()->whereKey($user)->firstOrFail();
        abort_if($this->access->responsible($target, $recipient), 409, 'Um responsável não pode ser removido unilateralmente.');
        $recipient->accesses()->where('user_id', $user)->delete();

        return response()->noContent();
    }
}
