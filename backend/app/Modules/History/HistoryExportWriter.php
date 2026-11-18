<?php

namespace App\Modules\History;

use Carbon\CarbonImmutable;
use Dompdf\Dompdf;
use RuntimeException;

class HistoryExportWriter
{
    private const HEADINGS = [
        'Registrado em', 'Ocorreu em', 'Autor', 'Área', 'Evento', 'Registro',
        'Situação', 'Operação', 'Versão', 'Identificador', 'Relacionado a', 'Resultado', 'Motivo', 'Observação',
    ];

    private const LABELS = [
        'routine' => 'Rotina', 'health' => 'Saúde', 'expenses' => 'Despesas',
        'proposal' => 'Proposta', 'decision' => 'Decisão',
        'withdrawal' => 'Retirada', 'execution' => 'Execução', 'correction' => 'Correção',
        'profile_proposal' => 'Proposta cadastral', 'profile_decision' => 'Decisão cadastral',
        'profile_withdrawal' => 'Retirada cadastral', 'pending' => 'Pendente',
        'accepted' => 'Aceita', 'rejected' => 'Recusada', 'withdrawn' => 'Retirada',
        'completed' => 'Registrado', 'save' => 'Salvar', 'archive' => 'Arquivar',
        'cancel' => 'Cancelar', 'cancel_occurrence' => 'Cancelar ocorrência',
        'administered' => 'Administrado', 'not_administered' => 'Não administrado',
    ];

    public function write($stream, HistoryExport $export, string $requester, iterable $events): void
    {
        if ($export->format !== 'pdf') {
            $this->writeReport($stream, $export, $requester, $events);

            return;
        }

        $html = fopen('php://temp', 'w+b');
        try {
            $this->writeReport($html, $export, $requester, $events);
            rewind($html);
            $pdf = new Dompdf;
            $pdf->setPaper('A4');
            $pdf->loadHtml(stream_get_contents($html), 'UTF-8');
            $pdf->render();
            $pdf->addInfo('Title', 'Histórico de cuidados - conViva');
            $canvas = $pdf->getCanvas();
            $canvas->page_text(48, 807, 'Gerado por conViva', 'Helvetica', 9);
            $canvas->page_text(455, 807, '{PAGE_NUM} / {PAGE_COUNT}', 'Helvetica', 9);
            $this->text($stream, $pdf->output());
        } finally {
            fclose($html);
        }
    }

    private function writeReport($stream, HistoryExport $export, string $requester, iterable $events): void
    {
        $filters = $export->filters;
        $timezone = $filters['timezone'];
        $csv = $export->format === 'csv';
        if ($csv) {
            $this->text($stream, "\xEF\xBB\xBF");
            $this->csv($stream, self::HEADINGS);
        } else {
            $range = CarbonImmutable::parse($filters['from'])->format('d/m/Y').' a '.CarbonImmutable::parse($filters['to'])->format('d/m/Y');
            $requestedAt = $export->created_at->setTimezone($timezone)->format('d/m/Y H:i:s');
            $area = collect($export->areas)->map(fn ($value) => self::LABELS[$value])->implode(', ');
            $caption = e("$range · $timezone · Áreas: $area");
            $requestedBy = e("$requester · $requestedAt");
            $brand = $export->format === 'pdf' ? '<p class="brand">conViva</p>' : '';
            $this->text($stream, '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">'
                .'<meta name="viewport" content="width=device-width, initial-scale=1">'
                .'<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'">'
                .'<title>Histórico de cuidados</title><style>@page{margin:40pt 48pt 52pt}'
                .'body{font:10pt "DejaVu Sans",sans-serif;color:#242936;line-height:1.4;margin:0}'
                .'h1{font-size:22pt;margin:0 0 8pt}header{margin-bottom:18pt}'
                .'h2{font-size:12pt;margin:0 0 4pt;page-break-after:avoid}'
                .'p{margin:0 0 3pt;white-space:pre-wrap;overflow-wrap:break-word}'
                .'.brand{color:#565873;font-size:12pt;font-weight:bold;margin-bottom:5pt}'
                .'article{margin:0 0 12pt;padding:8pt 0 0;border-top:1px solid #b8c0c9}</style></head>'
                ."<body><header>$brand<h1>Histórico de cuidados</h1><p>$caption</p><p>Solicitado por $requestedBy</p></header>");
        }

        foreach ($events as $event) {
            $cells = [
                CarbonImmutable::parse($event['recorded_at'])->setTimezone($timezone)->format('Y-m-d H:i:s P'),
                $event['occurred_at'] ? CarbonImmutable::parse($event['occurred_at'])->setTimezone($timezone)->format('Y-m-d H:i:s P') : '',
                $event['author'], $this->label($event['area']), $this->label($event['type']), $event['title'],
                $this->label($event['status']), $this->label($event['operation']), $event['version'], $event['record_id'],
                $event['related_id'], $this->label($event['outcome']), $event['reason'], $event['notes'],
            ];
            if ($csv) {
                $this->csv($stream, $cells);
            } else {
                $this->record($stream, $cells);
            }
        }

        if (! $csv) {
            $this->text($stream, '</body></html>');
        }
    }

    private function label(?string $key): ?string
    {
        return self::LABELS[$key] ?? $key;
    }

    private function csv($stream, array $cells): void
    {
        $safe = array_map(function ($value) {
            $value = (string) $value;

            return preg_match('/^[\p{Z}\p{C}]*[=+@\-＝＋＠－]|^[\t\r\n]/u', $value) ? "'".$value : $value;
        }, $cells);
        if (fputcsv($stream, $safe, ',', '"', '', "\r\n") === false) {
            throw new RuntimeException('Falha ao gravar CSV.');
        }
    }

    private function record($stream, array $cells): void
    {
        $title = e((string) $cells[5]);
        unset($cells[5]);
        $this->text($stream, "<article><h2>$title</h2>");
        foreach ($cells as $index => $value) {
            if ($value !== null && $value !== '') {
                $label = self::HEADINGS[$index];
                $this->text($stream, '<p><strong>'.$label.':</strong> '.e((string) $value).'</p>');
            }
        }
        $this->text($stream, '</article>');
    }

    private function text($stream, string $text): void
    {
        if (fwrite($stream, $text) !== strlen($text)) {
            throw new RuntimeException('Falha ao gravar exportação.');
        }
    }
}
