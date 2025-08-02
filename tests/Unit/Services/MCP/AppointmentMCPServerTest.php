<?php

namespace Tests\Unit\Services\MCP;

use Tests\TestCase;
use App\Services\MCP\AppointmentMCPServer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

class AppointmentMCPServerTest extends TestCase
{
    use RefreshDatabase;
    
    protected AppointmentMCPServer $mcp;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected Staff $staff;
    protected Service $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mcp = new AppointmentMCPServer();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $this->service = Service::factory()->create(['company_id' => $this->company->id]);
        
        // Associate staff with service
        $this->staff->services()->attach($this->service);
        
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
            'listAppointments',
            'getAppointmentDetails',
            'checkAvailability',
            'createAppointment',
            'updateAppointment',
            'cancelAppointment',
            'rescheduleAppointment',
            'getAppointmentStats'
        ];
        
        foreach ($expectedTools as $expectedTool) {
            $this->assertContains($expectedTool, $toolNames);
        }
    }
    
    public function test_list_appointments_with_filters()
    {
        // Create appointments
        $appointment1 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::now()->addDays(1),
            'status' => 'scheduled'
        ]);
        
        $appointment2 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::now()->addDays(2),
            'status' => 'confirmed'
        ]);
        
        // Test without filters
        $result = $this->mcp->executeTool('listAppointments', []);
        
        $this->assertArrayHasKey('appointments', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('filters_applied', $result);
        $this->assertEquals(2, $result['total']);
        
        // Test with date range filter
        $result = $this->mcp->executeTool('listAppointments', [
            'date_from' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'date_to' => Carbon::now()->addDays(1)->format('Y-m-d')
        ]);
        
        $this->assertEquals(1, $result['total']);
        $this->assertEquals($appointment1->id, $result['appointments'][0]['id']);
        
        // Test with status filter
        $result = $this->mcp->executeTool('listAppointments', [
            'status' => 'confirmed'
        ]);
        
        $this->assertEquals(1, $result['total']);
        $this->assertEquals($appointment2->id, $result['appointments'][0]['id']);
        
        // Test with branch filter
        $otherBranch = Branch::factory()->create(['company_id' => $this->company->id]);
        $result = $this->mcp->executeTool('listAppointments', [
            'branch_id' => $otherBranch->id
        ]);
        
        $this->assertEquals(0, $result['total']);
    }
    
    public function test_get_appointment_details()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::now()->addDays(1),
            'status' => 'scheduled',
            'notes' => 'Test notes'
        ]);
        
        $result = $this->mcp->executeTool('getAppointmentDetails', [
            'appointment_id' => $appointment->id
        ]);
        
        $this->assertEquals($appointment->id, $result['id']);
        $this->assertEquals('scheduled', $result['status']);
        $this->assertEquals('Test notes', $result['notes']);
        $this->assertArrayHasKey('customer', $result);
        $this->assertArrayHasKey('staff', $result);
        $this->assertArrayHasKey('service', $result);
        $this->assertArrayHasKey('branch', $result);
    }
    
    public function test_get_appointment_details_not_found()
    {
        $result = $this->mcp->executeTool('getAppointmentDetails', [
            'appointment_id' => 99999
        ]);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('not found', $result['error']);
    }
    
    public function test_check_availability()
    {
        // Create existing appointment
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::parse('2025-08-10 10:00:00'),
            'ends_at' => Carbon::parse('2025-08-10 11:00:00'),
            'status' => 'scheduled'
        ]);
        
        // Check availability for the same time (should be unavailable)
        $result = $this->mcp->executeTool('checkAvailability', [
            'date' => '2025-08-10',
            'time' => '10:00',
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id
        ]);
        
        $this->assertFalse($result['available']);
        $this->assertArrayHasKey('reason', $result);
        
        // Check availability for different time (should be available)
        $result = $this->mcp->executeTool('checkAvailability', [
            'date' => '2025-08-10',
            'time' => '14:00',
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id
        ]);
        
        $this->assertTrue($result['available']);
        $this->assertArrayHasKey('next_available_slots', $result);
    }
    
    public function test_create_appointment_success()
    {
        Event::fake();
        
        $result = $this->mcp->executeTool('createAppointment', [
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'date' => '2025-08-15',
            'time' => '10:00',
            'notes' => 'Test appointment'
        ]);
        
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('appointment', $result);
        $this->assertEquals('scheduled', $result['appointment']['status']);
        
        // Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'notes' => 'Test appointment'
        ]);
        
        Event::assertDispatched(\App\Events\AppointmentCreated::class);
    }
    
    public function test_create_appointment_with_conflict()
    {
        // Create existing appointment
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::parse('2025-08-15 10:00:00'),
            'ends_at' => Carbon::parse('2025-08-15 11:00:00'),
            'status' => 'scheduled'
        ]);
        
        $result = $this->mcp->executeTool('createAppointment', [
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'date' => '2025-08-15',
            'time' => '10:00'
        ]);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('not available', $result['error']);
    }
    
    public function test_update_appointment()
    {
        Event::fake();
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::now()->addDays(1),
            'status' => 'scheduled',
            'notes' => 'Original notes'
        ]);
        
        $result = $this->mcp->executeTool('updateAppointment', [
            'appointment_id' => $appointment->id,
            'status' => 'confirmed',
            'notes' => 'Updated notes'
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('confirmed', $result['appointment']['status']);
        $this->assertEquals('Updated notes', $result['appointment']['notes']);
        
        Event::assertDispatched(\App\Events\AppointmentUpdated::class);
    }
    
    public function test_cancel_appointment()
    {
        Event::fake();
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::now()->addDays(1),
            'status' => 'scheduled'
        ]);
        
        $result = $this->mcp->executeTool('cancelAppointment', [
            'appointment_id' => $appointment->id,
            'reason' => 'Customer requested cancellation'
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('cancelled', $result['appointment']['status']);
        
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Customer requested cancellation'
        ]);
        
        Event::assertDispatched(\App\Events\AppointmentCancelled::class);
    }
    
    public function test_reschedule_appointment()
    {
        Event::fake();
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::parse('2025-08-20 10:00:00'),
            'ends_at' => Carbon::parse('2025-08-20 11:00:00'),
            'status' => 'scheduled'
        ]);
        
        $result = $this->mcp->executeTool('rescheduleAppointment', [
            'appointment_id' => $appointment->id,
            'new_date' => '2025-08-21',
            'new_time' => '14:00'
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('2025-08-21', $result['appointment']['date']);
        $this->assertEquals('14:00', $result['appointment']['time']);
        
        Event::assertDispatched(\App\Events\AppointmentRescheduled::class);
    }
    
    public function test_get_appointment_stats()
    {
        // Create various appointments
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'scheduled',
            'created_at' => Carbon::now()->subDays(2)
        ]);
        
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'cancelled',
            'created_at' => Carbon::now()->subDays(1)
        ]);
        
        $result = $this->mcp->executeTool('getAppointmentStats', [
            'period' => 'last_7_days'
        ]);
        
        $this->assertArrayHasKey('total_appointments', $result);
        $this->assertArrayHasKey('appointments_by_status', $result);
        $this->assertArrayHasKey('appointments_by_day', $result);
        $this->assertArrayHasKey('popular_services', $result);
        $this->assertArrayHasKey('top_staff', $result);
        $this->assertArrayHasKey('revenue_stats', $result);
        
        $this->assertEquals(10, $result['total_appointments']);
        $this->assertEquals(5, $result['appointments_by_status']['completed']);
        $this->assertEquals(3, $result['appointments_by_status']['scheduled']);
        $this->assertEquals(2, $result['appointments_by_status']['cancelled']);
    }
    
    public function test_execute_tool_with_invalid_tool_name()
    {
        $result = $this->mcp->executeTool('invalidTool', []);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unknown tool', $result['error']);
    }
    
    public function test_appointment_with_invalid_data()
    {
        // Test with invalid customer ID
        $result = $this->mcp->executeTool('createAppointment', [
            'customer_id' => 99999,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'date' => '2025-08-15',
            'time' => '10:00'
        ]);
        
        $this->assertArrayHasKey('error', $result);
        
        // Test with invalid date format
        $result = $this->mcp->executeTool('createAppointment', [
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'date' => 'invalid-date',
            'time' => '10:00'
        ]);
        
        $this->assertArrayHasKey('error', $result);
    }
}