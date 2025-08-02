<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch1 = Branch::factory()->create(['company_id' => $this->company->id, 'name' => 'Main Branch']);
        $this->branch2 = Branch::factory()->create(['company_id' => $this->company->id, 'name' => 'Second Branch']);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        
        // Authenticate user
        Sanctum::actingAs($this->user);
    }
    
    public function test_get_overview_stats()
    {
        // Create test data
        $service = Service::factory()->create(['company_id' => $this->company->id, 'price' => 100]);
        $staff1 = Staff::factory()->create(['branch_id' => $this->branch1->id]);
        $staff2 = Staff::factory()->create(['branch_id' => $this->branch2->id]);
        
        // Today's appointments
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'staff_id' => $staff1->id,
            'service_id' => $service->id,
            'starts_at' => Carbon::today()->addHours(10),
            'status' => 'scheduled'
        ]);
        
        // Yesterday's appointments
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'staff_id' => $staff2->id,
            'service_id' => $service->id,
            'starts_at' => Carbon::yesterday(),
            'status' => 'completed',
            'price' => 100
        ]);
        
        // Calls
        Call::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::today()
        ]);
        
        // New customers
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::today()
        ]);
        
        $response = $this->getJson('/api/dashboard/overview?period=today');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'appointments' => [
                        'total',
                        'scheduled',
                        'completed',
                        'cancelled'
                    ],
                    'revenue' => [
                        'total',
                        'average_transaction',
                        'growth'
                    ],
                    'calls' => [
                        'total',
                        'average_duration',
                        'missed'
                    ],
                    'new_customers' => [
                        'count',
                        'growth'
                    ],
                    'period',
                    'comparisons'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertEquals(5, $data['appointments']['scheduled']);
        $this->assertEquals(10, $data['calls']['total']);
        $this->assertEquals(3, $data['new_customers']['count']);
    }
    
    public function test_get_revenue_metrics()
    {
        $service1 = Service::factory()->create(['company_id' => $this->company->id, 'price' => 100]);
        $service2 = Service::factory()->create(['company_id' => $this->company->id, 'price' => 200]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch1->id]);
        
        // Create completed appointments with revenue
        Appointment::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'staff_id' => $staff->id,
            'service_id' => $service1->id,
            'status' => 'completed',
            'price' => 100,
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'staff_id' => $staff->id,
            'service_id' => $service2->id,
            'status' => 'completed',
            'price' => 200,
            'created_at' => Carbon::now()->subDays(2)
        ]);
        
        $response = $this->getJson('/api/dashboard/revenue?period=last_7_days&group_by=day');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_revenue',
                    'revenue_by_period',
                    'revenue_by_service',
                    'revenue_by_branch',
                    'average_transaction_value',
                    'growth_rate'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertEquals(2000, $data['total_revenue']); // (10 * 100) + (5 * 200)
        $this->assertEquals(1000, $data['revenue_by_branch'][$this->branch1->id]['total']);
        $this->assertEquals(1000, $data['revenue_by_branch'][$this->branch2->id]['total']);
    }
    
    public function test_get_appointment_metrics()
    {
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch1->id]);
        
        // Create appointments with different statuses
        Appointment::factory()->count(20)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'status' => 'cancelled',
            'created_at' => Carbon::now()->subDays(3)
        ]);
        
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'status' => 'no_show',
            'created_at' => Carbon::now()->subDays(1)
        ]);
        
        $response = $this->getJson('/api/dashboard/appointments?period=last_7_days');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_appointments',
                    'appointments_by_status',
                    'completion_rate',
                    'cancellation_rate',
                    'no_show_rate',
                    'appointments_by_day',
                    'appointments_by_hour',
                    'popular_services',
                    'popular_time_slots'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertEquals(28, $data['total_appointments']);
        $this->assertEquals(20, $data['appointments_by_status']['completed']);
        $this->assertEquals(71.43, round($data['completion_rate'], 2));
        $this->assertEquals(17.86, round($data['cancellation_rate'], 2));
        $this->assertEquals(10.71, round($data['no_show_rate'], 2));
    }
    
    public function test_get_customer_metrics()
    {
        // Create customers with different registration dates
        Customer::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subDays(30)
        ]);
        
        Customer::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subDays(7)
        ]);
        
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::today()
        ]);
        
        // Create appointments for retention calculation
        $activeCustomers = Customer::factory()->count(8)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subMonths(2)
        ]);
        
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch1->id]);
        
        foreach ($activeCustomers as $customer) {
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'branch_id' => $this->branch1->id,
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(rand(1, 30))
            ]);
        }
        
        $response = $this->getJson('/api/dashboard/customers?period=last_30_days');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_customers',
                    'new_customers',
                    'active_customers',
                    'retention_rate',
                    'acquisition_rate',
                    'customers_by_day',
                    'top_customers'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertEquals(26, $data['total_customers']); // 10 + 5 + 3 + 8
        $this->assertEquals(8, $data['new_customers']); // 5 + 3 (last 30 days)
        $this->assertEquals(8, $data['active_customers']); // Customers with appointments
    }
    
    public function test_get_staff_performance()
    {
        $service = Service::factory()->create(['company_id' => $this->company->id, 'price' => 100]);
        $staff1 = Staff::factory()->create(['branch_id' => $this->branch1->id, 'first_name' => 'John']);
        $staff2 = Staff::factory()->create(['branch_id' => $this->branch1->id, 'first_name' => 'Jane']);
        
        // Create appointments for staff1
        Appointment::factory()->count(15)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'staff_id' => $staff1->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'price' => 100,
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'staff_id' => $staff1->id,
            'service_id' => $service->id,
            'status' => 'cancelled',
            'created_at' => Carbon::now()->subDays(2)
        ]);
        
        // Create appointments for staff2
        Appointment::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'staff_id' => $staff2->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'price' => 100,
            'created_at' => Carbon::now()->subDays(3)
        ]);
        
        $response = $this->getJson('/api/dashboard/staff-performance?period=last_7_days&branch_id=' . $this->branch1->id);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'staff_metrics' => [
                        '*' => [
                            'staff_id',
                            'staff_name',
                            'total_appointments',
                            'completed_appointments',
                            'revenue_generated',
                            'completion_rate',
                            'average_rating'
                        ]
                    ],
                    'top_performers',
                    'average_metrics'
                ]
            ]);
        
        $data = $response->json('data');
        $staff1Metrics = collect($data['staff_metrics'])->firstWhere('staff_id', $staff1->id);
        $this->assertEquals(18, $staff1Metrics['total_appointments']);
        $this->assertEquals(15, $staff1Metrics['completed_appointments']);
        $this->assertEquals(1500, $staff1Metrics['revenue_generated']);
        $this->assertEquals(83.33, round($staff1Metrics['completion_rate'], 2));
    }
    
    public function test_get_branch_comparison()
    {
        $service = Service::factory()->create(['company_id' => $this->company->id, 'price' => 100]);
        $staff1 = Staff::factory()->create(['branch_id' => $this->branch1->id]);
        $staff2 = Staff::factory()->create(['branch_id' => $this->branch2->id]);
        
        // Branch 1 data
        Appointment::factory()->count(20)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'staff_id' => $staff1->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'price' => 100,
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        Customer::factory()->count(15)->create([
            'company_id' => $this->company->id,
            'preferred_branch_id' => $this->branch1->id,
            'created_at' => Carbon::now()->subDays(10)
        ]);
        
        // Branch 2 data
        Appointment::factory()->count(15)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'staff_id' => $staff2->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'price' => 100,
            'created_at' => Carbon::now()->subDays(3)
        ]);
        
        Customer::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'preferred_branch_id' => $this->branch2->id,
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        $response = $this->getJson('/api/dashboard/branch-comparison?period=last_30_days');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'branches' => [
                        '*' => [
                            'branch_id',
                            'branch_name',
                            'appointments',
                            'revenue',
                            'customers',
                            'staff_count',
                            'utilization_rate'
                        ]
                    ],
                    'rankings'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertCount(2, $data['branches']);
        
        $branch1Data = collect($data['branches'])->firstWhere('branch_id', $this->branch1->id);
        $this->assertEquals(20, $branch1Data['appointments']);
        $this->assertEquals(2000, $branch1Data['revenue']);
        $this->assertEquals(15, $branch1Data['customers']);
    }
    
    public function test_get_recent_activity()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch1->id]);
        
        // Create recent appointments
        $appointment1 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'status' => 'scheduled',
            'created_at' => Carbon::now()->subMinutes(30)
        ]);
        
        $appointment2 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'updated_at' => Carbon::now()->subMinutes(10)
        ]);
        
        // Create recent calls
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'created_at' => Carbon::now()->subMinutes(5)
        ]);
        
        // Create new customer
        $newCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subMinutes(1)
        ]);
        
        $response = $this->getJson('/api/dashboard/recent-activity?limit=10');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'activities' => [
                        '*' => [
                            'type',
                            'description',
                            'timestamp',
                            'entity',
                            'user'
                        ]
                    ],
                    'total'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertCount(4, $data['activities']);
        
        // Verify activities are sorted by time (most recent first)
        $timestamps = array_map(function ($activity) {
            return Carbon::parse($activity['timestamp'])->timestamp;
        }, $data['activities']);
        
        $sortedTimestamps = $timestamps;
        rsort($sortedTimestamps);
        $this->assertEquals($sortedTimestamps, $timestamps);
    }
    
    public function test_dashboard_caching()
    {
        // Create initial data
        Customer::factory()->count(5)->create([
            'company_id' => $this->company->id
        ]);
        
        // First call - should hit database
        $response1 = $this->getJson('/api/dashboard/overview?period=today&use_cache=true');
        $response1->assertStatus(200);
        $data1 = $response1->json('data');
        
        // Add more customers
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id
        ]);
        
        // Second call - should return cached data
        $response2 = $this->getJson('/api/dashboard/overview?period=today&use_cache=true');
        $response2->assertStatus(200);
        $data2 = $response2->json('data');
        
        $this->assertEquals($data1['new_customers']['count'], $data2['new_customers']['count']);
        
        // Call without cache - should return updated data
        $response3 = $this->getJson('/api/dashboard/overview?period=today&use_cache=false');
        $response3->assertStatus(200);
        $data3 = $response3->json('data');
        
        $this->assertNotEquals($data1['new_customers']['count'], $data3['new_customers']['count']);
    }
    
    public function test_dashboard_period_filters()
    {
        // Test different period filters
        $periods = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'last_7_days', 'last_30_days'];
        
        foreach ($periods as $period) {
            $response = $this->getJson("/api/dashboard/overview?period={$period}");
            
            $response->assertStatus(200)
                ->assertJsonPath('data.period', $period);
        }
    }
    
    public function test_unauthorized_access()
    {
        auth()->logout();
        
        $response = $this->getJson('/api/dashboard/overview');
        
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
    
    public function test_cross_company_isolation()
    {
        // Create another company with data
        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);
        
        Customer::factory()->count(10)->create([
            'company_id' => $otherCompany->id,
            'created_at' => Carbon::today()
        ]);
        
        // Create own company data
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::today()
        ]);
        
        // Should only see data from user's company
        $response = $this->getJson('/api/dashboard/overview?period=today');
        
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('data.new_customers.count'));
    }
}