<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\CalcomMCPServer;
use App\Services\MCP\RetellMCPServer;
use App\Models\Company;
use App\Models\PhoneNumber;
use Illuminate\Support\Facades\Log;

class MCPComprehensiveSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:sync:all 
                            {--company= : Company ID to sync}
                            {--all : Sync all companies}
                            {--retell : Only sync Retell.ai data}
                            {--calcom : Only sync Cal.com data}
                            {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprehensive sync of all integrations using MCP servers';

    /**
     * Execute the console command.
     */
    public function handle(CalcomMCPServer $calcomMCP, RetellMCPServer $retellMCP): int
    {
        $this->info('🚀 Starting Comprehensive MCP Sync...');
        
        // Determine which companies to sync
        $companies = $this->getCompaniesToSync();
        
        if ($companies->isEmpty()) {
            $this->warn('No companies to sync.');
            return self::SUCCESS;
        }
        
        $syncRetell = !$this->option('calcom');
        $syncCalcom = !$this->option('retell');
        
        $totalSynced = [
            'event_types' => 0,
            'staff' => 0,
            'agents' => 0,
            'phone_numbers' => 0
        ];
        
        foreach ($companies as $company) {
            $this->newLine();
            $this->info("📁 Processing: {$company->name} (ID: {$company->id})");
            
            // Sync Retell.ai data
            if ($syncRetell && $company->retell_api_key) {
                $this->syncRetellData($retellMCP, $company, $totalSynced);
            }
            
            // Sync Cal.com data
            if ($syncCalcom && $company->calcom_api_key) {
                $this->syncCalcomData($calcomMCP, $company, $totalSynced);
            }
        }
        
        $this->newLine();
        $this->info('✨ Sync Complete!');
        $this->table(
            ['Type', 'Count'],
            [
                ['Event Types', $totalSynced['event_types']],
                ['Staff Members', $totalSynced['staff']],
                ['Agents', $totalSynced['agents']],
                ['Phone Numbers', $totalSynced['phone_numbers']]
            ]
        );
        
        return self::SUCCESS;
    }
    
    /**
     * Get companies to sync based on options
     */
    private function getCompaniesToSync()
    {
        if ($this->option('all')) {
            return Company::where('is_active', true)->get();
        }
        
        if ($companyId = $this->option('company')) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Company with ID {$companyId} not found.");
                return collect();
            }
            return collect([$company]);
        }
        
        $this->error('Please specify --company=ID or --all');
        return collect();
    }
    
    /**
     * Sync Retell.ai data for a company
     */
    private function syncRetellData(RetellMCPServer $retellMCP, Company $company, array &$totalSynced): void
    {
        $this->info('  🤖 Syncing Retell.ai Agents...');
        
        // Get all phone numbers for the company
        $phoneNumbers = PhoneNumber::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get();
        
        foreach ($phoneNumbers as $phoneNumber) {
            if (!$phoneNumber->retell_agent_id) {
                continue;
            }
            
            // Sync agent details
            $result = $retellMCP->syncAgentDetails([
                'agent_id' => $phoneNumber->retell_agent_id,
                'company_id' => $company->id
            ]);
            
            if ($result['success'] ?? false) {
                $this->line("    ✓ Agent {$phoneNumber->retell_agent_id} synced");
                $totalSynced['agents']++;
                
                // Update phone number with agent details
                if (isset($result['agent']['agent_name'])) {
                    $phoneNumber->metadata = array_merge(
                        $phoneNumber->metadata ?? [],
                        ['agent_name' => $result['agent']['agent_name']]
                    );
                    $phoneNumber->save();
                }
            } else {
                $this->warn("    ⚠️  Failed to sync agent {$phoneNumber->retell_agent_id}");
            }
            
            $totalSynced['phone_numbers']++;
        }
        
        $this->info("  ✅ Retell.ai: {$totalSynced['agents']} agents synced");
    }
    
    /**
     * Sync Cal.com data for a company
     */
    private function syncCalcomData(CalcomMCPServer $calcomMCP, Company $company, array &$totalSynced): void
    {
        $this->info('  📅 Syncing Cal.com Data...');
        
        // Sync event types
        $eventResult = $calcomMCP->syncEventTypesWithDetails([
            'company_id' => $company->id
        ]);
        
        if ($eventResult['success'] ?? false) {
            $totalSynced['event_types'] += $eventResult['synced'] ?? 0;
            $this->line("    ✓ Event Types: {$eventResult['message']}");
        } else {
            $this->error("    ❌ Event Types: " . ($eventResult['error'] ?? 'Unknown error'));
        }
        
        // Sync users/staff
        $userResult = $calcomMCP->syncUsersWithSchedules([
            'company_id' => $company->id
        ]);
        
        if ($userResult['success'] ?? false) {
            $totalSynced['staff'] += $userResult['synced'] ?? 0;
            $this->line("    ✓ Staff: {$userResult['message']}");
        } else {
            $this->error("    ❌ Staff: " . ($userResult['error'] ?? 'Unknown error'));
        }
    }
}