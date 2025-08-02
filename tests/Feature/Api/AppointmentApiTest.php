<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected Staff $staff;
    protected Service $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $this->service = Service::factory()->create(['company_id' => $this->company->id]);
        
        // Associate staff with service
        $this->staff->services()->attach($this->service);
        
        // Authenticate user
        Sanctum::actingAs($this->user);
    }
    
    public function test_list_appointments_returns_paginated_results()
    {
        // Create appointments
        Appointment::factory()->count(25)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id
        ]);
        
        $response = $this->getJson('/api/appointments');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'appointments' => [
                        '*' => [
                            'id',
                            'customer',
                            'staff',
                            'service',
                            'branch',
                            'starts_at',
                            'ends_at',
                            'status',
                            'price',
                            'notes'
                        ]
                    ],
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                        'last_page'
                    ]
                ]
            ]);
        
        $this->assertEquals(25, $response->json('data.pagination.total'));
    }
    
    public function test_list_appointments_with_filters()
    {
        // Create appointments with different statuses
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'scheduled',
            'starts_at' => Carbon::now()->addDay()
        ]);
        
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'completed',
            'starts_at' => Carbon::yesterday()
        ]);
        
        // Filter by status
        $response = $this->getJson('/api/appointments?status=scheduled');
        
        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('data.pagination.total'));
        
        // Filter by date range
        $response = $this->getJson('/api/appointments?date_from=' . Carbon::now()->format('Y-m-d'));
        
        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('data.pagination.total'));
    }
    
    public function test_get_appointment_details()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id
        ]);
        
        $response = $this->getJson("/api/appointments/{$appointment->id}");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $appointment->id,
                    'status' => $appointment->status,
                    'customer' => [
                        'id' => $this->customer->id,
                        'full_name' => $this->customer->full_name
                    ],
                    'staff' => [
                        'id' => $this->staff->id,
                        'full_name' => $this->staff->full_name
                    ],
                    'service' => [
                        'id' => $this->service->id,
                        'name' => $this->service->name
                    ]
                ]
            ]);
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
        
        // Check unavailable time
        $response = $this->postJson('/api/appointments/availability', [
            'date' => '2025-08-10',
            'time' => '10:00',
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'available' => false
                ]
            ]);
        
        // Check available time
        $response = $this->postJson('/api/appointments/availability', [
            'date' => '2025-08-10',
            'time' => '14:00',
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'available' => true
                ]
            ]);
    }
    
    public function test_create_appointment()
    {
        $response = $this->postJson('/api/appointments', [
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'date' => '2025-08-15',
            'time' => '10:00',
            'notes' => 'Test appointment'
        ]);
        
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Appointment created successfully'
            ]);
        
        $this->assertDatabaseHas('appointments', [
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'notes' => 'Test appointment'
        ]);
    }
    
    public function test_create_appointment_validation()
    {
        $response = $this->postJson('/api/appointments', [
            'customer_id' => 99999, // Invalid customer
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'date' => 'invalid-date',
            'time' => '25:00' // Invalid time
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'date', 'time']);
    }
    
    public function test_update_appointment()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'status' => 'scheduled'
        ]);
        
        $response = $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'confirmed',
            'notes' => 'Updated notes'
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Appointment updated successfully'
            ]);
        
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
            'notes' => 'Updated notes'
        ]);
    }
    
    public function test_cancel_appointment()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'scheduled'
        ]);
        
        $response = $this->postJson("/api/appointments/{$appointment->id}/cancel", [
            'reason' => 'Customer requested cancellation'
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Appointment cancelled successfully'
            ]);
        
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Customer requested cancellation'
        ]);
    }
    
    public function test_reschedule_appointment()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::parse('2025-08-20 10:00:00'),
            'status' => 'scheduled'
        ]);
        
        $response = $this->postJson("/api/appointments/{$appointment->id}/reschedule", [
            'new_date' => '2025-08-21',
            'new_time' => '14:00'
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Appointment rescheduled successfully'
            ]);
        
        $appointment->refresh();
        $this->assertEquals('2025-08-21', $appointment->starts_at->format('Y-m-d'));
        $this->assertEquals('14:00', $appointment->starts_at->format('H:i'));
    }
    
    public function test_get_appointment_stats()
    {
        // Create appointments with different statuses
        Appointment::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
            'created_at' => Carbon::now()->subDays(2)
        ]);
        
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'cancelled',
            'created_at' => Carbon::now()->subDay()
        ]);
        
        $response = $this->getJson('/api/appointments/stats?period=last_7_days');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_appointments',
                    'appointments_by_status',
                    'appointments_by_day',
                    'popular_services',
                    'top_staff',
                    'revenue_stats'
                ]
            ]);
        
        $this->assertEquals(17, $response->json('data.total_appointments'));
    }
    
    public function test_unauthorized_access()
    {
        // Logout user
        auth()->logout();
        
        $response = $this->getJson('/api/appointments');
        
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
    
    public function test_cross_company_isolation()
    {
        // Create another company with appointments
        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);
        Appointment::factory()->count(5)->create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id
        ]);
        
        // Should only see appointments from user's company
        $response = $this->getJson('/api/appointments');
        
        $response->assertStatus(200);
        
        $appointments = $response->json('data.appointments');
        foreach ($appointments as $appointment) {
            $this->assertEquals($this->company->id, $appointment['company_id']);
        }
    }
}