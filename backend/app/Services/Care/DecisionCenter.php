<?php

namespace App\Services\Care;

use App\Models\CareEntry;
use App\Models\CareProfileProposal;
use App\Models\CareProposal;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DecisionCenter
{
    public function __construct(private AccessControl $access, private ProposalGuard $guard) {}

    public function listing(User $user, array $filters)
    {
        $recipients = $this->access->visible($user)->get();
        $entryIds = CareEntry::query()->where(function ($query) use ($recipients, $user) {
            $query->whereRaw('1 = 0');
            foreach ($recipients as $recipient) {
                foreach (['routine' => ['event', 'task', 'journal', 'feeding'], 'health' => ['medication', 'vaccine'], 'finance' => ['expense']] as $area => $kinds) {
                    if ($this->access->allowed($user, $recipient, $area)) {
                        $query->orWhere(fn ($q) => $q->where('care_recipient_id', $recipient->id)->whereIn('kind', $kinds));
                    }
                }
            }
        })->select('id');
        $profileIds = $recipients->filter(fn ($p) => $this->access->allowed($user, $p, 'routine'))->pluck('id');
        $entries = CareProposal::query()->whereIn('care_entry_id', $entryIds);
        $profiles = CareProfileProposal::query()->whereIn('care_recipient_id', $profileIds);
        foreach ([$entries, $profiles] as $query) {
            if (($filters['scope'] ?? 'mine') === 'sent') {
                $query->where('created_by', $user->id);
            } elseif (($filters['scope'] ?? 'mine') === 'mine') {
                $query->whereHas('decisions', fn ($q) => $q->where('user_id', $user->id)->where('status', 'pending'));
                $query->where('status', 'pending');
            }
            if (($filters['status'] ?? '') !== '') {
                $query->where('status', $filters['status']);
            }
        }
        if (($filters['type'] ?? '') === 'profile') {
            $entries->whereRaw('1 = 0');
        } elseif (($filters['type'] ?? '') === 'entry') {
            $profiles->whereRaw('1 = 0');
        }
        $union = $entries->selectRaw("id, created_at, 'entry' AS type")->toBase()
            ->unionAll($profiles->selectRaw("id, created_at, 'profile' AS type")->toBase());
        $page = DB::query()->fromSub($union, 'proposals')->orderByDesc('created_at')->orderByDesc('id')->orderBy('type')
            ->paginate($filters['per_page'] ?? 10);
        $page->setCollection($page->getCollection()->map(fn ($row) => $this->show($user, $row->type, $row->id)));

        return $page;
    }

    public function show(User $user, string $type, int $id): array
    {
        if ($type === 'profile') {
            $proposal = CareProfileProposal::whereHas('recipient')->with('recipient', 'decisions.user:id,name')->findOrFail($id);
            $recipient = $proposal->recipient;
            $entry = null;
            $area = 'routine';
        } else {
            $proposal = CareProposal::whereHas('entry')->with('entry.recipient', 'decisions.user:id,name')->findOrFail($id);
            $entry = $proposal->entry;
            $recipient = $entry->recipient;
            $area = $this->access->area($entry->kind);
        }
        $this->access->authorize($user, $recipient, $area);
        $blockers = $this->guard->blockers($recipient, $proposal, $area, $type === 'profile');
        if ($proposal->status === 'pending' && $type === 'profile' && Arr::only($recipient->toArray(), ProfileRevisions::FIELDS) != $proposal->payload['before']) {
            $blockers[] = ['user_id' => null, 'message' => 'O cadastro vigente mudou. É necessária uma nova proposta.'];
        }
        $canWrite = $this->access->allowed($user, $recipient, $area, true);
        $vote = $proposal->decisions->firstWhere('user_id', $user->id);
        $canRespond = $canWrite && $proposal->status === 'pending' && $vote?->status === 'pending'
            && ($type !== 'profile' || $this->access->responsible($user, $recipient));
        $payload = $proposal->payload;
        $before = $payload['before'] ?? [];
        $after = $proposal->operation === 'cancel' ? [...$before, 'status' => 'cancelled'] : [...$before, ...($payload['data'] ?? [])];
        if ($proposal->operation === 'cancel') {
            $before['status'] = $payload['before_status'] ?? 'pending';
        }
        if ($type === 'entry' && $proposal->operation === 'save') {
            $before['shares'] = $payload['before_shares'] ?? [];
            $after['shares'] = $payload['shares'] ?? [];
            $before['affected_user_ids'] = $payload['before_affected_user_ids'] ?? [];
            $after['affected_user_ids'] = $payload['affected_user_ids'] ?? [];
        }
        $changes = [];
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $field) {
            if (($before[$field] ?? null) != ($after[$field] ?? null)) {
                $changes[] = ['field' => $field, 'before' => $before[$field] ?? null, 'after' => $after[$field] ?? null];
            }
        }

        return [
            ...$proposal->toArray(), 'type' => $type, 'care_recipient_id' => $recipient->id,
            'recipient_name' => $recipient->name, 'entry_id' => $entry?->id,
            'title' => $type === 'profile' ? 'Cadastro de '.$recipient->name : ($payload['data']['title'] ?? $entry->title),
            'author' => User::select('id', 'name')->find($proposal->created_by),
            'comparison_available' => array_key_exists('before', $payload),
            'changes' => $changes, 'blockers' => $blockers,
            'can_accept' => $canRespond && ! $blockers, 'can_reject' => $canRespond,
            'can_withdraw' => $canWrite && $proposal->status === 'pending' && (int) $proposal->created_by === (int) $user->id,
            'has_current_version' => $type === 'profile' || (bool) $entry?->revision,
        ];
    }
}
