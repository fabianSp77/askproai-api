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
     * 🎯 FIX 2025-11-11: Add performance indexes for Cal.com integration
     *
     * Root Cause: Slow queries causing API timeouts → rate limit violations
     * Impact: 5-30ms query speedup, -3-5 req/min (fewer timeouts)
     * Risk: Low (DDL operation, non-blocking with proper config)
     *
     * Analysis Source: Performance Engineer + Backend Architect agents
     */
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
            AND table_name = ?
            AND index_name = ?
        ", [$table, $indexName]);

        return $result[0]->count > 0;
    }

    public function up(): void
    {
        // 1. Appointments table - For availability checks and alternative finder
        if (!$this->indexExists('appointments', 'idx_appts_service_start')) {
            Schema::table('appointments', function (Blueprint $table) {
                // For availability overlap checks (used in getAvailableSlots())
                // Query: WHERE service_id = ? AND starts_at BETWEEN ? AND ? ORDER BY starts_at
                $table->index(['service_id', 'starts_at'], 'idx_appts_service_start');
            });
        }

        if (!$this->indexExists('appointments', 'idx_appts_branch_status_start')) {
            Schema::table('appointments', function (Blueprint $table) {
                // For alternative finder staff availability
                // Query: WHERE branch_id = ? AND status IN ('confirmed', 'pending') AND starts_at >= ?
                $table->index(['branch_id', 'status', 'starts_at'], 'idx_appts_branch_status_start');
            });
        }

        if (Schema::hasColumn('appointments', 'calcom_booking_id') && !$this->indexExists('appointments', 'idx_appts_calcom_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                // For Cal.com sync lookups - Query: WHERE calcom_booking_id = ?
                $table->index('calcom_booking_id', 'idx_appts_calcom_id');
            });
        }

        // 2. Staff table - For branch-based staff lookups
        if (!$this->indexExists('staff', 'idx_staff_branch_active')) {
            Schema::table('staff', function (Blueprint $table) {
                // For alternative finder staff selection - Query: WHERE branch_id = ? AND is_active = 1
                $table->index(['branch_id', 'is_active'], 'idx_staff_branch_active');
            });
        }

        if (Schema::hasColumn('staff', 'calcom_user_id') && !$this->indexExists('staff', 'idx_staff_calcom_user')) {
            Schema::table('staff', function (Blueprint $table) {
                // For Cal.com host mapping lookups - Query: WHERE calcom_user_id = ?
                $table->index('calcom_user_id', 'idx_staff_calcom_user');
            });
        }

        // 3. Services table - For event type lookups
        if (Schema::hasColumn('services', 'calcom_event_type_id') && !$this->indexExists('services', 'idx_services_calcom_event')) {
            Schema::table('services', function (Blueprint $table) {
                // For event type → service mapping - Query: WHERE calcom_event_type_id = ?
                $table->index('calcom_event_type_id', 'idx_services_calcom_event');
            });
        }

        if (Schema::hasColumn('services', 'is_active') && Schema::hasColumn('services', 'duration_minutes') && !$this->indexExists('services', 'idx_services_active_duration')) {
            Schema::table('services', function (Blueprint $table) {
                // For active services with duration filtering - Query: WHERE is_active = 1 AND duration_minutes <= ?
                $table->index(['is_active', 'duration_minutes'], 'idx_services_active_duration');
            });
        }

        // 4. Branches table - For active branch lookups
        if (Schema::hasColumn('branches', 'is_active') && !$this->indexExists('branches', 'idx_branches_company_active')) {
            Schema::table('branches', function (Blueprint $table) {
                // For active branch filtering - Query: WHERE is_active = 1 AND company_id = ?
                $table->index(['company_id', 'is_active'], 'idx_branches_company_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appts_service_start');
            $table->dropIndex('idx_appts_branch_status_start');
            if (Schema::hasColumn('appointments', 'calcom_booking_id')) {
                $table->dropIndex('idx_appts_calcom_id');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex('idx_staff_branch_active');
            if (Schema::hasColumn('staff', 'calcom_user_id')) {
                $table->dropIndex('idx_staff_calcom_user');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'calcom_event_type_id')) {
                $table->dropIndex('idx_services_calcom_event');
            }
            if (Schema::hasColumn('services', 'is_active') && Schema::hasColumn('services', 'duration_minutes')) {
                $table->dropIndex('idx_services_active_duration');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'is_active')) {
                $table->dropIndex('idx_branches_company_active');
            }
        });
    }
};
