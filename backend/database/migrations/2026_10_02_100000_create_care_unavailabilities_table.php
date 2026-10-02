<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_unavailabilities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('care_recipient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('starts_at');
            $t->date('ends_at');
            $t->string('timezone');
            $t->text('reason')->nullable();
            $t->dateTime('cancelled_at')->nullable();
            $t->timestamps();
            $t->index(['care_recipient_id', 'user_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_unavailabilities');
    }
};
