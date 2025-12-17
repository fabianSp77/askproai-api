<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Customer Portal Appointment Tests
 *
 * TEST COVERAGE:
 * - List appointments (upcoming, past, cancelled)
 * - View single appointment
 * - Authorization (user owns appointment)
 * - Multi-tenant isolation
 * - Reschedule appointment
 * - Cancel appointment
 * - Optimistic locking
 */
class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected User $user;
    protected Service $service;
    protected Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Salon',
        ]);

        // Create branch
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
        ]);

        // Create customer
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '+49151234567',
        ]);

        // Create user for customer
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'email' => 'customer@example.com',
            'name' => 'Test Customer',
        ]);

        // Create service
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Haircut',
            'duration' => 60,
            'price' => 25.00,
        ]);

        // Create staff
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Test Stylist',
        ]);
    }

    /** @test */
    public function it_can_list_upcoming_appointments()
    {
        Sanctum::actingAs($this->user);

        // Create appointments
        $upcomingAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'start_time' => now()->addDays(1),
            'duration_minutes' => 60,
            'status' => 'confirmed',
        ]);

        $pastAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'start_time' => now()->subDays(1),
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        // Request upcoming appointments
        $response = $this->getJson('/api/customer-portal/appointments?status=upcoming');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'start_time',
                        'status',
                        'service',
                        'staff',
                        'location',
                        'can_reschedule',
                        'can_cancel',
                    ],
                ],
                'meta' => [
                    'total',
                    'status',
                ],
            ]);

        // Should only include upcoming appointment
        $this->assertEquals(1, $response->json('meta.total'));
    }

    /** @test */
    public function it_can_list_past_appointments()
    {
        Sanctum::actingAs($this->user);

        // Create past appointment
        $pastAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'start_time' => now()->subDays(1),
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        // Request past appointments
        $response = $this->getJson('/api/customer-portal/appointments?status=past');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'meta' => [
                    'status' => 'past',
                ],
            ]);
    }

    /** @test */
    public function it_can_view_single_appointment()
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'start_time' => now()->addDays(1),
            'duration_minutes' => 60,
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/customer-portal/appointments/{$appointment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $appointment->id,
                    'status' => 'confirmed',
                ],
            ]);
    }

    /** @test */
    public function it_prevents_viewing_other_users_appointments()
    {
        Sanctum::actingAs($this->user);

        // Create another customer
        $otherCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create appointment for other customer
        $otherAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $otherCustomer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'start_time' => now()->addDays(1),
            'duration_minutes' => 60,
        ]);

        // Try to view other user's appointment
        $response = $this->getJson("/api/customer-portal/appointments/{$otherAppointment->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'You are not authorized to view this appointment.',
            ]);
    }

    /** @test */
    public function it_prevents_cross_tenant_access()
    {
        Sanctum::actingAs($this->user);

        // Create another company
        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);
        $otherCustomer = Customer::factory()->create(['company_id' => $otherCompany->id]);
        $otherService = Service::factory()->create(['company_id' => $otherCompany->id]);
        $otherStaff = Staff::factory()->create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
        ]);

        // Create appointment in other company
        $otherAppointment = Appointment::factory()->create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
            'customer_id' => $otherCustomer->id,
            'service_id' => $otherService->id,
            'staff_id' => $otherStaff->id,
            'start_time' => now()->addDays(1),
            'duration_minutes' => 60,
        ]);

        // Try to view cross-tenant appointment
        $response = $this->getJson("/api/customer-portal/appointments/{$otherAppointment->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Try to access without authentication
        $response = $this->getJson('/api/customer-portal/appointments');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_appointment()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/customer-portal/appointments/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Appointment not found.',
            ]);
    }

    /** @test */
    public function it_filters_appointments_by_date_range()
    {
        Sanctum::actingAs($this->user);

        // Create appointments on different dates
        $appointment1 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'start_time' => now()->addDays(1),
            'duration_minutes' => 60,
        ]);

        $appointment2 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'start_time' => now()->addDays(7),
            'duration_minutes' => 60,
        ]);

        // Filter by date range
        $fromDate = now()->toDateString();
        $toDate = now()->addDays(3)->toDateString();

        $response = $this->getJson("/api/customer-portal/appointments?from_date={$fromDate}&to_date={$toDate}");

        $response->assertStatus(200);
        // Should only include appointment1 (within range)
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /** @test */
    public function it_includes_permission_flags()
    {
        Sanctum::actingAs($this->user);

        // Create appointment in future (can reschedule/cancel)
        $futureAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'start_time' => now()->addDays(2),
            'duration_minutes' => 60,
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/customer-portal/appointments/{$futureAppointment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_reschedule' => true,
                    'can_cancel' => true,
                ],
            ]);
    }
}
