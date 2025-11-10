<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 🔧 FIX 2025-11-06: Add missing columns to policy_configurations table
     * ROOT CAUSE: Code expects branch_id, is_active, policy_value, description columns
     * but migration only created polymorphic relationship structure
     *
     * ERROR: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'branch_id' in 'WHERE'
     */
    public function up(): void
    {
        Schema::table('policy_configurations', function (Blueprint $table) {
            // Add branch_id for direct branch relationship
            if (!Schema::hasColumn('policy_configurations', 'branch_id')) {
                $table->uuid('branch_id')->nullable()->after('company_id')
                    ->comment('Direct branch reference (null for company-wide policies)');

                $table->foreign('branch_id')
                    ->references('id')->on('branches')
                    ->onDelete('cascade');

                $table->index('branch_id', 'idx_branch');
            }

            // Add is_active flag
            if (!Schema::hasColumn('policy_configurations', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_override')
                    ->comment('Whether this policy is currently active');

                $table->index(['company_id', 'is_active'], 'idx_company_active');
            }

            // Add policy_value for simple value storage (alternative to config JSON)
            if (!Schema::hasColumn('policy_configurations', 'policy_value')) {
                $table->string('policy_value')->nullable()->after('policy_type')
                    ->comment('Simple policy value (hours, percentage, etc.)');
            }

            // Add description for human-readable policy explanation
            if (!Schema::hasColumn('policy_configurations', 'description')) {
                $table->text('description')->nullable()->after('policy_value')
                    ->comment('Human-readable policy description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policy_configurations', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'is_active', 'policy_value', 'description']);
        });
    }
};
