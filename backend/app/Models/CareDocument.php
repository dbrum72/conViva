<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class CareDocument extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'care_recipient_id', 'created_by', 'filename', 'path', 'mime_type', 'size'];

    protected $hidden = ['path'];
}
