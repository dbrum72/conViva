<?php

namespace App\Services\Care;

use App\Models\CareEntry;
use App\Models\CareNotification;
use App\Models\CareOccurrence;
use App\Models\CareProposal;
use App\Models\CareRecipient;
use App\Models\CareSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CareRecords
{
    public function __construct(private AccessControl $access, private ProposalGuard $guard, private ProposalNotifications $notifications) {}

    public function save(User $user, CareRecipient $recipient, array $data, ?CareEntry $entry = null): CareEntry
    {
        $area = $this->access->area($data['kind']);
        $this->access->authorize($user, $recipient, $area, true);
        if ($entry) {
            $this->access->owner($user, $entry);
        }

        return DB::transaction(function () use ($user, $recipient, $data, $entry, $area) {
            // Serialize against membership changes as well as other proposals.
            $recipient = $this->guard->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $this->access->authorize($user, $recipient, $area, true);
            if ($entry) {
                $entry = $recipient->entries()->lockForUpdate()->findOrFail($entry->id);
                abort_if($entry->kind !== $data['kind'], 422, 'O tipo do registro não pode ser alterado.');
                $this->ensureMutable($entry);
                abort_if(isset($data['revision']) && (int) $data['revision'] !== (int) $entry->revision, 409, 'A versão vigente mudou. Atualize o registro antes de propor alterações.');
            }
            if ($entry && ($entry->schedule || ! empty($data['schedule']))) {
                abort_unless(isset($data['revision']), 422, 'Informe a versão vigente ao revisar uma programação.');
            }
            $data = app(GenerateCareOccurrences::class)->normalize($data, $entry);
            $data['publish_to_agenda'] = (bool) ($data['publish_to_agenda'] ?? $entry?->publish_to_agenda ?? false);
            if ($data['publish_to_agenda'] && ! (array_key_exists('due_at', $data) ? $data['due_at'] : $entry?->due_at)) {
                throw ValidationException::withMessages(['due_at' => 'Informe a data para publicar na agenda.']);
            }
            foreach (['due_at', 'ends_at'] as $dateField) {
                if (! empty($data[$dateField])) {
                    $data[$dateField] = CarbonImmutable::parse($data[$dateField])->utc()->toISOString();
                }
            }
            unset($data['revision']);
            $affected = array_map('intval', $data['affected_user_ids'] ?? []);
            unset($data['affected_user_ids']);
            $shares = $data['shares'] ?? [];
            unset($data['shares']);
            if ($data['kind'] === 'expense' && ! $shares) {
                $shares = [['user_id' => $user->id, 'amount_cents' => (int) $data['amount_cents']]];
            }
            abort_if($shares && $data['kind'] !== 'expense', 422, 'Rateio permitido apenas em despesas.');
            if ($shares && array_sum(array_column($shares, 'amount_cents')) !== (int) $data['amount_cents']) {
                throw ValidationException::withMessages(['shares' => 'A soma das parcelas deve corresponder ao total da despesa.']);
            }
            foreach ($shares as $share) {
                $affected[] = (int) $share['user_id'];
            }
            if (! empty($data['assigned_user_id'])) {
                $affected[] = (int) $data['assigned_user_id'];
            }
            // Routines affect the shared arrangement: every responsible party must decide.
            if (in_array($data['kind'], ['event', 'task', 'feeding', 'medication', 'vaccine'])) {
                $affected = [...$affected, ...$this->access->responsibleIds($recipient)];
            }
            // Removing someone from a previously agreed arrangement also affects them.
            $affected = array_values(array_unique([...$affected, ...($entry?->affected_user_ids ?? [])]));
            $affected = array_values(array_filter($affected, fn ($id) => $id !== (int) $user->id));
            foreach ($affected as $id) {
                $this->participant($recipient, $id, $area);
            }
            if (! $entry) {
                $entry = $recipient->entries()->create([...$data, 'created_by' => $user->id, 'status' => 'awaiting_approval']);
            }
            $payload = ['data' => $data, 'shares' => $shares, 'affected_user_ids' => $affected];
            $this->propose($user, $recipient, $entry, $payload, $affected, 'save');

            return $this->load($entry);
        });
    }

    private function participant(CareRecipient $recipient, int $id, string $area): void
    {
        $member = $recipient->organization->users()->whereKey($id)->wherePivot('status', 'active')->first();
        if (! $member || $member->hasRole('observador') || ! $this->access->allowed($member, $recipient, $area, true)) {
            throw ValidationException::withMessages(['affected_user_ids' => 'Todo participante afetado precisa de vínculo ativo e acesso de edição à área; observadores não assumem obrigações.']);
        }
    }

    private function ensureMutable(CareEntry $entry): void
    {
        abort_if($entry->related_entry_id, 422, 'Registros de execução são históricos. Acrescente uma correção em novo registro.');
        abort_if($entry->status === 'cancelled', 422, 'Um registro cancelado permanece apenas no histórico.');
        abort_if($entry->shares()->whereNotNull('paid_at')->exists(), 422, 'A despesa possui pagamentos registrados.');
        abort_if($entry->proposals()->where('status', 'pending')->exists(), 409, 'Existe uma proposta aguardando decisão. Aguarde a decisão ou retire a proposta.');
    }

    private function propose(User $user, CareRecipient $recipient, CareEntry $entry, array $payload, array $affected, string $operation): void
    {
        $version = (int) $entry->proposals()->max('version') + 1;
        $payload['responsible_ids'] = $this->access->responsibleIds($recipient);
        $payload['base_revision'] = (int) $entry->revision;
        $payload['before_status'] = $entry->status;
        $payload['before'] = $entry->revision ? $entry->only(['title', 'description', 'due_at', 'ends_at', 'assigned_user_id', 'amount_cents', 'details', 'schedule', 'publish_to_agenda']) : [];
        $payload['before_affected_user_ids'] = $entry->affected_user_ids ?? [];
        $payload['before_shares'] = $entry->revision ? $entry->shares()->get(['user_id', 'amount_cents'])->toArray() : [];
        $proposal = $entry->proposals()->create(['created_by' => $user->id, 'version' => $version, 'operation' => $operation, 'payload' => $payload, 'status' => $affected ? 'pending' : 'accepted']);
        foreach ($affected as $id) {
            $proposal->decisions()->create(['user_id' => $id]);
        }
        if (! $affected) {
            $this->apply($entry, $proposal);
        } elseif (! $entry->revision) {
            $entry->update(['status' => 'awaiting_approval']);
        }
        $this->notifications->send($recipient, $this->access->area($entry->kind), $affected ? 'Proposta aguardando aceite: '.$payload['data']['title'] : 'Registro atualizado: '.$entry->title, $proposal);
    }

    public function decide(User $user, CareRecipient $recipient, int $entryId, int $proposalId, string $decision, ?string $reason): CareEntry
    {
        return DB::transaction(function () use ($user, $recipient, $entryId, $proposalId, $decision, $reason) {
            $recipient = $this->guard->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $entry = $recipient->entries()->lockForUpdate()->findOrFail($entryId);
            $this->access->authorize($user, $recipient, $this->access->area($entry->kind), true);
            $proposal = $entry->proposals()->lockForUpdate()->findOrFail($proposalId);
            abort_unless($proposal->status === 'pending', 409, 'Esta proposta já foi encerrada.');
            $vote = $proposal->decisions()->where('user_id', $user->id)->first();
            abort_unless($vote, 403, 'Somente o participante afetado pode responder por si.');
            abort_unless($vote->status === 'pending', 409, 'Sua decisão já foi registrada.');
            if ($decision === 'rejected' && ! trim($reason ?? '')) {
                throw ValidationException::withMessages(['reason' => 'Informe o motivo da recusa.']);
            }
            if ($decision === 'accepted') {
                $this->guard->ensureApplicable($recipient, $proposal, $this->access->area($entry->kind));
                abort_unless((int) ($proposal->payload['base_revision'] ?? $entry->revision) === (int) $entry->revision, 409, 'A versão vigente mudou.');
            }
            $vote->update(['status' => $decision, 'reason' => $decision === 'rejected' ? trim($reason) : null, 'decided_at' => now()]);
            if ($decision === 'rejected') {
                $proposal->update(['status' => 'rejected']);
                if (! $entry->revision) {
                    $entry->update(['status' => 'rejected']);
                }
            } elseif (! $proposal->decisions()->where('status', '!=', 'accepted')->exists()) {
                // Recheck every participant, including the author, at application time.
                $this->guard->ensureApplicable($recipient, $proposal->fresh(), $this->access->area($entry->kind));
                $proposal->update(['status' => 'accepted']);
                $this->apply($entry, $proposal);
            }
            $this->notifications->send($recipient, $this->access->area($entry->kind), ($decision === 'rejected' ? 'Proposta recusada: ' : 'Aceite registrado: ').$proposal->payload['data']['title'], $proposal);

            return $this->load($entry);
        });
    }

    private function apply(CareEntry $entry, CareProposal $proposal): void
    {
        if ($proposal->operation === 'cancel_occurrence') {
            app(CareAgenda::class)->applyException($entry, $proposal);
            $entry->update(['revision' => $proposal->version]);

            return;
        }
        if ($proposal->operation === 'cancel') {
            $entry->update(['status' => 'cancelled', 'revision' => $proposal->version]);
            app(GenerateCareOccurrences::class)->apply($entry);

            return;
        }
        $payload = $proposal->payload;
        $entry->update([...$payload['data'], 'affected_user_ids' => $payload['affected_user_ids'], 'revision' => $proposal->version, 'status' => 'pending', 'completed_at' => null]);
        app(GenerateCareOccurrences::class)->apply($entry);
        $entry->shares()->delete();
        if ($payload['shares']) {
            $entry->shares()->createMany($payload['shares']);
        }
    }

    public function cancelOccurrence(User $user, CareRecipient $recipient, int $occurrenceId, string $scope): CareEntry
    {
        return DB::transaction(function () use ($user, $recipient, $occurrenceId, $scope) {
            $recipient = $this->guard->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $occurrence = CareOccurrence::with('schedule.entry')->findOrFail($occurrenceId);
            $entry = $recipient->entries()->lockForUpdate()->findOrFail($occurrence->schedule->care_entry_id);
            $this->access->authorize($user, $recipient, $this->access->area($entry->kind), true);
            $this->access->owner($user, $entry);
            $this->ensureMutable($entry);
            abort_unless($occurrence->status === 'scheduled' && $occurrence->starts_at->isFuture(), 409, 'Somente ocorrências futuras sem execução podem ser canceladas.');
            $affected = array_values(array_diff(array_unique([...($entry->affected_user_ids ?? []), ...$this->access->responsibleIds($recipient)]), [$user->id]));
            foreach ($affected as $id) {
                $this->participant($recipient, $id, $this->access->area($entry->kind));
            }
            $this->propose($user, $recipient, $entry, ['data' => ['title' => $entry->title], 'exception' => ['occurrence_id' => $occurrence->id, 'scope' => $scope, 'starts_at' => $occurrence->starts_at->toISOString()], 'affected_user_ids' => $affected], $affected, 'cancel_occurrence');

            return $this->load($entry);
        });
    }

    public function cancel(User $user, CareRecipient $recipient, int $id): CareEntry
    {
        return DB::transaction(function () use ($user, $recipient, $id) {
            $recipient = $this->guard->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $entry = $recipient->entries()->lockForUpdate()->findOrFail($id);
            $this->access->authorize($user, $recipient, $this->access->area($entry->kind), true);
            $this->access->owner($user, $entry);
            $this->ensureMutable($entry);
            $affected = $entry->affected_user_ids ?? [];
            if (in_array($entry->kind, ['event', 'task', 'feeding', 'medication', 'vaccine'])) {
                $affected = array_values(array_unique([...$affected, ...$this->access->responsibleIds($recipient)]));
                $affected = array_values(array_filter($affected, fn ($id) => (int) $id !== (int) $user->id));
            }
            foreach ($affected as $affectedId) {
                $this->participant($recipient, $affectedId, $this->access->area($entry->kind));
            }
            $this->propose($user, $recipient, $entry, ['data' => $entry->only(['title']), 'affected_user_ids' => $affected], $affected, 'cancel');

            return $this->load($entry);
        });
    }

    public function withdraw(User $user, CareRecipient $recipient, int $id, int $proposalId): CareEntry
    {
        return DB::transaction(function () use ($user, $recipient, $id, $proposalId) {
            $recipient = $this->guard->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $entry = $recipient->entries()->lockForUpdate()->findOrFail($id);
            $this->access->authorize($user, $recipient, $this->access->area($entry->kind), true);
            $this->access->owner($user, $entry);
            $proposal = $entry->proposals()->findOrFail($proposalId);
            abort_unless($proposal->status === 'pending', 409, 'Proposta já encerrada.');
            $proposal->update(['status' => 'withdrawn']);
            if (! $entry->revision) {
                $entry->update(['status' => 'withdrawn']);
            }
            $this->notifications->send($recipient, $this->access->area($entry->kind), 'Proposta retirada: '.$entry->title, $proposal);

            return $this->load($entry);
        });
    }

    public function complete(User $user, CareRecipient $recipient, int $id): CareEntry
    {
        return DB::transaction(function () use ($user, $recipient, $id) {
            $entry = $recipient->entries()->lockForUpdate()->findOrFail($id);
            $this->access->authorize($user, $recipient, $this->access->area($entry->kind), true);
            $this->access->owner($user, $entry);
            abort_unless(in_array($entry->status, ['pending', 'completed']), 422, 'Aguarde o aceite antes de executar este cuidado.');
            abort_if($entry->kind === 'expense', 422, 'Registre o pagamento da própria parcela.');
            abort_if($entry->proposals()->where('status', 'pending')->exists(), 409, 'Existe uma alteração aguardando decisão.');
            $entry->update(['status' => 'completed', 'completed_at' => $entry->completed_at ?? now()]);

            return $this->load($entry);
        });
    }

    public function execute(User $user, CareRecipient $recipient, int $id, ?string $description, ?string $occurredAt = null): CareEntry
    {
        return DB::transaction(function () use ($user, $recipient, $id, $description, $occurredAt) {
            $recipient = $this->guard->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $task = $recipient->entries()->lockForUpdate()->findOrFail($id);
            $this->access->authorize($user, $recipient, $this->access->area($task->kind), true);
            abort_if($task->related_entry_id, 422, 'Este registro já representa uma execução.');
            abort_if($task->due_at || CareSchedule::where('care_entry_id', $task->id)->exists(), 422, 'Registre a execução da ocorrência na agenda.');
            abort_if(in_array($task->kind, ['expense', 'journal']), 422, 'Este registro não representa um serviço de cuidado.');
            abort_unless($this->access->canExecuteCare($user, $recipient, $task->kind, $task->assigned_user_id), 403, 'Você não pode registrar esta execução.');
            abort_unless(in_array($task->status, ['pending', 'completed']) && ! $task->proposals()->where('status', 'pending')->exists(), 409, 'O cuidado precisa estar confirmado e sem alterações pendentes.');
            abort_if($recipient->entries()->where('related_entry_id', $id)->exists(), 409, 'A execução já foi registrada.');
            $record = $recipient->entries()->create(['created_by' => $user->id, 'related_entry_id' => $id, 'kind' => $task->kind, 'title' => 'Execução: '.$task->title, 'description' => $description, 'due_at' => $occurredAt ? CarbonImmutable::parse($occurredAt)->utc() : now(), 'status' => 'completed', 'completed_at' => now(), 'revision' => 1, 'details' => $task->details]);
            $record->proposals()->create(['created_by' => $user->id, 'version' => 1, 'operation' => 'save', 'status' => 'accepted', 'payload' => ['data' => $record->only(['title', 'description', 'details']), 'shares' => [], 'affected_user_ids' => []]]);
            $this->notify($recipient, $this->access->area($task->kind), 'Execução registrada: '.$task->title);

            return $this->load($record);
        });
    }

    public function load(CareEntry $entry): CareEntry
    {
        return $this->decorate($entry->refresh()->load('shares.user:id,name', 'author:id,name', 'proposals.decisions.user:id,name'));
    }

    public function decorate(CareEntry $entry): CareEntry
    {
        $entry->setAttribute('has_occurrences', (! $entry->related_entry_id && $entry->revision && $entry->due_at) || CareSchedule::where('care_entry_id', $entry->id)->exists());
        $user = auth()->user();
        $entry->setAttribute('can_execute', $user && ! $entry->has_occurrences && ! $entry->related_entry_id
            && ! in_array($entry->kind, ['expense', 'journal']) && in_array($entry->status, ['pending', 'completed'])
            && ! $entry->proposals->contains('status', 'pending')
            && $this->access->canExecuteCare($user, $entry->recipient, $entry->kind, $entry->assigned_user_id)
            && ! CareEntry::where('related_entry_id', $entry->id)->exists());
        foreach ($entry->proposals as $proposal) {
            $proposal->setAttribute('blockers', $this->guard->blockers($entry->recipient, $proposal, $this->access->area($entry->kind)));
        }

        return $entry;
    }

    private function notify(CareRecipient $recipient, string $area, string $message): void
    {
        foreach ($recipient->organization->users()->wherePivot('status', 'active')->get() as $member) {
            if ($this->access->allowed($member, $recipient, $area)) {
                CareNotification::create(['care_recipient_id' => $recipient->id, 'user_id' => $member->id, 'area' => $area, 'message' => $message]);
            }
        }
    }
}
