<?php

namespace Tests\Unit\Services\MCP;

use Tests\TestCase;
use App\Services\MCP\EventMCPServer;
use App\Models\Company;
use App\Models\User;
use App\Models\EventLog;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

class EventMCPServerTest extends TestCase
{
    use RefreshDatabase;
    
    protected EventMCPServer $mcp;
    protected Company $company;
    protected User $user;
    protected Branch $branch;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mcp = new EventMCPServer();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        
        // Set company context
        app()->instance('currentCompany', $this->company);
        
        // Set user context
        $this->actingAs($this->user);
    }
    
    public function test_get_tools_returns_correct_structure()
    {
        $tools = $this->mcp->getTools();
        
        $this->assertIsArray($tools);
        $this->assertCount(6, $tools);
        
        $toolNames = array_column($tools, 'name');
        $expectedTools = [
            'logEvent',
            'getEvents',
            'getEventDetails',
            'getEventStats',
            'searchEvents',
            'getAuditTrail'
        ];
        
        foreach ($expectedTools as $expectedTool) {
            $this->assertContains($expectedTool, $toolNames);
        }
    }
    
    public function test_log_event_success()
    {
        Event::fake();
        
        $result = $this->mcp->executeTool('logEvent', [
            'type' => 'appointment.created',
            'entity_type' => 'appointment',
            'entity_id' => 123,
            'data' => [
                'customer_name' => 'John Doe',
                'service' => 'Haircut',
                'date' => '2025-08-15',
                'time' => '10:00'
            ],
            'severity' => 'info'
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('event', $result);
        $this->assertEquals('appointment.created', $result['event']['type']);
        $this->assertEquals('info', $result['event']['severity']);
        
        $this->assertDatabaseHas('event_logs', [
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => 'appointment.created',
            'entity_type' => 'appointment',
            'entity_id' => '123',
            'severity' => 'info'
        ]);
        
        Event::assertDispatched(\App\Events\EventLogged::class);
    }
    
    public function test_log_event_with_different_severities()
    {
        $severities = ['debug', 'info', 'warning', 'error', 'critical'];
        
        foreach ($severities as $severity) {
            $result = $this->mcp->executeTool('logEvent', [
                'type' => "test.event.{$severity}",
                'entity_type' => 'test',
                'entity_id' => 1,
                'data' => ['test' => true],
                'severity' => $severity
            ]);
            
            $this->assertTrue($result['success']);
            $this->assertEquals($severity, $result['event']['severity']);
        }
        
        $this->assertEquals(5, EventLog::count());
    }
    
    public function test_get_events_with_filters()
    {
        // Create various events
        EventLog::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'type' => 'appointment.created',
            'severity' => 'info',
            'created_at' => Carbon::now()->subDays(2)
        ]);
        
        EventLog::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'type' => 'customer.updated',
            'severity' => 'info',
            'created_at' => Carbon::now()->subDay()
        ]);
        
        EventLog::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'type' => 'payment.failed',
            'severity' => 'error',
            'created_at' => Carbon::now()
        ]);
        
        // Test without filters
        $result = $this->mcp->executeTool('getEvents', []);
        
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals(10, $result['pagination']['total']);
        
        // Test with type filter
        $result = $this->mcp->executeTool('getEvents', [
            'type' => 'appointment.created'
        ]);
        
        $this->assertEquals(5, $result['pagination']['total']);
        
        // Test with severity filter
        $result = $this->mcp->executeTool('getEvents', [
            'severity' => 'error'
        ]);
        
        $this->assertEquals(2, $result['pagination']['total']);
        
        // Test with date range
        $result = $this->mcp->executeTool('getEvents', [
            'date_from' => Carbon::now()->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d')
        ]);
        
        $this->assertEquals(2, $result['pagination']['total']);
    }
    
    public function test_get_event_details()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        $event = EventLog::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => 'customer.created',
            'entity_type' => 'customer',
            'entity_id' => $customer->id,
            'data' => [
                'customer_name' => $customer->full_name,
                'email' => $customer->email,
                'phone' => $customer->phone
            ],
            'severity' => 'info',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0'
        ]);
        
        $result = $this->mcp->executeTool('getEventDetails', [
            'event_id' => $event->id
        ]);
        
        $this->assertEquals($event->id, $result['id']);
        $this->assertEquals('customer.created', $result['type']);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('entity', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('192.168.1.1', $result['ip_address']);
    }
    
    public function test_get_event_stats()
    {
        // Create events for statistics
        EventLog::factory()->count(20)->create([
            'company_id' => $this->company->id,
            'type' => 'appointment.created',
            'severity' => 'info',
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        EventLog::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'type' => 'appointment.cancelled',
            'severity' => 'warning',
            'created_at' => Carbon::now()->subDays(3)
        ]);
        
        EventLog::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'type' => 'payment.failed',
            'severity' => 'error',
            'created_at' => Carbon::now()->subDays(1)
        ]);
        
        EventLog::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'type' => 'system.error',
            'severity' => 'critical',
            'created_at' => Carbon::now()
        ]);
        
        $result = $this->mcp->executeTool('getEventStats', [
            'period' => 'last_7_days'
        ]);
        
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('events_by_type', $result);
        $this->assertArrayHasKey('events_by_severity', $result);
        $this->assertArrayHasKey('events_by_day', $result);
        $this->assertArrayHasKey('top_users', $result);
        $this->assertArrayHasKey('trends', $result);
        
        $this->assertEquals(37, $result['total_events']);
        $this->assertEquals(20, $result['events_by_type']['appointment.created']);
        $this->assertEquals(5, $result['events_by_severity']['error']);
        $this->assertEquals(2, $result['events_by_severity']['critical']);
    }
    
    public function test_search_events()
    {
        // Create events with searchable content
        $event1 = EventLog::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'customer.note.added',
            'data' => [
                'note' => 'Customer requested special discount',
                'customer' => 'John Doe'
            ]
        ]);
        
        $event2 = EventLog::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'appointment.note.added',
            'data' => [
                'note' => 'Regular checkup appointment',
                'service' => 'Dental Cleaning'
            ]
        ]);
        
        $event3 = EventLog::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'payment.note.added',
            'data' => [
                'note' => 'Discount applied for loyalty',
                'amount' => 50
            ]
        ]);
        
        // Search for "discount"
        $result = $this->mcp->executeTool('searchEvents', [
            'query' => 'discount'
        ]);
        
        $this->assertEquals(2, $result['total']);
        $foundIds = array_column($result['results'], 'id');
        $this->assertContains($event1->id, $foundIds);
        $this->assertContains($event3->id, $foundIds);
        
        // Search for "appointment"
        $result = $this->mcp->executeTool('searchEvents', [
            'query' => 'appointment',
            'search_in' => ['type', 'data']
        ]);
        
        $this->assertEquals(1, $result['total']);
        $this->assertEquals($event2->id, $result['results'][0]['id']);
    }
    
    public function test_get_audit_trail()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id
        ]);
        
        // Create audit trail events
        EventLog::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => 'appointment.created',
            'entity_type' => 'appointment',
            'entity_id' => $appointment->id,
            'created_at' => Carbon::now()->subHours(3)
        ]);
        
        EventLog::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => 'appointment.updated',
            'entity_type' => 'appointment',
            'entity_id' => $appointment->id,
            'data' => ['changes' => ['time' => ['10:00', '14:00']]],
            'created_at' => Carbon::now()->subHours(2)
        ]);
        
        EventLog::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => 'appointment.confirmed',
            'entity_type' => 'appointment',
            'entity_id' => $appointment->id,
            'created_at' => Carbon::now()->subHour()
        ]);
        
        $result = $this->mcp->executeTool('getAuditTrail', [
            'entity_type' => 'appointment',
            'entity_id' => $appointment->id
        ]);
        
        $this->assertArrayHasKey('entity', $result);
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('timeline', $result);
        $this->assertCount(3, $result['events']);
        
        // Verify events are in chronological order
        $types = array_column($result['events'], 'type');
        $this->assertEquals(['appointment.created', 'appointment.updated', 'appointment.confirmed'], $types);
    }
    
    public function test_event_log_with_context()
    {
        // Set request context
        request()->headers->set('User-Agent', 'Test Browser');
        request()->server->set('REMOTE_ADDR', '10.0.0.1');
        
        $result = $this->mcp->executeTool('logEvent', [
            'type' => 'test.with.context',
            'entity_type' => 'test',
            'entity_id' => 1,
            'data' => ['test' => true]
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('10.0.0.1', $result['event']['ip_address']);
        $this->assertEquals('Test Browser', $result['event']['user_agent']);
    }
    
    public function test_execute_tool_with_invalid_tool_name()
    {
        $result = $this->mcp->executeTool('invalidTool', []);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unknown tool', $result['error']);
    }
    
    public function test_event_grouping_and_aggregation()
    {
        // Create events for the same entity over time
        $customerId = 123;
        
        EventLog::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'customer.created',
            'entity_type' => 'customer',
            'entity_id' => $customerId,
            'created_at' => Carbon::now()->subDays(30)
        ]);
        
        EventLog::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'type' => 'customer.updated',
            'entity_type' => 'customer',
            'entity_id' => $customerId,
            'created_at' => Carbon::now()->subDays(20)
        ]);
        
        EventLog::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'type' => 'customer.appointment.booked',
            'entity_type' => 'customer',
            'entity_id' => $customerId,
            'created_at' => Carbon::now()->subDays(10)
        ]);
        
        $result = $this->mcp->executeTool('getEvents', [
            'entity_type' => 'customer',
            'entity_id' => $customerId,
            'group_by' => 'type'
        ]);
        
        $this->assertArrayHasKey('grouped', $result);
        $this->assertEquals(1, $result['grouped']['customer.created']);
        $this->assertEquals(5, $result['grouped']['customer.updated']);
        $this->assertEquals(10, $result['grouped']['customer.appointment.booked']);
    }
    
    public function test_event_severity_escalation()
    {
        // Log multiple errors in short time
        for ($i = 0; $i < 5; $i++) {
            $this->mcp->executeTool('logEvent', [
                'type' => 'payment.failed',
                'entity_type' => 'payment',
                'entity_id' => $i + 1,
                'severity' => 'error',
                'data' => ['amount' => 100, 'reason' => 'Insufficient funds']
            ]);
        }
        
        // Check if pattern detection works
        $result = $this->mcp->executeTool('getEventStats', [
            'period' => 'last_hour'
        ]);
        
        $this->assertArrayHasKey('alerts', $result);
        $this->assertArrayHasKey('patterns', $result);
        $this->assertContains('payment.failed', array_column($result['patterns'], 'type'));
    }
}