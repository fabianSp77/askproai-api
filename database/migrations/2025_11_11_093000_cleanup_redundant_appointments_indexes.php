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
     * 🎯 FIX 2025-11-11: Remove redundant indexes from appointments table
     *
     * Problem: Appointments table has 64 indexes (MySQL limit), blocking new performance indexes
     * Root Cause: Multiple duplicate indexes created over time
     * Solution: Drop 10 redundant indexes to make room for critical Cal.com performance indexes
     * Impact: No performance degradation (removing only duplicates)
     * Risk: Low (keeping the better-named versions)
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Helper to safely drop index if it exists
            $dropIfExists = function($indexName) {
                $exists = DB::select("
                    SELECT COUNT(*) as count
                    FROM information_schema.statistics
                    WHERE table_schema = DATABASE()
                    AND table_name = 'appointments'
                    AND index_name = ?
                ", [$indexName]);

                return $exists[0]->count > 0;
            };

            // Drop single-column duplicates (keeping the idx_* versions as they're more descriptive)
            if ($dropIfExists('appointments_call_id_index')) {
                $table->dropIndex('appointments_call_id_index'); // Duplicate of idx_appointments_call_lookup
            }

            if ($dropIfExists('appointments_branch_id_index')) {
                $table->dropIndex('appointments_branch_id_index'); // Duplicate of idx_appointments_branch_id
            }

            if ($dropIfExists('appointments_staff_id_index')) {
                $table->dropIndex('appointments_staff_id_index'); // Duplicate of idx_appointments_staff_id
            }

            if ($dropIfExists('appointments_customer_id_index')) {
                $table->dropIndex('appointments_customer_id_index'); // Duplicate of idx_appointments_customer_id
            }

            if ($dropIfExists('appointments_service_id_index')) {
                $table->dropIndex('appointments_service_id_index'); // Duplicate of idx_appointments_service_id
            }

            if ($dropIfExists('appointments_company_id_index')) {
                $table->dropIndex('appointments_company_id_index'); // Duplicate of idx_appointments_company_id
            }

            if ($dropIfExists('appointments_parent_appointment_id_index')) {
                $table->dropIndex('appointments_parent_appointment_id_index'); // Duplicate of foreign key index
            }

            // Drop composite duplicates (keeping the better-named idx_* versions)
            if ($dropIfExists('appointments_company_starts_at_index')) {
                $table->dropIndex('appointments_company_starts_at_index'); // Duplicate of idx_company_starts
            }

            if ($dropIfExists('appointments_company_status_index')) {
                $table->dropIndex('appointments_company_status_index'); // Duplicate of idx_appointments_company_status
            }

            if ($dropIfExists('appointments_starts_at_ends_at_index')) {
                $table->dropIndex('appointments_starts_at_ends_at_index'); // Duplicate of idx_appointments_date_range
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Recreate the indexes (in case rollback is needed)
            $table->index('call_id', 'appointments_call_id_index');
            $table->index('branch_id', 'appointments_branch_id_index');
            $table->index('staff_id', 'appointments_staff_id_index');
            $table->index('customer_id', 'appointments_customer_id_index');
            $table->index('service_id', 'appointments_service_id_index');
            $table->index('company_id', 'appointments_company_id_index');
            $table->index('parent_appointment_id', 'appointments_parent_appointment_id_index');
            $table->index(['company_id', 'starts_at'], 'appointments_company_starts_at_index');
            $table->index(['company_id', 'status'], 'appointments_company_status_index');
            $table->index(['starts_at', 'ends_at'], 'appointments_starts_at_ends_at_index');
        });
    }
};
