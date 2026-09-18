<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareRecipientAvatar extends Model
{
    protected $fillable = ['care_recipient_id', 'user_id', 'path'];

    protected $hidden = ['path'];
}
