<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 🔧 PHASE 3 IMPROVEMENTS (2025-11-24): Enhanced sync error handling
     *
     * 1. Increase sync_error_message from TEXT to LONGTEXT
     *    - TEXT: max 65,535 bytes (~65KB)
     *    - LONGTEXT: max 4,294,967,295 bytes (~4GB)
     *    - Cal.com API responses are often 1-5KB, TEXT truncates them
     *
     * 2. Add unique constraint to prevent duplicate bookings
     *    - Constraint: (customer_id, starts_at, service_id, status)
     *    - Allows same booking only if previous is cancelled
     *    - Prevents race conditions and UI double-submissions
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // 1. Increase sync_error_message field length
            $table->longText('sync_error_message')->nullable()->change();

            // 2. NOTE: Unique constraint NOT added due to MySQL/MariaDB limitations
            // - MySQL/MariaDB doesn't support partial unique indexes (WHERE clause)
            // - PostgreSQL supports: CREATE UNIQUE INDEX ... WHERE status NOT IN (...)
            // - Workaround: Application-level validation in AppointmentService
            // - Alternative: Use triggers (complex, maintenance overhead)
            //
            // TODO: Implement application-level duplicate check:
            // - Before creating appointment, check for existing active booking
            // - Query: WHERE customer_id = ? AND starts_at = ? AND service_id = ?
            //          AND status NOT IN ('cancelled', 'no_show') AND deleted_at IS NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Revert sync_error_message to TEXT
            $table->text('sync_error_message')->nullable()->change();
        });
    }
};
