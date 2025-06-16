<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use App\Services\PhoneNumberResolver;
use Illuminate\Support\Facades\DB;

class UpdateCallBranchAssignments extends Command
{
    protected $signature = 'calls:update-branch-assignments {--dry-run} {--limit=}';
    protected $description = 'Update branch assignments for calls based on to_number';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');
        
        $this->info('Updating call branch assignments...');
        
        // Get calls without branch assignment but with to_number
        $query = Call::whereNull('branch_id')
            ->whereNotNull('to_number')
            ->where('to_number', '!=', 'unbekannt');
            
        if ($limit) {
            $query->limit($limit);
        }
        
        $calls = $query->get();
        
        if ($calls->isEmpty()) {
            $this->info('No calls found that need branch assignment.');
            return 0;
        }
        
        $this->info("Found {$calls->count()} calls to process.");
        
        $resolver = new PhoneNumberResolver();
        $updated = 0;
        $failed = 0;
        
        $this->withProgressBar($calls, function ($call) use ($resolver, $dryRun, &$updated, &$failed) {
            // Prepare webhook-like data
            $webhookData = [
                'to' => $call->to_number,
                'from' => $call->from_number ?? $call->phone_number,
                'call_id' => $call->call_id,
            ];
            
            // Try to decode raw_data for agent_id
            if ($call->raw_data) {
                $rawData = is_string($call->raw_data) ? json_decode($call->raw_data, true) : $call->raw_data;
                if (isset($rawData['agent_id'])) {
                    $webhookData['agent_id'] = $rawData['agent_id'];
                }
            }
            
            $result = $resolver->resolveFromWebhook($webhookData);
            
            if ($result['branch_id']) {
                if (!$dryRun) {
                    try {
                        Call::where('id', $call->id)->update([
                            'branch_id' => $result['branch_id'],
                            'company_id' => $result['company_id'] ?? $call->company_id,
                            'agent_id' => $result['agent_id'],
                        ]);
                    } catch (\Exception $e) {
                        $this->error("Failed to update call {$call->id}: " . $e->getMessage());
                        $failed++;
                        return; // Use return instead of continue in closure
                    }
                }
                $updated++;
            } else {
                $failed++;
            }
        });
        
        $this->newLine(2);
        $this->info("Process complete!");
        $this->info("Updated: {$updated} calls");
        $this->warn("Failed: {$failed} calls (no matching branch found)");
        
        if ($dryRun) {
            $this->warn("This was a dry run. No changes were made.");
        }
        
        // Show summary of branches
        if ($updated > 0 && !$dryRun) {
            $this->newLine();
            $this->info("Branch distribution:");
            
            $distribution = Call::select('branch_id', DB::raw('count(*) as count'))
                ->whereNotNull('branch_id')
                ->groupBy('branch_id')
                ->with('branch')
                ->get();
                
            foreach ($distribution as $item) {
                $branchName = $item->branch ? $item->branch->name : 'Unknown';
                $this->line("  {$branchName}: {$item->count} calls");
            }
        }
        
        return 0;
    }
}