<?php

namespace App\Services\Care;

use App\Models\CareOccurrence;
use App\Models\CareRecipient;
use App\Models\Organization;

class ProposalGuard
{
    public function __construct(private AccessControl $access) {}

    public function lock(CareRecipient $recipient): CareRecipient
    {
        Organization::whereKey($recipient->organization_id)->lockForUpdate()->firstOrFail();

        return CareRecipient::whereKey($recipient->id)->lockForUpdate()->firstOrFail();
    }

    public function blockers(CareRecipient $recipient, $proposal, string $area, bool $profile = false): array
    {
        if ($proposal->status !== 'pending') {
            return [];
        }
        $reasons = [];
        if ($proposal->operation === 'cancel_occurrence') {
            $occurrence = CareOccurrence::find($proposal->payload['exception']['occurrence_id']);
            if (! $occurrence || $occurrence->status !== 'scheduled' || ! $occurrence->starts_at->isFuture()) {
                $reasons[] = ['user_id' => null, 'message' => 'A ocorrência já passou ou não está disponível para cancelamento. Retire e apresente uma nova proposta.'];
            }
        }
        if ($recipient->status !== 'active') {
            $reasons[] = ['user_id' => null, 'message' => 'O assistido está arquivado.'];
        }
        $ids = $proposal->decisions->pluck('user_id')->push($proposal->created_by)->unique();
        foreach ($ids as $id) {
            $member = $recipient->organization->users()->whereKey($id)->wherePivot('status', 'active')->first();
            if (! $member || ! $this->access->allowed($member, $recipient, $area, true) || ($profile && ($proposal->payload['before']['health_profile'] ?? null) != ($proposal->payload['data']['health_profile'] ?? null) && ! $this->access->allowed($member, $recipient, 'health', true)) || ($profile && ! $this->access->responsible($member, $recipient))) {
                $reasons[] = ['user_id' => (int) $id, 'message' => 'Participante necessário sem acesso vigente para decidir.'];
            }
        }
        if (isset($proposal->payload['responsible_ids'])) {
            $before = $proposal->payload['responsible_ids'];
            $now = $this->access->responsibleIds($recipient);
            sort($before);
            sort($now);
            if ($before !== $now) {
                $reasons[] = ['user_id' => null, 'message' => 'A rede de responsáveis mudou. Retire esta proposta e apresente uma nova versão.'];
            }
        }

        return $reasons;
    }

    public function ensureApplicable(CareRecipient $recipient, $proposal, string $area, bool $profile = false): void
    {
        abort_if(count($this->blockers($recipient, $proposal, $area, $profile)) > 0, 409, 'Proposta bloqueada: um participante perdeu acesso ou a rede de responsáveis mudou. Retire e apresente uma nova versão.');
    }
}
