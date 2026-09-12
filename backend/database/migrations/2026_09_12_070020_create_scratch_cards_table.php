<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scratch_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('triggered_by');
            $table->foreignId('reward_id')->nullable()->constrained('scratch_rewards_master')->nullOnDelete();
            $table->boolean('is_scratched')->default(false);
            $table->timestamp('scratched_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'is_scratched']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scratch_cards');
    }
};
