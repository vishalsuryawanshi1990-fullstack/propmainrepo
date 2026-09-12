<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widened for the `encrypted` cast (05-security-compliance.md: "KYC
     * documents: encrypted at rest") — the encrypted payload is far
     * longer than a plain path and won't fit in varchar(255). Dropped
     * and re-added rather than ->change() to avoid a doctrine/dbal
     * dependency just for this; safe pre-production, no data to migrate.
     */
    public function up(): void
    {
        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });

        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->text('file_path')->after('doc_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });

        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->string('file_path')->after('doc_type');
        });
    }
};
