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
        // 1. Add index on calls.phone_number if it doesn't exist
        if (!$this->indexExists('calls', 'idx_calls_phone_number')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index('phone_number', 'idx_calls_phone_number');
            });
        }

        // 2. Add index on webhook_events.event_id (already unique in table creation, but adding regular index for queries)
        // event_id already has a unique index, which serves as an index too
        
        // 3. Add index on companies.phone_number if column exists
        if (Schema::hasColumn('companies', 'phone_number') && !$this->indexExists('companies', 'idx_companies_phone_number')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->index('phone_number', 'idx_companies_phone_number');
            });
        }

        // 4. Add compound indexes for frequently used query patterns
        
        // calls: company_id + start_timestamp (if not already exists)
        if (!$this->indexExists('calls', 'idx_calls_company_start_timestamp')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['company_id', 'start_timestamp'], 'idx_calls_company_start_timestamp');
            });
        }

        // appointments: company_id + status (if not already exists)
        if (!$this->indexExists('appointments', 'idx_appointments_company_status')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['company_id', 'status'], 'idx_appointments_company_status');
            });
        }

        // 5. Additional critical missing indexes identified
        
        // calls: index for phone number reverse lookup (to find customer)
        if (!$this->indexExists('calls', 'idx_calls_to_number')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index('to_number', 'idx_calls_to_number');
            });
        }

        // webhook_events: compound index for deduplication queries
        if (!$this->indexExists('webhook_events', 'idx_webhook_dedup')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->index(['provider', 'event_id', 'status'], 'idx_webhook_dedup');
            });
        }

        // companies: compound index for active companies with phone lookup
        if (Schema::hasColumn('companies', 'is_active') && 
            Schema::hasColumn('companies', 'phone_number') && 
            !$this->indexExists('companies', 'idx_companies_active_phone')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->index(['is_active', 'phone_number'], 'idx_companies_active_phone');
            });
        }

        // appointments: index for calendar view queries
        if (!$this->indexExists('appointments', 'idx_appointments_calendar')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['branch_id', 'starts_at', 'ends_at', 'status'], 'idx_appointments_calendar');
            });
        }

        // calls: index for recent calls by status
        if (!$this->indexExists('calls', 'idx_calls_status_recent')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['call_status', 'created_at'], 'idx_calls_status_recent');
            });
        }

        // customers: compound index for duplicate detection
        if (!$this->indexExists('customers', 'idx_customers_duplicate_check')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index(['company_id', 'phone', 'email'], 'idx_customers_duplicate_check');
            });
        }

        // staff: index for available staff queries
        if (Schema::hasColumn('staff', 'active') && !$this->indexExists('staff', 'idx_staff_availability')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->index(['branch_id', 'active', 'created_at'], 'idx_staff_availability');
            });
        }

        // webhook_events: index for retry processing
        if (!$this->indexExists('webhook_events', 'idx_webhook_retry')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->index(['status', 'retry_count', 'created_at'], 'idx_webhook_retry');
            });
        }

        // Log successful migration
        \Log::info('Missing performance indexes added successfully', [
            'migration' => '2025_06_28_add_missing_performance_indexes',
            'indexes_added' => [
                'calls.phone_number',
                'companies.phone_number',
                'calls.company_id+start_timestamp',
                'appointments.company_id+status',
                'additional_compound_indexes'
            ]
        ]);

        // Analyze tables to update statistics (MySQL only)
        if (DB::connection()->getDriverName() === 'mysql') {
            $tables = ['calls', 'appointments', 'webhook_events', 'companies', 'customers', 'staff'];
            $existingTables = array_filter($tables, fn($table) => Schema::hasTable($table));
            
            if (!empty($existingTables)) {
                try {
                    DB::statement('ANALYZE TABLE ' . implode(', ', $existingTables));
                } catch (\Exception $e) {
                    // Ignore analyze errors
                    \Log::warning('Could not analyze tables: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes in reverse order
        $indexesToRemove = [
            ['calls', 'idx_calls_phone_number'],
            ['companies', 'idx_companies_phone_number'],
            ['calls', 'idx_calls_company_start_timestamp'],
            ['appointments', 'idx_appointments_company_status'],
            ['calls', 'idx_calls_to_number'],
            ['webhook_events', 'idx_webhook_dedup'],
            ['companies', 'idx_companies_active_phone'],
            ['appointments', 'idx_appointments_calendar'],
            ['calls', 'idx_calls_status_recent'],
            ['customers', 'idx_customers_duplicate_check'],
            ['staff', 'idx_staff_availability'],
            ['webhook_events', 'idx_webhook_retry'],
        ];

        foreach ($indexesToRemove as [$table, $index]) {
            if (Schema::hasTable($table) && $this->indexExists($table, $index)) {
                Schema::table($table, function (Blueprint $table) use ($index) {
                    $table->dropIndex($index);
                });
            }
        }
    }

    /**
     * Check if an index exists on a table
     *
     * @param string $table
     * @param string $indexName
     * @return bool
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        // Use parent method from CompatibleMigration that handles DB differences
        return parent::indexExists($table, $indexName);
    }
};