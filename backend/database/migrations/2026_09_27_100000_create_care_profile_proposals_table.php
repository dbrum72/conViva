<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_profile_proposals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('care_recipient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('created_by')->constrained('users');
            $t->unsignedInteger('version');
            $t->string('operation');
            $t->string('status')->default('pending');
            $t->json('payload');
            $t->timestamps();
            $t->unique(['care_recipient_id', 'version']);
        });
        Schema::create('care_profile_decisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('care_profile_proposal_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users');
            $t->string('status')->default('pending');
            $t->text('reason')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
            $t->unique(['care_profile_proposal_id', 'user_id'], 'profile_decision_user_unique');
        });
        Schema::create('care_notification_targets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('care_notification_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('care_proposal_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('care_profile_proposal_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_notification_targets');
        Schema::dropIfExists('care_profile_decisions');
        Schema::dropIfExists('care_profile_proposals');
    }
};
