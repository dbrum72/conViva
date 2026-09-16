<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareAccess extends Model
{
    protected $fillable = ['care_recipient_id', 'user_id', 'areas', 'can_edit', 'expires_at'];

    protected function casts(): array
    {
        return ['areas' => 'array', 'can_edit' => 'boolean', 'expires_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
