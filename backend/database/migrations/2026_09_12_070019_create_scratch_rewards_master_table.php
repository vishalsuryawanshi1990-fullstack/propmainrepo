<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scratch_rewards_master', function (Blueprint $table) {
            $table->id();
            $table->string('reward_type');
            $table->decimal('value', 12, 2)->default(0);
            $table->unsignedInteger('probability_weight');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scratch_rewards_master');
    }
};
