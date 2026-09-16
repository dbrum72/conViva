<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class CareEntry extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'care_recipient_id', 'created_by', 'assigned_user_id', 'kind', 'title', 'description', 'status', 'due_at', 'ends_at', 'completed_at', 'amount_cents', 'details', 'revision', 'affected_user_ids', 'related_entry_id'];

    protected function casts(): array
    {
        return ['affected_user_ids' => 'array', 'revision' => 'integer', 'details' => 'array', 'due_at' => 'datetime', 'ends_at' => 'datetime', 'completed_at' => 'datetime', 'amount_cents' => 'integer'];
    }

    public function proposals()
    {
        return $this->hasMany(CareProposal::class)->orderByDesc('version');
    }

    public function recipient()
    {
        return $this->belongsTo(CareRecipient::class, 'care_recipient_id');
    }

    public function shares()
    {
        return $this->hasMany(ExpenseShare::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
