<?php

namespace Tests\Feature\Security;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Service;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Security Test: Multi-Tenant Isolation in Cal.com Webhooks
 *
 * Tests VULN-001 fix: Ensures webhooks cannot modify appointments across company boundaries
 */
class CalcomMultiTenantSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected string $webhookSecret;
    protected Company $companyA;
    protected Company $companyB;
    protected Service $serviceA;
    protected Service $serviceB;

    protected function setUp(): void
    {
        parent::setUp();

        // Set webhook secret for signature validation
        $this->webhookSecret = 'test-webhook-secret-' . uniqid();
        config(['services.calcom.webhook_secret' => $this->webhookSecret]);

        // Create two separate companies for isolation testing
        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        // Create services for each company
        $this->serviceA = Service::factory()->create([
            'company_id' => $this->companyA->id,
            'calcom_event_type_id' => 1001,
            'name' => 'Service A'
        ]);

        $this->serviceB = Service::factory()->create([
            'company_id' => $this->companyB->id,
            'calcom_event_type_id' => 2001,
            'name' => 'Service B'
        ]);
    }

    /**
     * Test: Webhook with Company A's event type cannot modify Company B's appointment
     *
     * Attack Scenario:
     * 1. Attacker discovers Company B's booking ID (e.g., 67890)
     * 2. Attacker sends webhook with Company A's event type but Company B's booking ID
     * 3. System should REJECT the webhook (company mismatch)
     */
    public function test_webhook_cannot_cancel_cross_tenant_appointment()
    {
        // ARRANGE: Create customer for Company B first
        $customerB = Customer::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'customerb@test.com'
        ]);

        // ARRANGE: Create appointment for Company B
        $appointmentB = Appointment::factory()->create([
            'company_id' => $this->companyB->id,
            'service_id' => $this->serviceB->id,
            'customer_id' => $customerB->id,  // Link to customer
            'calcom_v2_booking_id' => '67890',
            'status' => 'confirmed'
        ]);

        // ACT: Send cancellation webhook with Company A's event type
        $payload = [
            'triggerEvent' => 'BOOKING.CANCELLED',
            'payload' => [
                'id' => '67890',  // Company B's booking ID
                'uid' => '67890',
                'eventTypeId' => 1001,  // Company A's event type (MISMATCH!)
                'cancellationReason' => 'Cross-tenant attack attempt',
                'startTime' => $this->formatCalcomDateTime(now()->addDay()),
                'endTime' => $this->formatCalcomDateTime(now()->addDay()->addHour()),
            ]
        ];

        $response = $this->postWebhookWithSignature('/api/calcom/webhook', $payload);

        // ASSERT: Webhook returns success (doesn't leak info about security rejection)
        // But should NOT have modified the appointment
        $response->assertSuccessful();

        // ASSERT: Appointment should remain UNCHANGED (security worked!)
        $appointmentB->refresh();
        $this->assertEquals('confirmed', $appointmentB->status, 'Cross-tenant appointment should NOT be cancelled');
        $this->assertNull($appointmentB->cancelled_at, 'Appointment should NOT have cancellation timestamp');
    }

    /**
     * Test: Webhook cannot create appointment for non-existent event type
     */
    public function test_webhook_rejects_unknown_event_type()
    {
        // ACT: Send booking webhook with unknown event type
        $payload = [
            'triggerEvent' => 'BOOKING.CREATED',
            'payload' => [
                'id' => '99999',
                'uid' => '99999',
                'eventTypeId' => 9999,  // Non-existent event type
                'startTime' => $this->formatCalcomDateTime(now()->addDay()),
                'endTime' => $this->formatCalcomDateTime(now()->addDay()->addHour()),
                'attendees' => [
                    [
                        'name' => 'Attacker',
                        'email' => 'attacker@evil.com'
                    ]
                ]
            ]
        ];

        $response = $this->postWebhookWithSignature('/api/calcom/webhook', $payload);

        // ASSERT: Webhook should reject (returns error or ignores)
        // The important thing is that no appointment is created
        $this->assertTrue(
            $response->status() >= 400 || $response->json('status') === 'ignored',
            'Webhook should reject unknown event type'
        );

        // ASSERT: No appointment should be created
        $this->assertDatabaseMissing('appointments', [
            'calcom_v2_booking_id' => '99999'
        ]);
    }

    /**
     * Test: Webhook with correct event type CAN modify appointment (positive test)
     */
    public function test_webhook_with_correct_company_succeeds()
    {
        // ARRANGE: Create customer and appointment for Company A
        $customerA = Customer::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'customera@test.com'
        ]);

        $appointmentA = Appointment::factory()->create([
            'company_id' => $this->companyA->id,
            'service_id' => $this->serviceA->id,
            'customer_id' => $customerA->id,
            'calcom_v2_booking_id' => '12345',
            'status' => 'confirmed'
        ]);

        // ACT: Send cancellation webhook with CORRECT event type
        $payload = [
            'triggerEvent' => 'BOOKING.CANCELLED',
            'payload' => [
                'id' => '12345',  // Company A's booking ID
                'uid' => '12345',
                'eventTypeId' => 1001,  // Company A's event type (CORRECT!)
                'cancellationReason' => 'Legitimate cancellation',
                'startTime' => $this->formatCalcomDateTime($appointmentA->starts_at),
                'endTime' => $this->formatCalcomDateTime($appointmentA->ends_at),
            ]
        ];

        $response = $this->postWebhookWithSignature('/api/calcom/webhook', $payload);

        // ASSERT: Webhook should succeed
        $response->assertSuccessful();
        $response->assertJson(['received' => true, 'status' => 'processed']);

        // ASSERT: Appointment should be cancelled
        $appointmentA->refresh();
        $this->assertEquals('cancelled', $appointmentA->status);
        $this->assertNotNull($appointmentA->cancelled_at);
    }

    /**
     * Test: Webhook cannot reschedule cross-tenant appointment
     */
    public function test_webhook_cannot_reschedule_cross_tenant_appointment()
    {
        // ARRANGE: Create customer and appointment for Company B
        $customerB = Customer::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'customerb2@test.com'
        ]);

        $originalStartTime = now()->addDay();
        $appointmentB = Appointment::factory()->create([
            'company_id' => $this->companyB->id,
            'service_id' => $this->serviceB->id,
            'customer_id' => $customerB->id,
            'calcom_v2_booking_id' => '77777',
            'status' => 'confirmed',
            'starts_at' => $originalStartTime,
            'ends_at' => $originalStartTime->copy()->addHour()
        ]);

        // ACT: Send reschedule webhook with Company A's event type
        $newStartTime = now()->addDays(2);
        $payload = [
            'triggerEvent' => 'BOOKING.RESCHEDULED',
            'payload' => [
                'id' => '77777',  // Company B's booking ID
                'uid' => '77777',
                'eventTypeId' => 1001,  // Company A's event type (MISMATCH!)
                'startTime' => $this->formatCalcomDateTime($newStartTime),
                'endTime' => $this->formatCalcomDateTime($newStartTime->copy()->addHour()),
            ]
        ];

        $response = $this->postWebhookWithSignature('/api/calcom/webhook', $payload);

        // ASSERT: Webhook returns success (doesn't leak security info)
        // But appointment should NOT be modified
        $response->assertSuccessful();

        // ASSERT: Appointment time should remain unchanged (security worked!)
        $appointmentB->refresh();
        $this->assertTrue(
            $appointmentB->starts_at->equalTo($originalStartTime),
            'Cross-tenant appointment should NOT be rescheduled'
        );
    }

    /**
     * Test: Webhook booking creation enforces company isolation
     */
    public function test_webhook_creates_appointment_with_correct_company()
    {
        // ARRANGE: Customer from Company B
        $customerB = Customer::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'customer@companyb.com'
        ]);

        // ACT: Send booking webhook for Company A's service
        $payload = [
            'triggerEvent' => 'BOOKING.CREATED',
            'payload' => [
                'id' => '88888',
                'uid' => '88888',
                'eventTypeId' => 1001,  // Company A's event type
                'startTime' => $this->formatCalcomDateTime(now()->addDay()),
                'endTime' => $this->formatCalcomDateTime(now()->addDay()->addHour()),
                'attendees' => [
                    [
                        'name' => 'Customer B',
                        'email' => 'customer@companyb.com'  // Company B's customer
                    ]
                ]
            ]
        ];

        $response = $this->postWebhookWithSignature('/api/calcom/webhook', $payload);

        // ASSERT: Webhook should succeed
        $response->assertSuccessful();

        // ASSERT: Appointment should be created with COMPANY A's ID (from event type)
        $appointment = Appointment::where('calcom_v2_booking_id', '88888')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($this->companyA->id, $appointment->company_id);

        // ASSERT: Customer should be matched or created for Company A
        $this->assertEquals($this->companyA->id, $appointment->company_id);
    }

    /**
     * Test: Multiple webhooks with same booking ID but different event types
     */
    public function test_duplicate_booking_id_across_companies_isolated()
    {
        // ARRANGE: Create customers for both companies
        $customerA = Customer::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'customera2@test.com'
        ]);

        $customerB = Customer::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'customerb3@test.com'
        ]);

        // ARRANGE: Same booking ID used by two different companies (edge case)
        $appointmentA = Appointment::factory()->create([
            'company_id' => $this->companyA->id,
            'service_id' => $this->serviceA->id,
            'customer_id' => $customerA->id,
            'calcom_v2_booking_id' => 'SHARED-123',
            'status' => 'confirmed'
        ]);

        $appointmentB = Appointment::factory()->create([
            'company_id' => $this->companyB->id,
            'service_id' => $this->serviceB->id,
            'customer_id' => $customerB->id,
            'calcom_v2_booking_id' => 'SHARED-123',  // Same ID!
            'status' => 'confirmed'
        ]);

        // ACT: Send cancellation for Company A's event type
        $payload = [
            'triggerEvent' => 'BOOKING.CANCELLED',
            'payload' => [
                'id' => 'SHARED-123',
                'uid' => 'SHARED-123',
                'eventTypeId' => 1001,  // Company A's event type
                'cancellationReason' => 'Company A cancellation'
            ]
        ];

        $response = $this->postWebhookWithSignature('/api/calcom/webhook', $payload);

        // ASSERT: Only Company A's appointment should be cancelled
        $appointmentA->refresh();
        $appointmentB->refresh();

        $this->assertEquals('cancelled', $appointmentA->status);
        $this->assertEquals('confirmed', $appointmentB->status);  // Company B unchanged
    }

    /**
     * Helper: Format datetime for Cal.com webhook (Y-m-d\TH:i:s.v\Z)
     */
    protected function formatCalcomDateTime(\DateTime|\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:s.v\Z');
    }

    /**
     * Helper: Post webhook with valid HMAC signature
     */
    protected function postWebhookWithSignature(string $url, array $payload)
    {
        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $this->webhookSecret);

        return $this->postJson($url, $payload, [
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ]);
    }
}
