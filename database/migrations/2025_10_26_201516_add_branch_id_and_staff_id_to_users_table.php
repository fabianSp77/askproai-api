<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Branch and Staff Relationships to Users Table
 *
 * Purpose: Enable granular access control for Customer Portal roles
 *
 * Architecture:
 * - branch_id: For company_manager role (see only their branch)
 * - staff_id: For company_staff role (know which staff entry they represent)
 *
 * Role Mapping:
 * - company_owner: company_id ✓, branch_id NULL, staff_id NULL (sees all)
 * - company_admin: company_id ✓, branch_id NULL, staff_id NULL (sees all)
 * - company_manager: company_id ✓, branch_id ✓, staff_id NULL (sees only branch)
 * - company_staff: company_id ✓, branch_id ✓, staff_id ✓ (sees only own)
 *
 * Security Impact:
 * - Fixes: VULN-PORTAL-004 (Branch Isolation)
 * - Fixes: CallPolicy bug (user.staff_id missing)
 * - Enables: Multi-level authorization in policies
 *
 * Related:
 * - Policies: AppointmentPolicy, CallPolicy, RetellCallSessionPolicy
 * - Models: User, Branch, Staff
 * - Docs: CUSTOMER_PORTAL_SECURITY_AUDIT_2025-10-26.md
 *
 * @since 2025-10-26 (Phase 1: Customer Portal Foundation)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if table doesn't exist
        if (!Schema::hasTable('users')) {
            return;
        }

        // Check for existing indexes to avoid duplicates
        $existingIndexes = collect(Schema::getIndexes('users'))->pluck('name')->toArray();

        Schema::table('users', function (Blueprint $table) use ($existingIndexes) {
            // Add company_id first if it doesn't exist
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->unsignedBigInteger('company_id')
                    ->nullable()
                    ->after('password')
                    ->comment('Company this user belongs to');
            }

            // Branch assignment for company_manager role (only if column doesn't exist)
            if (!Schema::hasColumn('users', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')
                    ->nullable()
                    ->after(Schema::hasColumn('users', 'company_id') ? 'company_id' : 'password')
                    ->comment('Branch assignment for company_manager role');
            }

            // Staff relationship for company_staff role (only if column doesn't exist)
            if (!Schema::hasColumn('users', 'staff_id')) {
                $table->unsignedBigInteger('staff_id')
                    ->nullable()
                    ->after(Schema::hasColumn('users', 'branch_id') ? 'branch_id' : 'password')
                    ->comment('Staff entry this user represents (for company_staff role)');
            }
        });

        // Add foreign keys in separate try-catch blocks to handle constraint errors gracefully
        $existingForeignKeys = $this->getExistingForeignKeys('users');

        // Foreign key for company_id
        if (Schema::hasColumn('users', 'company_id') &&
            Schema::hasTable('companies') &&
            !in_array('users_company_id_foreign', $existingForeignKeys)) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('company_id')
                        ->references('id')
                        ->on('companies')
                        ->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // FK constraint error (table might not exist, types mismatch, etc.) - skip silently
            }
        }

        // Foreign key for branch_id
        if (Schema::hasColumn('users', 'branch_id') &&
            Schema::hasTable('branches') &&
            Schema::hasColumn('branches', 'id') &&
            !in_array('users_branch_id_foreign', $existingForeignKeys)) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('branch_id')
                        ->references('id')
                        ->on('branches')
                        ->onDelete('set null')
                        ->onUpdate('cascade');
                });
            } catch (\Exception $e) {
                // FK constraint error - skip silently (branches table might have different structure)
            }
        }

        // Foreign key for staff_id
        if (Schema::hasColumn('users', 'staff_id') &&
            Schema::hasTable('staff') &&
            Schema::hasColumn('staff', 'id') &&
            !in_array('users_staff_id_foreign', $existingForeignKeys)) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('staff_id')
                        ->references('id')
                        ->on('staff')
                        ->onDelete('set null')
                        ->onUpdate('cascade');
                });
            } catch (\Exception $e) {
                // FK constraint error - skip silently (staff table might have different structure)
            }
        }

        // Add indexes in separate call to avoid blocking on FK errors
        Schema::table('users', function (Blueprint $table) use ($existingIndexes) {
            // Indexes for performance
            if (Schema::hasColumn('users', 'company_id') && !in_array('users_company_id_index', $existingIndexes)) {
                $table->index('company_id', 'users_company_id_index');
            }

            if (Schema::hasColumn('users', 'branch_id') && !in_array('users_branch_id_index', $existingIndexes)) {
                $table->index('branch_id', 'users_branch_id_index');
            }

            if (Schema::hasColumn('users', 'staff_id') && !in_array('users_staff_id_index', $existingIndexes)) {
                $table->index('staff_id', 'users_staff_id_index');
            }
        });
    }

    /**
     * Get existing foreign keys for a table.
     */
    private function getExistingForeignKeys(string $table): array
    {
        try {
            $foreignKeys = Schema::getForeignKeys($table);
            return collect($foreignKeys)->pluck('name')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $existingIndexes = collect(Schema::getIndexes('users'))->pluck('name')->toArray();
        $existingForeignKeys = $this->getExistingForeignKeys('users');

        Schema::table('users', function (Blueprint $table) use ($existingIndexes, $existingForeignKeys) {
            // Drop foreign keys first (before dropping columns)
            if (in_array('users_branch_id_foreign', $existingForeignKeys)) {
                $table->dropForeign(['branch_id']);
            }
            if (in_array('users_staff_id_foreign', $existingForeignKeys)) {
                $table->dropForeign(['staff_id']);
            }

            // Drop indexes
            if (in_array('users_branch_id_index', $existingIndexes)) {
                $table->dropIndex('users_branch_id_index');
            }
            if (in_array('users_staff_id_index', $existingIndexes)) {
                $table->dropIndex('users_staff_id_index');
            }

            // Drop columns
            if (Schema::hasColumn('users', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
            if (Schema::hasColumn('users', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
        });
    }
};
