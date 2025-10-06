<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * P4 Performance Optimization: Critical indexes for widget queries
     * Expected Impact: 10-100x query speed improvement
     */
    public function up(): void
    {
        // Appointments - Critical for TimeBasedAnalyticsWidget
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['company_id', 'starts_at'], 'idx_appointments_company_starts_at');
            $table->index(['company_id', 'status'], 'idx_appointments_company_status');
        });

        // Appointment Modification Stats - Critical for all policy widgets
        Schema::table('appointment_modification_stats', function (Blueprint $table) {
            $table->index(['customer_id', 'stat_type'], 'idx_ams_customer_stat_type');
            $table->index(['company_id', 'created_at'], 'idx_ams_company_created');
            $table->index(['stat_type', 'created_at'], 'idx_ams_stat_type_created');
        });

        // Notification Queue - Critical for notification widgets
        Schema::table('notification_queues', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_nq_status_created');
            $table->index(['channel', 'created_at', 'status'], 'idx_nq_channel_created_status');
            $table->index(['created_at', 'status'], 'idx_nq_created_status');
        });

        // Staff - Critical for StaffPerformanceWidget
        Schema::table('staff', function (Blueprint $table) {
            $table->index(['company_id', 'is_active'], 'idx_staff_company_active');
        });

        // Customers - Critical for CustomerComplianceWidget
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['company_id', 'journey_status'], 'idx_customers_company_journey');
        });

        // Policy Configurations - Critical for PolicyEffectivenessWidget
        Schema::table('policy_configurations', function (Blueprint $table) {
            $table->index(['company_id', 'is_active'], 'idx_policy_configs_company_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_company_starts_at');
            $table->dropIndex('idx_appointments_company_status');
        });

        Schema::table('appointment_modification_stats', function (Blueprint $table) {
            $table->dropIndex('idx_ams_customer_stat_type');
            $table->dropIndex('idx_ams_company_created');
            $table->dropIndex('idx_ams_stat_type_created');
        });

        Schema::table('notification_queues', function (Blueprint $table) {
            $table->dropIndex('idx_nq_status_created');
            $table->dropIndex('idx_nq_channel_created_status');
            $table->dropIndex('idx_nq_created_status');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex('idx_staff_company_active');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_company_journey');
        });

        Schema::table('policy_configurations', function (Blueprint $table) {
            $table->dropIndex('idx_policy_configs_company_active');
        });
    }
};
