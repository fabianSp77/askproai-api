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
        // Critical indexes for multi-tenancy performance
        Schema::table('appointments', function (Blueprint $table) {
            // Most queries filter by company_id and created_at
            if (!$this->indexExists('appointments', 'idx_appointments_company_created')) {
                $table->index(['company_id', 'created_at'], 'idx_appointments_company_created');
            }
            
            // Dashboard queries filter by status
            if (!$this->indexExists('appointments', 'idx_appointments_company_status')) {
                $table->index(['company_id', 'status'], 'idx_appointments_company_status');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            // All branch queries filter by company and active status
            if (!$this->indexExists('branches', 'idx_branches_company_active')) {
                $table->index(['company_id', 'is_active'], 'idx_branches_company_active');
            }
        });

        Schema::table('calls', function (Blueprint $table) {
            // Dashboard widgets query calls by date
            if (!$this->indexExists('calls', 'idx_calls_created_company')) {
                $table->index(['created_at', 'company_id'], 'idx_calls_created_company');
            }
            
            // Call lookups by retell_call_id
            if (!$this->indexExists('calls', 'idx_calls_retell_call_id')) {
                $table->index('retell_call_id', 'idx_calls_retell_call_id');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            // Staff queries by company and branch
            if (!$this->indexExists('staff', 'idx_staff_company_branch')) {
                $table->index(['company_id', 'branch_id'], 'idx_staff_company_branch');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            // Phone number lookups for incoming calls
            if (!$this->indexExists('customers', 'idx_customers_phone_company')) {
                $table->index(['phone', 'company_id'], 'idx_customers_phone_company');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            // Service lookups by company
            if (!$this->indexExists('services', 'idx_services_company_active')) {
                $table->index(['company_id', 'active'], 'idx_services_company_active');
            }
        });

        // Add index for webhook deduplication
        if (Schema::hasTable('webhook_events')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                if (Schema::hasColumn('webhook_events', 'external_id') && 
                    !$this->indexExists('webhook_events', 'idx_webhook_events_external_id')) {
                    $table->index('external_id', 'idx_webhook_events_external_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'created_at']);
            $table->dropIndex(['company_id', 'status']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'is_active']);
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['created_at', 'company_id']);
            $table->dropIndex(['retell_call_id']);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'branch_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['phone', 'company_id']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'active']);
        });

        if (Schema::hasTable('webhook_events')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                if (Schema::hasColumn('webhook_events', 'external_id')) {
                    $table->dropIndex(['external_id']);
                }
            });
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        if (config('database.default') === 'sqlite') {
            // SQLite doesn't support SHOW INDEX, so we'll assume index doesn't exist
            return false;
        }
        
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
