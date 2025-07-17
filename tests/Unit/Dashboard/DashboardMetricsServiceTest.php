<?php

namespace Tests\Unit\Dashboard;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardMetricsServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private DashboardMetricsService $service;
    private Company $company;
    private Branch $branch;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache to ensure fresh calculations
        Cache::flush();
        
        $this->service = app(DashboardMetricsService::class);
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        
        // Create phone numbers for the company (required for Call filtering)
        // Note: Using minimal fields to avoid migration issues in tests
        PhoneNumber::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'number' => '+4917612345678',
        ]);
        
        // Create a mock authenticated user with the company
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($user);
    }    #[Test]
    public function it_calculates_appointment_kpis_correctly()
    {
        // Create test appointments
        $service = Service::factory()->create([
            'company_id' => $this->company->id,
            'price' => 100.00
        ]);
        
        // Today's appointments
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'starts_at' => now(),
            'ends_at' => now()->addHour()
        ]);
        
        // Yesterday's appointments (for trend calculation)
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->subDay()->addHour()
        ]);
        
        $filters = [
            'company_id' => $this->company->id,
            'period' => 'today',
        ];
        
        // Debug: Check if appointments were created
        $appointmentCount = Appointment::where('company_id', $this->company->id)->count();
        $this->assertEquals(8, $appointmentCount, "Expected 8 appointments in database, found {$appointmentCount}");
        
        // Debug: Check appointments for today
        $appointmentsToday = Appointment::where('company_id', $this->company->id)
            ->whereBetween('starts_at', [now()->startOfDay(), now()->endOfDay()])
            ->count();
        $this->assertEquals(5, $appointmentsToday, "Expected 5 appointments today, found {$appointmentsToday}");
        
        $kpis = $this->service->getAppointmentKpis($filters);
        
        // Assert revenue calculation
        $this->assertEquals(500, $kpis['revenue']['value']);
        $this->assertEquals('500€', $kpis['revenue']['formatted']);
        $this->assertEqualsWithDelta(66.67, $kpis['revenue']['change'], 0.01); // 500 vs 300 = +66.67%
        
        // Assert appointment count
        $this->assertEquals(5, $kpis['appointments']['value']);
    }    #[Test]
    public function it_calculates_call_kpis_correctly()
    {
        // Create test calls with the correct phone number
        Call::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'to_number' => '+4917612345678', // Match the PhoneNumber we created
            'created_at' => now(),
            'duration_sec' => 300, // 5 minutes
            'call_status' => 'completed'
        ]);
        
        // Calls with appointments (conversion)
        $appointmentCalls = Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'to_number' => '+4917612345678', // Match the PhoneNumber we created
            'created_at' => now(),
            'duration_sec' => 600,
            'call_status' => 'completed'
        ]);
        
        foreach ($appointmentCalls as $call) {
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'call_id' => $call->id,
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDay()->addHour()
            ]);
        }
        
        $filters = [
            'company_id' => $this->company->id,
            'period' => 'today',
        ];
        
        // Debug: Check if calls were created
        $callCount = Call::where('company_id', $this->company->id)->count();
        $this->assertEquals(13, $callCount, "Expected 13 calls in database, found {$callCount}");
        
        // Debug: Check calls with date filter
        $callsToday = Call::where('company_id', $this->company->id)
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->count();
        $this->assertEquals(13, $callsToday, "Expected 13 calls today, found {$callsToday}");
        
        // Debug: Check calls with forCompany
        try {
            $callsWithForCompany = Call::forCompany($this->company->id)
                ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
                ->count();
            $this->assertEquals(13, $callsWithForCompany, "Expected 13 calls with forCompany, found {$callsWithForCompany}");
        } catch (\Exception $e) {
            // forCompany might fail in tests, try withoutGlobalScope
            $callsWithoutScope = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $this->company->id)
                ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
                ->count();
            $this->assertEquals(13, $callsWithoutScope, "Expected 13 calls withoutGlobalScope, found {$callsWithoutScope}");
        }
        
        $kpis = $this->service->getCallKpis($filters);
        
        // Assert total calls
        $this->assertEquals(13, $kpis['total_calls']['value']);
        
        // Assert success rate (calls with appointments / total calls)
        $this->assertEqualsWithDelta(23.08, $kpis['success_rate']['value'], 0.1); // 3/13 = 23.08%
        
        // Assert average duration
        $avgDuration = (10 * 300 + 3 * 600) / 13; // 369.23 seconds
        $this->assertEquals(369, round($kpis['avg_duration']['value']));
    }    #[Test]
    public function it_calculates_customer_kpis_correctly()
    {
        // Create customers
        $existingCustomers = Customer::factory()->count(100)->create([
            'company_id' => $this->company->id,
            'created_at' => now()->subMonth()
        ]);
        
        $newCustomers = Customer::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'created_at' => now()
        ]);
        
        // Create appointments for some customers
        foreach ($existingCustomers->take(30) as $customer) {
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'status' => 'completed',
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->subWeek()->addHour()
            ]);
        }
        
        $filters = [
            'company_id' => $this->company->id,
            'period' => 'today',
        ];
        
        $kpis = $this->service->getCustomerKpis($filters);
        
        // Assert new customers
        $this->assertEquals(10, $kpis['new_customers']['value']);
        
        // Assert total customers
        $this->assertEquals(110, $kpis['total_customers']['value']);
    }    #[Test]
    public function it_caches_results_correctly()
    {
        $filters = [
            'company_id' => $this->company->id,
            'period' => 'today',
        ];
        
        // First call - should hit database
        $kpis1 = $this->service->getAppointmentKpis($filters);
        
        // Create new appointment
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'starts_at' => now()
        ]);
        
        // Second call - should return cached result
        $kpis2 = $this->service->getAppointmentKpis($filters);
        
        // Results should be identical (cached)
        $this->assertEquals($kpis1, $kpis2);
        
        // Clear cache and call again
        Cache::flush();
        $kpis3 = $this->service->getAppointmentKpis($filters);
        
        // Now it should reflect the new appointment
        $this->assertNotEquals($kpis1['appointments']['value'], $kpis3['appointments']['value']);
    }    #[Test]
    public function it_calculates_trends_correctly()
    {
        // Create service for revenue calculation
        $service = Service::factory()->create([
            'company_id' => $this->company->id,
            'price' => 100.00
        ]);
        
        // This month: 10 appointments
        Appointment::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'starts_at' => now()->startOfMonth()->addDays(5)
        ]);
        
        // Last month: 5 appointments
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'starts_at' => now()->subMonth()->startOfMonth()->addDays(5)
        ]);
        
        $filters = [
            'company_id' => $this->company->id,
            'period' => 'this_month',
        ];
        
        $kpis = $this->service->getAppointmentKpis($filters);
        
        // Revenue should be 1000 this month vs 500 last month = +100%
        $this->assertEquals(1000, $kpis['revenue']['value']);
        $this->assertEqualsWithDelta(100.0, $kpis['revenue']['change'], 0.01);
        $this->assertEquals('up', $kpis['revenue']['trend']);
    }    #[Test]
    public function it_handles_branch_filters_correctly()
    {
        $branch2 = Branch::factory()->create(['company_id' => $this->company->id]);
        
        // Create appointments for different branches
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'starts_at' => now()
        ]);
        
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch2->id,
            'starts_at' => now()
        ]);
        
        // Filter by first branch
        $filters = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'period' => 'today',
        ];
        
        $kpis = $this->service->getAppointmentKpis($filters);
        
        // Should only count appointments from first branch
        $this->assertEquals(5, $kpis['appointments']['value']);
    }    #[Test]
    public function it_handles_custom_date_ranges()
    {
        // Create appointments across multiple days
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'starts_at' => now()->subDays(5)
        ]);
        
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'starts_at' => now()->subDays(2)
        ]);
        
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'starts_at' => now()
        ]);
        
        $filters = [
            'company_id' => $this->company->id,
            'period' => 'custom',
            'date_from' => now()->subDays(3)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ];
        
        $kpis = $this->service->getAppointmentKpis($filters);
        
        // Should only count appointments from last 3 days (5 + 2 = 7)
        $this->assertEquals(7, $kpis['appointments']['value']);
    }    #[Test]
    public function it_handles_empty_results_gracefully()
    {
        $filters = [
            'company_id' => $this->company->id,
            'period' => 'today',
        ];
        
        $appointmentKpis = $this->service->getAppointmentKpis($filters);
        $callKpis = $this->service->getCallKpis($filters);
        $customerKpis = $this->service->getCustomerKpis($filters);
        
        // All values should be 0 but properly formatted
        $this->assertEquals(0, $appointmentKpis['revenue']['value']);
        $this->assertEquals('0€', $appointmentKpis['revenue']['formatted']);
        $this->assertEquals(0, $appointmentKpis['revenue']['change']);
        $this->assertEquals('stable', $appointmentKpis['revenue']['trend']);
        
        $this->assertEquals(0, $callKpis['total_calls']['value']);
        $this->assertEquals(0, $callKpis['success_rate']['value']);
        
        $this->assertEquals(0, $customerKpis['new_customers']['value']);
    }
}