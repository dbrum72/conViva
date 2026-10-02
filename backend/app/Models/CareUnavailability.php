<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class CareUnavailability extends Model
{
    use BelongsToOrganization;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_date:Y-m-d', 'ends_at' => 'immutable_date:Y-m-d', 'cancelled_at' => 'immutable_datetime'];
    }

    public function intervalStart(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->starts_at->format('Y-m-d'), $this->timezone)->startOfDay()->utc();
    }

    public function intervalEnd(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->ends_at->format('Y-m-d'), $this->timezone)->addDay()->startOfDay()->utc();
    }
}
