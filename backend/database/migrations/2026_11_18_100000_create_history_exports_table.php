<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('recipient_id');
            $table->unsignedBigInteger('user_id');
            $table->string('format', 8);
            $table->string('status', 16);
            $table->json('filters');
            $table->json('areas');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->index(['organization_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_exports');
    }
};
