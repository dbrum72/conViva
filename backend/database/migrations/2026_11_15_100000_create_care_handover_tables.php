<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_facts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('care_recipient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('request_id');
            $table->string('kind', 20);
            $table->dateTime('occurred_at');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['care_recipient_id', 'created_by', 'request_id'], 'care_facts_request_unique');
            $table->index(['care_recipient_id', 'occurred_at']);
        });

        Schema::create('care_handover_reads', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('care_recipient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('version', 64);
            $table->dateTime('read_at');
            $table->primary(['organization_id', 'care_recipient_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_handover_reads');
        Schema::dropIfExists('care_facts');
    }
};
