<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities_master', function (Blueprint $table) {
            $table->string('country')->nullable()->after('name');
        });

        // Many countries have no admin1 (state/province) subdivision at
        // all (Monaco, Singapore, Vatican City, ...) — was NOT NULL
        // because every city so far had been manually entered with one.
        Schema::table('cities_master', function (Blueprint $table) {
            $table->string('state')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cities_master', function (Blueprint $table) {
            $table->dropColumn('country');
        });

        Schema::table('cities_master', function (Blueprint $table) {
            $table->string('state')->nullable(false)->change();
        });
    }
};
