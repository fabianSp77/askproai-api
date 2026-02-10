<?php

use App\Models\Company;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up Cal.com webhook secret
    config(['services.calcom.webhook_secret' => 'test_webhook_secret']);

    // Create test company and service
    $this->company = Company::factory()->create();
    $this->service = Service::factory()->create([
        'company_id' => $this->company->id,
        'calcom_event_type_id' => 'evt_test_123',
        'name' => 'Test Service',
    ]);
});

it('handles BOOKING.CREATED event and creates appointment', function () {
    $payload = [
        'triggerEvent' => 'BOOKING.CREATED',
        'payload' => [
            'id' => 'booking_123',
            'uid' => 'booking_uid_123',
            'eventTypeId' => 'evt_test_123',
            'startTime' => Carbon::now()->addDays(1)->toIso8601String(),
            'endTime' => Carbon::now()->addDays(1)->addHour()->toIso8601String(),
            'attendees' => [
                [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'phone' => '+491234567890',
                ],
            ],
            'description' => 'Test booking',
        ],
    ];

    $response = $this->postJson('/api/calcom/webhook', $payload);

    $response->assertStatus(200);
    $response->assertJson(['received' => true, 'status' => 'processed']);

    // Verify appointment was created
    expect(Appointment::where('calcom_v2_booking_id', 'booking_123')->exists())->toBeTrue();

    $appointment = Appointment::where('calcom_v2_booking_id', 'booking_123')->first();
    expect($appointment->status)->toBe('confirmed');
    expect($appointment->source)->toBe('cal.com');
    expect($appointment->sync_origin)->toBe('calcom');
    expect($appointment->company_id)->toBe($this->company->id);
});

it('handles BOOKING.CANCELLED event and marks appointment cancelled', function () {
    // Create existing appointment
    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'service_id' => $this->service->id,
        'calcom_v2_booking_id' => 'booking_cancel_123',
        'status' => 'confirmed',
    ]);

    $payload = [
        'triggerEvent' => 'BOOKING.CANCELLED',
        'payload' => [
            'id' => 'booking_cancel_123',
            'uid' => 'booking_uid_cancel_123',
            'cancellationReason' => 'Customer requested',
        ],
    ];

    $response = $this->postJson('/api/calcom/webhook', $payload);

    $response->assertStatus(200);

    // Verify appointment was cancelled
    $appointment->refresh();
    expect($appointment->status)->toBe('cancelled');
    expect($appointment->cancelled_by)->toBe('customer');
    expect($appointment->cancellation_source)->toBe('calcom_webhook');
    expect($appointment->sync_origin)->toBe('calcom');
});

it('prevents sync loop with sync_origin=calcom', function () {
    // Create appointment that came from Cal.com
    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'service_id' => $this->service->id,
        'calcom_v2_booking_id' => 'booking_loop_123',
        'status' => 'confirmed',
        'sync_origin' => 'calcom',
        'calcom_sync_status' => 'synced',
    ]);

    // Webhook should update appointment but NOT trigger re-sync to Cal.com
    $payload = [
        'triggerEvent' => 'BOOKING.UPDATED',
        'payload' => [
            'id' => 'booking_loop_123',
            'uid' => 'booking_uid_loop_123',
            'eventTypeId' => 'evt_test_123',
            'startTime' => Carbon::now()->addDays(2)->toIso8601String(),
            'endTime' => Carbon::now()->addDays(2)->addHour()->toIso8601String(),
            'status' => 'ACCEPTED',
        ],
    ];

    $response = $this->postJson('/api/calcom/webhook', $payload);

    $response->assertStatus(200);

    $appointment->refresh();
    expect($appointment->sync_origin)->toBe('calcom');
    expect($appointment->calcom_sync_status)->toBe('synced');
});

it('rejects webhook with invalid/missing webhook secret', function () {
    // Remove webhook secret
    config(['services.calcom.webhook_secret' => null]);

    $payload = [
        'triggerEvent' => 'BOOKING.CREATED',
        'payload' => [
            'id' => 'booking_invalid_123',
        ],
    ];

    // This would normally be rejected by middleware
    // For this test we verify controller behavior when secret is missing
    $response = $this->postJson('/api/calcom/webhook', $payload);

    // Expect middleware rejection or controller validation
    expect($response->status())->toBeGreaterThanOrEqual(400);
});

