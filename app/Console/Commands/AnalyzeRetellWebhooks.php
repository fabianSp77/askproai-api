<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use App\Models\RetellWebhook;
use Illuminate\Support\Facades\Schema;

class AnalyzeRetellWebhooks extends Command
{
    protected $signature = 'retell:analyze-webhooks {--limit=10}';
    protected $description = 'Analyze actual Retell webhook data to see what fields we receive';

    public function handle()
    {
        $limit = $this->option('limit');
        
        $this->info("Analyzing last {$limit} Retell webhooks...\n");
        
        // Method 1: Check calls table raw_data
        $calls = Call::whereNotNull('raw_data')
            ->latest()
            ->limit($limit)
            ->get();
            
        if ($calls->isNotEmpty()) {
            $this->info("=== CALLS TABLE ANALYSIS ===");
            $this->analyzeCallsData($calls);
        }
        
        // Method 2: Check retell_webhooks table if exists
        if (Schema::hasTable('retell_webhooks')) {
            $webhooks = RetellWebhook::latest()->limit($limit)->get();
            
            if ($webhooks->isNotEmpty()) {
                $this->info("\n=== RETELL_WEBHOOKS TABLE ANALYSIS ===");
                $this->analyzeWebhooksData($webhooks);
            }
        }
        
        // Summary of critical fields
        $this->info("\n=== CRITICAL FIELDS SUMMARY ===");
        $this->analyzeCriticalFields($calls);
        
        return 0;
    }
    
    private function analyzeCallsData($calls)
    {
        $allFields = [];
        $fieldFrequency = [];
        
        foreach ($calls as $call) {
            $data = is_string($call->raw_data) ? json_decode($call->raw_data, true) : $call->raw_data;
            
            if (!$data) continue;
            
            $this->info("\nCall ID: {$call->id} (Created: {$call->created_at})");
            $this->info("Stored branch_id: " . ($call->branch_id ?? 'NULL'));
            $this->info("Stored company_id: " . ($call->company_id ?? 'NULL'));
            
            // Track all fields
            foreach ($data as $key => $value) {
                $allFields[$key] = true;
                $fieldFrequency[$key] = ($fieldFrequency[$key] ?? 0) + 1;
                
                if (in_array($key, ['agent_id', 'phone_number', 'to_number', 'from_number', 'metadata', 'destination'])) {
                    $displayValue = is_array($value) ? json_encode($value) : $value;
                    $this->line("  {$key}: {$displayValue}");
                }
            }
        }
        
        $this->info("\n=== ALL WEBHOOK FIELDS (frequency) ===");
        arsort($fieldFrequency);
        foreach ($fieldFrequency as $field => $count) {
            $percentage = round(($count / $calls->count()) * 100);
            $this->line("  {$field}: {$count}/{$calls->count()} ({$percentage}%)");
        }
    }
    
    private function analyzeWebhooksData($webhooks)
    {
        foreach ($webhooks as $webhook) {
            $data = $webhook->payload;
            
            $this->info("\nWebhook ID: {$webhook->id} (Type: {$webhook->event_type})");
            
            // Show specific fields
            $criticalFields = ['agent_id', 'call_id', 'phone_number', 'to_number', 'from_number', 'metadata'];
            foreach ($criticalFields as $field) {
                if (isset($data[$field])) {
                    $value = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
                    $this->line("  {$field}: {$value}");
                }
            }
        }
    }
    
    private function analyzeCriticalFields($calls)
    {
        $hasToNumber = 0;
        $hasFromNumber = 0;
        $hasAgentId = 0;
        $hasMetadata = 0;
        $hasPhoneNumber = 0;
        
        foreach ($calls as $call) {
            $data = is_string($call->raw_data) ? json_decode($call->raw_data, true) : $call->raw_data;
            if (!$data) continue;
            
            if (!empty($data['to_number'])) $hasToNumber++;
            if (!empty($data['from_number'])) $hasFromNumber++;
            if (!empty($data['agent_id'])) $hasAgentId++;
            if (!empty($data['metadata'])) $hasMetadata++;
            if (!empty($data['phone_number'])) $hasPhoneNumber++;
        }
        
        $total = $calls->count();
        
        $this->table(
            ['Field', 'Found In', 'Percentage'],
            [
                ['to_number', "{$hasToNumber}/{$total}", round(($hasToNumber/$total)*100) . '%'],
                ['from_number', "{$hasFromNumber}/{$total}", round(($hasFromNumber/$total)*100) . '%'],
                ['phone_number', "{$hasPhoneNumber}/{$total}", round(($hasPhoneNumber/$total)*100) . '%'],
                ['agent_id', "{$hasAgentId}/{$total}", round(($hasAgentId/$total)*100) . '%'],
                ['metadata', "{$hasMetadata}/{$total}", round(($hasMetadata/$total)*100) . '%'],
            ]
        );
        
        // Recommendations
        $this->info("\n=== RECOMMENDATIONS ===");
        
        if ($hasToNumber == 0) {
            $this->warn("⚠️  'to_number' is NOT being sent by Retell!");
            $this->line("   → Must use agent_id for branch resolution");
        }
        
        if ($hasAgentId == $total) {
            $this->info("✅ 'agent_id' is always present - use this for branch mapping!");
        }
        
        if ($hasMetadata < $total) {
            $this->line("📝 Metadata is not always present ({$hasMetadata}/{$total})");
            $this->line("   → Run: php artisan retell:sync-metadata");
        }
    }
}