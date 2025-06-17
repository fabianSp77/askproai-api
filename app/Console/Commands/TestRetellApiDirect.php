<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RetellV2Service;
use App\Models\Company;

class TestRetellApiDirect extends Command
{
    protected $signature = 'test:retell-api-direct';
    protected $description = 'Test Retell API directly with minimal config';

    public function handle()
    {
        $this->info("🔧 TESTING RETELL API DIRECTLY");
        $this->info("==============================\n");
        
        // Get API key
        $apiKey = env('RETELL_TOKEN') ?? config('services.retell.api_key');
        
        if (!$apiKey) {
            $company = Company::first();
            if ($company && $company->retell_api_key) {
                try {
                    $apiKey = decrypt($company->retell_api_key);
                } catch (\Exception $e) {
                    $apiKey = $company->retell_api_key;
                }
            }
        }
        
        if (!$apiKey) {
            $this->error("No API key found!");
            return 1;
        }
        
        $this->info("Using API key: " . substr($apiKey, 0, 10) . "...");
        
        // Test 1: List existing agents
        $this->info("\n1. LISTING EXISTING AGENTS");
        $this->info("--------------------------");
        
        try {
            $retell = new RetellV2Service($apiKey);
            $agents = $retell->listAgents();
            
            if (isset($agents['agents'])) {
                $this->info("Found " . count($agents['agents']) . " agents");
                foreach ($agents['agents'] as $agent) {
                    $this->info("  - {$agent['agent_name']} (ID: {$agent['agent_id']})");
                }
            } else {
                $this->warn("No agents found or unexpected response");
            }
        } catch (\Exception $e) {
            $this->error("Failed to list agents: " . $e->getMessage());
        }
        
        // Test 2: Create minimal agent
        $this->info("\n2. CREATING MINIMAL TEST AGENT");
        $this->info("-------------------------------");
        
        $minimalConfig = [
            'agent_name' => 'Test Agent ' . now()->format('Y-m-d H:i:s'),
            'voice_id' => '11labs-Adrian',
            'response_engine' => [
                'type' => 'retell-llm',
                'llm_id' => 'gpt-3.5-turbo',
                'system_prompt' => 'You are a helpful assistant.',
            ],
        ];
        
        $this->info("Config:");
        $this->line(json_encode($minimalConfig, JSON_PRETTY_PRINT));
        
        try {
            $result = $retell->createAgent($minimalConfig);
            
            if (isset($result['agent_id'])) {
                $this->info("\n✅ Agent created successfully!");
                $this->info("Agent ID: " . $result['agent_id']);
                $this->table(
                    ['Property', 'Value'],
                    [
                        ['ID', $result['agent_id']],
                        ['Name', $result['agent_name'] ?? 'N/A'],
                        ['Voice', $result['voice_id'] ?? 'N/A'],
                        ['Status', 'Active'],
                    ]
                );
                
                // Test 3: Delete the test agent
                if ($this->confirm("\nDelete this test agent?", true)) {
                    $deleted = $retell->deleteAgent($result['agent_id']);
                    if ($deleted) {
                        $this->info("✅ Test agent deleted");
                    } else {
                        $this->warn("Failed to delete test agent");
                    }
                }
            } else {
                $this->error("Failed to create agent - unexpected response");
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
            }
        } catch (\Exception $e) {
            $this->error("Failed to create agent: " . $e->getMessage());
        }
        
        return 0;
    }
}