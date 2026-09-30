<?php

namespace Tests\Unit;

use App\Models\CareOccurrence;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CareOccurrenceStatusTest extends TestCase
{
    public static function states(): array
    {
        return [
            'encerrado sem execução' => ['scheduled', null, '2026-10-01T11:59:00Z', true],
            'ainda em andamento' => ['scheduled', null, '2026-10-01T12:01:00Z', false],
            'no instante do término' => ['scheduled', null, '2026-10-01T12:00:00Z', false],
            'execução registrada' => ['executed', 10, '2026-10-01T11:00:00Z', false],
            'cancelado' => ['cancelled', null, '2026-10-01T11:00:00Z', false],
            'substituído' => ['superseded', null, '2026-10-01T11:00:00Z', false],
            'referência de execução presente' => ['scheduled', 10, '2026-10-01T11:00:00Z', false],
            'sem horário de término' => ['scheduled', null, null, false],
        ];
    }

    #[DataProvider('states')]
    public function test_overdue_requires_elapsed_end_and_no_execution(string $status, ?int $execution, ?string $end, bool $expected): void
    {
        CarbonImmutable::setTestNow('2026-10-01T12:00:00Z');
        try {
            $occurrence = (new CareOccurrence)->setDateFormat('Y-m-d H:i:s');
            $occurrence->fill(['status' => $status, 'execution_entry_id' => $execution, 'ends_at' => $end]);
            $this->assertSame($expected, $occurrence->isOverdue());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
