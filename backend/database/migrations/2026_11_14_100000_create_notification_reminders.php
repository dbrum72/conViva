<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('settings');
            $table->unique(['organization_id', 'user_id']);
        });
        Schema::create('notification_reminder_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_key', 100);
            $table->string('channel', 12);
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('care_notification_id')->nullable()->constrained()->nullOnDelete();
            $table->date('agenda_date')->nullable();
            $table->unique(['organization_id', 'user_id', 'event_key', 'channel'], 'notification_reminder_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reminder_deliveries');
        Schema::dropIfExists('notification_preferences');
    }
};
