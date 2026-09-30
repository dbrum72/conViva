<?php

namespace App\Services\Care;

use App\Models\CareRecipient;
use App\Models\User;

class RecipientProfile
{
    public function __construct(private AccessControl $access) {}

    public function filter(User $user, CareRecipient $recipient, array $data): array
    {
        foreach (['routine', 'health'] as $area) {
            if (! $this->access->allowed($user, $recipient, $area)) {
                unset($data[$area.'_profile']);
            }
        }

        return $data;
    }

    public function present(User $user, CareRecipient $recipient): array
    {
        return $this->filter($user, $recipient, [...$recipient->toArray(),
            'routine_profile' => $recipient->routine_profile,
            'health_profile' => $recipient->health_profile,
        ]);
    }

    public function authorizeChanges(User $user, CareRecipient $recipient, array $data): void
    {
        foreach (['routine', 'health'] as $area) {
            $field = $area.'_profile';
            if (array_key_exists($field, $data) && $data[$field] != $recipient->$field) {
                $this->access->authorize($user, $recipient, $area, true);
            }
        }
    }
}
