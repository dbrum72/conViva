<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_recipients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->unique('organization_id');
            $t->foreignId('created_by')->constrained('users');
            $t->string('name');
            $t->string('kind');
            $t->date('birth_date')->nullable();
            $t->string('species')->nullable();
            $t->string('breed')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('care_accesses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('care_recipient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->json('areas');
            $t->boolean('can_edit')->default(false);
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->unique(['care_recipient_id', 'user_id']);
        });
        Schema::create('care_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('care_recipient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('kind');
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('status')->default('pending');
            $t->timestamp('due_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->unsignedBigInteger('amount_cents')->nullable();
            $t->json('details')->nullable();
            $t->unsignedInteger('revision')->default(0);
            $t->json('affected_user_ids')->nullable();
            $t->foreignId('related_entry_id')->nullable()->constrained('care_entries');
            $t->timestamps();
            $t->index(['care_recipient_id', 'kind', 'due_at']);
        });
        Schema::create('care_proposals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('care_entry_id')->constrained()->cascadeOnDelete();
            $t->foreignId('created_by')->constrained('users');
            $t->unsignedInteger('version');
            $t->string('operation')->default('save');
            $t->json('payload');
            $t->string('status')->default('pending');
            $t->timestamps();
            $t->unique(['care_entry_id', 'version']);
        });
        Schema::create('care_decisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('care_proposal_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users');
            $t->string('status')->default('pending');
            $t->text('reason')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
            $t->unique(['care_proposal_id', 'user_id']);
        });
        Schema::create('care_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('care_recipient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('created_by')->constrained('users');
            $t->string('filename');
            $t->string('path');
            $t->string('mime_type');
            $t->unsignedBigInteger('size');
            $t->timestamps();
        });
        Schema::create('expense_shares', function (Blueprint $t) {
            $t->id();
            $t->foreignId('care_entry_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('amount_cents');
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
            $t->unique(['care_entry_id', 'user_id']);
        });
        Schema::create('care_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('care_recipient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('area');
            $t->string('message');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['care_decisions', 'care_proposals', 'care_notifications', 'expense_shares', 'care_documents', 'care_entries', 'care_accesses', 'care_recipients'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
