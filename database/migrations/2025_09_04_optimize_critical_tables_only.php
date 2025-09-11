<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add only the most critical performance indexes
     * Focus on the highest-impact queries
     */
    public function up(): void
    {
        echo "\n🎯 Adding Critical Performance Indexes Only...\n\n";

        // APPOINTMENTS - Most critical for business operations
        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'start_time')) {
            try {
                // Index for finding upcoming appointments
                DB::statement("ALTER TABLE appointments ADD INDEX idx_apt_start_time (start_time)");
                echo "✅ Added index on appointments.start_time\n";
            } catch (\Exception $e) {
                echo "⏭️  Index on appointments.start_time exists or failed\n";
            }

            if (Schema::hasColumn('appointments', 'status')) {
                try {
                    // Index for filtering by status
                    DB::statement("ALTER TABLE appointments ADD INDEX idx_apt_status (status)");
                    echo "✅ Added index on appointments.status\n";
                } catch (\Exception $e) {
                    echo "⏭️  Index on appointments.status exists or failed\n";
                }
            }
        }

        // CUSTOMERS - Critical for lookups
        if (Schema::hasTable('customers')) {
            if (Schema::hasColumn('customers', 'email')) {
                try {
                    DB::statement("ALTER TABLE customers ADD INDEX idx_cust_email (email)");
                    echo "✅ Added index on customers.email\n";
                } catch (\Exception $e) {
                    echo "⏭️  Index on customers.email exists or failed\n";
                }
            }

            if (Schema::hasColumn('customers', 'phone')) {
                try {
                    DB::statement("ALTER TABLE customers ADD INDEX idx_cust_phone (phone)");
                    echo "✅ Added index on customers.phone\n";
                } catch (\Exception $e) {
                    echo "⏭️  Index on customers.phone exists or failed\n";
                }
            }
        }

        // USERS - Critical for authentication
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'email')) {
            try {
                // May already exist, but ensure it's there
                DB::statement("ALTER TABLE users ADD INDEX idx_users_email_lookup (email)");
                echo "✅ Added index on users.email\n";
            } catch (\Exception $e) {
                echo "⏭️  Index on users.email exists or failed\n";
            }
        }

        // STAFF - Critical for scheduling
        if (Schema::hasTable('staff')) {
            if (Schema::hasColumn('staff', 'branch_id')) {
                try {
                    DB::statement("ALTER TABLE staff ADD INDEX idx_staff_branch (branch_id)");
                    echo "✅ Added index on staff.branch_id\n";
                } catch (\Exception $e) {
                    echo "⏭️  Index on staff.branch_id exists or failed\n";
                }
            }
        }

        // SERVICES - Critical for booking
        if (Schema::hasTable('services') && Schema::hasColumn('services', 'is_active')) {
            try {
                DB::statement("ALTER TABLE services ADD INDEX idx_services_active (is_active)");
                echo "✅ Added index on services.is_active\n";
            } catch (\Exception $e) {
                echo "⏭️  Index on services.is_active exists or failed\n";
            }
        }

        echo "\n✅ Critical index optimization complete!\n";
        echo "📊 These indexes target the highest-impact queries for immediate performance gains.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safely attempt to drop indexes if they exist
        $dropIndexIfExists = function($table, $indexName) {
            try {
                DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
                echo "Dropped index {$indexName} from {$table}\n";
            } catch (\Exception $e) {
                // Index doesn't exist or can't be dropped
            }
        };

        $dropIndexIfExists('appointments', 'idx_apt_start_time');
        $dropIndexIfExists('appointments', 'idx_apt_status');
        $dropIndexIfExists('customers', 'idx_cust_email');
        $dropIndexIfExists('customers', 'idx_cust_phone');
        $dropIndexIfExists('users', 'idx_users_email_lookup');
        $dropIndexIfExists('staff', 'idx_staff_branch');
        $dropIndexIfExists('services', 'idx_services_active');
    }
};