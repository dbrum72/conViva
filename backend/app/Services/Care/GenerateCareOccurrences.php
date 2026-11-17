<?php

namespace App\Services\Care;

use App\Models\CareEntry;
use App\Models\CareOccurrence;
use App\Models\CareSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerateCareOccurrences
{
    public function normalize(array $data, ?CareEntry $entry = null): array
    {
        if (! array_key_exists('schedule', $data)) {
            $data['schedule'] = $entry?->schedule;
        }
        if ($data['schedule'] === []) {
            throw ValidationException::withMessages(['schedule' => 'Preencha a programação ou remova-a.']);
        }
        if (! empty($data['schedule'])) {
            $rule = $data['schedule'];
            if (! in_array($data['kind'], ['event', 'task', 'feeding', 'medication', 'vaccine'])) {
                throw ValidationException::withMessages(['schedule' => 'Este tipo de cuidado não admite programação.']);
            }
            if ($data['kind'] === 'vaccine' && $rule['frequency'] !== 'once') {
                throw ValidationException::withMessages(['schedule.frequency' => 'Use uma data isolada para cada aplicação de vacina.']);
            }
            $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $rule['local_start'], $rule['timezone']);
            if ($start->format('Y-m-d\TH:i') !== $rule['local_start']) {
                throw ValidationException::withMessages(['schedule.local_start' => 'Horário inexistente neste fuso. Escolha outro horário.']);
            }
            if ($rule['until'] < $start->format('Y-m-d') || $rule['until'] > $start->addYear()->format('Y-m-d')) {
                throw ValidationException::withMessages(['schedule.until' => 'O término deve estar entre o início e um ano após ele.']);
            }
            $rule['weekdays'] = array_values(array_unique(array_map('intval', $rule['weekdays'] ?? [])));
            if ($rule['frequency'] === 'weekly' && ! $rule['weekdays']) {
                throw ValidationException::withMessages(['schedule.weekdays' => 'Escolha ao menos um dia da semana.']);
            }
            if ($data['kind'] === 'medication') {
                $times = $rule['times'] ?? [];
                sort($times);
                if (! $times || $times[0] !== $start->format('H:i') || ($rule['frequency'] === 'once' && count($times) !== 1)) {
                    throw ValidationException::withMessages(['schedule.times' => 'Informe horários de dose distintos, começando pelo horário de início. Uma data isolada admite uma única dose.']);
                }
                $rule['times'] = $times;
            } elseif (array_key_exists('times', $rule)) {
                throw ValidationException::withMessages(['schedule.times' => 'Horários de doses são exclusivos de medicamentos.']);
            }
            $data['schedule'] = $rule;
            $data['due_at'] = $start->utc()->toISOString();
            $data['ends_at'] = $start->addMinutes($rule['duration_minutes'])->utc()->toISOString();
        }

        return $data;
    }

    // Called under the group/recipient/entry locks used by approved proposals.
    public function apply(CareEntry $entry, bool $legacy = false): void
    {
        $cutoff = CarbonImmutable::now()->utc();
        $previous = CareSchedule::where('care_entry_id', $entry->id)->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', $cutoff))->get();
        foreach ($previous as $schedule) {
            $schedule->update(['valid_until' => $cutoff]);
            $schedule->occurrences()->where('starts_at', '>=', $cutoff)->where('status', 'scheduled')->update(['status' => $entry->status === 'cancelled' ? 'cancelled' : 'superseded']);
        }
        if (! $entry->due_at || $entry->status === 'cancelled') {
            return;
        }
        $rule = $entry->schedule ?? [
            'frequency' => 'once', 'timezone' => $entry->organization->timezone,
            'local_start' => CarbonImmutable::parse($entry->due_at)->setTimezone($entry->organization->timezone)->format('Y-m-d\TH:i'),
            'instant' => $entry->due_at->toISOString(),
            'duration_minutes' => $entry->ends_at ? max(0, $entry->due_at->diffInMinutes($entry->ends_at)) : 0,
        ];
        CareSchedule::firstOrCreate(['care_entry_id' => $entry->id, 'version' => $entry->revision], [
            'organization_id' => $entry->organization_id, 'rule' => $rule,
            'snapshot' => [...$entry->only(['title', 'description', 'kind', 'assigned_user_id', 'care_recipient_id', 'created_by', 'details']), 'legacy_execution_id' => $legacy ? CareEntry::where('related_entry_id', $entry->id)->orderBy('id')->value('id') : null],
            'valid_from' => CareSchedule::where('care_entry_id', $entry->id)->exists() ? $cutoff : null,
        ]);
    }

    public function generate(CareSchedule $schedule, CarbonImmutable $from, CarbonImmutable $to): void
    {
        abort_if($from->diffInDays($to, false) < 0 || $from->diffInDays($to) > 94, 422, 'Consulte no máximo 93 dias por vez.');
        DB::transaction(function () use ($schedule, $from, $to) {
            // Same lock order as proposals and executions; protects against concurrent revisions.
            $entry = $schedule->entry;
            if (! $entry) {
                return;
            }
            app(ProposalGuard::class)->lock($entry->recipient);
            $schedule = CareSchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            $rule = $schedule->rule;
            $zone = $rule['timezone'];
            $start = CarbonImmutable::parse($rule['local_start'], $zone);
            $first = $from->setTimezone($zone)->startOfDay()->subDay();
            if ($first < $start->startOfDay()) {
                $first = $start->startOfDay();
            }
            $last = $to->setTimezone($zone)->endOfDay();
            if ($rule['frequency'] === 'once') {
                $first = $start->startOfDay();
                $last = $first;
            }
            for ($day = $first; $day <= $last; $day = $day->addDay()) {
                $date = $day->format('Y-m-d');
                if ($rule['frequency'] === 'once' && $date !== $start->format('Y-m-d')) {
                    continue;
                }
                if (isset($rule['until']) && $date > $rule['until']) {
                    break;
                }
                if ($rule['frequency'] === 'weekly' && ! in_array($day->dayOfWeekIso, $rule['weekdays'])) {
                    continue;
                }
                foreach ($this->intervalsForDay($rule, $day) as [$instant, $end]) {
                    if (($instant < $from && $end <= $from) || $instant >= $to
                        || ($schedule->valid_from && $instant < $schedule->valid_from)
                        || ($schedule->valid_until && $instant >= $schedule->valid_until)) {
                        continue;
                    }
                    // An early execution remains the execution of this instant across revisions.
                    if (CareOccurrence::whereHas('schedule', fn ($q) => $q->where('care_entry_id', $entry->id))
                        ->where('care_schedule_id', '!=', $schedule->id)->where('starts_at', $instant)
                        ->whereNotNull('execution_entry_id')->lockForUpdate()->exists()) {
                        continue;
                    }
                    $schedule->occurrences()->lockForUpdate()->firstOrCreate(['starts_at' => $instant], [
                        'organization_id' => $schedule->organization_id,
                        'execution_entry_id' => $schedule->snapshot['legacy_execution_id'] ?? null,
                        'status' => ! empty($schedule->snapshot['legacy_execution_id']) ? 'executed' : 'scheduled',
                        'ends_at' => $end,
                    ]);
                }
            }
        });
    }

    public function intervalsForDay(array $rule, CarbonImmutable $day): iterable
    {
        foreach ($rule['times'] ?? [substr($rule['local_start'], 11, 5)] as $time) {
            $wall = $day->format('Y-m-d').'T'.$time;
            $local = CarbonImmutable::parse($wall, $rule['timezone']);
            // Skip nonexistent wall times. Carbon resolves repeated times to a single instant.
            if ($local->format('Y-m-d\TH:i') !== $wall) {
                continue;
            }
            $instant = isset($rule['instant']) ? CarbonImmutable::parse($rule['instant']) : $local->utc();
            yield [$instant, $instant->addMinutes($rule['duration_minutes'])];
        }
    }
}
