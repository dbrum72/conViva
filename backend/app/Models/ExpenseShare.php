<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseShare extends Model
{
    protected $fillable = ['care_entry_id', 'user_id', 'amount_cents', 'paid_at'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'amount_cents' => 'integer'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
