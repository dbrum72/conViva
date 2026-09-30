<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class CareOccurrence extends Model
{
    use BelongsToOrganization;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }

    public function isOverdue(): bool
    {
        return $this->status === 'scheduled'
            && $this->execution_entry_id === null
            && $this->ends_at !== null
            && $this->ends_at->isPast();
    }

    public function schedule()
    {
        return $this->belongsTo(CareSchedule::class, 'care_schedule_id');
    }

    public function execution()
    {
        return $this->belongsTo(CareEntry::class, 'execution_entry_id');
    }
}
