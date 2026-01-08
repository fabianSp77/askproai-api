<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Cal.com V2 API key storage for company-specific credentials.
     * This allows each company to have its own Cal.com organization/team API key.
     */
    public function up(): void
    {
        // Skip if table doesn't exist
        if (!Schema::hasTable('companies')) {
            return;
        }

        // Skip if column already exists
        if (Schema::hasColumn('companies', 'calcom_v2_api_key')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            // Determine where to place the column (fallback if calcom_api_key doesn't exist)
            $afterColumn = Schema::hasColumn('companies', 'calcom_api_key') ? 'calcom_api_key' : 'id';
            $table->string('calcom_v2_api_key', 255)->nullable()->after($afterColumn)
                ->comment('Cal.com V2 API key for this company (overrides ENV config)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        if (!Schema::hasColumn('companies', 'calcom_v2_api_key')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('calcom_v2_api_key');
        });
    }
};
