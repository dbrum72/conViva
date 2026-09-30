<?php

namespace App\Services\Care;

use App\Models\CareNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CareNotifications
{
    public function __construct(private AccessControl $access) {}

    public function visible(User $user): Builder
    {
        $recipients = $this->access->visible($user)->get();

        return CareNotification::where('user_id', $user->id)->where(function ($query) use ($user, $recipients) {
            $query->whereRaw('1 = 0');
            foreach ($recipients as $recipient) {
                foreach (AccessControl::AREAS as $area) {
                    if ($this->access->allowed($user, $recipient, $area)) {
                        $query->orWhere(fn ($q) => $q->where('care_recipient_id', $recipient->id)->where('area', $area));
                    }
                }
            }
        });
    }
}
