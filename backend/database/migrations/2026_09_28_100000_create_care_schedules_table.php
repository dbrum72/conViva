<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('care_entry_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->json('rule');
            $t->json('snapshot');
            $t->dateTime('valid_from')->nullable();
            $t->dateTime('valid_until')->nullable();
            $t->timestamps();
            $t->unique(['care_entry_id', 'version']);
        });
        Schema::create('care_occurrences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('care_schedule_id')->constrained()->cascadeOnDelete();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->string('status')->default('scheduled');
            $t->foreignId('execution_entry_id')->nullable()->constrained('care_entries');
            $t->foreignId('cancelled_by_proposal_id')->nullable()->constrained('care_proposals');
            $t->timestamps();
            $t->unique(['care_schedule_id', 'starts_at']);
            $t->index(['organization_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_occurrences');
        Schema::dropIfExists('care_schedules');
    }
};
