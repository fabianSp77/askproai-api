<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Add company_id if it doesn't exist
            if (!Schema::hasColumn('appointments', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->index('company_id');
            }
            
            // Add branch_id if it doesn't exist
            if (!Schema::hasColumn('appointments', 'branch_id')) {
                $table->char('branch_id', 36)->nullable()->after('company_id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            }
            
            // Add service_id if it doesn't exist
            if (!Schema::hasColumn('appointments', 'service_id')) {
                $table->unsignedBigInteger('service_id')->nullable()->after('staff_id');
                $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            }
            
            // Add reminder columns if they don't exist
            if (!Schema::hasColumn('appointments', 'reminder_24h_sent_at')) {
                $table->timestamp('reminder_24h_sent_at')->nullable();
            }
        });

        // Update existing appointments to have company_id based on calls table if available
        if (Schema::hasColumn('appointments', 'company_id') && Schema::hasColumn('appointments', 'call_id')) {
            if (DB::getDriverName() === 'sqlite') {
                // SQLite compatible version
                DB::statement('
                    UPDATE appointments 
                    SET company_id = (SELECT company_id FROM calls WHERE calls.id = appointments.call_id)
                    WHERE company_id IS NULL AND call_id IS NOT NULL
                ');
            } else {
                // MySQL version
                DB::statement('
                    UPDATE appointments a
                    JOIN calls c ON a.call_id = c.id
                    SET a.company_id = c.company_id
                    WHERE a.company_id IS NULL AND a.call_id IS NOT NULL
                ');
            }
        }

        // Update branch_id based on staff's branch
        if (Schema::hasColumn('appointments', 'branch_id') && Schema::hasColumn('appointments', 'staff_id')) {
            if (DB::getDriverName() === 'sqlite') {
                // SQLite compatible version
                DB::statement('
                    UPDATE appointments
                    SET branch_id = (SELECT branch_id FROM staff WHERE staff.id = appointments.staff_id)
                    WHERE branch_id IS NULL AND staff_id IS NOT NULL
                ');
            } else {
                // MySQL version
                DB::statement('
                    UPDATE appointments a
                    JOIN staff s ON a.staff_id = s.id
                    SET a.branch_id = s.branch_id
                    WHERE a.branch_id IS NULL AND a.staff_id IS NOT NULL
                ');
            }
        }

        // Make the company_id column non-nullable after populating data
        if (Schema::hasColumn('appointments', 'company_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'reminder_24h_sent_at')) {
                $table->dropColumn('reminder_24h_sent_at');
            }
            
            if (Schema::hasColumn('appointments', 'service_id')) {
                $table->dropForeign(['service_id']);
                $table->dropColumn('service_id');
            }
            
            if (Schema::hasColumn('appointments', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
            
            if (Schema::hasColumn('appointments', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });
    }
};
