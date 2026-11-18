<?php

namespace App\Modules\History;

use App\Models\CareRecipient;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateHistoryExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $exportId) {}

    public function handle(CurrentOrganization $context, HistoryTimeline $timeline, HistoryExportWriter $writer): void
    {
        try {
            $export = HistoryExport::find($this->exportId);
            if ($export?->statusForDownload() !== 'pending') {
                return;
            }

            $stream = tmpfile();
            if ($stream === false) {
                throw new RuntimeException('Falha ao abrir arquivo temporário de exportação.');
            }

            try {
                $written = DB::transaction(fn () => $this->withAccess(
                    $export, $context, $timeline, function (User $user, CareRecipient $recipient) use ($export, $timeline, $writer, $stream) {
                        $events = $timeline->query($recipient, $export->areas, $export->filters)->lazy()
                            ->map($timeline->present(...));
                        $writer->write($stream, $export, $user->name, $events);
                    },
                ));
                rewind($stream);

                // A fresh transaction observes access changes committed during the read.
                DB::transaction(function () use ($context, $timeline, $stream, $written) {
                    $export = HistoryExport::lockForUpdate()->find($this->exportId);
                    if ($export?->statusForDownload() !== 'pending') {
                        return;
                    }

                    $ready = $written && $this->withAccess($export, $context, $timeline, function () use ($export, $stream) {
                        if (! Storage::disk('local')->put($export->path(), $stream, 'private')) {
                            throw new RuntimeException('Falha ao armazenar exportação.');
                        }
                    });
                    $export->update(['status' => $ready ? 'ready' : 'denied']);
                });
            } finally {
                fclose($stream);
            }
        } catch (Throwable) {
            // Failed-job storage and worker logs receive a message without the underlying data.
            throw new RuntimeException('Falha no processamento da exportação de histórico.');
        }
    }

    private function withAccess(HistoryExport $export, CurrentOrganization $context, HistoryTimeline $timeline, callable $action): bool
    {
        $group = Organization::where('status', 'active')->find($export->organization_id);
        $user = User::find($export->user_id);
        if (! $group || ! $user) {
            return false;
        }

        $team = getPermissionsTeamId();
        setPermissionsTeamId($group->id);
        try {
            return $context->run($group, function () use ($export, $user, $timeline, $action) {
                $recipient = CareRecipient::find($export->recipient_id);
                if (! $recipient || array_diff($export->areas, $timeline->areas($user, $recipient))) {
                    return false;
                }

                $action($user, $recipient);

                return true;
            });
        } finally {
            setPermissionsTeamId($team);
        }
    }

    public function failed(): void
    {
        HistoryExport::whereKey($this->exportId)->where('status', 'pending')->update(['status' => 'failed']);
    }
}
