<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add index for appointments dashboard queries
        if (!$this->indexExists('appointments', 'idx_company_status_date')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['company_id', 'status', 'starts_at'], 'idx_company_status_date');
            });
        }

        // Add index for customer phone lookups
        if (!$this->indexExists('customers', 'idx_company_phone')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index(['company_id', 'phone'], 'idx_company_phone');
            });
        }

        // Add index for calls dashboard queries
        if (!$this->indexExists('calls', 'idx_company_created')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['company_id', 'created_at'], 'idx_company_created');
            });
        }

        // Add index for call status queries
        if (!$this->indexExists('calls', 'idx_status_created')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_status_created');
            });
        }

        // Add index for branches active queries
        if (!$this->indexExists('branches', 'idx_company_active')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->index(['company_id', 'is_active'], 'idx_company_active');
            });
        }

        // Add index for staff active queries
        if (!$this->indexExists('staff', 'idx_branch_active')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->index(['branch_id', 'active'], 'idx_branch_active');
            });
        }

        // Add index for webhook deduplication
        if (!$this->indexExists('webhook_events', 'idx_provider_event_created')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->index(['provider', 'event_id', 'created_at'], 'idx_provider_event_created');
            });
        }

        // Add index for phone number lookups
        if (!$this->indexExists('phone_numbers', 'idx_number_type')) {
            Schema::table('phone_numbers', function (Blueprint $table) {
                $table->index(['number', 'type'], 'idx_number_type');
            });
        }

        // Add index for appointment time slot queries
        if (!$this->indexExists('appointments', 'idx_branch_starts_ends')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['branch_id', 'starts_at', 'ends_at'], 'idx_branch_starts_ends');
            });
        }

        // Add index for user company queries (for multi-tenancy)
        if (!$this->indexExists('users', 'idx_company_email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['company_id', 'email'], 'idx_company_email');
            });
        }

        // Migration completed successfully
        \Log::info('Critical performance indexes created successfully');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes
        $indexesToRemove = [
            'appointments' => ['idx_company_status_date', 'idx_branch_starts_ends'],
            'customers' => ['idx_company_phone'],
            'calls' => ['idx_company_created', 'idx_status_created'],
            'branches' => ['idx_company_active'],
            'staff' => ['idx_branch_active'],
            'webhook_events' => ['idx_provider_event_created'],
            'phone_numbers' => ['idx_number_type'],
            'users' => ['idx_company_email']
        ];

        foreach ($indexesToRemove as $table => $indexes) {
            Schema::table($table, function (Blueprint $table) use ($indexes) {
                foreach ($indexes as $index) {
                    if ($this->indexExists($table->getTable(), $index)) {
                        $table->dropIndex($index);
                    }
                }
            });
        }
    }

    /**
     * Check if an index exists
     */
    protected function indexExists($table, $index): bool
    {
        // Use parent method from CompatibleMigration that handles DB differences
        return parent::indexExists($table, $index);
    }
};