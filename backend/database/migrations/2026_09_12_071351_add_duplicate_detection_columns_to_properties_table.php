<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('address_price_hash', 64)->nullable()->after('rera_registration_no');
            $table->string('primary_image_hash', 64)->nullable()->after('address_price_hash');
            $table->foreignId('duplicate_of_property_id')->nullable()->after('primary_image_hash')
                ->constrained('properties')->nullOnDelete();
            $table->boolean('is_flagged_duplicate')->default(false)->after('duplicate_of_property_id');

            $table->index('address_price_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('duplicate_of_property_id');
            $table->dropColumn(['address_price_hash', 'primary_image_hash', 'is_flagged_duplicate']);
        });
    }
};