it('handles unknown event type gracefully', function () {
    $payload = [
        'triggerEvent' => 'UNKNOWN.EVENT.TYPE',
        'payload' => [
            'id' => 'booking_unknown_123',
        ],
    ];

    $response = $this->postJson('/api/calcom/webhook', $payload);

    $response->assertStatus(200);
    $response->assertJson(['received' => true, 'status' => 'ignored']);
});

it('handles BOOKING.UPDATED event and reschedules appointment', function () {
    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'service_id' => $this->service->id,
        'calcom_v2_booking_id' => 'booking_update_123',
        'status' => 'confirmed',
        'starts_at' => Carbon::now()->addDays(1),
        'ends_at' => Carbon::now()->addDays(1)->addHour(),
    ]);

    $oldStartsAt = $appointment->starts_at;
    $newStartsAt = Carbon::now()->addDays(3);

    $payload = [
        'triggerEvent' => 'BOOKING.UPDATED',
        'payload' => [
            'id' => 'booking_update_123',
            'uid' => 'booking_uid_update_123',
            'eventTypeId' => 'evt_test_123',
            'startTime' => $newStartsAt->toIso8601String(),
            'endTime' => $newStartsAt->copy()->addHour()->toIso8601String(),
            'status' => 'ACCEPTED',
        ],
    ];

    $response = $this->postJson('/api/calcom/webhook', $payload);

    $response->assertStatus(200);

    $appointment->refresh();
    expect($appointment->status)->toBe('rescheduled');
    expect($appointment->starts_at->toDateString())->toBe($newStartsAt->toDateString());
    expect($appointment->rescheduled_by)->toBe('customer');
    expect($appointment->reschedule_source)->toBe('calcom_webhook');
});

it('creates customer if not found during BOOKING.CREATED', function () {
    $payload = [
        'triggerEvent' => 'BOOKING.CREATED',
        'payload' => [
            'id' => 'booking_new_customer_123',
            'uid' => 'booking_uid_new_123',
            'eventTypeId' => 'evt_test_123',
            'startTime' => Carbon::now()->addDays(1)->toIso8601String(),
            'endTime' => Carbon::now()->addDays(1)->addHour()->toIso8601String(),
            'attendees' => [
                [
                    'name' => 'New Customer',
                    'email' => 'newcustomer@example.com',
                    'phone' => '+491234567890',
                ],
            ],
        ],
    ];

    $response = $this->postJson('/api/calcom/webhook', $payload);

    $response->assertStatus(200);

    // Verify customer was created
    $customer = Customer::where('email', 'newcustomer@example.com')->first();
    expect($customer)->not->toBeNull();
    expect($customer->company_id)->toBe($this->company->id);
    expect($customer->source)->toBe('cal.com');
});

it('handles PING event', function () {
    $payload = [
        'triggerEvent' => 'PING',
        'payload' => [],
    ];

    $response = $this->postJson('/api/calcom/webhook', $payload);

    $response->assertStatus(200);
    $response->assertJson(['received' => true, 'status' => 'ok']);
});

it('verifies webhook ownership via service lookup (VULN-001 fix)', function () {
    // Create company with service
    $otherCompany = Company::factory()->create();

    // Webhook with event type that doesn't exist in our system
    $payload = [
        'triggerEvent' => 'BOOKING.CREATED',
        'payload' => [
            'id' => 'booking_unauthorized_123',
            'uid' => 'booking_uid_unauthorized_123',
            'eventTypeId' => 'evt_unauthorized_999', // Unknown event type
            'startTime' => Carbon::now()->addDays(1)->toIso8601String(),
            'endTime' => Carbon::now()->addDays(1)->addHour()->toIso8601String(),
            'attendees' => [
                [
                    'name' => 'Unauthorized User',
                    'email' => 'unauthorized@example.com',
                ],
            ],
        ],
    ];

    $response = $this->postJson('/api/calcom/webhook', $payload);

    // Should fail because event type not found in system
    $response->assertStatus(500);

    // Verify no appointment was created
    expect(Appointment::where('calcom_v2_booking_id', 'booking_unauthorized_123')->exists())->toBeFalse();
});
