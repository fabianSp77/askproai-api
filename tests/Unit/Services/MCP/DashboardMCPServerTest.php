<?php

namespace Tests\Unit\Services\MCP;

use Tests\TestCase;
use App\Services\MCP\DashboardMCPServer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardMCPServerTest extends TestCase
{
    use RefreshDatabase;
    
    protected DashboardMCPServer $mcp;
    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mcp = new DashboardMCPServer();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch1 = Branch::factory()->create(['company_id' => $this->company->id, 'name' => 'Main Branch']);
        $this->branch2 = Branch::factory()->create(['company_id' => $this->company->id, 'name' => 'Second Branch']);
        
        // Set company context
        app()->instance('currentCompany', $this->company);
    }
    
    public function test_get_tools_returns_correct_structure()
    {
        $tools = $this->mcp->getTools();
        
        $this->assertIsArray($tools);
        $this->assertCount(7, $tools);
        
        $toolNames = array_column($tools, 'name');
        $expectedTools = [
            'getOverviewStats',
            'getRevenueMetrics',
            'getAppointmentMetrics',
            'getCustomerMetrics',
            'getStaffPerformance',
            'getBranchComparison',
            'getRecentActivity'
        ];
        
        foreach ($expectedTools as $expectedTool) {
            $this->assertContains($expectedTool, $toolNames);
        }
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
        
        $result = $this->mcp->executeTool('getOverviewStats', [
            'period' => 'today'
        ]);
        
        $this->assertArrayHasKey('appointments', $result);
        $this->assertArrayHasKey('revenue', $result);
        $this->assertArrayHasKey('calls', $result);
        $this->assertArrayHasKey('new_customers', $result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('comparisons', $result);
        
        $this->assertEquals(5, $result['appointments']['scheduled']);
        $this->assertEquals(10, $result['calls']['total']);
        $this->assertEquals(3, $result['new_customers']['count']);
        
        // Test with different period
        $result = $this->mcp->executeTool('getOverviewStats', [
            'period' => 'this_week'
        ]);
        
        $this->assertEquals(8, $result['appointments']['total']); // 5 today + 3 yesterday
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
        
        // Create invoices
        Invoice::factory()->count(15)->create([
            'company_id' => $this->company->id,
            'total_amount' => 100,
            'paid_amount' => 100,
            'status' => 'paid',
            'paid_at' => Carbon::now()->subDays(3)
        ]);
        
        $result = $this->mcp->executeTool('getRevenueMetrics', [
            'period' => 'last_7_days',
            'group_by' => 'day'
        ]);
        
        $this->assertArrayHasKey('total_revenue', $result);
        $this->assertArrayHasKey('revenue_by_period', $result);
        $this->assertArrayHasKey('revenue_by_service', $result);
        $this->assertArrayHasKey('revenue_by_branch', $result);
        $this->assertArrayHasKey('average_transaction_value', $result);
        $this->assertArrayHasKey('growth_rate', $result);
        
        $this->assertEquals(2000, $result['total_revenue']); // (10 * 100) + (5 * 200)
        $this->assertEquals(1000, $result['revenue_by_branch'][$this->branch1->id]['total']);
        $this->assertEquals(1000, $result['revenue_by_branch'][$this->branch2->id]['total']);
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
        
        $result = $this->mcp->executeTool('getAppointmentMetrics', [
            'period' => 'last_7_days'
        ]);
        
        $this->assertArrayHasKey('total_appointments', $result);
        $this->assertArrayHasKey('appointments_by_status', $result);
        $this->assertArrayHasKey('completion_rate', $result);
        $this->assertArrayHasKey('cancellation_rate', $result);
        $this->assertArrayHasKey('no_show_rate', $result);
        $this->assertArrayHasKey('appointments_by_day', $result);
        $this->assertArrayHasKey('appointments_by_hour', $result);
        $this->assertArrayHasKey('popular_services', $result);
        $this->assertArrayHasKey('popular_time_slots', $result);
        
        $this->assertEquals(28, $result['total_appointments']);
        $this->assertEquals(20, $result['appointments_by_status']['completed']);
        $this->assertEquals(71.43, $result['completion_rate']); // 20/28 * 100
        $this->assertEquals(17.86, $result['cancellation_rate']); // 5/28 * 100
        $this->assertEquals(10.71, $result['no_show_rate']); // 3/28 * 100
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
        
        $result = $this->mcp->executeTool('getCustomerMetrics', [
            'period' => 'last_30_days'
        ]);
        
        $this->assertArrayHasKey('total_customers', $result);
        $this->assertArrayHasKey('new_customers', $result);
        $this->assertArrayHasKey('active_customers', $result);
        $this->assertArrayHasKey('retention_rate', $result);
        $this->assertArrayHasKey('acquisition_rate', $result);
        $this->assertArrayHasKey('customers_by_day', $result);
        $this->assertArrayHasKey('top_customers', $result);
        
        $this->assertEquals(26, $result['total_customers']); // 10 + 5 + 3 + 8
        $this->assertEquals(8, $result['new_customers']); // 5 + 3 (last 30 days)
        $this->assertEquals(8, $result['active_customers']); // Customers with appointments
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
        
        $result = $this->mcp->executeTool('getStaffPerformance', [
            'period' => 'last_7_days',
            'branch_id' => $this->branch1->id
        ]);
        
        $this->assertArrayHasKey('staff_metrics', $result);
        $this->assertArrayHasKey('top_performers', $result);
        $this->assertArrayHasKey('average_metrics', $result);
        
        $staff1Metrics = collect($result['staff_metrics'])->firstWhere('staff_id', $staff1->id);
        $this->assertEquals(18, $staff1Metrics['total_appointments']);
        $this->assertEquals(15, $staff1Metrics['completed_appointments']);
        $this->assertEquals(1500, $staff1Metrics['revenue_generated']);
        $this->assertEquals(83.33, $staff1Metrics['completion_rate']);
        
        // Verify top performer
        $this->assertEquals($staff1->id, $result['top_performers']['by_revenue'][0]['staff_id']);
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
        
        $result = $this->mcp->executeTool('getBranchComparison', [
            'period' => 'last_30_days'
        ]);
        
        $this->assertArrayHasKey('branches', $result);
        $this->assertArrayHasKey('rankings', $result);
        $this->assertCount(2, $result['branches']);
        
        $branch1Data = collect($result['branches'])->firstWhere('branch_id', $this->branch1->id);
        $this->assertEquals(20, $branch1Data['appointments']);
        $this->assertEquals(2000, $branch1Data['revenue']);
        $this->assertEquals(15, $branch1Data['customers']);
        
        // Verify rankings
        $this->assertEquals($this->branch1->id, $result['rankings']['by_revenue'][0]['branch_id']);
        $this->assertEquals($this->branch1->id, $result['rankings']['by_appointments'][0]['branch_id']);
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
        
        $result = $this->mcp->executeTool('getRecentActivity', [
            'limit' => 10,
            'include_types' => ['appointments', 'calls', 'customers']
        ]);
        
        $this->assertArrayHasKey('activities', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(4, $result['activities']);
        
        // Verify activities are sorted by time (most recent first)
        $timestamps = array_map(function ($activity) {
            return Carbon::parse($activity['timestamp'])->timestamp;
        }, $result['activities']);
        
        $sortedTimestamps = $timestamps;
        rsort($sortedTimestamps);
        $this->assertEquals($sortedTimestamps, $timestamps);
        
        // Verify activity types
        $types = array_column($result['activities'], 'type');
        $this->assertContains('new_customer', $types);
        $this->assertContains('new_call', $types);
        $this->assertContains('appointment_completed', $types);
        $this->assertContains('appointment_created', $types);
    }
    
    public function test_caching_functionality()
    {
        // Create initial data
        Customer::factory()->count(5)->create([
            'company_id' => $this->company->id
        ]);
        
        // First call - should hit database
        $result1 = $this->mcp->executeTool('getOverviewStats', [
            'period' => 'today',
            'use_cache' => true
        ]);
        
        // Add more customers
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id
        ]);
        
        // Second call - should return cached data
        $result2 = $this->mcp->executeTool('getOverviewStats', [
            'period' => 'today',
            'use_cache' => true
        ]);
        
        $this->assertEquals($result1['new_customers']['count'], $result2['new_customers']['count']);
        
        // Clear cache and verify updated data
        Cache::forget('dashboard:overview:today:' . $this->company->id);
        
        $result3 = $this->mcp->executeTool('getOverviewStats', [
            'period' => 'today',
            'use_cache' => true
        ]);
        
        $this->assertNotEquals($result1['new_customers']['count'], $result3['new_customers']['count']);
    }
    
    public function test_execute_tool_with_invalid_tool_name()
    {
        $result = $this->mcp->executeTool('invalidTool', []);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unknown tool', $result['error']);
    }
}