<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareProposal extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function decisions()
    {
        return $this->hasMany(CareDecision::class);
    }

    public function entry()
    {
        return $this->belongsTo(CareEntry::class, 'care_entry_id');
    }
}
