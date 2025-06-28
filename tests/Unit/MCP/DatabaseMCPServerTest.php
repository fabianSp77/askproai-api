<?php

namespace Tests\Unit\MCP;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Services\MCP\MCPError;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use App\Services\MCP\Servers\DatabaseMCPServer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseMCPServerTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseMCPServer $server;
    private Company $company;
    private Branch $branch;
    private Staff $staff;
    private Service $service;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->server = new DatabaseMCPServer();
        
        // Create test data
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'is_active' => true
        ]);
        
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Dr. Test',
            'email' => 'dr.test@example.com'
        ]);
        
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Consultation',
            'duration' => 30,
            'price' => 50.00
        ]);
        
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe',
            'phone' => '+49 30 12345678',
            'email' => 'john@example.com'
        ]);
    }

    #[Test]

    public function test_can_handle_database_methods()
    {
        $this->assertTrue($this->server->canHandle('customers.find'));
        $this->assertTrue($this->server->canHandle('appointments.create'));
        $this->assertTrue($this->server->canHandle('calls.list'));
        $this->assertTrue($this->server->canHandle('analytics.revenue'));
        $this->assertFalse($this->server->canHandle('calcom.sync'));
        $this->assertFalse($this->server->canHandle('unknown.method'));
    }

    #[Test]

    public function test_find_customer_by_phone()
    {
        $request = new MCPRequest([
            'method' => 'customers.find',
            'params' => [
                'phone' => '+49 30 12345678',
                'company_id' => $this->company->id
            ]
        ]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertEquals('John Doe', $response->getData()['name']);
        $this->assertEquals('john@example.com', $response->getData()['email']);
    }

    #[Test]

    public function test_find_customer_not_found()
    {
        $request = new MCPRequest([
            'method' => 'customers.find',
            'params' => [
                'phone' => '+49 30 99999999',
                'company_id' => $this->company->id
            ]
        ]);
        
        $response = $this->server->execute($request);
        
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('NOT_FOUND', $response->getError()->getCode());
    }

    #[Test]

    public function test_create_appointment()
    {
        $request = new MCPRequest([
            'method' => 'appointments.create',
            'params' => [
                'customer_id' => $this->customer->id,
                'staff_id' => $this->staff->id,
                'service_id' => $this->service->id,
                'branch_id' => $this->branch->id,
                'start_time' => '2025-06-25 10:00:00',
                'end_time' => '2025-06-25 10:30:00',
                'status' => 'scheduled'
            ]
        ]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertIsNumeric($response->getData()['id']);
        $this->assertEquals('scheduled', $response->getData()['status']);
        
        // Verify in database
        $this->assertDatabaseHas('appointments', [
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id
        ]);
    }

    #[Test]

    public function test_check_availability()
    {
        // Create some existing appointments
        Appointment::factory()->create([
            'staff_id' => $this->staff->id,
            'start_time' => '2025-06-25 09:00:00',
            'end_time' => '2025-06-25 10:00:00',
            'status' => 'scheduled'
        ]);
        
        Appointment::factory()->create([
            'staff_id' => $this->staff->id,
            'start_time' => '2025-06-25 11:00:00',
            'end_time' => '2025-06-25 12:00:00',
            'status' => 'scheduled'
        ]);
        
        $request = new MCPRequest([
            'method' => 'appointments.check_availability',
            'params' => [
                'staff_id' => $this->staff->id,
                'date' => '2025-06-25',
                'duration' => 30
            ]
        ]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertIsArray($response->getData()['available_slots']);
        $this->assertContains('10:00', $response->getData()['available_slots']);
        $this->assertContains('10:30', $response->getData()['available_slots']);
        $this->assertNotContains('09:00', $response->getData()['available_slots']);
        $this->assertNotContains('11:00', $response->getData()['available_slots']);
    }

    #[Test]

    public function test_list_recent_calls()
    {
        // Create test calls
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subMinutes(30)
        ]);
        
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subDays(2)
        ]);
        
        $request = new MCPRequest([
            'method' => 'calls.list',
            'params' => [
                'company_id' => $this->company->id,
                'limit' => 10,
                'days' => 1
            ]
        ]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertCount(5, $response->getData()['calls']);
        $this->assertEquals(5, $response->getData()['total']);
    }

    #[Test]

    public function test_analytics_revenue_calculation()
    {
        // Create completed appointments with services
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'service_id' => $this->service->id,
            'status' => 'completed',
            'start_time' => Carbon::now()->subDays(5),
            'price' => 50.00
        ]);
        
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'service_id' => $this->service->id,
            'status' => 'completed',
            'start_time' => Carbon::now()->subDays(15),
            'price' => 50.00
        ]);
        
        $request = new MCPRequest([
            'method' => 'analytics.revenue',
            'params' => [
                'company_id' => $this->company->id,
                'period' => 'last_7_days'
            ]
        ]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(150.00, $response->getData()['total_revenue']);
        $this->assertEquals(3, $response->getData()['appointment_count']);
        $this->assertEquals(50.00, $response->getData()['average_revenue']);
    }

    #[Test]

    public function test_database_transaction_rollback_on_error()
    {
        $request = new MCPRequest([
            'method' => 'appointments.create',
            'params' => [
                'customer_id' => 99999, // Non-existent customer
                'staff_id' => $this->staff->id,
                'service_id' => $this->service->id,
                'branch_id' => $this->branch->id,
                'start_time' => '2025-06-25 10:00:00',
                'end_time' => '2025-06-25 10:30:00'
            ]
        ]);
        
        $appointmentCountBefore = Appointment::count();
        
        $response = $this->server->execute($request);
        
        $this->assertFalse($response->isSuccess());
        $this->assertEquals($appointmentCountBefore, Appointment::count());
    }

    #[Test]

    public function test_sql_injection_prevention()
    {
        $request = new MCPRequest([
            'method' => 'customers.find',
            'params' => [
                'phone' => "'; DROP TABLE customers; --",
                'company_id' => $this->company->id
            ]
        ]);
        
        $response = $this->server->execute($request);
        
        // Should handle safely without SQL injection
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('NOT_FOUND', $response->getError()->getCode());
        
        // Verify table still exists
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('customers'));
    }

    #[Test]

    public function test_bulk_operations_performance()
    {
        // Create many customers
        $customerIds = [];
        for ($i = 0; $i < 100; $i++) {
            $customerIds[] = Customer::factory()->create([
                'company_id' => $this->company->id
            ])->id;
        }
        
        $request = new MCPRequest([
            'method' => 'customers.bulk_fetch',
            'params' => [
                'ids' => $customerIds,
                'company_id' => $this->company->id
            ]
        ]);
        
        $startTime = microtime(true);
        $response = $this->server->execute($request);
        $executionTime = microtime(true) - $startTime;
        
        $this->assertTrue($response->isSuccess());
        $this->assertCount(100, $response->getData()['customers']);
        $this->assertLessThan(0.5, $executionTime); // Should complete in under 500ms
    }

    #[Test]

    public function test_complex_query_with_joins()
    {
        // Create appointments with relationships
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'status' => 'completed'
        ]);
        
        $request = new MCPRequest([
            'method' => 'reports.customer_history',
            'params' => [
                'customer_id' => $this->customer->id,
                'include' => ['staff', 'service', 'branch']
            ]
        ]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertCount(5, $response->getData()['appointments']);
        
        // Verify relationships are loaded
        $firstAppointment = $response->getData()['appointments'][0];
        $this->assertEquals('Dr. Test', $firstAppointment['staff']['name']);
        $this->assertEquals('Consultation', $firstAppointment['service']['name']);
        $this->assertEquals('Main Branch', $firstAppointment['branch']['name']);
    }

    #[Test]

    public function test_database_connection_pool_handling()
    {
        $requests = [];
        
        // Create multiple concurrent requests
        for ($i = 0; $i < 20; $i++) {
            $requests[] = new MCPRequest([
                'method' => 'customers.find',
                'params' => [
                    'phone' => $this->customer->phone,
                    'company_id' => $this->company->id
                ]
            ]);
        }
        
        $responses = [];
        foreach ($requests as $request) {
            $responses[] = $this->server->execute($request);
        }
        
        // All requests should succeed
        foreach ($responses as $response) {
            $this->assertTrue($response->isSuccess());
        }
        
        // Verify connection count didn't explode
        $connectionCount = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
        $this->assertLessThan(50, $connectionCount);
    }

    #[Test]

    public function test_data_validation_errors()
    {
        $testCases = [
            [
                'method' => 'appointments.create',
                'params' => ['customer_id' => 'not-a-number'],
                'expectedError' => 'VALIDATION_ERROR'
            ],
            [
                'method' => 'appointments.create',
                'params' => ['start_time' => 'invalid-date'],
                'expectedError' => 'VALIDATION_ERROR'
            ],
            [
                'method' => 'customers.find',
                'params' => [], // Missing required phone
                'expectedError' => 'MISSING_PARAMS'
            ]
        ];
        
        foreach ($testCases as $testCase) {
            $request = new MCPRequest($testCase);
            $response = $this->server->execute($request);
            
            $this->assertFalse($response->isSuccess());
            $this->assertEquals($testCase['expectedError'], $response->getError()->getCode());
        }
    }
}