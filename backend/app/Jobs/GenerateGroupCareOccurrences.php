<?php

namespace App\Jobs;

use App\Models\CareSchedule;
use App\Models\Organization;
use App\Services\Care\GenerateCareOccurrences;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateGroupCareOccurrences implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $organizationId) {}

    public function handle(CurrentOrganization $context, GenerateCareOccurrences $generator): void
    {
        $group = Organization::where('status', 'active')->find($this->organizationId);
        if (! $group) {
            return;
        }
        $context->run($group, function () use ($generator) {
            CareSchedule::where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', now()))->whereHas('entry.recipient', fn ($q) => $q->where('status', 'active'))->each(function ($s) use ($generator) {
                $generator->generate($s, CarbonImmutable::now()->startOfDay(), CarbonImmutable::now()->startOfDay()->addDays(90));
            });
        });
    }
}
