<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class CareFact extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['care_recipient_id', 'created_by', 'request_id', 'kind', 'occurred_at', 'description'];

    protected function casts(): array
    {
        return ['occurred_at' => 'immutable_datetime'];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
