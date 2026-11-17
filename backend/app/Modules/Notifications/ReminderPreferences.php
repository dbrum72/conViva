<?php

namespace App\Modules\Notifications;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Support\Facades\DB;

class ReminderPreferences
{
    public function __construct(private CurrentOrganization $organization) {}

    public function get(int $userId): array
    {
        $stored = DB::table('notification_preferences')->where($this->scope($userId))->value('settings');

        return $stored ? json_decode($stored, true, flags: JSON_THROW_ON_ERROR) : [
            'internal' => false, 'email' => false, 'third_party' => false,
            'summary' => false, 'advance' => 60, 'quiet_start' => null, 'quiet_end' => null,
        ];
    }

    public function save(int $userId, array $data): array
    {
        $settings = array_replace($this->get($userId), $data);
        foreach (['internal', 'email', 'third_party', 'summary'] as $key) {
            $settings[$key] = (bool) $settings[$key];
        }
        $settings['advance'] = (int) $settings['advance'];
        DB::table('notification_preferences')->upsert(
            [...$this->scope($userId), 'settings' => json_encode($settings, JSON_THROW_ON_ERROR)],
            ['organization_id', 'user_id'], ['settings'],
        );

        return $settings;
    }

    private function scope(int $userId): array
    {
        return ['organization_id' => $this->organization->id(), 'user_id' => $userId];
    }
}
