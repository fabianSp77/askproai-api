<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix for 500 errors: Add missing company_id column to notification_queue
     *
     * Root Cause: SEC-003 security fixes added company_id filtering to widgets,
     * but the column was never added to the database schema.
     *
     * This migration:
     * 1. Adds company_id column
     * 2. Populates it from existing notifiable relationships
     * 3. Adds performance index
     */
    public function up(): void
    {
        // Skip if table doesn't exist (idempotent migration)
        if (!Schema::hasTable('notification_queue')) {
            return;
        }

        // Skip if column already exists
        if (Schema::hasColumn('notification_queue', 'company_id')) {
            return;
        }

        // Add company_id column to notification_queue
        Schema::table('notification_queue', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('notifiable_id');
            $table->index('company_id', 'idx_notification_queue_company_id');
        });

        // Populate company_id from Customer records (most common notifiable)
        DB::statement("
            UPDATE notification_queue nq
            INNER JOIN customers c ON nq.notifiable_id = c.id
                AND nq.notifiable_type = 'App\\\\Models\\\\Customer'
            SET nq.company_id = c.company_id
            WHERE nq.company_id IS NULL
        ");

        // Populate company_id from Staff records
        DB::statement("
            UPDATE notification_queue nq
            INNER JOIN staff s ON nq.notifiable_id = s.id
                AND nq.notifiable_type = 'App\\\\Models\\\\Staff'
            SET nq.company_id = s.company_id
            WHERE nq.company_id IS NULL
        ");

        // Populate company_id from User records
        DB::statement("
            UPDATE notification_queue nq
            INNER JOIN users u ON nq.notifiable_id = u.id
                AND nq.notifiable_type = 'App\\\\Models\\\\User'
            SET nq.company_id = u.company_id
            WHERE nq.company_id IS NULL
        ");

        // Log any notifications that couldn't be assigned a company_id
        $orphaned = DB::table('notification_queue')->whereNull('company_id')->count();
        if ($orphaned > 0) {
            \Log::warning("Notification Queue Migration: {$orphaned} notifications could not be assigned a company_id");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_queue', function (Blueprint $table) {
            $table->dropIndex('idx_notification_queue_company_id');
            $table->dropColumn('company_id');
        });
    }
};
