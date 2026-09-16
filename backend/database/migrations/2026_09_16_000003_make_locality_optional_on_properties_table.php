<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Worldwide cities (WorldCitiesSeeder) have no locality/neighborhood
 * data — that only exists, hand-curated, for a handful of Indian
 * cities (see MasterDataSeeder). A property in a city with no seeded
 * localities has nowhere to point locality_id, so it becomes optional
 * with a free-text fallback instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('locality_text')->nullable()->after('locality_name');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('locality_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('locality_id')->nullable(false)->change();
            $table->dropColumn('locality_text');
        });
    }
};
