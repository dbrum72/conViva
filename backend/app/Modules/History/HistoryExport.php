<?php

namespace App\Modules\History;

use Illuminate\Database\Eloquent\Model;

class HistoryExport extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'areas' => 'array', 'expires_at' => 'immutable_datetime'];
    }

    public function path(): string
    {
        return "history-exports/{$this->id}.{$this->format}";
    }

    public function statusForDownload(): string
    {
        return $this->expires_at->lte(now()) ? 'expired' : $this->status;
    }
}
