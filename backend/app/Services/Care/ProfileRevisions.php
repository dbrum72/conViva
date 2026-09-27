<?php

namespace App\Services\Care;

use App\Models\CareProfileProposal;
use App\Models\CareRecipient;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProfileRevisions
{
    public const FIELDS = ['name', 'kind', 'birth_date', 'species', 'breed', 'status'];

    public function __construct(private AccessControl $access, private ProposalGuard $guard, private ProposalNotifications $notifications) {}

    public function snapshot(CareRecipient $recipient): array
    {
        return Arr::only($recipient->toArray(), self::FIELDS);
    }

    public function propose(User $user, CareRecipient $recipient, array $data, string $operation, int $version): CareProfileProposal
    {
        return DB::transaction(function () use ($user, $recipient, $data, $operation, $version) {
            $recipient = $this->guard->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $this->authorizeAuthor($user, $recipient);
            abort_unless($recipient->status === 'active', 409, 'Este assistido está arquivado.');
            $query = CareProfileProposal::where('care_recipient_id', $recipient->id);
            abort_if((clone $query)->where('status', 'pending')->exists(), 409, 'Existe uma revisão cadastral aguardando decisão.');
            $latest = (int) (clone $query)->max('version');
            abort_unless($latest === $version, 409, 'O cadastro mudou. Atualize a página antes de propor outra revisão.');
            $before = $this->snapshot($recipient);
            $after = $operation === 'archive' ? [...$before, 'status' => 'archived'] : [...$before, ...$data, 'status' => 'active'];
            if ($after['kind'] !== 'pet') {
                $after['species'] = null;
                $after['breed'] = null;
            }
            $ids = $this->access->responsibleIds($recipient);
            $proposal = CareProfileProposal::create([
                'care_recipient_id' => $recipient->id, 'created_by' => $user->id,
                'version' => $latest + 1, 'operation' => $operation,
                'payload' => ['before' => $before, 'data' => $after, 'responsible_ids' => $ids],
            ]);
            foreach (array_diff($ids, [$user->id]) as $id) {
                $proposal->decisions()->create(['user_id' => $id]);
            }
            if (! $proposal->decisions()->exists()) {
                $this->apply($recipient, $proposal);
            }
            $this->notifications->send($recipient, 'routine', 'Revisão cadastral: '.$recipient->name, $proposal);

            return $proposal->refresh()->load('decisions.user:id,name');
        });
    }

    public function decide(User $user, CareRecipient $recipient, int $id, string $decision, ?string $reason): CareProfileProposal
    {
        return DB::transaction(function () use ($user, $recipient, $id, $decision, $reason) {
            $recipient = $this->guard->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $this->access->authorize($user, $recipient, 'routine', true);
            abort_unless($this->access->responsible($user, $recipient), 403);
            $proposal = CareProfileProposal::where('care_recipient_id', $recipient->id)->lockForUpdate()->findOrFail($id);
            abort_unless($proposal->status === 'pending', 409, 'Esta proposta já foi encerrada.');
            $vote = $proposal->decisions()->where('user_id', $user->id)->first();
            abort_unless($vote, 403, 'Responda somente pela sua própria participação.');
            abort_unless($vote->status === 'pending', 409, 'Sua decisão já foi registrada.');
            if ($decision === 'accepted') {
                $this->guard->ensureApplicable($recipient, $proposal, 'routine', true);
            }
            $vote->update(['status' => $decision, 'reason' => $decision === 'rejected' ? trim($reason ?? '') : null, 'decided_at' => now()]);
            if ($decision === 'rejected') {
                $proposal->update(['status' => 'rejected']);
            } elseif (! $proposal->decisions()->where('status', '!=', 'accepted')->exists()) {
                $this->apply($recipient, $proposal->fresh());
            }
            $this->notifications->send($recipient, 'routine', 'Decisão sobre cadastro: '.$recipient->name, $proposal);

            return $proposal->refresh()->load('decisions.user:id,name');
        });
    }

    public function withdraw(User $user, CareRecipient $recipient, int $id): CareProfileProposal
    {
        return DB::transaction(function () use ($user, $recipient, $id) {
            $recipient = $this->guard->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $this->access->authorize($user, $recipient, 'routine', true);
            $proposal = CareProfileProposal::where('care_recipient_id', $recipient->id)->lockForUpdate()->findOrFail($id);
            abort_unless((int) $proposal->created_by === (int) $user->id, 403);
            abort_unless($proposal->status === 'pending', 409, 'Esta proposta já foi encerrada.');
            $proposal->update(['status' => 'withdrawn']);
            $this->notifications->send($recipient, 'routine', 'Revisão cadastral retirada: '.$recipient->name, $proposal);

            return $proposal->load('decisions.user:id,name');
        });
    }

    private function authorizeAuthor(User $user, CareRecipient $recipient): void
    {
        $this->access->authorize($user, $recipient, 'routine', true);
        abort_unless($this->access->responsible($user, $recipient) && (int) $recipient->created_by === (int) $user->id, 403, 'Somente o responsável autor pode propor uma revisão deste cadastro.');
    }

    private function apply(CareRecipient $recipient, CareProfileProposal $proposal): void
    {
        $this->guard->ensureApplicable($recipient, $proposal, 'routine', true);
        abort_unless($this->snapshot($recipient) == $proposal->payload['before'], 409, 'O cadastro vigente mudou. Retire a proposta e apresente uma nova revisão.');
        $recipient->update($proposal->payload['data']);
        $proposal->update(['status' => 'accepted']);
    }
}
