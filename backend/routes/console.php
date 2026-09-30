<?php

use App\Jobs\GenerateGroupCareOccurrences;
use App\Models\Organization;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('care:generate-occurrences', function () {
    Organization::where('status', 'active')->each(fn ($group) => GenerateGroupCareOccurrences::dispatch($group->id));
    $this->info('Geração de ocorrências enviada à fila.');
})->purpose('Gerar os próximos 90 dias das séries aprovadas por grupo');

Schedule::command('care:generate-occurrences')->daily()->withoutOverlapping();
