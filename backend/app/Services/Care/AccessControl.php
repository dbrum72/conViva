<?php

namespace App\Services\Care;

use App\Models\CareEntry;
use App\Models\CareRecipient;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Database\Eloquent\Builder;

class AccessControl
{
    public const AREAS = ['routine', 'health', 'documents', 'finance'];

    public function administrator(User $user): bool
    {
        return $user->hasRole('responsavel');
    }

    public function allowed(User $user, CareRecipient $recipient, ?string $area = null, bool $write = false): bool
    {
        $group = app(CurrentOrganization::class)->get();
        if (! $group || (int) $recipient->organization_id !== (int) $group->id) {
            return false;
        }
        if (! $group->users()->whereKey($user->id)->wherePivot('status', 'active')->exists()) {
            return false;
        }
        if ($this->administrator($user) && (int) $recipient->created_by === (int) $user->id) {
            return true;
        }
        if ($write && ($user->hasRole('observador') || ! $user->can('care.write'))) {
            return false;
        }

        return $recipient->accesses()->where('user_id', $user->id)->when($area, fn ($q) => $q->whereJsonContains('areas', $area))->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->when($write, fn ($q) => $q->where('can_edit', true))->exists();
    }

    public function authorize(User $user, CareRecipient $recipient, ?string $area = null, bool $write = false): void
    {
        abort_unless($this->allowed($user, $recipient, $area, $write), 403, 'Acesso não autorizado a esta área do assistido.');
    }

    public function visible(User $user, ?string $area = null): Builder
    {
        return CareRecipient::query()->where(fn ($q) => $q->when($this->administrator($user), fn ($q) => $q->where('created_by', $user->id))->orWhereHas('accesses', fn ($a) => $a->where('user_id', $user->id)->when($area, fn ($q) => $q->whereJsonContains('areas', $area))->where(fn ($e) => $e->whereNull('expires_at')->orWhere('expires_at', '>', now()))));
    }

    public function responsible(User $user, CareRecipient $recipient): bool
    {
        return $this->administrator($user) && $this->allowed($user, $recipient);
    }

    public function responsibleIds(CareRecipient $recipient): array
    {
        return $recipient->organization->users()->wherePivot('status', 'active')->get()->filter(fn ($u) => $this->responsible($u, $recipient))->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function owner(User $user, CareEntry $entry): void
    {
        abort_unless((int) $entry->created_by === (int) $user->id, 403, 'Somente o autor pode alterar este registro.');
    }

    public function capabilities(User $user, CareRecipient $recipient): array
    {
        return collect(self::AREAS)->mapWithKeys(fn ($area) => [$area => ['view' => $this->allowed($user, $recipient, $area), 'edit' => $this->allowed($user, $recipient, $area, true)]])->all();
    }

    public function area(string $kind): string
    {
        return match ($kind) {
            'medication','vaccine' => 'health','expense' => 'finance',default => 'routine'
        };
    }
}
