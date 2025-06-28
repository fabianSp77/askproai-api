<?php

namespace Tests\Feature\API\V2;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Staff $staff;
    protected Service $service;
    protected Customer $customer;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenant and company
        $this->tenant = Tenant::factory()->create();
        $this->company = Company::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create user with company association
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'tenant_id' => $this->tenant->id
        ]);
        
        // Create related entities
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->staff = Staff::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        // Associate staff with branch
        $this->staff->branches()->attach($this->branch);
        
        // Associate staff with service
        $this->staff->services()->attach($this->service, [
            'duration_minutes' => 30,
            'price' => 50.00
        ]);
    }

    /**
     * Test unauthenticated access is rejected
     */
    #[Test]
    public function test_unauthenticated_access_is_rejected()
    {
        $response = $this->getJson('/api/v2/appointments');
        
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    /**
     * Test GET /appointments - List appointments with pagination
     */
    #[Test]
    public function test_can_list_appointments_with_pagination()
    {
        Sanctum::actingAs($this->user);
        
        // Create 25 appointments
        Appointment::factory(25)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id
        ]);
        
        $response = $this->getJson('/api/v2/appointments?page=1&per_page=10');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'start_time',
                        'end_time',
                        'status',
                        'duration',
                        'price',
                        'notes',
                        'customer' => ['id', 'first_name', 'last_name', 'email', 'phone'],
                        'staff' => ['id', 'first_name', 'last_name'],
                        'service' => ['id', 'name', 'duration', 'price'],
                        'branch' => ['id', 'name', 'address']
                    ]
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'total'
                ],
                'links' => ['first', 'last', 'prev', 'next']
            ])
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25);
    }

    /**
     * Test GET /appointments with filters
     */
    #[Test]
    public function test_can_filter_appointments()
    {
        Sanctum::actingAs($this->user);
        
        // Create appointments with different statuses
        Appointment::factory(5)->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled'
        ]);
        
        Appointment::factory(3)->create([
            'company_id' => $this->company->id,
            'status' => 'completed'
        ]);
        
        // Filter by status
        $response = $this->getJson('/api/v2/appointments?status=scheduled');
        
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.status', 'scheduled');
        
        // Filter by date range
        $startDate = now()->format('Y-m-d');
        $endDate = now()->addWeek()->format('Y-m-d');
        
        $response = $this->getJson("/api/v2/appointments?start_date={$startDate}&end_date={$endDate}");
        $response->assertStatus(200);
        
        // Filter by staff
        $response = $this->getJson("/api/v2/appointments?staff_id={$this->staff->id}");
        $response->assertStatus(200);
    }

    /**
     * Test POST /appointments - Create new appointment
     */
    #[Test]
    public function test_can_create_appointment()
    {
        Sanctum::actingAs($this->user);
        
        $appointmentData = [
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'start_time' => now()->addDay()->setHour(14)->setMinute(0)->format('Y-m-d H:i:s'),
            'duration' => 30,
            'price' => 50.00,
            'notes' => 'Test appointment notes'
        ];
        
        $response = $this->postJson('/api/v2/appointments', $appointmentData);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'start_time',
                    'end_time',
                    'status',
                    'duration',
                    'price',
                    'notes',
                    'customer',
                    'staff',
                    'service',
                    'branch'
                ]
            ])
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.notes', 'Test appointment notes');
        
        $this->assertDatabaseHas('appointments', [
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'status' => 'scheduled'
        ]);
    }

    /**
     * Test POST /appointments with validation errors
     */
    #[Test]
    public function test_create_appointment_validation()
    {
        Sanctum::actingAs($this->user);
        
        // Missing required fields
        $response = $this->postJson('/api/v2/appointments', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'branch_id',
                'staff_id',
                'service_id',
                'customer_id',
                'start_time'
            ]);
        
        // Invalid date format
        $response = $this->postJson('/api/v2/appointments', [
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'start_time' => 'invalid-date'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
        
        // Past date
        $response = $this->postJson('/api/v2/appointments', [
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'start_time' => now()->subDay()->format('Y-m-d H:i:s')
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    /**
     * Test GET /appointments/{id} - Show single appointment
     */
    #[Test]
    public function test_can_show_single_appointment()
    {
        Sanctum::actingAs($this->user);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id
        ]);
        
        $response = $this->getJson("/api/v2/appointments/{$appointment->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'start_time',
                    'end_time',
                    'status',
                    'duration',
                    'price',
                    'notes',
                    'customer',
                    'staff',
                    'service',
                    'branch',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJsonPath('data.id', $appointment->id);
    }

    /**
     * Test PUT /appointments/{id} - Update appointment
     */
    #[Test]
    public function test_can_update_appointment()
    {
        Sanctum::actingAs($this->user);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled'
        ]);
        
        $updateData = [
            'start_time' => now()->addDays(2)->setHour(15)->setMinute(0)->format('Y-m-d H:i:s'),
            'notes' => 'Updated appointment notes'
        ];
        
        $response = $this->putJson("/api/v2/appointments/{$appointment->id}", $updateData);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.notes', 'Updated appointment notes');
        
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'notes' => 'Updated appointment notes'
        ]);
    }

    /**
     * Test POST /appointments/{id}/cancel - Cancel appointment
     */
    #[Test]
    public function test_can_cancel_appointment()
    {
        Sanctum::actingAs($this->user);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled'
        ]);
        
        $response = $this->postJson("/api/v2/appointments/{$appointment->id}/cancel", [
            'reason' => 'Customer requested cancellation'
        ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
        
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled'
        ]);
    }

    /**
     * Test POST /appointments/{id}/complete - Complete appointment
     */
    #[Test]
    public function test_can_complete_appointment()
    {
        Sanctum::actingAs($this->user);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
            'start_time' => now()->subHour()
        ]);
        
        $response = $this->postJson("/api/v2/appointments/{$appointment->id}/complete");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    /**
     * Test POST /appointments/{id}/no-show - Mark as no-show
     */
    #[Test]
    public function test_can_mark_appointment_as_no_show()
    {
        Sanctum::actingAs($this->user);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
            'start_time' => now()->subHours(2)
        ]);
        
        $response = $this->postJson("/api/v2/appointments/{$appointment->id}/no-show");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'no_show');
    }

    /**
     * Test GET /appointments/available-slots - Get available time slots
     */
    #[Test]
    public function test_can_get_available_slots()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/v2/appointments/available-slots?' . http_build_query([
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now()->addDay()->format('Y-m-d')
        ]));
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'start_time',
                        'end_time',
                        'available'
                    ]
                ]
            ]);
    }

    /**
     * Test multi-tenancy isolation
     */
    #[Test]
    public function test_appointments_are_isolated_by_tenant()
    {
        // Create another tenant with appointments
        $otherTenant = Tenant::factory()->create();
        $otherCompany = Company::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'tenant_id' => $otherTenant->id
        ]);
        
        // Create appointments for both tenants
        Appointment::factory(5)->create(['company_id' => $this->company->id]);
        Appointment::factory(3)->create(['company_id' => $otherCompany->id]);
        
        // Login as first tenant user
        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/v2/appointments');
        
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
        
        // Login as other tenant user
        Sanctum::actingAs($otherUser);
        $response = $this->getJson('/api/v2/appointments');
        
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test rate limiting
     */
    #[Test]
    public function test_rate_limiting_is_enforced()
    {
        Sanctum::actingAs($this->user);
        
        // Clear rate limiter
        RateLimiter::clear('api:' . $this->user->id);
        
        // Make requests up to the limit (assuming 60 per minute)
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v2/appointments')->assertStatus(200);
        }
        
        // Next request should be rate limited
        $response = $this->getJson('/api/v2/appointments');
        
        $response->assertStatus(429)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }

    /**
     * Test API versioning headers
     */
    #[Test]
    public function test_api_versioning_headers()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/v2/appointments');
        
        $response->assertStatus(200)
            ->assertHeader('X-API-Version', 'v2');
    }

    /**
     * Test search functionality
     */
    #[Test]
    public function test_can_search_appointments()
    {
        Sanctum::actingAs($this->user);
        
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);
        
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'notes' => 'Dental cleaning appointment'
        ]);
        
        // Search by customer name
        $response = $this->getJson('/api/v2/appointments/search?q=John');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
        
        // Search by notes
        $response = $this->getJson('/api/v2/appointments/search?q=dental');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test statistics endpoint
     */
    #[Test]
    public function test_can_get_appointment_statistics()
    {
        Sanctum::actingAs($this->user);
        
        // Create appointments with different statuses
        Appointment::factory(10)->create([
            'company_id' => $this->company->id,
            'status' => 'completed'
        ]);
        
        Appointment::factory(5)->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled'
        ]);
        
        Appointment::factory(2)->create([
            'company_id' => $this->company->id,
            'status' => 'no_show'
        ]);
        
        $response = $this->getJson('/api/v2/appointments/statistics');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_appointments',
                    'completed_appointments',
                    'scheduled_appointments',
                    'cancelled_appointments',
                    'no_show_appointments',
                    'revenue_total',
                    'average_duration',
                    'busiest_day',
                    'busiest_hour'
                ]
            ])
            ->assertJsonPath('data.total_appointments', 17)
            ->assertJsonPath('data.completed_appointments', 10)
            ->assertJsonPath('data.no_show_appointments', 2);
    }

    /**
     * Test handling of non-existent resources
     */
    #[Test]
    public function test_returns_404_for_non_existent_appointment()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/v2/appointments/99999');
        
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Resource not found'
            ]);
    }

    /**
     * Test cross-tenant access is blocked
     */
    #[Test]
    public function test_cannot_access_other_tenant_appointment()
    {
        Sanctum::actingAs($this->user);
        
        // Create appointment for another tenant
        $otherTenant = Tenant::factory()->create();
        $otherCompany = Company::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherAppointment = Appointment::factory()->create(['company_id' => $otherCompany->id]);
        
        $response = $this->getJson("/api/v2/appointments/{$otherAppointment->id}");
        
        $response->assertStatus(404);
    }
}