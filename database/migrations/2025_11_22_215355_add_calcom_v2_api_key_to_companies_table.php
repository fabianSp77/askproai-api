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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('calcom_v2_api_key', 255)->nullable()->after('calcom_api_key')
                ->comment('Cal.com V2 API key for this company (overrides ENV config)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('calcom_v2_api_key');
        });
    }
};
