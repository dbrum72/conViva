<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'status'];

    public function users()
    {
        return $this->belongsToMany(User::class)->as('membership')->withPivot(['status', 'joined_at'])->withTimestamps();
    }

    public function invitations()
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function recipients()
    {
        return $this->hasMany(CareRecipient::class);
    }
}
