<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePerformanceIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:create-indexes {--check : Only check what would be created}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create performance indexes safely by checking column existence';

    /**
     * Index definitions with fallback column names
     */
    protected array $indexes = [
        'phone_numbers' => [
            'idx_phone_branch_lookup' => [
                'columns' => ['number', 'branch_id', ['active', 'is_active']],
                'description' => 'Phone to branch lookup'
            ]
        ],
        'branches' => [
            'idx_company_active_branches' => [
                'columns' => ['company_id', 'is_active', 'id'],
                'description' => 'Company active branches lookup'
            ],
            'idx_calcom_event_lookup' => [
                'columns' => ['calcom_event_type_id', 'is_active'],
                'description' => 'Cal.com event type lookup'
            ]
        ],
        'appointments' => [
            'idx_branch_appointments_time' => [
                'columns' => ['branch_id', ['starts_at', 'start_time'], 'status'],
                'description' => 'Branch appointments by time'
            ],
            'idx_customer_appointments' => [
                'columns' => ['customer_id', ['starts_at', 'start_time'], 'status'],
                'description' => 'Customer appointments lookup'
            ],
            'idx_staff_schedule' => [
                'columns' => ['staff_id', ['starts_at', 'start_time'], ['ends_at', 'end_time']],
                'description' => 'Staff schedule lookup'
            ]
        ],
        'calls' => [
            'idx_company_recent_calls' => [
                'columns' => ['company_id', 'created_at', ['call_status', 'status']],
                'description' => 'Company recent calls'
            ],
            'idx_phone_call_history' => [
                'columns' => ['from_number', 'created_at'],
                'description' => 'Phone call history'
            ],
            'idx_retell_call_status' => [
                'columns' => ['retell_call_id', ['call_status', 'status']],
                'description' => 'Retell call status lookup'
            ]
        ],
        'staff' => [
            'idx_branch_active_staff' => [
                'columns' => ['branch_id', ['active', 'is_active'], 'id'],
                'description' => 'Branch active staff lookup'
            ],
            'idx_company_staff' => [
                'columns' => ['company_id', ['active', 'is_active']],
                'description' => 'Company staff lookup'
            ]
        ],
        'customers' => [
            'idx_customer_phone_lookup' => [
                'columns' => ['phone', 'company_id'],
                'description' => 'Customer phone lookup'
            ],
            'idx_customer_email_lookup' => [
                'columns' => ['email', 'company_id'],
                'description' => 'Customer email lookup'
            ],
            'idx_company_recent_customers' => [
                'columns' => ['company_id', 'created_at'],
                'description' => 'Company recent customers'
            ]
        ],
        'services' => [
            'idx_company_active_services' => [
                'columns' => ['company_id', ['is_active', 'active'], 'id'],
                'description' => 'Company active services',
                'fallback' => ['company_id', 'id'] // If no active column exists
            ]
        ]
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $checkOnly = $this->option('check');
        
        $this->info($checkOnly ? '🔍 Checking indexes...' : '🚀 Creating performance indexes...');
        
        $created = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($this->indexes as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                $this->warn("Table {$table} does not exist, skipping...");
                continue;
            }
            
            foreach ($indexes as $indexName => $config) {
                try {
                    $result = $this->processIndex($table, $indexName, $config, $checkOnly);
                    
                    if ($result === 'created') {
                        $created++;
                    } elseif ($result === 'skipped') {
                        $skipped++;
                    } else {
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing {$table}.{$indexName}: " . $e->getMessage());
                    $errors++;
                }
            }
        }
        
        $this->info('\n📦 Summary:');
        $this->info("✅ Created: {$created}");
        $this->info("⏭️ Skipped: {$skipped}");
        if ($errors > 0) {
            $this->error("❌ Errors: {$errors}");
        }
        
        if ($checkOnly) {
            $this->warn('\nThis was a check run. Use without --check to actually create indexes.');
        }
        
        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
    
    /**
     * Process a single index
     */
    protected function processIndex(string $table, string $indexName, array $config, bool $checkOnly): string
    {
        // Check if index already exists
        if ($this->indexExists($table, $indexName)) {
            $this->line("⏭️ {$table}.{$indexName} already exists");
            return 'skipped';
        }
        
        // Resolve actual column names
        $columns = $this->resolveColumns($table, $config['columns']);
        
        if (empty($columns)) {
            // Try fallback columns if available
            if (isset($config['fallback'])) {
                $columns = $this->resolveColumns($table, $config['fallback']);
            }
            
            if (empty($columns)) {
                $this->warn("⚠️ Cannot create {$table}.{$indexName} - required columns not found");
                return 'error';
            }
        }
        
        $columnsStr = implode(', ', $columns);
        $description = $config['description'] ?? '';
        
        if ($checkOnly) {
            $this->info("🔍 Would create {$table}.{$indexName} on ({$columnsStr}) - {$description}");
            return 'skipped';
        }
        
        // Create the index
        try {
            $sql = "CREATE INDEX {$indexName} ON {$table} (" . implode(', ', array_map(fn($col) => "`{$col}`", $columns)) . ")";
            DB::statement($sql);
            $this->info("✅ Created {$table}.{$indexName} on ({$columnsStr}) - {$description}");
            return 'created';
        } catch (\Exception $e) {
            $this->error("❌ Failed to create {$table}.{$indexName}: " . $e->getMessage());
            return 'error';
        }
    }
    
    /**
     * Resolve column names, handling alternatives
     */
    protected function resolveColumns(string $table, array $columnDefs): array
    {
        $resolved = [];
        
        foreach ($columnDefs as $column) {
            if (is_array($column)) {
                // Try each alternative
                foreach ($column as $alternative) {
                    if (Schema::hasColumn($table, $alternative)) {
                        $resolved[] = $alternative;
                        break;
                    }
                }
            } else {
                // Single column name
                if (Schema::hasColumn($table, $column)) {
                    $resolved[] = $column;
                }
            }
        }
        
        // Return resolved columns only if all required columns exist
        return count($resolved) === count($columnDefs) ? $resolved : [];
    }
    
    /**
     * Check if an index exists
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        try {
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");
            
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.statistics 
                WHERE table_schema = ? 
                AND table_name = ? 
                AND index_name = ?
            ", [$database, $table, $indexName]);
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}