<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unlocker_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('credits_spent');
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['unlocker_user_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_unlocks');
    }
};
