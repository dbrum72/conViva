<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'invited_by',
    'email',
    'role',
    'care_accesses',
    'token_hash',
    'status',
    'expires_at',
    'accepted_at',
    'revoked_at',
])]
#[Hidden([
    'token_hash',
])]
class OrganizationInvitation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_EXPIRED = 'expired';

    public const DEFAULT_EXPIRATION_DAYS = 7;

    protected function casts(): array
    {
        return [
            'care_accesses' => 'array',
            'expires_at' => 'datetime',

            'accepted_at' => 'datetime',

            'revoked_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'invited_by'
        );
    }

    public function isPending(): bool
    {
        return $this->status ===
            self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status ===
            self::STATUS_ACCEPTED;
    }

    public function isRevoked(): bool
    {
        return $this->status ===
            self::STATUS_REVOKED;
    }

    public function isExpired(): bool
    {
        return $this
            ->expires_at
            ->isPast();
    }

    public function isAcceptable(): bool
    {
        return $this->isPending()
            && ! $this->isExpired();
    }

    public function administrativeStatus(): string
    {
        if (
            $this->isPending()
            && $this->isExpired()
        ) {
            return self::STATUS_EXPIRED;
        }

        return $this->status;
    }

    public function matchesToken(
        string $token,
    ): bool {
        return hash_equals(
            $this->token_hash,
            self::hashToken(
                $token
            )
        );
    }

    public static function hashToken(
        string $token,
    ): string {
        return hash(
            'sha256',
            $token
        );
    }
}
