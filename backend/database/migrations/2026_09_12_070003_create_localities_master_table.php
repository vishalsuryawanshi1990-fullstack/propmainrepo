<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localities_master', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities_master')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('long', 10, 7)->nullable();
            $table->decimal('avg_price_sqft', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['city_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localities_master');
    }
};
