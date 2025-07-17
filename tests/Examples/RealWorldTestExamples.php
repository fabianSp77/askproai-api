<?php

namespace Tests\Examples;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Services\BookingService;
use App\Services\CustomerMatchingService;
use App\Jobs\ProcessRetellCallEndedJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

/**
 * Real-world test examples demonstrating common testing scenarios
 * in the AskProAI application.
 */
class RealWorldTestExamples extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /**
     * Example 1: Testing the complete phone-to-appointment booking flow
     * This is the core business logic of AskProAI
     */
    public function test_complete_phone_to_appointment_booking_flow()
    {
        // Arrange: Set up test data
        $company = Company::factory()->create([
            'retell_api_key' => 'test_key',
            'calcom_api_key' => 'cal_test_key',
        ]);

        // Mock external API responses
        Http::fake([
            'api.retellai.com/*' => Http::response([
                'call_id' => 'call_123',
                'status' => 'ended',
                'duration' => 180,
                'transcript' => 'Ich möchte gerne einen Termin für morgen um 14 Uhr buchen.',
                'metadata' => [
                    'customer_name' => 'Max Mustermann',
                    'service_requested' => 'Beratung',
                    'preferred_time' => '14:00',
                ],
            ], 200),
            
            'api.cal.com/*' => Http::response([
                'booking' => [
                    'id' => 'cal_booking_123',
                    'status' => 'confirmed',
                ],
            ], 201),
        ]);

        // Act: Process the webhook from Retell.ai
        $webhookPayload = [
            'event' => 'call_ended',
            'call_id' => 'call_123',
            'phone_number' => '+49123456789',
            'company_id' => $company->id,
        ];

        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'X-Retell-Signature' => $this->generateRetellSignature($webhookPayload),
        ]);

        // Assert: Verify the complete flow
        $response->assertOk();

        // Check that call was created
        $this->assertDatabaseHas('calls', [
            'external_id' => 'call_123',
            'phone_number' => '+49123456789',
            'company_id' => $company->id,
            'status' => 'completed',
        ]);

        // Check that customer was created or matched
        $this->assertDatabaseHas('customers', [
            'phone_number' => '+49123456789',
            'name' => 'Max Mustermann',
            'company_id' => $company->id,
        ]);

        // Check that appointment was created
        $this->assertDatabaseHas('appointments', [
            'company_id' => $company->id,
            'status' => 'scheduled',
        ]);

        // Verify job was dispatched
        Queue::assertPushed(ProcessRetellCallEndedJob::class);
    }

    /**
     * Example 2: Testing customer duplicate detection and merging
     */
    public function test_detects_and_merges_duplicate_customers()
    {
        // Arrange
        $company = Company::factory()->create();
        
        // Create existing customer
        $existingCustomer = Customer::factory()->create([
            'company_id' => $company->id,
            'email' => 'max@example.com',
            'phone_number' => '+49123456789',
            'name' => 'Max Mustermann',
        ]);

        // Create appointments for existing customer
        Appointment::factory()->count(3)->create([
            'company_id' => $company->id,
            'customer_id' => $existingCustomer->id,
        ]);

        // Act: Try to create duplicate with slight variation
        $service = new CustomerMatchingService();
        $matchedCustomer = $service->findOrCreateCustomer([
            'company_id' => $company->id,
            'phone_number' => '+49 123 456 789', // Different formatting
            'email' => 'MAX@EXAMPLE.COM', // Different case
            'name' => 'Maximilian Mustermann', // Slightly different name
        ]);

        // Assert
        $this->assertEquals($existingCustomer->id, $matchedCustomer->id);
        $this->assertEquals('Maximilian Mustermann', $matchedCustomer->name); // Name updated
        $this->assertCount(3, $matchedCustomer->appointments); // Appointments preserved
    }

    /**
     * Example 3: Testing appointment conflict detection
     */
    public function test_prevents_double_booking_same_time_slot()
    {
        // Arrange
        $company = Company::factory()->create();
        $staff = \App\Models\Staff::factory()->create();
        $service = \App\Models\Service::factory()->create([
            'duration_minutes' => 60,
        ]);

        // Create existing appointment
        $existingAppointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'staff_id' => $staff->id,
            'appointment_datetime' => '2024-01-15 14:00:00',
            'duration_minutes' => 60,
        ]);

        // Act: Try to book overlapping appointment
        $bookingService = new BookingService();
        
        $result = $bookingService->checkAvailability([
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'requested_datetime' => '2024-01-15 14:30:00',
        ]);

        // Assert
        $this->assertFalse($result['available']);
        $this->assertEquals('Time slot not available', $result['message']);
        $this->assertContains('15:00', $result['next_available_slots']);
    }

    /**
     * Example 4: Testing prepaid balance and billing
     */
    public function test_deducts_prepaid_balance_after_call()
    {
        // Arrange
        $company = Company::factory()->create([
            'prepaid_balance' => 50.00,
            'billing_rate_per_minute' => 0.50,
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration' => 300, // 5 minutes
            'status' => 'pending',
        ]);

        // Act: Process call billing
        Event::dispatch('call.completed', $call);

        // Assert
        $call->refresh();
        $company->refresh();

        $this->assertEquals(2.50, $call->cost); // 5 min * 0.50
        $this->assertEquals(47.50, $company->prepaid_balance); // 50 - 2.50
        $this->assertDatabaseHas('call_charges', [
            'call_id' => $call->id,
            'amount' => 2.50,
            'description' => 'Call charge for 5 minutes',
        ]);
    }

    /**
     * Example 5: Testing email notifications with localization
     */
    public function test_sends_appointment_reminder_in_customer_language()
    {
        // Arrange
        \Mail::fake();
        
        $customer = Customer::factory()->create([
            'preferred_language' => 'en',
            'email' => 'customer@example.com',
        ]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'appointment_datetime' => now()->addDay(),
        ]);

        // Act
        \App\Jobs\SendAppointmentReminderJob::dispatch($appointment);

        // Assert
        \Mail::assertSent(\App\Mail\AppointmentReminderMail::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email) &&
                   $mail->locale === 'en' &&
                   str_contains($mail->render(), 'Appointment Reminder'); // English content
        });
    }

    /**
     * Example 6: Testing API rate limiting
     */
    public function test_rate_limits_api_requests()
    {
        // Arrange
        $user = \App\Models\PortalUser::factory()->create();
        Sanctum::actingAs($user);

        // Act: Make requests up to the limit
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/appointments');
            $response->assertOk();
        }

        // Make one more request over the limit
        $response = $this->getJson('/api/appointments');

        // Assert
        $response->assertStatus(429); // Too Many Requests
        $response->assertHeader('X-RateLimit-Limit', 60);
        $response->assertHeader('X-RateLimit-Remaining', 0);
        $response->assertJsonStructure(['message', 'retry_after']);
    }

    /**
     * Example 7: Testing transaction rollback on failure
     */
    public function test_rolls_back_appointment_creation_on_calendar_sync_failure()
    {
        // Arrange
        $company = Company::factory()->create();
        $initialAppointmentCount = Appointment::count();

        // Mock Cal.com to fail
        Http::fake([
            'api.cal.com/*' => Http::response(['error' => 'Calendar full'], 400),
        ]);

        // Act: Try to create appointment
        try {
            \DB::transaction(function () use ($company) {
                $appointment = Appointment::create([
                    'company_id' => $company->id,
                    'appointment_datetime' => now()->addDay(),
                    // ... other fields
                ]);

                // This should fail and trigger rollback
                app(\App\Services\CalcomService::class)->createBooking($appointment);
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Assert: No appointment should be created
        $this->assertEquals($initialAppointmentCount, Appointment::count());
    }

    /**
     * Example 8: Testing complex permission scenarios
     */
    public function test_staff_can_only_see_own_branch_appointments()
    {
        // Arrange
        $company = Company::factory()->create();
        $branch1 = \App\Models\Branch::factory()->create(['company_id' => $company->id]);
        $branch2 = \App\Models\Branch::factory()->create(['company_id' => $company->id]);

        $staffUser = \App\Models\PortalUser::factory()->create([
            'company_id' => $company->id,
            'role' => 'staff',
            'branch_id' => $branch1->id,
        ]);

        // Create appointments for both branches
        Appointment::factory()->count(3)->create(['branch_id' => $branch1->id]);
        Appointment::factory()->count(2)->create(['branch_id' => $branch2->id]);

        Sanctum::actingAs($staffUser);

        // Act
        $response = $this->getJson('/api/appointments');

        // Assert: Staff should only see branch1 appointments
        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.branch_id', $branch1->id);
    }

    /**
     * Helper method to generate Retell webhook signature
     */
    private function generateRetellSignature($payload): string
    {
        $secret = config('services.retell.webhook_secret');
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}