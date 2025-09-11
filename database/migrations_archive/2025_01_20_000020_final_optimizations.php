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
     * Final optimizations and views
     * Creates database views and final performance optimizations
     */
    public function up(): void
    {
        // Create useful database views for common queries
        
        // Tenant Statistics View
        DB::statement("
            CREATE OR REPLACE VIEW tenant_stats AS
            SELECT 
                t.id as tenant_id,
                t.name as tenant_name,
                t.slug,
                COUNT(DISTINCT c.id) as total_customers,
                COUNT(DISTINCT s.id) as total_staff,
                COUNT(DISTINCT b.id) as total_branches,
                COUNT(DISTINCT sv.id) as total_services,
                COUNT(DISTINCT ca.id) as total_calls,
                COUNT(DISTINCT a.id) as total_appointments,
                t.balance_cents,
                t.created_at,
                t.updated_at
            FROM tenants t
            LEFT JOIN customers c ON t.id = c.tenant_id
            LEFT JOIN staff s ON t.id = s.tenant_id
            LEFT JOIN branches b ON t.id = b.tenant_id
            LEFT JOIN services sv ON t.id = sv.tenant_id
            LEFT JOIN calls ca ON t.id = ca.tenant_id
            LEFT JOIN appointments a ON t.id = a.tenant_id
            GROUP BY t.id, t.name, t.slug, t.balance_cents, t.created_at, t.updated_at
        ");

        // Recent Calls View
        DB::statement("
            CREATE OR REPLACE VIEW recent_calls AS
            SELECT 
                ca.id,
                ca.tenant_id,
                ca.call_id,
                ca.from_number,
                ca.to_number,
                ca.call_status,
                ca.call_successful,
                ca.duration_sec,
                ca.cost_cents,
                cu.name as customer_name,
                cu.email as customer_email,
                b.name as branch_name,
                a.name as agent_name,
                ca.created_at,
                ca.updated_at
            FROM calls ca
            LEFT JOIN customers cu ON ca.customer_id = cu.id
            LEFT JOIN branches b ON ca.branch_id = b.id
            LEFT JOIN agents a ON ca.agent_id = a.id
            WHERE ca.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY ca.created_at DESC
        ");

        // Upcoming Appointments View
        DB::statement("
            CREATE OR REPLACE VIEW upcoming_appointments AS
            SELECT 
                ap.id,
                ap.tenant_id,
                ap.start_time,
                ap.end_time,
                ap.status,
                cu.name as customer_name,
                cu.email as customer_email,
                cu.phone as customer_phone,
                st.name as staff_name,
                sv.name as service_name,
                sv.duration_minutes,
                sv.price_cents,
                b.name as branch_name,
                ap.created_at,
                ap.updated_at
            FROM appointments ap
            LEFT JOIN customers cu ON ap.customer_id = cu.id
            LEFT JOIN staff st ON ap.staff_id = st.id
            LEFT JOIN services sv ON ap.service_id = sv.id
            LEFT JOIN branches b ON ap.branch_id = b.id
            WHERE ap.start_time >= NOW()
            AND ap.status IN ('scheduled', 'confirmed')
            ORDER BY ap.start_time ASC
        ");

        // Staff Availability View
        DB::statement("
            CREATE OR REPLACE VIEW staff_availability AS
            SELECT 
                s.id as staff_id,
                s.tenant_id,
                s.name as staff_name,
                s.email,
                s.phone,
                s.active,
                wh.weekday,
                wh.start as work_start,
                wh.end as work_end,
                b.name as home_branch_name,
                COUNT(DISTINCT bs.branch_id) as branches_count,
                COUNT(DISTINCT ss.service_id) as services_count
            FROM staff s
            LEFT JOIN working_hours wh ON s.id = wh.staff_id AND wh.active = 1
            LEFT JOIN branches b ON s.home_branch_id = b.id
            LEFT JOIN branch_staff bs ON s.id = bs.staff_id
            LEFT JOIN staff_service ss ON s.id = ss.staff_id
            WHERE s.active = 1
            GROUP BY s.id, s.tenant_id, s.name, s.email, s.phone, s.active, 
                     wh.weekday, wh.start, wh.end, b.name
        ");

        // Add final performance optimizations
        
        // Add full-text indexes for search functionality
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE customers ADD FULLTEXT(name, email)');
            DB::statement('ALTER TABLE staff ADD FULLTEXT(name, email)');
            DB::statement('ALTER TABLE services ADD FULLTEXT(name, description)');
            DB::statement('ALTER TABLE calls ADD FULLTEXT(transcript)');
        }

        // Create indexes for common date range queries
        Schema::table('calls', function (Blueprint $table) {
            $table->index(['tenant_id', 'start_timestamp', 'call_successful']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['tenant_id', 'start_time', 'status']);
        });

        // Add database constraints for data integrity
        DB::statement('ALTER TABLE tenants ADD CONSTRAINT chk_balance_positive CHECK (balance_cents >= 0)');
        DB::statement('ALTER TABLE services ADD CONSTRAINT chk_price_positive CHECK (price_cents >= 0)');
        DB::statement('ALTER TABLE calls ADD CONSTRAINT chk_duration_positive CHECK (duration_sec IS NULL OR duration_sec >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop database views
        DB::statement('DROP VIEW IF EXISTS staff_availability');
        DB::statement('DROP VIEW IF EXISTS upcoming_appointments');
        DB::statement('DROP VIEW IF EXISTS recent_calls');
        DB::statement('DROP VIEW IF EXISTS tenant_stats');

        // Drop full-text indexes
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE customers DROP INDEX name');
                DB::statement('ALTER TABLE staff DROP INDEX name');
                DB::statement('ALTER TABLE services DROP INDEX name');
                DB::statement('ALTER TABLE calls DROP INDEX transcript');
            } catch (Exception $e) {
                // Indexes might not exist, continue
            }
        }

        // Drop additional indexes
        Schema::table('calls', function (Blueprint $table) {
            try {
                $table->dropIndex(['tenant_id', 'start_timestamp', 'call_successful']);
            } catch (Exception $e) {
                // Index might not exist
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            try {
                $table->dropIndex(['tenant_id', 'start_time', 'status']);
            } catch (Exception $e) {
                // Index might not exist
            }
        });

        // Drop constraints
        try {
            DB::statement('ALTER TABLE tenants DROP CONSTRAINT chk_balance_positive');
            DB::statement('ALTER TABLE services DROP CONSTRAINT chk_price_positive');
            DB::statement('ALTER TABLE calls DROP CONSTRAINT chk_duration_positive');
        } catch (Exception $e) {
            // Constraints might not exist
        }
    }
};