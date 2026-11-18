<?php

namespace App\Modules\History;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeHistoryExports
{
    public function __invoke(): void
    {
        $disk = Storage::disk('local');
        HistoryExport::where('expires_at', '<=', now())->where('status', '!=', 'expired')
            ->eachById(function (HistoryExport $item) use ($disk) {
                DB::transaction(function () use ($item, $disk) {
                    $export = HistoryExport::lockForUpdate()->findOrFail($item->id);
                    if ($disk->delete($export->path()) || ! $disk->exists($export->path())) {
                        $export->update(['status' => 'expired']);
                    }
                });
            });

        HistoryExport::where('status', 'expired')->where('created_at', '<=', now()->subDays(90))->delete();
    }
}
