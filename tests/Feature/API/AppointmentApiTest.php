<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PortalUser;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use Laravel\Sanctum\Sanctum;

class AppointmentApiTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private Company $company;
    private PortalUser $user;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);
        
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function can_list_appointments()
    {
        Appointment::factory()->count(15)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->getJson('/api/appointments');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer',
                        'service',
                        'staff',
                        'appointment_datetime',
                        'duration_minutes',
                        'status',
                        'notes',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(15, 'data');
    }

    /** @test */
    public function can_filter_appointments_by_status()
    {
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
        ]);
        
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/appointments?status=scheduled');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.status', 'scheduled');
    }

    /** @test */
    public function can_filter_appointments_by_date_range()
    {
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'appointment_datetime' => now()->addDays(1),
        ]);
        
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'appointment_datetime' => now()->addDays(7),
        ]);

        $response = $this->getJson('/api/appointments?date_from=' . now()->format('Y-m-d') . '&date_to=' . now()->addDays(5)->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function can_create_appointment()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);

        $appointmentData = [
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
            'appointment_datetime' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'notes' => 'Test appointment',
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer',
                    'service',
                    'staff',
                    'appointment_datetime',
                    'duration_minutes',
                    'status',
                    'notes',
                ],
            ]);

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'notes' => 'Test appointment',
        ]);
    }

    /** @test */
    public function cannot_create_appointment_with_time_conflict()
    {
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        // Create existing appointment
        $existingAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $staff->id,
            'appointment_datetime' => now()->addDay()->setTime(10, 0),
            'duration_minutes' => 60,
        ]);

        // Try to create conflicting appointment
        $response = $this->postJson('/api/appointments', [
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
            'appointment_datetime' => now()->addDay()->setTime(10, 30)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['appointment_datetime'])
            ->assertJson([
                'errors' => [
                    'appointment_datetime' => [
                        'The selected time slot is not available.',
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_update_appointment()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        $updateData = [
            'appointment_datetime' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'duration_minutes' => 90,
            'notes' => 'Updated notes',
        ];

        $response = $this->putJson("/api/appointments/{$appointment->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.notes', 'Updated notes')
            ->assertJsonPath('data.duration_minutes', 90);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'notes' => 'Updated notes',
            'duration_minutes' => 90,
        ]);
    }

    /** @test */
    public function can_cancel_appointment()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
        ]);

        $response = $this->postJson("/api/appointments/{$appointment->id}/cancel", [
            'reason' => 'Customer requested cancellation',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function can_confirm_appointment()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
        ]);

        $response = $this->postJson("/api/appointments/{$appointment->id}/confirm");

        $response->assertOk()
            ->assertJsonPath('data.status', 'confirmed');
    }

    /** @test */
    public function can_mark_appointment_as_completed()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'confirmed',
            'appointment_datetime' => now()->subHour(),
        ]);

        $response = $this->postJson("/api/appointments/{$appointment->id}/complete", [
            'notes' => 'Service completed successfully',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    /** @test */
    public function can_mark_appointment_as_no_show()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'confirmed',
            'appointment_datetime' => now()->subHours(2),
        ]);

        $response = $this->postJson("/api/appointments/{$appointment->id}/no-show");

        $response->assertOk()
            ->assertJsonPath('data.status', 'no_show');

        // Check if customer no-show count was incremented
        $appointment->refresh();
        $this->assertGreaterThan(0, $appointment->customer->no_show_count);
    }

    /** @test */
    public function can_get_appointment_details()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson("/api/appointments/{$appointment->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer' => ['id', 'name', 'email', 'phone_number'],
                    'service' => ['id', 'name', 'duration', 'price'],
                    'staff' => ['id', 'name', 'email'],
                    'branch' => ['id', 'name'],
                    'appointment_datetime',
                    'duration_minutes',
                    'status',
                    'notes',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /** @test */
    public function can_check_availability()
    {
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $service = Service::factory()->create([
            'company_id' => $this->company->id,
            'duration_minutes' => 60,
        ]);

        $response = $this->postJson('/api/appointments/check-availability', [
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'available_slots' => [
                    '*' => [
                        'start_time',
                        'end_time',
                        'available',
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_reschedule_appointment()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
        ]);

        $newDateTime = now()->addDays(3)->setTime(14, 0);

        $response = $this->postJson("/api/appointments/{$appointment->id}/reschedule", [
            'appointment_datetime' => $newDateTime->format('Y-m-d H:i:s'),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.appointment_datetime', $newDateTime->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function cannot_access_appointments_from_other_companies()
    {
        $otherCompany = Company::factory()->create();
        $otherAppointment = Appointment::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->getJson("/api/appointments/{$otherAppointment->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function can_bulk_update_appointment_status()
    {
        $appointments = Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
        ]);

        $appointmentIds = $appointments->pluck('id')->toArray();

        $response = $this->postJson('/api/appointments/bulk-update', [
            'appointment_ids' => $appointmentIds,
            'status' => 'confirmed',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => '5 appointments updated successfully',
            ]);

        foreach ($appointmentIds as $id) {
            $this->assertDatabaseHas('appointments', [
                'id' => $id,
                'status' => 'confirmed',
            ]);
        }
    }
}