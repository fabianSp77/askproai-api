<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if table doesn't exist (testing environment)
        if (!Schema::hasTable('phone_numbers')) {
            return;
        }

        // Check columns existence before adding (must be outside Schema::table closure)
        $hasPhoneNumber = Schema::hasColumn('phone_numbers', 'phone_number');
        $hasFriendlyName = Schema::hasColumn('phone_numbers', 'friendly_name');
        $hasProvider = Schema::hasColumn('phone_numbers', 'provider');
        $hasProviderId = Schema::hasColumn('phone_numbers', 'provider_id');
        $hasCountryCode = Schema::hasColumn('phone_numbers', 'country_code');
        $hasMonthlyCost = Schema::hasColumn('phone_numbers', 'monthly_cost');
        $hasUsageMinutes = Schema::hasColumn('phone_numbers', 'usage_minutes');
        $hasLastUsedAt = Schema::hasColumn('phone_numbers', 'last_used_at');
        $hasLabel = Schema::hasColumn('phone_numbers', 'label');
        $hasNotes = Schema::hasColumn('phone_numbers', 'notes');

        Schema::table('phone_numbers', function (Blueprint $table) use (
            $hasPhoneNumber, $hasFriendlyName, $hasProvider, $hasProviderId,
            $hasCountryCode, $hasMonthlyCost, $hasUsageMinutes, $hasLastUsedAt,
            $hasLabel, $hasNotes
        ) {
            // Add missing columns that Model expects
            if (!$hasPhoneNumber) {
                $table->string('phone_number')->nullable();
            }
            if (!$hasFriendlyName) {
                $table->string('friendly_name')->nullable();
            }
            if (!$hasProvider) {
                $table->string('provider')->nullable();
            }
            if (!$hasProviderId) {
                $table->string('provider_id')->nullable();
            }
            if (!$hasCountryCode) {
                $table->string('country_code', 10)->default('+49');
            }
            if (!$hasMonthlyCost) {
                $table->decimal('monthly_cost', 10, 2)->nullable();
            }
            if (!$hasUsageMinutes) {
                $table->integer('usage_minutes')->default(0);
            }
            if (!$hasLastUsedAt) {
                $table->timestamp('last_used_at')->nullable();
            }
            if (!$hasLabel) {
                $table->string('label')->nullable();
            }
            if (!$hasNotes) {
                $table->text('notes')->nullable();
            }

            // Indexes are already created, skip them
        });

        // Copy data from 'number' to 'phone_number' for compatibility (only if 'number' column exists)
        if (Schema::hasColumn('phone_numbers', 'number')) {
            DB::statement('UPDATE phone_numbers SET phone_number = number WHERE phone_number IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropColumn([
                'phone_number',
                'friendly_name',
                'provider',
                'provider_id',
                'country_code',
                'monthly_cost',
                'usage_minutes',
                'last_used_at',
                'label',
                'notes'
            ]);

            // Drop indexes if they exist
            if (Schema::hasIndex('phone_numbers', 'phone_numbers_is_active_index')) {
                $table->dropIndex(['is_active']);
            }
            if (Schema::hasIndex('phone_numbers', 'phone_numbers_type_index')) {
                $table->dropIndex(['type']);
            }
            if (Schema::hasIndex('phone_numbers', 'phone_numbers_company_id_is_primary_index')) {
                $table->dropIndex(['company_id', 'is_primary']);
            }
        });
    }
};
