<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\CalcomMCPServer;
use App\Models\Company;

class MCPCalcomSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:calcom:sync 
                            {--company= : Company ID to sync}
                            {--all : Sync all companies}
                            {--only-events : Only sync event types}
                            {--only-users : Only sync users/staff}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Cal.com data (event types, users, schedules) via MCP Server';

    /**
     * Execute the console command.
     */
    public function handle(CalcomMCPServer $calcomMCP): int
    {
        $this->info('🔄 Starting Cal.com MCP Sync...');
        
        // Determine which companies to sync
        $companies = [];
        
        if ($this->option('all')) {
            $companies = Company::where('is_active', true)
                ->whereNotNull('calcom_api_key')
                ->get();
        } elseif ($companyId = $this->option('company')) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Company with ID {$companyId} not found.");
                return self::FAILURE;
            }
            $companies = collect([$company]);
        } else {
            $this->error('Please specify --company=ID or --all');
            return self::FAILURE;
        }
        
        if ($companies->isEmpty()) {
            $this->warn('No companies to sync.');
            return self::SUCCESS;
        }
        
        $totalSynced = 0;
        $totalErrors = 0;
        
        foreach ($companies as $company) {
            $this->info("\n📁 Syncing company: {$company->name} (ID: {$company->id})");
            
            // Event Types Sync
            if (!$this->option('only-users')) {
                $this->info('  📋 Syncing Event Types...');
                
                $result = $calcomMCP->syncEventTypesWithDetails([
                    'company_id' => $company->id
                ]);
                
                if ($result['success'] ?? false) {
                    $this->info("  ✅ Event Types: {$result['message']}");
                    $totalSynced += $result['synced'] ?? 0;
                    
                    // Show errors if any
                    if (!empty($result['errors'])) {
                        foreach ($result['errors'] as $error) {
                            $this->warn("  ⚠️  Error for Event Type {$error['event_type_id']}: {$error['error']}");
                        }
                    }
                } else {
                    $this->error("  ❌ Event Types: " . ($result['error'] ?? 'Unknown error'));
                    $totalErrors++;
                }
            }
            
            // Users/Staff Sync
            if (!$this->option('only-events')) {
                $this->info('  👥 Syncing Users/Staff...');
                
                $result = $calcomMCP->syncUsersWithSchedules([
                    'company_id' => $company->id
                ]);
                
                if ($result['success'] ?? false) {
                    $this->info("  ✅ Users: {$result['message']}");
                    $totalSynced += $result['synced'] ?? 0;
                    
                    // Show errors if any
                    if (!empty($result['errors'])) {
                        foreach ($result['errors'] as $error) {
                            $this->warn("  ⚠️  Error for User {$error['email']}: {$error['error']}");
                        }
                    }
                } else {
                    $this->error("  ❌ Users: " . ($result['error'] ?? 'Unknown error'));
                    $totalErrors++;
                }
            }
        }
        
        $this->info("\n✨ Sync Complete!");
        $this->info("   Total items synced: {$totalSynced}");
        if ($totalErrors > 0) {
            $this->warn("   Total errors: {$totalErrors}");
        }
        
        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
    }
}