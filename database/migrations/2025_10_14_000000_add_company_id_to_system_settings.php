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
        Schema::table('system_settings', function (Blueprint $table) {
            // Add company_id for multi-tenant support
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();

            // Add unique constraint: one setting per company per key
            // Drop existing unique key first if it exists
            $table->dropUnique(['key']);

            // Add new composite unique key
            $table->unique(['company_id', 'key'], 'unique_company_setting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            // Drop composite unique key
            $table->dropUnique('unique_company_setting');

            // Restore original unique key
            $table->unique('key');

            // Drop foreign key and column
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
