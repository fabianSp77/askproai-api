<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentModification;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PolicyConfiguration;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RetellPolicyIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable middleware for testing
        $this->withoutMiddleware();

        $this->setupTestData();
    }

    private Company $company;
    private Branch $branch;
    private Customer $customer;
    private Service $service;
    private Call $call;

    private function setupTestData(): void
    {
        // Setup test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create(['company_id' => $this->company->id]);
        $this->call = Call::factory()->create([
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'retell_call_id' => 'test_call_' . uniqid()
        ]);
    }

    /** @test */
    public function it_cancels_appointment_when_policy_allows()
    {
        // Arrange: Create appointment 50 hours in future (beyond 48h for 0€ fee)
        $appointment = Appointment::factory()->create([
            'call_id' => $this->call->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::now()->addHours(50),
            'ends_at' => Carbon::now()->addHours(51),
            'status' => 'scheduled'
        ]);

        // Set company cancellation policy
        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24, 'max_cancellations_per_month' => 3]
        ]);

        // Act: Cancel via Retell function call
        $response = $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'cancel_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'appointment_date' => $appointment->starts_at->format('Y-m-d'),
                'reason' => 'Integration test cancellation'
            ]
        ]);

        // Assert response
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'cancelled',
            'fee' => 0.0
        ]);

        // Assert database changes
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled'
        ]);

        $this->assertDatabaseHas('appointment_modifications', [
            'appointment_id' => $appointment->id,
            'customer_id' => $this->customer->id,
            'modification_type' => 'cancel',
            'within_policy' => true,
            'fee_charged' => 0.0
        ]);
    }

    /** @test */
    public function it_denies_cancellation_when_deadline_missed()
    {
        // Arrange: Create appointment 12 hours in future (less than 24h deadline)
        $appointment = Appointment::factory()->create([
            'call_id' => $this->call->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addHours(12),
            'ends_at' => Carbon::now()->addHours(13),
            'status' => 'scheduled'
        ]);

        // Set strict policy
        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24]
        ]);

        // Act: Try to cancel
        $response = $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'cancel_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'appointment_date' => $appointment->starts_at->format('Y-m-d')
            ]
        ]);

        // Assert response
        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'status' => 'denied',
            'reason' => 'deadline_missed'
        ]);

        // Appointment should still be scheduled
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'scheduled' // NOT cancelled
        ]);

        // No modification record should be created
        $this->assertDatabaseMissing('appointment_modifications', [
            'appointment_id' => $appointment->id
        ]);
    }

    /** @test */
    public function it_denies_cancellation_when_quota_exceeded()
    {
        // Arrange: Customer has already cancelled 3 times this month
        for ($i = 0; $i < 3; $i++) {
            AppointmentModification::create([
                'appointment_id' => Appointment::factory()->create([
                    'customer_id' => $this->customer->id,
                    'company_id' => $this->company->id
                ])->id,
                'customer_id' => $this->customer->id,
                'modification_type' => 'cancel',
                'within_policy' => true,
                'fee_charged' => 0,
                'created_at' => Carbon::now()->subDays(rand(1, 25))
            ]);
        }

        // New appointment to cancel
        $appointment = Appointment::factory()->create([
            'call_id' => $this->call->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addDays(5),
            'status' => 'scheduled'
        ]);

        // Set policy with quota
        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24, 'max_cancellations_per_month' => 3]
        ]);

        // Act
        $response = $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'cancel_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'appointment_date' => $appointment->starts_at->format('Y-m-d')
            ]
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'status' => 'denied',
            'reason' => 'quota_exceeded'
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'scheduled' // NOT cancelled
        ]);
    }

    /** @test */
    public function it_reschedules_appointment_when_policy_allows()
    {
        // Arrange: Appointment 48 hours in future
        $oldDateTime = Carbon::now()->addHours(48);
        $newDateTime = Carbon::now()->addHours(72);

        $appointment = Appointment::factory()->create([
            'call_id' => $this->call->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'starts_at' => $oldDateTime,
            'ends_at' => $oldDateTime->copy()->addHour(),
            'status' => 'scheduled'
        ]);

        // Set reschedule policy
        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $this->company->id,
            'policy_type' => 'reschedule',
            'config' => ['hours_before' => 24, 'max_reschedules_per_appointment' => 2]
        ]);

        // Act: Reschedule via Retell (simulated - would normally check Cal.com)
        $response = $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'reschedule_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'old_date' => $oldDateTime->format('Y-m-d'),
                // Note: In real scenario, new_date/new_time would trigger availability check
                // For unit test, we're just checking policy enforcement
            ]
        ]);

        // Assert: Should allow reschedule (ready status since we didn't provide new time)
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'ready_to_reschedule'
        ]);
    }

    /** @test */
    public function it_denies_reschedule_when_max_reschedules_reached()
    {
        // Arrange: Appointment already rescheduled 2 times
        $appointment = Appointment::factory()->create([
            'call_id' => $this->call->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addDays(3),
            'status' => 'scheduled'
        ]);

        // Create 2 reschedule records
        for ($i = 0; $i < 2; $i++) {
            AppointmentModification::create([
                'appointment_id' => $appointment->id,
                'customer_id' => $this->customer->id,
                'modification_type' => 'reschedule',
                'within_policy' => true,
                'fee_charged' => 0
            ]);
        }

        // Set policy with max 2 reschedules
        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $this->company->id,
            'policy_type' => 'reschedule',
            'config' => ['hours_before' => 24, 'max_reschedules_per_appointment' => 2]
        ]);

        // Act: Try to reschedule again
        $response = $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'reschedule_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'old_date' => $appointment->starts_at->format('Y-m-d')
            ]
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'status' => 'denied',
            'reason' => 'max_reschedules_reached'
        ]);
    }

    /** @test */
    public function it_charges_fee_when_configured_in_policy()
    {
        // Arrange: Appointment with policy that has fixed fee
        $appointment = Appointment::factory()->create([
            'call_id' => $this->call->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addHours(50),
            'status' => 'scheduled'
        ]);

        // Set policy with fee
        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24, 'fee' => 25.00]
        ]);

        // Act
        $response = $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'cancel_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'appointment_date' => $appointment->starts_at->format('Y-m-d')
            ]
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'cancelled',
            'fee' => 25.00
        ]);

        // Check modification record has fee
        $this->assertDatabaseHas('appointment_modifications', [
            'appointment_id' => $appointment->id,
            'modification_type' => 'cancel',
            'fee_charged' => 25.00
        ]);
    }

    /** @test */
    public function it_returns_not_found_when_appointment_does_not_exist()
    {
        // Act: Try to cancel non-existent appointment
        $response = $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'cancel_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'appointment_date' => '2025-12-31'
            ]
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'status' => 'not_found'
        ]);
    }
}
