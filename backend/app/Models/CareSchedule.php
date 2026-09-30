<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class CareSchedule extends Model
{
    use BelongsToOrganization;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['rule' => 'array', 'snapshot' => 'array', 'valid_from' => 'immutable_datetime', 'valid_until' => 'immutable_datetime'];
    }

    public function entry()
    {
        return $this->belongsTo(CareEntry::class, 'care_entry_id');
    }

    public function occurrences()
    {
        return $this->hasMany(CareOccurrence::class);
    }
}
