<?php

namespace Tests\Unit;

use App\Modules\History\HistoryExport;
use App\Modules\History\HistoryExportWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HistoryExportWriterTest extends TestCase
{
    public static function writeFailures(): array
    {
        return [
            'byte order mark' => [0, 'Falha ao gravar exportação.'],
            'CSV header' => [1, 'Falha ao gravar CSV.'],
        ];
    }

    #[DataProvider('writeFailures')]
    public function test_report_writes_fail_if_the_stream_rejects_bytes(int $allowedWrites, string $message): void
    {
        stream_wrapper_register('historyfailingstream', HistoryFailingStream::class);
        $stream = fopen('historyfailingstream://'.$allowedWrites, 'wb');
        try {
            $export = (new HistoryExport)->setRawAttributes([
                'format' => 'csv',
                'filters' => '{"from":"2026-11-18","to":"2026-11-18","timezone":"UTC"}',
            ]);
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);
            (new HistoryExportWriter)->write($stream, $export, 'Responsável', []);
        } finally {
            fclose($stream);
            stream_wrapper_unregister('historyfailingstream');
        }
    }
}

class HistoryFailingStream
{
    public $context;

    private int $remainingWrites;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        $this->remainingWrites = (int) parse_url($path, PHP_URL_HOST);

        return true;
    }

    public function stream_write(string $data): int|false
    {
        return $this->remainingWrites-- > 0 ? strlen($data) : false;
    }
}
