<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class CareRecipient extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'created_by', 'name', 'kind', 'birth_date', 'species', 'breed', 'status', 'routine_profile', 'health_profile'];

    protected $hidden = ['routine_profile', 'health_profile'];

    protected function casts(): array
    {
        return ['birth_date' => 'date:Y-m-d', 'routine_profile' => 'array', 'health_profile' => 'array'];
    }

    public function accesses()
    {
        return $this->hasMany(CareAccess::class);
    }

    public function entries()
    {
        return $this->hasMany(CareEntry::class);
    }

    public function documents()
    {
        return $this->hasMany(CareDocument::class);
    }
}
