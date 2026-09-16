<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class CareNotification extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'care_recipient_id', 'user_id', 'area', 'message', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function recipient()
    {
        return $this->belongsTo(CareRecipient::class, 'care_recipient_id');
    }
}
