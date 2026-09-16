<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'organization_invitations',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('organization_id')
                    ->constrained('organizations')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('invited_by')
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();

                $table
                    ->string(
                        'email',
                        255
                    );

                $table
                    ->string(
                        'role',
                        100
                    );

                $table
                    ->char(
                        'token_hash',
                        64
                    )
                    ->unique();

                $table
                    ->string(
                        'status',
                        20
                    )
                    ->default(
                        'pending'
                    );

                $table
                    ->timestamp(
                        'expires_at'
                    );

                $table
                    ->timestamp(
                        'accepted_at'
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'revoked_at'
                    )
                    ->nullable();

                $table->json('care_accesses');

                $table->timestamps();

                $table->index(
                    [
                        'organization_id',
                        'status',
                    ],
                    'organization_invitations_org_status_index'
                );

                $table->index(
                    [
                        'organization_id',
                        'email',
                    ],
                    'organization_invitations_org_email_index'
                );

                $table->index(
                    'expires_at',
                    'organization_invitations_expires_at_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'organization_invitations'
        );
    }
};
