<?php

use App\Jobs\GenerateGroupCareOccurrences;
use App\Models\Organization;
use App\Modules\Notifications\DeliverReminders;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('care:generate-occurrences', function () {
    Organization::where('status', 'active')->each(fn ($group) => GenerateGroupCareOccurrences::dispatch($group->id));
    $this->info('Geração de ocorrências enviada à fila.');
})->purpose('Gerar os próximos 90 dias das séries aprovadas por grupo');

Schedule::command('care:generate-occurrences')->daily()->withoutOverlapping();

Artisan::command('notifications:dispatch-reminders', function () {
    DB::table('notification_preferences')
        ->where(fn ($query) => $query->where('settings->internal', true)->orWhere('settings->email', true))
        ->eachById(fn ($row) => dispatch(new DeliverReminders($row->organization_id, $row->user_id)));
    Cache::put('notifications:dispatch-heartbeat', now()->timestamp, now()->addDay());
})->purpose('Enfileirar lembretes dos participantes inscritos');

Schedule::command('notifications:dispatch-reminders')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('notifications:check-reminders', function () {
    $deadline = now()->subMinutes(15)->timestamp;
    if ((int) Cache::get('notifications:dispatch-heartbeat') < $deadline) {
        $this->error('A distribuição de lembretes não foi observada nos últimos 15 minutos.');

        return 1;
    }
    $subscribers = DB::table('notification_preferences')
        ->where(fn ($query) => $query->where('settings->internal', true)->orWhere('settings->email', true))->exists();
    if ($subscribers && (int) Cache::get('notifications:worker-heartbeat') < $deadline) {
        $this->error('A fila de lembretes não apresentou atividade nos últimos 15 minutos.');

        return 1;
    }

    $this->info('Distribuição de lembretes ativa.');

    return 0;
})->purpose('Verificar atividade do dispatcher e do worker de lembretes');
