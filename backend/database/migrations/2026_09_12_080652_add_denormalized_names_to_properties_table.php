<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Denormalized read-only copies of city/locality name, kept in sync
     * by PropertyObserver. Needed so free-text search can match on them
     * with Scout's "database" engine (used locally/in tests) — it runs
     * plain LIKE queries against real columns on this table and can't
     * join to cities_master/localities_master the way Meilisearch's
     * indexed toSearchableArray() can in production.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('city_name')->nullable()->after('city_id');
            $table->string('locality_name')->nullable()->after('locality_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['city_name', 'locality_name']);
        });
    }
};
