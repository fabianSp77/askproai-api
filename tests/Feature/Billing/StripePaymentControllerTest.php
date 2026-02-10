<?php

use App\Models\Tenant;
use App\Models\BalanceTopup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up Stripe webhook secret in config
    config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
    config(['services.stripe.secret' => 'sk_test_secret']);
});

it('processes valid webhook with correct signature', function () {
    // Create test tenant
    $tenant = Tenant::factory()->create();

    // Create topup record
    $topup = BalanceTopup::factory()->create([
        'tenant_id' => $tenant->id,
        'stripe_payment_intent_id' => 'pi_test_123',
        'status' => 'pending',
        'amount' => 100,
    ]);

    // Simulate Stripe webhook event
    $event = [
        'type' => 'payment_intent.succeeded',
        'id' => 'evt_test_123',
        'data' => [
            'object' => [
                'id' => 'pi_test_123',
                'status' => 'succeeded',
                'latest_charge' => 'ch_test_123',
                'metadata' => [],
            ],
        ],
    ];

    // Send webhook with stripe_event in request (added by middleware)
    $response = $this->postJson('/api/stripe/webhook', [
        'stripe_event' => $event,
    ]);

    $response->assertStatus(200);

    // Verify topup was updated
    $topup->refresh();
    expect($topup->status)->toBe('completed');
});

it('rejects webhook with invalid signature', function () {
    // Mock middleware behavior - no stripe_event means signature failed
    $response = $this->postJson('/api/stripe/webhook', [
        'type' => 'payment_intent.succeeded',
    ]);

    $response->assertStatus(400);
    $response->assertJson(['error' => 'Invalid request']);
});

it('handles invoice.paid event correctly', function () {
    // Create test tenant with subscription
    $tenant = Tenant::factory()->create();

    $event = [
        'type' => 'invoice.paid',
        'id' => 'evt_invoice_123',
        'data' => [
            'object' => [
                'id' => 'in_test_123',
                'customer' => 'cus_test_123',
                'subscription' => 'sub_test_123',
                'status' => 'paid',
                'amount_paid' => 2000,
            ],
        ],
    ];

    // Send webhook
    $response = $this->postJson('/api/stripe/webhook', [
        'stripe_event' => $event,
    ]);

    $response->assertStatus(200);
    $response->assertSeeText('Webhook handled');
});

it('handles unknown event type gracefully', function () {
    $event = [
        'type' => 'unknown.event.type',
        'id' => 'evt_unknown_123',
        'data' => [
            'object' => [],
        ],
    ];

    $response = $this->postJson('/api/stripe/webhook', [
        'stripe_event' => $event,
    ]);

    // Should still return 200 (acknowledged but not handled)
    $response->assertStatus(200);
});

it('handles malformed payload', function () {
    // Missing stripe_event (added by middleware on valid signature)
    $response = $this->postJson('/api/stripe/webhook', []);

    $response->assertStatus(400);
});

it('handles payment_intent.payment_failed event', function () {
    $tenant = Tenant::factory()->create();

    $topup = BalanceTopup::factory()->create([
        'tenant_id' => $tenant->id,
        'stripe_payment_intent_id' => 'pi_test_failed',
        'status' => 'pending',
    ]);

    $event = [
        'type' => 'payment_intent.payment_failed',
        'id' => 'evt_fail_123',
        'data' => [
            'object' => [
                'id' => 'pi_test_failed',
                'status' => 'failed',
                'last_payment_error' => [
                    'message' => 'Card declined',
                ],
            ],
        ],
    ];

    $response = $this->postJson('/api/stripe/webhook', [
        'stripe_event' => $event,
    ]);

    $response->assertStatus(200);

    $topup->refresh();
    expect($topup->status)->toBe('failed');
    expect($topup->failure_reason)->toContain('Card declined');
});

it('handles charge.refunded event', function () {
    $tenant = Tenant::factory()->create();

    $topup = BalanceTopup::factory()->create([
        'tenant_id' => $tenant->id,
        'stripe_charge_id' => 'ch_test_123',
        'status' => 'completed',
        'amount' => 100,
    ]);

    $event = [
        'type' => 'charge.refunded',
        'id' => 'evt_refund_123',
        'data' => [
            'object' => [
                'id' => 'ch_test_123',
                'amount_refunded' => 5000, // 50.00 in cents
            ],
        ],
    ];

    $response = $this->postJson('/api/stripe/webhook', [
        'stripe_event' => $event,
    ]);

    $response->assertStatus(200);
});

it('prevents duplicate payment confirmation', function () {
    $tenant = Tenant::factory()->create();

    $topup = BalanceTopup::factory()->create([
        'tenant_id' => $tenant->id,
        'stripe_payment_intent_id' => 'pi_test_duplicate',
        'status' => 'completed', // Already completed
    ]);

    $event = [
        'type' => 'payment_intent.succeeded',
        'id' => 'evt_duplicate_123',
        'data' => [
            'object' => [
                'id' => 'pi_test_duplicate',
                'status' => 'succeeded',
                'latest_charge' => 'ch_test_123',
            ],
        ],
    ];

    $response = $this->postJson('/api/stripe/webhook', [
        'stripe_event' => $event,
    ]);

    $response->assertStatus(200);

    // Topup should remain completed (not processed twice)
    $topup->refresh();
    expect($topup->status)->toBe('completed');
});
