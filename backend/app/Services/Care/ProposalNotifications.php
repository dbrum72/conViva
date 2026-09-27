<?php

namespace App\Services\Care;

use App\Models\CareNotification;
use App\Models\CareProfileProposal;
use App\Models\CareRecipient;
use Illuminate\Support\Facades\DB;

class ProposalNotifications
{
    public function __construct(private AccessControl $access) {}

    public function send(CareRecipient $recipient, string $area, string $message, $proposal = null): void
    {
        foreach ($recipient->organization->users()->wherePivot('status', 'active')->get() as $member) {
            if (! $this->access->allowed($member, $recipient, $area)) {
                continue;
            }
            $notification = CareNotification::create(['care_recipient_id' => $recipient->id, 'user_id' => $member->id, 'area' => $area, 'message' => $message]);
            if ($proposal) {
                DB::table('care_notification_targets')->insert([
                    'care_notification_id' => $notification->id,
                    $proposal instanceof CareProfileProposal ? 'care_profile_proposal_id' : 'care_proposal_id' => $proposal->id,
                ]);
            }
        }
    }
}
