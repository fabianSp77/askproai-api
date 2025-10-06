<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing indexes for activity_log
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                if (!$this->indexExists('activity_log', 'idx_activity_created_at')) {
                    $table->index('created_at', 'idx_activity_created_at');
                }
                if (!$this->indexExists('activity_log', 'idx_activity_updated_at')) {
                    $table->index('updated_at', 'idx_activity_updated_at');
                }
            });
        }

        // Add missing indexes for backup_logs
        if (Schema::hasTable('backup_logs')) {
            Schema::table('backup_logs', function (Blueprint $table) {
                if (!$this->indexExists('backup_logs', 'idx_backup_created_at')) {
                    $table->index('created_at', 'idx_backup_created_at');
                }
            });
        }

        // Add missing indexes for outbound_call_templates
        if (Schema::hasTable('outbound_call_templates')) {
            Schema::table('outbound_call_templates', function (Blueprint $table) {
                if (!$this->indexExists('outbound_call_templates', 'idx_outbound_created_at')) {
                    $table->index('created_at', 'idx_outbound_created_at');
                }
                if (!$this->indexExists('outbound_call_templates', 'idx_outbound_updated_at')) {
                    $table->index('updated_at', 'idx_outbound_updated_at');
                }
            });
        }

        // Add performance indexes for frequently queried columns
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!$this->indexExists('customers', 'idx_customers_email')) {
                    $table->index('email', 'idx_customers_email');
                }
                if (!$this->indexExists('customers', 'idx_customers_company_id')) {
                    $table->index('company_id', 'idx_customers_company_id');
                }
            });
        }

        // Add indexes for calls table
        if (Schema::hasTable('calls')) {
            Schema::table('calls', function (Blueprint $table) {
                if (!$this->indexExists('calls', 'idx_calls_customer_id')) {
                    $table->index('customer_id', 'idx_calls_customer_id');
                }
                if (!$this->indexExists('calls', 'idx_calls_created_at')) {
                    $table->index('created_at', 'idx_calls_created_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes
        $indexes = [
            'activity_log' => ['idx_activity_created_at', 'idx_activity_updated_at'],
            'backup_logs' => ['idx_backup_created_at'],
            'outbound_call_templates' => ['idx_outbound_created_at', 'idx_outbound_updated_at'],
            'customers' => ['idx_customers_email', 'idx_customers_company_id'],
            'calls' => ['idx_calls_customer_id', 'idx_calls_created_at']
        ];

        foreach ($indexes as $table => $indexList) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexList) {
                    foreach ($indexList as $index) {
                        if ($this->indexExists($table->getTable(), $index)) {
                            $table->dropIndex($index);
                        }
                    }
                });
            }
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $index): bool
    {
        $indexes = Schema::getIndexes($table);
        return collect($indexes)->pluck('name')->contains($index);
    }
};