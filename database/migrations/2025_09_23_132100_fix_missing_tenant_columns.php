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
        
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            // Add missing columns that TenantResource expects
            if (!Schema::hasColumn('tenants', 'is_verified')) {
                $table->boolean('is_verified')->default(false);
            }

            if (!Schema::hasColumn('tenants', 'type')) {
                $table->string('type')->default('standard');
            }

            if (!Schema::hasColumn('tenants', 'logo_url')) {
                $table->string('logo_url', 500)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'is_verified')) {
                $table->dropColumn('is_verified');
            }

            if (Schema::hasColumn('tenants', 'type')) {
                $table->dropColumn('type');
            }

            if (Schema::hasColumn('tenants', 'logo_url')) {
                $table->dropColumn('logo_url');
            }
        });
    }
};