<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_recipient_avatars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_recipient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
            $table->unique(['care_recipient_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_recipient_avatars');
    }
};
