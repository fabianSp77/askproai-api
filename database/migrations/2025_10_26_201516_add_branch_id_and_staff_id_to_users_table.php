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
        Schema::table('users', function (Blueprint $table) {
            // Add company_id first if it doesn't exist
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->unsignedBigInteger('company_id')
                    ->nullable()
                    ->after('password')
                    ->comment('Company this user belongs to');

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');

                $table->index('company_id');
            }

            // Branch assignment for company_manager role (with idempotency check)
            if (!Schema::hasColumn('users', 'branch_id')) {
                // Allows managers to be assigned to specific branches
                // Note: branches.id is bigint unsigned auto-increment
                // NULL = not a manager OR sees all branches (company_owner)
                $table->unsignedBigInteger('branch_id')
                    ->nullable()
                    ->after('company_id')
                    ->comment('Branch assignment for company_manager role');

                // Foreign key constraint
                $table->foreign('branch_id')
                    ->references('id')
                    ->on('branches')
                    ->onDelete('set null')  // If branch deleted, set NULL (don't delete user)
                    ->onUpdate('cascade');  // If branch ID changes, update reference

                // Index for performance (policy checks will query this frequently)
                $table->index('branch_id', 'users_branch_id_index');
            }

            // Staff relationship for company_staff role (with idempotency check)
            if (!Schema::hasColumn('users', 'staff_id')) {
                // Links user account to their staff entry (for appointments, calls, etc.)
                // Note: staff.id is bigint unsigned auto-increment
                $table->unsignedBigInteger('staff_id')
                    ->nullable()
                    ->after('branch_id')
                    ->comment('Staff entry this user represents (for company_staff role)');

                // Foreign key constraint
                $table->foreign('staff_id')
                    ->references('id')
                    ->on('staff')
                    ->onDelete('set null')  // If staff deleted, set NULL (don't delete user)
                    ->onUpdate('cascade');  // If staff ID changes, update reference

                // Index for performance (policy checks will query this frequently)
                $table->index('staff_id', 'users_staff_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign keys, indexes, and columns with idempotency checks

            // Check and drop branch_id
            if (Schema::hasColumn('users', 'branch_id')) {
                // Drop foreign key constraint
                $table->dropForeign(['branch_id']);

                // Drop index
                $table->dropIndex('users_branch_id_index');

                // Drop column
                $table->dropColumn('branch_id');
            }

            // Check and drop staff_id
            if (Schema::hasColumn('users', 'staff_id')) {
                // Drop foreign key constraint
                $table->dropForeign(['staff_id']);

                // Drop index
                $table->dropIndex('users_staff_id_index');

                // Drop column
                $table->dropColumn('staff_id');
            }
        });
    }
};
