<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareProfileProposal extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function recipient()
    {
        return $this->belongsTo(CareRecipient::class, 'care_recipient_id');
    }

    public function decisions()
    {
        return $this->hasMany(CareProfileDecision::class);
    }
}
