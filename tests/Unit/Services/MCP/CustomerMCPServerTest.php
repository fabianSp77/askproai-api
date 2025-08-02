<?php

namespace Tests\Unit\Services\MCP;

use Tests\TestCase;
use App\Services\MCP\CustomerMCPServer;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

class CustomerMCPServerTest extends TestCase
{
    use RefreshDatabase;
    
    protected CustomerMCPServer $mcp;
    protected Company $company;
    protected Branch $branch;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mcp = new CustomerMCPServer();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        
        // Set company context
        app()->instance('currentCompany', $this->company);
    }
    
    public function test_get_tools_returns_correct_structure()
    {
        $tools = $this->mcp->getTools();
        
        $this->assertIsArray($tools);
        $this->assertCount(8, $tools);
        
        $toolNames = array_column($tools, 'name');
        $expectedTools = [
            'listCustomers',
            'getCustomerDetails',
            'createCustomer',
            'updateCustomer',
            'searchCustomers',
            'getCustomerHistory',
            'getCustomerStats',
            'mergeCustomers'
        ];
        
        foreach ($expectedTools as $expectedTool) {
            $this->assertContains($expectedTool, $toolNames);
        }
    }
    
    public function test_list_customers_with_pagination()
    {
        // Create customers
        Customer::factory()->count(25)->create([
            'company_id' => $this->company->id
        ]);
        
        // Test first page
        $result = $this->mcp->executeTool('listCustomers', [
            'page' => 1,
            'per_page' => 10
        ]);
        
        $this->assertArrayHasKey('customers', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(10, $result['customers']);
        $this->assertEquals(25, $result['pagination']['total']);
        $this->assertEquals(3, $result['pagination']['last_page']);
        
        // Test with filters
        $vipCustomers = Customer::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'tags' => ['vip']
        ]);
        
        $result = $this->mcp->executeTool('listCustomers', [
            'tags' => ['vip']
        ]);
        
        $this->assertEquals(5, $result['pagination']['total']);
    }
    
    public function test_get_customer_details_with_relationships()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'notes' => 'VIP customer',
            'tags' => ['vip', 'regular']
        ]);
        
        // Create related data
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id
        ]);
        
        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id
        ]);
        
        $result = $this->mcp->executeTool('getCustomerDetails', [
            'customer_id' => $customer->id
        ]);
        
        $this->assertEquals($customer->id, $result['id']);
        $this->assertEquals('John', $result['first_name']);
        $this->assertEquals('john@example.com', $result['email']);
        $this->assertArrayHasKey('tags', $result);
        $this->assertContains('vip', $result['tags']);
        $this->assertArrayHasKey('stats', $result);
        $this->assertEquals(3, $result['stats']['total_appointments']);
        $this->assertEquals(2, $result['stats']['total_calls']);
    }
    
    public function test_create_customer_success()
    {
        Event::fake();
        
        $result = $this->mcp->executeTool('createCustomer', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '+1987654321',
            'date_of_birth' => '1990-05-15',
            'address' => '123 Main St',
            'city' => 'New York',
            'postal_code' => '10001',
            'notes' => 'New customer from phone call',
            'tags' => ['new', 'phone-lead']
        ]);
        
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('customer', $result);
        $this->assertEquals('Jane', $result['customer']['first_name']);
        $this->assertEquals('jane@example.com', $result['customer']['email']);
        
        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'email' => 'jane@example.com',
            'phone' => '+1987654321'
        ]);
        
        Event::assertDispatched(\App\Events\CustomerCreated::class);
    }
    
    public function test_create_customer_with_duplicate_email()
    {
        // Create existing customer
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'existing@example.com'
        ]);
        
        $result = $this->mcp->executeTool('createCustomer', [
            'first_name' => 'New',
            'last_name' => 'Customer',
            'email' => 'existing@example.com',
            'phone' => '+1234567890'
        ]);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('already exists', $result['error']);
    }
    
    public function test_update_customer()
    {
        Event::fake();
        
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'old@example.com'
        ]);
        
        $result = $this->mcp->executeTool('updateCustomer', [
            'customer_id' => $customer->id,
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => 'new@example.com',
            'tags' => ['updated']
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('New', $result['customer']['first_name']);
        $this->assertEquals('new@example.com', $result['customer']['email']);
        $this->assertContains('updated', $result['customer']['tags']);
        
        Event::assertDispatched(\App\Events\CustomerUpdated::class);
    }
    
    public function test_search_customers()
    {
        // Create customers with searchable data
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john.smith@example.com',
            'phone' => '+1234567890'
        ]);
        
        $customer2 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Jane',
            'last_name' => 'Johnson',
            'email' => 'jane@example.com',
            'phone' => '+1987654321'
        ]);
        
        $customer3 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'email' => 'bob@example.com',
            'phone' => '+1555555555'
        ]);
        
        // Search by name
        $result = $this->mcp->executeTool('searchCustomers', [
            'query' => 'Smith'
        ]);
        
        $this->assertEquals(2, $result['total']);
        $foundIds = array_column($result['results'], 'id');
        $this->assertContains($customer1->id, $foundIds);
        $this->assertContains($customer3->id, $foundIds);
        
        // Search by email
        $result = $this->mcp->executeTool('searchCustomers', [
            'query' => 'jane@example.com'
        ]);
        
        $this->assertEquals(1, $result['total']);
        $this->assertEquals($customer2->id, $result['results'][0]['id']);
        
        // Search by phone
        $result = $this->mcp->executeTool('searchCustomers', [
            'query' => '555555'
        ]);
        
        $this->assertEquals(1, $result['total']);
        $this->assertEquals($customer3->id, $result['results'][0]['id']);
    }
    
    public function test_get_customer_history()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id
        ]);
        
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        // Create appointments
        $appointment1 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'status' => 'completed',
            'starts_at' => Carbon::now()->subDays(10)
        ]);
        
        $appointment2 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'status' => 'scheduled',
            'starts_at' => Carbon::now()->addDays(5)
        ]);
        
        // Create calls
        $call1 = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'created_at' => Carbon::now()->subDays(7)
        ]);
        
        $result = $this->mcp->executeTool('getCustomerHistory', [
            'customer_id' => $customer->id,
            'include_appointments' => true,
            'include_calls' => true
        ]);
        
        $this->assertArrayHasKey('appointments', $result);
        $this->assertArrayHasKey('calls', $result);
        $this->assertArrayHasKey('timeline', $result);
        $this->assertCount(2, $result['appointments']);
        $this->assertCount(1, $result['calls']);
        $this->assertCount(3, $result['timeline']); // Combined timeline
        
        // Verify timeline is sorted by date
        $dates = array_column($result['timeline'], 'date');
        $sortedDates = $dates;
        rsort($sortedDates);
        $this->assertEquals($sortedDates, $dates);
    }
    
    public function test_get_customer_stats()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subMonths(6)
        ]);
        
        $service = Service::factory()->create([
            'company_id' => $this->company->id,
            'price' => 100
        ]);
        
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        // Create appointments with different statuses
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'status' => 'completed',
            'price' => 100
        ]);
        
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'status' => 'cancelled'
        ]);
        
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'status' => 'no_show'
        ]);
        
        // Create calls
        Call::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'duration' => 180
        ]);
        
        $result = $this->mcp->executeTool('getCustomerStats', [
            'customer_id' => $customer->id
        ]);
        
        $this->assertArrayHasKey('lifetime_value', $result);
        $this->assertArrayHasKey('total_appointments', $result);
        $this->assertArrayHasKey('completed_appointments', $result);
        $this->assertArrayHasKey('cancelled_appointments', $result);
        $this->assertArrayHasKey('no_show_appointments', $result);
        $this->assertArrayHasKey('cancellation_rate', $result);
        $this->assertArrayHasKey('no_show_rate', $result);
        $this->assertArrayHasKey('total_calls', $result);
        $this->assertArrayHasKey('average_call_duration', $result);
        $this->assertArrayHasKey('customer_since', $result);
        $this->assertArrayHasKey('last_appointment', $result);
        $this->assertArrayHasKey('last_call', $result);
        
        $this->assertEquals(500, $result['lifetime_value']); // 5 completed * 100
        $this->assertEquals(8, $result['total_appointments']);
        $this->assertEquals(5, $result['completed_appointments']);
        $this->assertEquals(25, $result['cancellation_rate']); // 2/8 * 100
        $this->assertEquals(12.5, $result['no_show_rate']); // 1/8 * 100
        $this->assertEquals(10, $result['total_calls']);
        $this->assertEquals(180, $result['average_call_duration']);
    }
    
    public function test_merge_customers()
    {
        Event::fake();
        
        // Create primary customer with data
        $primaryCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890'
        ]);
        
        // Create duplicate customer with some appointments
        $duplicateCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Johnny',
            'last_name' => 'Doe',
            'email' => null,
            'phone' => '+1234567890'
        ]);
        
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        // Create data for duplicate customer
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $duplicateCustomer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id
        ]);
        
        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $duplicateCustomer->id
        ]);
        
        $result = $this->mcp->executeTool('mergeCustomers', [
            'primary_customer_id' => $primaryCustomer->id,
            'duplicate_customer_id' => $duplicateCustomer->id
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('merged_customer', $result);
        $this->assertArrayHasKey('transferred_data', $result);
        $this->assertEquals(3, $result['transferred_data']['appointments']);
        $this->assertEquals(2, $result['transferred_data']['calls']);
        
        // Verify data was transferred
        $this->assertDatabaseHas('appointments', [
            'customer_id' => $primaryCustomer->id
        ]);
        
        $this->assertDatabaseHas('calls', [
            'customer_id' => $primaryCustomer->id
        ]);
        
        // Verify duplicate was soft deleted
        $this->assertSoftDeleted('customers', [
            'id' => $duplicateCustomer->id
        ]);
        
        Event::assertDispatched(\App\Events\CustomersMerged::class);
    }
    
    public function test_execute_tool_with_invalid_tool_name()
    {
        $result = $this->mcp->executeTool('invalidTool', []);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unknown tool', $result['error']);
    }
    
    public function test_customer_validation_errors()
    {
        // Test invalid email
        $result = $this->mcp->executeTool('createCustomer', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'invalid-email'
        ]);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('valid email', $result['error']);
        
        // Test missing required fields
        $result = $this->mcp->executeTool('createCustomer', [
            'email' => 'test@example.com'
        ]);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('required', $result['error']);
    }
}