<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Billing System Extension: Transaction Enhancements
     *
     * Adds linkage to service change fees and enhances transaction tracking.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Link to service change fee (for setup fees and service changes)
            if (!Schema::hasColumn('transactions', 'service_change_fee_id')) {
                $table->foreignId('service_change_fee_id')
                    ->nullable()
                    ->after('appointment_id')
                    ->constrained('service_change_fees')
                    ->nullOnDelete();
            }

            // Link to company fee schedule (for setup fee tracking)
            if (!Schema::hasColumn('transactions', 'fee_schedule_id')) {
                $table->foreignId('fee_schedule_id')
                    ->nullable()
                    ->after('service_change_fee_id')
                    ->constrained('company_fee_schedules')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'service_change_fee_id')) {
                $table->dropForeign(['service_change_fee_id']);
                $table->dropColumn('service_change_fee_id');
            }

            if (Schema::hasColumn('transactions', 'fee_schedule_id')) {
                $table->dropForeign(['fee_schedule_id']);
                $table->dropColumn('fee_schedule_id');
            }
        });
    }
};
