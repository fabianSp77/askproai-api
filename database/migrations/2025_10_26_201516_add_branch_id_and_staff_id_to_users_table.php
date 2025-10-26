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
            // Branch assignment for company_manager role
            // Allows managers to be assigned to specific branches
            // Note: branches.id is UUID (char 36), not auto-increment
            // NULL = not a manager OR sees all branches (company_owner)
            $table->char('branch_id', 36)
                ->nullable()
                ->after('company_id')
                ->comment('Branch assignment for company_manager role');

            // Staff relationship for company_staff role
            // Links user account to their staff entry (for appointments, calls, etc.)
            // Note: staff.id is UUID (char 36), not auto-increment
            $table->char('staff_id', 36)
                ->nullable()
                ->after('branch_id')
                ->comment('Staff entry this user represents (for company_staff role)');

            // Foreign key constraints
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('set null')  // If branch deleted, set NULL (don't delete user)
                ->onUpdate('cascade');  // If branch ID changes, update reference

            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->onDelete('set null')  // If staff deleted, set NULL (don't delete user)
                ->onUpdate('cascade');  // If staff ID changes, update reference

            // Indexes for performance (policy checks will query these frequently)
            $table->index('branch_id', 'users_branch_id_index');
            $table->index('staff_id', 'users_staff_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign keys first (before dropping columns)
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['staff_id']);

            // Drop indexes
            $table->dropIndex('users_branch_id_index');
            $table->dropIndex('users_staff_id_index');

            // Drop columns
            $table->dropColumn(['branch_id', 'staff_id']);
        });
    }
};
