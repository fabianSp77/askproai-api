<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Services\Provisioning\RetellAgentProvisioner;

class SetupTestBranchForProvisioning extends Command
{
    protected $signature = 'test:setup-branch {branch_id}';
    protected $description = 'Setup a branch with test data for Retell provisioning';

    public function handle()
    {
        $branchId = $this->argument('branch_id');
        $branch = Branch::find($branchId);
        
        if (!$branch) {
            $this->error("Branch not found: {$branchId}");
            return 1;
        }
        
        $this->info("Setting up test data for branch: {$branch->name}");
        
        // 1. Create test services
        $this->info("\n1. Creating test services...");
        $services = [
            ['name' => 'Beratungsgespräch', 'duration' => 30, 'price' => 50.00],
            ['name' => 'Behandlung', 'duration' => 60, 'price' => 120.00],
            ['name' => 'Nachkontrolle', 'duration' => 15, 'price' => 0.00],
        ];
        
        foreach ($services as $serviceData) {
            $service = Service::create([
                'company_id' => $branch->company_id,
                'name' => $serviceData['name'],
                'duration' => $serviceData['duration'],
                'price' => $serviceData['price'],
                'is_active' => true,
            ]);
            
            // Attach to branch
            $branch->services()->attach($service->id, [
                'price' => $serviceData['price'],
                'duration' => $serviceData['duration'],
                'active' => true,
            ]);
            
            $this->info("  ✓ Created service: {$service->name}");
        }
        
        // 2. Create test staff
        $this->info("\n2. Creating test staff...");
        $staffMembers = [
            ['name' => 'Dr. Maria Schmidt', 'title' => 'Ärztin'],
            ['name' => 'Dr. Thomas Weber', 'title' => 'Arzt'],
        ];
        
        foreach ($staffMembers as $staffData) {
            $staff = Staff::create([
                'company_id' => $branch->company_id,
                'home_branch_id' => $branch->id,
                'name' => $staffData['name'],
                'title' => $staffData['title'],
                'email' => strtolower(str_replace(' ', '.', $staffData['name'])) . '@test.com',
                'phone' => '+49' . rand(1000000000, 9999999999),
                'active' => true,
            ]);
            
            // Assign to branch
            $branch->availableStaff()->attach($staff->id);
            
            $this->info("  ✓ Created staff: {$staff->name}");
        }
        
        // 3. Set business hours
        $this->info("\n3. Setting business hours...");
        $businessHours = [
            'monday' => ['isOpen' => true, 'openTime' => '09:00', 'closeTime' => '18:00'],
            'tuesday' => ['isOpen' => true, 'openTime' => '09:00', 'closeTime' => '18:00'],
            'wednesday' => ['isOpen' => true, 'openTime' => '09:00', 'closeTime' => '18:00'],
            'thursday' => ['isOpen' => true, 'openTime' => '09:00', 'closeTime' => '18:00'],
            'friday' => ['isOpen' => true, 'openTime' => '09:00', 'closeTime' => '16:00'],
            'saturday' => ['isOpen' => false],
            'sunday' => ['isOpen' => false],
        ];
        
        $branch->update(['business_hours' => $businessHours]);
        $this->info("  ✓ Business hours set");
        
        // 4. Add settings for agent provisioning
        $this->info("\n4. Adding agent settings...");
        $settings = [
            'voice_id' => 'de-DE-KatjaNeural',
            'language' => 'de-DE',
            'custom_prompt_instructions' => 'Sei besonders freundlich und hilfsbereit. Betone die Kompetenz unseres Teams.',
            'industry' => 'medical',
        ];
        
        $branch->update(['settings' => array_merge($branch->settings ?? [], $settings)]);
        $this->info("  ✓ Agent settings configured");
        
        // 5. Summary
        $this->info("\n✅ SETUP COMPLETE");
        $this->info("------------------");
        $branch->refresh();
        $this->info("Branch: {$branch->name}");
        $this->info("Services: {$branch->services->count()}");
        $this->info("Staff: {$branch->staff->count()}");
        $this->info("Business Hours: Set");
        $this->info("Phone: {$branch->phone_number}");
        
        // 6. Test provisioning
        if ($this->confirm("\nDo you want to test Retell agent provisioning now?", true)) {
            $this->info("\n🚀 TESTING AGENT PROVISIONING");
            $this->info("-----------------------------");
            
            $provisioner = new RetellAgentProvisioner();
            $result = $provisioner->createAgentForBranch($branch);
            
            if ($result['success']) {
                $this->info("✅ Agent provisioned successfully!");
                $this->info("Agent ID: " . $result['agent_id']);
                $this->table(
                    ['Property', 'Value'],
                    [
                        ['Agent ID', $result['agent_id']],
                        ['Status', 'Active'],
                        ['Voice', $settings['voice_id']],
                        ['Language', $settings['language']],
                    ]
                );
            } else {
                $this->error("❌ Agent provisioning failed!");
                $this->error("Error: " . $result['error']);
            }
        }
        
        return 0;
    }
}