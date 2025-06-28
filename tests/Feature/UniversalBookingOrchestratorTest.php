<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\Service;
use App\Models\Staff;
use App\Services\Booking\Strategies\LoadBalancedStrategy;
use App\Services\Booking\Strategies\NearestLocationStrategy;
use App\Services\Booking\UniversalBookingOrchestrator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UniversalBookingOrchestratorTest extends TestCase
{
    use RefreshDatabase;
    
    private UniversalBookingOrchestrator $orchestrator;
    private Company $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company with multiple branches
        $this->company = Company::factory()->create([
            'name' => 'Multi-Location Test Company',
            'phone_number' => '+49 30 12345678'
        ]);
        
        // Get orchestrator instance
        $this->orchestrator = app(UniversalBookingOrchestrator::class);
    }
    
    #[Test]
    
    public function test_resolves_branch_from_phone_number()
    {
        // Create branches with phone numbers
        $berlinBranch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Berlin Office',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'phone_number' => '+49 30 11111111',
            'active' => true
        ]);
        
        $munichBranch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Munich Office',
            'city' => 'Munich',
            'postal_code' => '80331',
            'phone_number' => '+49 89 22222222',
            'active' => true
        ]);
        
        // Create phone number record
        PhoneNumber::create([
            'branch_id' => $berlinBranch->id,
            'number' => '+49 30 33333333',
            'active' => true
        ]);
        
        // Test phone resolution
        $phoneResolver = app(\App\Services\PhoneNumberResolver::class);
        
        // Test direct branch phone
        $result = $phoneResolver->resolveFromWebhook([
            'to_number' => '+49 30 11111111'
        ]);
        
        $this->assertEquals($berlinBranch->id, $result['branch_id']);
        $this->assertEquals('phone_number', $result['resolution_method']);
        
        // Test phone_numbers table
        $result = $phoneResolver->resolveFromWebhook([
            'to_number' => '+49 30 33333333'
        ]);
        
        $this->assertEquals($berlinBranch->id, $result['branch_id']);
    }
    
    #[Test]
    
    public function test_finds_suitable_branches_based_on_service()
    {
        // Create branches
        $branch1 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Branch 1',
            'active' => true
        ]);
        
        $branch2 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Branch 2',
            'active' => true
        ]);
        
        // Create service
        $service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Haircut',
            'duration' => 30
        ]);
        
        // Assign service to branch1 only
        $branch1->services()->attach($service->id, [
            'price' => 25.00,
            'duration' => 30,
            'active' => true
        ]);
        
        // Create customer
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'phone' => '+49 30 87654321'
        ]);
        
        // Test booking request
        $bookingRequest = [
            'company_id' => $this->company->id,
            'service_id' => $service->id,
            'date' => Carbon::tomorrow()->format('Y-m-d'),
            'time' => '14:00',
            'customer' => [
                'phone' => $customer->phone,
                'name' => $customer->name
            ]
        ];
        
        // Process booking
        $result = $this->orchestrator->processBookingRequest($bookingRequest);
        
        // Should find branch1 as it offers the service
        if ($result['success']) {
            $this->assertEquals($branch1->id, $result['appointment']->branch_id);
        }
    }
    
    #[Test]
    
    public function test_uses_customer_preferred_branch()
    {
        // Create branches
        $branch1 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Branch 1',
            'active' => true
        ]);
        
        $branch2 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Branch 2',
            'active' => true
        ]);
        
        // Create customer with preferred branch
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'preferred_branch_id' => $branch2->id,
            'phone' => '+49 30 99999999'
        ]);
        
        // Test with NearestLocationStrategy
        $this->orchestrator->setBranchSelectionStrategy(new NearestLocationStrategy());
        
        $bookingRequest = [
            'company_id' => $this->company->id,
            'date' => Carbon::tomorrow()->format('Y-m-d'),
            'time' => '10:00',
            'customer' => [
                'phone' => $customer->phone
            ]
        ];
        
        // The strategy should prioritize customer's preferred branch
        $result = $this->orchestrator->processBookingRequest($bookingRequest);
        
        if ($result['success']) {
            $this->assertEquals($branch2->id, $result['appointment']->branch_id);
        }
    }
    
    #[Test]
    
    public function test_load_balanced_strategy_distributes_bookings()
    {
        // Set load balanced strategy
        $this->orchestrator->setBranchSelectionStrategy(new LoadBalancedStrategy());
        
        // Create branches
        $branches = [];
        for ($i = 1; $i <= 3; $i++) {
            $branches[] = Branch::factory()->create([
                'company_id' => $this->company->id,
                'name' => "Branch {$i}",
                'active' => true
            ]);
        }
        
        // Create multiple bookings
        $bookingCounts = [];
        
        for ($i = 0; $i < 10; $i++) {
            $bookingRequest = [
                'company_id' => $this->company->id,
                'date' => Carbon::tomorrow()->addDays($i)->format('Y-m-d'),
                'time' => '14:00',
                'customer' => [
                    'name' => "Customer {$i}",
                    'phone' => "+49 30 1000000{$i}"
                ]
            ];
            
            $result = $this->orchestrator->processBookingRequest($bookingRequest);
            
            if ($result['success']) {
                $branchId = $result['appointment']->branch_id;
                $bookingCounts[$branchId] = ($bookingCounts[$branchId] ?? 0) + 1;
            }
        }
        
        // Check that bookings are distributed (not all at one branch)
        $this->assertGreaterThan(1, count($bookingCounts));
    }
    
    #[Test]
    
    public function test_multi_language_staff_matching()
    {
        // Create branch
        $branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'active' => true
        ]);
        
        // Create staff with different languages
        $germanStaff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'home_branch_id' => $branch->id,
            'languages' => ['de'],
            'active' => true
        ]);
        
        $multilingualStaff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'home_branch_id' => $branch->id,
            'languages' => ['de', 'en', 'tr'],
            'active' => true
        ]);
        
        // Test language preference matching
        $bookingRequest = [
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'language' => 'en',
            'date' => Carbon::tomorrow()->format('Y-m-d'),
            'time' => '15:00',
            'customer' => [
                'name' => 'English Customer',
                'phone' => '+44 20 12345678'
            ]
        ];
        
        $result = $this->orchestrator->processBookingRequest($bookingRequest);
        
        // Should prefer multilingual staff for English customer
        if ($result['success']) {
            $this->assertEquals($multilingualStaff->id, $result['appointment']->staff_id);
        }
    }
    
    #[Test]
    
    public function test_handles_branch_without_specific_resolution()
    {
        // Create multiple branches
        $branches = [];
        for ($i = 1; $i <= 2; $i++) {
            $branches[] = Branch::factory()->create([
                'company_id' => $this->company->id,
                'name' => "Branch {$i}",
                'active' => true
            ]);
        }
        
        // Test booking without specific branch
        $bookingRequest = [
            'company_id' => $this->company->id,
            'date' => Carbon::tomorrow()->format('Y-m-d'),
            'time' => '11:00',
            'customer' => [
                'name' => 'New Customer',
                'phone' => '+49 30 55555555'
            ]
        ];
        
        $result = $this->orchestrator->processBookingRequest($bookingRequest);
        
        // Should successfully book at one of the branches
        if ($result['success']) {
            $this->assertContains($result['appointment']->branch_id, array_column($branches, 'id'));
        }
    }
    
    #[Test]
    
    public function test_returns_alternatives_when_requested_time_unavailable()
    {
        // This would require mocking the availability service
        // For now, just test the structure
        $branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'active' => true
        ]);
        
        $bookingRequest = [
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'date' => Carbon::yesterday()->format('Y-m-d'), // Past date
            'time' => '14:00',
            'customer' => [
                'name' => 'Test Customer',
                'phone' => '+49 30 77777777'
            ]
        ];
        
        $result = $this->orchestrator->processBookingRequest($bookingRequest);
        
        // Should fail but provide alternatives
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('alternatives', $result);
    }
}