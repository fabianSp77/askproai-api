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

        Schema::table('phone_numbers', function (Blueprint $table) {
            // Add missing columns that Model expects
            if (!Schema::hasColumn('phone_numbers', 'phone_number')) {
                $table->string('phone_number')->nullable();
            }
            if (!Schema::hasColumn('phone_numbers', 'friendly_name')) {
                $table->string('friendly_name')->nullable();
            }
            if (!Schema::hasColumn('phone_numbers', 'provider')) {
                $table->string('provider')->nullable();
            }
            if (!Schema::hasColumn('phone_numbers', 'provider_id')) {
                $table->string('provider_id')->nullable();
            }
            if (!Schema::hasColumn('phone_numbers', 'country_code')) {
                $table->string('country_code', 10)->default('+49');
            }
            if (!Schema::hasColumn('phone_numbers', 'monthly_cost')) {
                $table->decimal('monthly_cost', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('phone_numbers', 'usage_minutes')) {
                $table->integer('usage_minutes')->default(0);
            }
            if (!Schema::hasColumn('phone_numbers', 'last_used_at')) {
                $table->timestamp('last_used_at')->nullable();
            }
            if (!Schema::hasColumn('phone_numbers', 'label')) {
                $table->string('label')->nullable();
            }
            if (!Schema::hasColumn('phone_numbers', 'notes')) {
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
