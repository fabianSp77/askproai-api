<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * RCA: Anonymous calls from Retell AI have no email address.
     * The system was setting email = '' (empty string), which violated
     * the UNIQUE constraint when multiple anonymous calls occurred.
     *
     * Fix:
     * 1. Convert all empty string emails to NULL
     * 2. Make email column nullable
     * 3. MySQL automatically allows multiple NULL values in UNIQUE indexes
     *
     * Related: Testcall c09 - call_c09a5098aa68c7ea28fc840dabd
     * Error: "Duplicate entry '' for key 'customers_email_unique'"
     */
    public function up(): void
    {
        // Step 1: Convert all empty string emails to NULL
        DB::statement("UPDATE customers SET email = NULL WHERE email = ''");

        // Step 2: Drop existing UNIQUE constraint
        // Laravel 11: Use try/catch instead of Doctrine
        try {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique('customers_email_unique');
            });
        } catch (\Exception $e) {
            // Index doesn't exist, that's OK
            \Log::info('customers_email_unique index does not exist, skipping drop');
        }

        // Step 3: Make email nullable
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->change();
        });

        // Step 4: Re-create UNIQUE index
        // MySQL automatically allows multiple NULL values in UNIQUE indexes
        // but only one occurrence of any non-NULL value
        Schema::table('customers', function (Blueprint $table) {
            $table->unique('email', 'customers_email_unique');
        });

        // Log migration execution
        \Log::info('✅ Migration: customers.email now nullable, UNIQUE constraint allows multiple NULL values', [
            'migration' => '2025_11_11_231608_fix_customers_email_unique_constraint',
            'empty_strings_converted' => DB::table('customers')->whereNull('email')->count(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop UNIQUE constraint
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_email_unique');
        });

        // Step 2: Make email NOT NULL again (convert NULL back to empty string)
        DB::statement("UPDATE customers SET email = '' WHERE email IS NULL");

        Schema::table('customers', function (Blueprint $table) {
            $table->string('email', 255)->nullable(false)->change();
        });

        // Step 3: Re-create UNIQUE constraint
        Schema::table('customers', function (Blueprint $table) {
            $table->unique('email', 'customers_email_unique');
        });

        \Log::warning('⚠️ Migration rolled back: customers.email back to NOT NULL, may cause issues with anonymous calls');
    }
};
