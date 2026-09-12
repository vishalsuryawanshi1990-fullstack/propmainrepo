<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('property_type_id')->constrained('property_types_master');
            $table->string('listing_type');
            $table->decimal('price', 14, 2);
            $table->boolean('price_negotiable')->default(false);
            $table->decimal('area_sqft', 10, 2)->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->unsignedSmallInteger('floor_no')->nullable();
            $table->unsignedSmallInteger('total_floors')->nullable();
            $table->string('furnishing_status')->nullable();
            $table->foreignId('city_id')->constrained('cities_master');
            $table->foreignId('locality_id')->constrained('localities_master');
            $table->string('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('rera_registration_no')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['city_id', 'locality_id', 'listing_type', 'status', 'price'], 'properties_search_index');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
