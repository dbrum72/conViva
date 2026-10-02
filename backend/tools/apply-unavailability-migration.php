<?php

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$connection = config('database.connections.mysql');
if (config('database.default') !== 'mysql' || $connection['database'] !== 'conviva_db' || ! empty($connection['url'])
    || DB::selectOne('SELECT DATABASE() AS name')->name !== 'conviva_db') {
    throw new RuntimeException('Migration recusada: use exclusivamente MySQL conviva_db.');
}
$exit = Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_02_100000_create_care_unavailabilities_table.php', '--force' => true]);
echo Artisan::output();
// Keep the prototype creation migration current without rebuilding existing records.
if ($exit === 0 && ! Schema::hasColumn('care_unavailabilities', 'reason')) {
    Schema::table('care_unavailabilities', function (Blueprint $table) {
        $table->text('reason')->nullable();
    });
    echo "Campo opcional reason aplicado em conviva_db; registros preservados.\n";
}
if ($exit === 0 && Schema::getColumnType('care_unavailabilities', 'starts_at') !== 'date') {
    // Stage civil dates before the atomic DDL swap; never truncate UTC timestamps.
    foreach (['starts_on_conversion', 'ends_on_conversion'] as $field) {
        if (! Schema::hasColumn('care_unavailabilities', $field)) {
            Schema::table('care_unavailabilities', fn (Blueprint $table) => $table->date($field)->nullable());
        }
    }
    DB::transaction(function () {
        foreach (DB::table('care_unavailabilities')->lockForUpdate()->get() as $period) {
            $start = CarbonImmutable::parse($period->starts_at, 'UTC')->setTimezone($period->timezone);
            $last = CarbonImmutable::parse($period->ends_at, 'UTC')->subMicrosecond()->setTimezone($period->timezone);
            DB::table('care_unavailabilities')->where('id', $period->id)->update([
                'starts_on_conversion' => $start->format('Y-m-d'),
                'ends_on_conversion' => $last->format('Y-m-d'),
            ]);
        }
    });
    DB::statement('ALTER TABLE care_unavailabilities
        DROP INDEX care_unavailabilities_care_recipient_id_user_id_starts_at_index,
        DROP COLUMN starts_at, DROP COLUMN ends_at,
        CHANGE COLUMN starts_on_conversion starts_at DATE NOT NULL,
        CHANGE COLUMN ends_on_conversion ends_at DATE NOT NULL,
        ADD INDEX care_unavailabilities_care_recipient_id_user_id_starts_at_index (care_recipient_id, user_id, starts_at)');
    echo "Datas inclusivas aplicadas em conviva_db; dias locais, autores e motivos preservados.\n";
}
exit($exit);
