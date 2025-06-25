<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Services\MCP\RetellMCPServer;

class RetellSyncAgentConfigurations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retell:sync-configurations 
                            {--company= : The company ID to sync agents for}
                            {--all : Sync agents for all companies}
                            {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync complete Retell agent configurations including functions to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mcpServer = new RetellMCPServer();
        
        if ($this->option('all')) {
            // Sync all companies
            $companies = Company::whereNotNull('retell_api_key')->get();
            
            if ($companies->isEmpty()) {
                $this->error('No companies found with Retell API key configured.');
                return 1;
            }
            
            $this->info("Found {$companies->count()} companies to sync.");
            
            foreach ($companies as $company) {
                $this->syncCompanyAgents($mcpServer, $company);
            }
            
        } elseif ($companyId = $this->option('company')) {
            // Sync specific company
            $company = Company::find($companyId);
            
            if (!$company) {
                $this->error("Company with ID {$companyId} not found.");
                return 1;
            }
            
            if (!$company->retell_api_key) {
                $this->error("Company '{$company->name}' does not have Retell API key configured.");
                return 1;
            }
            
            $this->syncCompanyAgents($mcpServer, $company);
            
        } else {
            $this->error('Please specify --company=ID or --all option.');
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Sync agents for a specific company
     */
    private function syncCompanyAgents(RetellMCPServer $mcpServer, Company $company): void
    {
        $this->info("\nSyncing agents for company: {$company->name} (ID: {$company->id})");
        
        $result = $mcpServer->syncAllAgentData([
            'company_id' => $company->id,
            'force' => $this->option('force')
        ]);
        
        if (isset($result['error'])) {
            $this->error("Error: {$result['error']}");
            return;
        }
        
        if (!isset($result['success']) || !$result['success']) {
            $this->error("Sync failed for company {$company->name}");
            return;
        }
        
        // Display summary
        $summary = $result['summary'] ?? [];
        $this->info("Summary:");
        $this->info("  Total agents: " . ($summary['total'] ?? 0));
        $this->info("  Synced: " . ($summary['synced'] ?? 0));
        $this->info("  Errors: " . ($summary['errors'] ?? 0));
        $this->info("  Skipped: " . ($summary['skipped'] ?? 0));
        
        // Display details if verbose
        if ($this->getOutput()->isVerbose() && isset($result['details'])) {
            $this->newLine();
            $this->info("Details:");
            
            foreach ($result['details'] as $detail) {
                $status = $detail['status'] ?? 'unknown';
                $agentName = $detail['agent_name'] ?? 'Unknown';
                $agentId = $detail['agent_id'] ?? 'N/A';
                
                switch ($status) {
                    case 'success':
                        $funcCount = $detail['function_count'] ?? 0;
                        $this->info("  ✓ {$agentName} (ID: {$agentId}) - {$funcCount} functions");
                        break;
                        
                    case 'error':
                        $error = $detail['error'] ?? 'Unknown error';
                        $this->error("  ✗ {$agentName} (ID: {$agentId}) - Error: {$error}");
                        break;
                        
                    case 'skipped':
                        $reason = $detail['reason'] ?? 'Unknown reason';
                        $this->comment("  - {$agentName} (ID: {$agentId}) - Skipped: {$reason}");
                        break;
                }
            }
        }
        
        $this->newLine();
    }
}