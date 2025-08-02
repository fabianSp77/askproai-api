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
        // Calls table indexes
        Schema::table('calls', function (Blueprint $table) {
            // Check if indexes don't exist before adding
            if (!$this->indexExists('calls', 'idx_company_created')) {
                $table->index(['company_id', 'created_at'], 'idx_company_created');
            }
            if (!$this->indexExists('calls', 'idx_phone_status')) {
                $table->index(['phone_number', 'status'], 'idx_phone_status');
            }
        });

        // Appointments table indexes
        Schema::table('appointments', function (Blueprint $table) {
            if (!$this->indexExists('appointments', 'idx_branch_date')) {
                $table->index(['branch_id', 'appointment_date'], 'idx_branch_date');
            }
            if (!$this->indexExists('appointments', 'idx_customer_status')) {
                $table->index(['customer_id', 'status'], 'idx_customer_status');
            }
        });

        // API logs table indexes (if table exists)
        if (Schema::hasTable('api_logs')) {
            Schema::table('api_logs', function (Blueprint $table) {
                if (!$this->indexExists('api_logs', 'idx_correlation_created')) {
                    $table->index(['correlation_id', 'created_at'], 'idx_correlation_created');
                }
            });
        }

        // Additional performance indexes
        Schema::table('calls', function (Blueprint $table) {
            if (!$this->indexExists('calls', 'idx_created_at')) {
                $table->index('created_at', 'idx_created_at');
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (!$this->indexExists('appointments', 'idx_appointment_date')) {
                $table->index('appointment_date', 'idx_appointment_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop calls indexes
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'created_at']);
            $table->dropIndex(['phone_number', 'status']);
            $table->dropIndex(['created_at']);
        });

        // Drop appointments indexes
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'appointment_date']);
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['appointment_date']);
        });

        // Drop api_logs indexes if table exists
        if (Schema::hasTable('api_logs')) {
            Schema::table('api_logs', function (Blueprint $table) {
                $table->dropIndex(['correlation_id', 'created_at']);
            });
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists($table, $name): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name]);
        return count($indexes) > 0;
    }
};