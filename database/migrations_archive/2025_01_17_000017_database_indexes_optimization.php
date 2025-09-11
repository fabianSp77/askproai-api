<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Performance indexes optimization for all tables
     * Adds composite indexes for common query patterns
     */
    public function up(): void
    {
        // Additional composite indexes for better performance
        
        // Tenants table optimizations
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->index(['slug', 'active']);
                $table->index(['api_key', 'active']);
            });
        }

        // Users table optimizations
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['tenant_id', 'email']);
                $table->index(['tenant_id', 'created_at']);
            });
        }

        // Calls table additional indexes
        if (Schema::hasTable('calls')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['tenant_id', 'customer_id', 'created_at']);
                $table->index(['tenant_id', 'call_status', 'start_timestamp']);
                $table->index(['from_number', 'created_at']);
                $table->index(['to_number', 'created_at']);
            });
        }

        // Appointments table additional indexes
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['tenant_id', 'staff_id', 'start_time']);
                $table->index(['tenant_id', 'customer_id', 'status']);
                $table->index(['tenant_id', 'service_id', 'start_time']);
                $table->index(['start_time', 'end_time']);
            });
        }

        // Working hours optimization
        if (Schema::hasTable('working_hours')) {
            Schema::table('working_hours', function (Blueprint $table) {
                $table->index(['tenant_id', 'staff_id', 'weekday']);
            });
        }

        // Activity log optimization
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->index(['tenant_id', 'log_name', 'created_at']);
                $table->index(['subject_type', 'subject_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the added indexes
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropIndex(['slug', 'active']);
                $table->dropIndex(['api_key', 'active']);
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'email']);
                $table->dropIndex(['tenant_id', 'created_at']);
            });
        }

        if (Schema::hasTable('calls')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'customer_id', 'created_at']);
                $table->dropIndex(['tenant_id', 'call_status', 'start_timestamp']);
                $table->dropIndex(['from_number', 'created_at']);
                $table->dropIndex(['to_number', 'created_at']);
            });
        }

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'staff_id', 'start_time']);
                $table->dropIndex(['tenant_id', 'customer_id', 'status']);
                $table->dropIndex(['tenant_id', 'service_id', 'start_time']);
                $table->dropIndex(['start_time', 'end_time']);
            });
        }

        if (Schema::hasTable('working_hours')) {
            Schema::table('working_hours', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'staff_id', 'weekday']);
            });
        }

        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'log_name', 'created_at']);
                $table->dropIndex(['subject_type', 'subject_id', 'created_at']);
            });
        }
    }
};