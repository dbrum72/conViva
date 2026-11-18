<?php

namespace App\Modules\History;

use App\Models\CareRecipient;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryController
{
    public function __construct(private HistoryTimeline $timeline, private CurrentOrganization $context) {}

    public function index(Request $request): JsonResponse
    {
        $recipient = CareRecipient::firstOrFail();
        $areas = $this->timeline->areas($request->user(), $recipient);
        [$filters, $selected] = $this->filters($request, $areas);
        $page = $this->timeline->query($recipient, $selected, $filters)->simplePaginate(25);
        $export = HistoryExport::where('user_id', $request->user()->id)
            ->where('organization_id', $this->context->id())->where('recipient_id', $recipient->id)
            ->where('expires_at', '>', now())->latest('id')->first();

        return $this->json([
            'events' => $page->getCollection()->map($this->timeline->present(...)),
            'page' => $page->currentPage(),
            'has_more' => $page->hasMorePages(),
            'areas' => $areas,
            'filters' => $filters,
            'export' => $export && ! array_diff($export->areas, $areas) ? $this->exportData($export) : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $format = $request->validate(['format' => ['required', Rule::in(['pdf'])]])['format'];
        $recipient = CareRecipient::firstOrFail();
        [$filters, $areas] = $this->filters($request, $this->timeline->areas($request->user(), $recipient));
        $export = HistoryExport::create([
            'organization_id' => $this->context->id(),
            'recipient_id' => $recipient->id,
            'user_id' => $request->user()->id,
            'filters' => $filters,
            'areas' => $areas,
            'format' => $format,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);
        GenerateHistoryExport::dispatch($export->id)->afterCommit();

        return $this->json($this->exportData($export->refresh()), 202);
    }

    public function show(Request $request, int $export): JsonResponse
    {
        return $this->json($this->exportData($this->authorizeExport($request, $export)));
    }

    public function download(Request $request, int $export): StreamedResponse
    {
        $item = $this->authorizeExport($request, $export);
        $status = $item->statusForDownload();
        abort_if($status === 'expired', 410, 'A exportação expirou.');
        abort_unless($status === 'ready', 409, 'A exportação está indisponível.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($item->path()), 410, 'O arquivo está indisponível.');

        $filename = $item->format === 'pdf' ? "historico-conviva-{$item->id}.pdf" : 'historico.'.$item->format;

        return $disk->download($item->path(), $filename, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type' => match ($item->format) {
                'csv' => 'text/csv; charset=utf-8',
                'pdf' => 'application/pdf',
                default => 'text/html; charset=utf-8',
            },
        ]);
    }

    private function authorizeExport(Request $request, int $export): HistoryExport
    {
        $item = HistoryExport::where('organization_id', $this->context->id())
            ->where('user_id', $request->user()->id)->findOrFail($export);
        $recipient = CareRecipient::findOrFail($item->recipient_id);
        abort_if(array_diff($item->areas, $this->timeline->areas($request->user(), $recipient)), 403);

        return $item;
    }

    private function filters(Request $request, array $areas): array
    {
        abort_if($areas === [], 403, 'Seu acesso não inclui áreas com histórico.');
        $validated = $request->validate([
            'area' => ['nullable', Rule::in(array_keys(HistoryTimeline::KINDS))],
            'from' => ['required_with:to', 'nullable', 'date_format:Y-m-d'],
            'to' => ['required_with:from', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ]);
        $area = $validated['area'] ?? null;
        abort_if($area !== null && ! in_array($area, $areas, true), 403);
        $timezone = $this->context->get()->timezone;
        $today = CarbonImmutable::today($timezone);
        $from = $validated['from'] ?? $today->subDays(29)->toDateString();
        $to = $validated['to'] ?? $today->toDateString();
        if (CarbonImmutable::parse($to)->greaterThan(CarbonImmutable::parse($from)->addDays(365))) {
            throw ValidationException::withMessages(['to' => 'Escolha um período de até 366 dias.']);
        }

        return [
            ['from' => $from, 'to' => $to, 'area' => $area, 'timezone' => $timezone],
            $area === null ? $areas : [$area],
        ];
    }

    private function exportData(HistoryExport $export): array
    {
        return [
            'id' => $export->id,
            'format' => $export->format,
            'status' => $export->statusForDownload(),
            'requested_at' => $export->created_at->toIso8601String(),
            'expires_at' => $export->expires_at->toIso8601String(),
            'filters' => $export->filters,
        ];
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status, ['Cache-Control' => 'private, no-store']);
    }
}
