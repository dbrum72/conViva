<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected string $guard_name = 'api';

    protected function getDefaultGuardName(): string
    {
        return 'api';
    }

    protected function casts(): array
    {
        return ['password' => 'hashed', 'email_verified_at' => 'datetime'];
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class)->as('membership')->withPivot(['status', 'joined_at'])->withTimestamps();
    }

    public function sentOrganizationInvitations()
    {
        return $this->hasMany(OrganizationInvitation::class, 'invited_by');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
