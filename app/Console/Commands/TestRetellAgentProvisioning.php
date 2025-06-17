<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Services\Provisioning\RetellAgentProvisioner;

class TestRetellAgentProvisioning extends Command
{
    protected $signature = 'test:retell-provisioning {branch_id}';
    protected $description = 'Test Retell agent provisioning for a branch';

    public function handle()
    {
        $branchId = $this->argument('branch_id');
        $branch = Branch::find($branchId);
        
        if (!$branch) {
            $this->error("Branch not found: {$branchId}");
            return 1;
        }
        
        $this->info("🤖 TESTING RETELL AGENT PROVISIONING");
        $this->info("=====================================\n");
        
        $this->info("Branch: {$branch->name}");
        $this->info("Company: {$branch->company->name}");
        $this->info("Phone: {$branch->phone_number}");
        $this->info("Current Agent ID: " . ($branch->retell_agent_id ?: 'None'));
        $this->info("Current Status: " . ($branch->retell_agent_status ?: 'Not provisioned'));
        
        // Check prerequisites
        $this->info("\n📋 CHECKING PREREQUISITES");
        $this->info("-------------------------");
        
        $checks = [
            'Company exists' => !is_null($branch->company),
            'Phone number configured' => !empty($branch->phone_number),
            'Services available' => $branch->services->count() > 0,
            'Staff available' => $branch->staff->count() > 0,
            'Business hours set' => !empty($branch->business_hours),
        ];
        
        $allPassed = true;
        foreach ($checks as $check => $passed) {
            if ($passed) {
                $this->info("✓ {$check}");
            } else {
                $this->warn("✗ {$check}");
                $allPassed = false;
            }
        }
        
        if (!$allPassed) {
            $this->error("\n⚠️  Some prerequisites are missing. Please configure the branch first.");
            return 1;
        }
        
        // Test provisioning
        $this->info("\n🚀 PROVISIONING AGENT");
        $this->info("--------------------");
        
        $provisioner = new RetellAgentProvisioner();
        
        if ($branch->hasRetellAgent()) {
            $this->warn("Branch already has an active agent. Testing update instead...");
            $result = $provisioner->updateAgentForBranch($branch);
        } else {
            $result = $provisioner->createAgentForBranch($branch);
        }
        
        if ($result['success']) {
            $this->info("✅ Agent provisioned successfully!");
            $this->info("Agent ID: " . $result['agent_id']);
            
            // Refresh branch data
            $branch->refresh();
            
            // Display agent configuration
            $this->info("\n📞 AGENT CONFIGURATION");
            $this->info("---------------------");
            
            $agentData = $branch->getSetting('retell_agent');
            if ($agentData) {
                $this->table(
                    ['Property', 'Value'],
                    [
                        ['Name', $agentData['agent_name'] ?? 'N/A'],
                        ['Voice', $agentData['voice_id'] ?? 'N/A'],
                        ['Language', $agentData['language'] ?? 'N/A'],
                        ['LLM Model', $agentData['llm_id'] ?? 'N/A'],
                        ['Webhook URL', $agentData['webhook_url'] ?? 'N/A'],
                        ['Max Duration', ($agentData['max_call_duration_ms'] ?? 0) / 60000 . ' minutes'],
                    ]
                );
            }
            
            // Test phone number assignment
            if ($branch->phone_number) {
                $this->info("\n📱 PHONE NUMBER ASSIGNMENT");
                $this->info("-------------------------");
                $this->info("Phone: {$branch->phone_number}");
                $this->info("Status: Check Retell.ai dashboard to verify assignment");
            }
            
        } else {
            $this->error("❌ Agent provisioning failed!");
            $this->error("Error: " . $result['error']);
        }
        
        // Summary
        $this->info("\n📊 SUMMARY");
        $this->info("----------");
        $this->info("Branch ID: {$branch->id}");
        $this->info("Agent ID: " . ($branch->retell_agent_id ?: 'None'));
        $this->info("Status: " . ($branch->retell_agent_status ?: 'Not provisioned'));
        $this->info("Created At: " . ($branch->retell_agent_created_at ?: 'N/A'));
        
        return 0;
    }
}