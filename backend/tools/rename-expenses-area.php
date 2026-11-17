<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = config('database.connections.mysql');
if (config('database.default') !== 'mysql' || $connection['database'] !== 'conviva_db' || ! empty($connection['url'])
    || DB::selectOne('SELECT DATABASE() AS name')->name !== 'conviva_db') {
    throw new RuntimeException('Alteração recusada: use exclusivamente MySQL conviva_db.');
}

$oldArea = 'finance';
$newArea = 'expenses';
$renameAreas = static fn (array $areas): array => array_values(array_unique(array_map(
    static fn (string $area): string => $area === $oldArea ? $newArea : $area,
    $areas,
)));

$counts = DB::transaction(static function () use ($oldArea, $newArea, $renameAreas): array {
    $accesses = 0;
    DB::table('care_accesses')->select('id', 'areas')->whereJsonContains('areas', $oldArea)
        ->lockForUpdate()->chunkById(200, static function ($rows) use ($renameAreas, &$accesses) {
            foreach ($rows as $row) {
                $areas = $renameAreas(json_decode($row->areas, true, flags: JSON_THROW_ON_ERROR));
                $accesses += DB::table('care_accesses')->where('id', $row->id)
                    ->update(['areas' => json_encode($areas, JSON_THROW_ON_ERROR)]);
            }
        });

    $invitations = 0;
    DB::table('organization_invitations')->select('id', 'care_accesses')
        ->whereRaw("JSON_SEARCH(care_accesses, 'one', ?, NULL, '$[*].areas[*]') IS NOT NULL", [$oldArea])
        ->lockForUpdate()->chunkById(200, static function ($rows) use ($renameAreas, &$invitations) {
            foreach ($rows as $row) {
                $grants = json_decode($row->care_accesses, true, flags: JSON_THROW_ON_ERROR);
                foreach ($grants as $index => $grant) {
                    if (isset($grant['areas'])) {
                        $grants[$index]['areas'] = $renameAreas($grant['areas']);
                    }
                }
                $invitations += DB::table('organization_invitations')->where('id', $row->id)
                    ->update(['care_accesses' => json_encode($grants, JSON_THROW_ON_ERROR)]);
            }
        });

    $notifications = DB::table('care_notifications')->where('area', $oldArea)->update(['area' => $newArea]);
    $deliveries = DB::update(
        "UPDATE notification_reminder_deliveries
         SET event_key = CONCAT(SUBSTRING_INDEX(event_key, ':', 2), ':', ?)
         WHERE event_key LIKE ?",
        [$newArea, 'summary:____-__-__:'.$oldArea],
    );

    return compact('accesses', 'invitations', 'notifications', 'deliveries');
});

echo json_encode($counts, JSON_THROW_ON_ERROR).PHP_EOL;
