<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareDecision extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
