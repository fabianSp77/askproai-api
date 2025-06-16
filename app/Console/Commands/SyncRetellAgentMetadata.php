<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Services\RetellService;
use Illuminate\Support\Facades\Log;

class SyncRetellAgentMetadata extends Command
{
    protected $signature = 'retell:sync-metadata {--branch=} {--dry-run}';
    protected $description = 'Sync branch/company metadata to Retell agents';

    protected RetellService $retell;

    public function __construct(RetellService $retell)
    {
        parent::__construct();
        $this->retell = $retell;
    }

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $branchId = $this->option('branch');

        $query = Branch::with('company');
        
        if ($branchId) {
            $query->where('id', $branchId);
        }
        
        $branches = $query->whereNotNull('retell_agent_id')->get();

        if ($branches->isEmpty()) {
            $this->warn('No branches with Retell agents found.');
            return 0;
        }

        $this->info("Found {$branches->count()} branches with Retell agents");

        foreach ($branches as $branch) {
            $this->info("\nProcessing: {$branch->name} (ID: {$branch->id})");
            $this->info("Retell Agent ID: {$branch->retell_agent_id}");
            
            if ($dryRun) {
                $this->info("Would sync metadata:");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['askproai_branch_id', $branch->id],
                        ['askproai_company_id', $branch->company_id],
                        ['branch_name', $branch->name],
                        ['company_name', $branch->company->name ?? 'N/A'],
                        ['phone_number', $branch->phone_number ?? 'N/A'],
                    ]
                );
                continue;
            }

            try {
                // Prepare metadata
                $metadata = [
                    'askproai_branch_id' => (string)$branch->id,
                    'askproai_company_id' => (string)$branch->company_id,
                    'askproai_branch_name' => $branch->name,
                    'askproai_company_name' => $branch->company->name ?? '',
                    'askproai_phone_number' => $branch->phone_number ?? ''
                ];
                
                // Update agent in Retell
                $result = $this->retell->updateAgent($branch->retell_agent_id, [
                    'metadata' => $metadata
                ]);

                if ($result) {
                    $this->info("✓ Successfully synced metadata");
                    if (is_array($result)) {
                        $this->info("  Response: " . json_encode($result));
                    }
                } else {
                    $this->error("✗ Failed to sync metadata");
                    
                    // Check logs for more details
                    $logPath = storage_path('logs/laravel.log');
                    $lastError = `tail -n 50 $logPath | grep -A5 "Failed to update Retell" | tail -10`;
                    if ($lastError) {
                        $this->error("  Last error from logs:");
                        $this->line($lastError);
                    }
                }
            } catch (\Exception $e) {
                $this->error("✗ Exception: " . $e->getMessage());
                Log::error('Retell metadata sync failed', [
                    'branch_id' => $branch->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("\nSync completed!");
        return 0;
    }
}