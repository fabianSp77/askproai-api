<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Services\MCP\RetellMCPServer;
use App\Services\Config\RetellConfigValidator;
use App\Services\RetellV2Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncRetellAgents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retell:sync-agents 
                            {--company= : Specific company ID to sync}
                            {--validate : Validate agent configurations}
                            {--fix : Auto-fix configuration issues}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Retell agents and phone numbers with branches';

    private RetellMCPServer $mcpServer;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->mcpServer = new RetellMCPServer();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $shouldValidate = $this->option('validate');
        $shouldFix = $this->option('fix');
        $companyId = $this->option('company');

        $this->info('Starting Retell agent synchronization...');
        
        // Get companies to process
        $query = Company::whereNotNull('retell_api_key');
        if ($companyId) {
            $query->where('id', $companyId);
        }
        $companies = $query->get();

        if ($companies->isEmpty()) {
            $this->warn('No companies found with Retell API key configured.');
            return 1;
        }

        $totalSynced = 0;
        $totalErrors = 0;

        foreach ($companies as $company) {
            $this->info("\nProcessing company: {$company->name} (ID: {$company->id})");
            
            try {
                // Get agents with phone numbers
                $result = $this->mcpServer->getAgentsWithPhoneNumbers([
                    'company_id' => $company->id
                ]);

                if (isset($result['error'])) {
                    $this->error("  Error: {$result['error']}");
                    $totalErrors++;
                    continue;
                }

                $agents = $result['agents'] ?? [];
                $this->info("  Found {$result['total_agents']} agents with {$result['total_phone_numbers']} phone numbers");

                // Process each agent
                foreach ($agents as $agent) {
                    $this->processAgent($company, $agent, $isDryRun, $shouldValidate, $shouldFix);
                    $totalSynced++;
                }

                // Sync phone numbers
                if (!$isDryRun) {
                    $this->info("  Syncing phone numbers...");
                    $syncResult = $this->mcpServer->syncPhoneNumbers([
                        'company_id' => $company->id
                    ]);

                    if (isset($syncResult['error'])) {
                        $this->error("  Phone sync error: {$syncResult['error']}");
                    } else {
                        $this->info("  Synced {$syncResult['synced_count']} phone numbers");
                    }
                } else {
                    $this->comment("  [DRY RUN] Would sync phone numbers");
                }

            } catch (\Exception $e) {
                $this->error("  Exception: {$e->getMessage()}");
                $totalErrors++;
                Log::error('Retell sync command error', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("Synchronization complete!");
        $this->info("Total agents processed: {$totalSynced}");
        if ($totalErrors > 0) {
            $this->warn("Total errors: {$totalErrors}");
        }

        return $totalErrors > 0 ? 1 : 0;
    }

    /**
     * Process individual agent
     */
    private function processAgent($company, $agent, $isDryRun, $shouldValidate, $shouldFix)
    {
        $agentId = $agent['agent_id'];
        $agentName = $agent['agent_name'] ?? 'Unknown';
        
        $this->info("  Processing agent: {$agentName} (ID: {$agentId})");

        // Validate configuration if requested
        if ($shouldValidate) {
            $this->validateAgent($company, $agentId, $shouldFix, $isDryRun);
        }

        // Check branch mapping
        if ($agent['branch'] ?? false) {
            $this->comment("    ✓ Mapped to branch: {$agent['branch']['name']}");
        } else {
            $this->warn("    ⚠ No branch mapping");
            
            // Try to find matching branch
            $branches = $company->branches;
            $matchFound = false;
            
            foreach ($branches as $branch) {
                if ($this->agentMatchesBranch($agent, $branch)) {
                    if (!$isDryRun) {
                        $branch->update(['retell_agent_id' => $agentId]);
                        $this->info("    → Auto-mapped to branch: {$branch->name}");
                    } else {
                        $this->comment("    [DRY RUN] Would map to branch: {$branch->name}");
                    }
                    $matchFound = true;
                    break;
                }
            }
            
            if (!$matchFound) {
                $this->warn("    → No matching branch found");
            }
        }

        // Display phone numbers
        $phoneCount = count($agent['phone_numbers'] ?? []);
        if ($phoneCount > 0) {
            $this->comment("    Phone numbers: {$phoneCount}");
            foreach ($agent['phone_numbers'] as $phone) {
                $this->comment("      - {$phone['phone_number']}");
            }
        }
    }

    /**
     * Validate agent configuration
     */
    private function validateAgent($company, $agentId, $shouldFix, $isDryRun)
    {
        $this->comment("    Validating configuration...");
        
        $validationResult = $this->mcpServer->validateAndFixAgentConfig([
            'agent_id' => $agentId,
            'company_id' => $company->id,
            'auto_fix' => false // First just validate
        ]);

        if (isset($validationResult['error'])) {
            $this->error("    Validation error: {$validationResult['error']}");
            return;
        }

        $isValid = $validationResult['valid'] ?? false;
        $issueCount = $validationResult['critical_count'] ?? 0;
        $autoFixable = $validationResult['auto_fixable_count'] ?? 0;

        if ($isValid) {
            $this->info("    ✓ Configuration is valid");
        } else {
            $this->warn("    ✗ {$issueCount} issues found ({$autoFixable} auto-fixable)");
            
            // Show issues
            foreach ($validationResult['issues'] ?? [] as $issue) {
                $this->comment("      - {$issue['message']}");
            }

            // Auto-fix if requested
            if ($shouldFix && $autoFixable > 0) {
                if (!$isDryRun) {
                    $this->comment("    Applying auto-fixes...");
                    
                    $fixResult = $this->mcpServer->validateAndFixAgentConfig([
                        'agent_id' => $agentId,
                        'company_id' => $company->id,
                        'auto_fix' => true
                    ]);

                    if ($fixResult['fix_result']['success'] ?? false) {
                        $this->info("    ✓ Fixed {$autoFixable} issues");
                    } else {
                        $this->error("    ✗ Fix failed: " . ($fixResult['fix_result']['error'] ?? 'Unknown error'));
                    }
                } else {
                    $this->comment("    [DRY RUN] Would fix {$autoFixable} issues");
                }
            }
        }

        // Show warnings
        $warningCount = count($validationResult['warnings'] ?? []);
        if ($warningCount > 0) {
            $this->comment("    {$warningCount} warnings:");
            foreach ($validationResult['warnings'] ?? [] as $warning) {
                $this->comment("      ⚠ {$warning['message']}");
            }
        }
    }

    /**
     * Check if agent matches branch
     */
    private function agentMatchesBranch($agent, $branch): bool
    {
        $agentName = strtolower($agent['agent_name'] ?? '');
        $branchName = strtolower($branch->name);
        
        // Exact match
        if ($agentName === $branchName) {
            return true;
        }
        
        // Partial match
        if (str_contains($agentName, $branchName) || str_contains($branchName, $agentName)) {
            return true;
        }
        
        // Check metadata
        $metadata = $agent['metadata'] ?? [];
        if (isset($metadata['branch_id']) && $metadata['branch_id'] == $branch->id) {
            return true;
        }
        
        return false;
    }
}