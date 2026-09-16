<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(
            new OrganizationScope
        );

        static::creating(
            function ($model): void {
                $currentOrganization = app(
                    CurrentOrganization::class
                );

                if (! $currentOrganization->has()) {
                    return;
                }

                $organizationId =
                    $currentOrganization->id();

                if (
                    $model->organization_id !== null
                    && (int) $model->organization_id !== $organizationId
                ) {
                    throw new LogicException(
                        'O registro não pode ser criado em uma organização diferente da organização atual.'
                    );
                }

                $model->organization_id =
                    $organizationId;
            }
        );

        static::updating(
            function ($model): void {
                if (
                    $model->isDirty(
                        'organization_id'
                    )
                ) {
                    throw new LogicException(
                        'A organização de um registro não pode ser alterada.'
                    );
                }
            }
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }
}
