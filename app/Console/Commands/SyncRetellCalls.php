<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use App\Models\Company;
use App\Services\RetellService;
use Carbon\Carbon;

class SyncRetellCalls extends Command
{
    protected $signature = 'retell:sync-calls {--hours=1 : Number of hours to sync}';
    protected $description = 'Sync recent calls from Retell API';

    public function handle()
    {
        $hours = $this->option('hours');
        $this->info("Syncing Retell calls from the last {$hours} hours...");
        
        $companies = Company::whereNotNull('retell_api_key')->get();
        
        foreach ($companies as $company) {
            $this->info("Processing company: {$company->name}");
            
            // Set company context
            app()->bind('current_company_id', function () use ($company) {
                return $company->id;
            });
            
            try {
                $retellService = new RetellService();
                $calls = $retellService->listCalls([
                    'start_timestamp' => Carbon::now()->subHours($hours)->timestamp * 1000,
                    'limit' => 100
                ]);
                
                foreach ($calls as $callData) {
                    $this->processCall($company, $callData);
                }
                
                $this->info("Synced " . count($calls) . " calls for {$company->name}");
                
            } catch (\Exception $e) {
                $this->error("Error syncing calls for {$company->name}: " . $e->getMessage());
            }
        }
        
        $this->info('Sync completed!');
    }
    
    private function processCall($company, $callData)
    {
        // Check if call already exists
        $existingCall = Call::where('retell_call_id', $callData['call_id'])->first();
        
        if ($existingCall && $existingCall->transcript) {
            // Skip if already has transcript
            return;
        }
        
        if (!$existingCall) {
            // Create new call
            $existingCall = Call::create([
                'company_id' => $company->id,
                'call_id' => $callData['call_id'],
                'retell_call_id' => $callData['call_id'],
                'agent_id' => $callData['agent_id'] ?? null,
                'from_number' => $callData['from_number'] ?? null,
                'to_number' => $callData['to_number'] ?? null,
                'direction' => $callData['call_type'] ?? 'inbound',
                'start_timestamp' => isset($callData['start_timestamp']) 
                    ? Carbon::createFromTimestampMs($callData['start_timestamp']) 
                    : now(),
                'end_timestamp' => isset($callData['end_timestamp']) 
                    ? Carbon::createFromTimestampMs($callData['end_timestamp']) 
                    : now(),
            ]);
        }
        
        // Update with full details
        $existingCall->duration_sec = round(($callData['duration_ms'] ?? 0) / 1000);
        $existingCall->transcript = $callData['transcript'] ?? null;
        $existingCall->transcript_object = $callData['transcript_object'] ?? null;
        $existingCall->analysis = $callData['call_analysis'] ?? null;
        $existingCall->summary = $callData['call_analysis']['call_summary'] ?? null;
        $existingCall->sentiment = $callData['call_analysis']['sentiment'] ?? null;
        
        $existingCall->save();
    }
}