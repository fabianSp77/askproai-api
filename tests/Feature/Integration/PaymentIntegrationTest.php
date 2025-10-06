<?php

use App\Services\PaymentService;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\BalanceTopup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Stripe\Stripe;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->paymentService = app(PaymentService::class);
    $this->customer = Customer::factory()->create([
        'email' => 'test@example.com',
        'phone' => '+49 30 12345678',
        'balance' => 100.00
    ]);
    $this->appointment = Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'price' => 75.00,
        'status' => 'scheduled'
    ]);
});

it('can process stripe payment successfully', function () {
    // Mock Stripe API
    Http::fake([
        'api.stripe.com/*' => Http::response([
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'amount' => 7500,
            'currency' => 'eur'
        ], 200)
    ]);

    $paymentData = [
        'method' => 'stripe',
        'payment_method_id' => 'pm_test_123',
        'currency' => 'EUR'
    ];

    $result = $this->paymentService->processAppointmentPayment($this->appointment, $paymentData);

    expect($result->success)->toBeTrue();
    expect($result->transactionId)->toBe('pi_test_123');

    // Check database records
    $payment = Payment::where('appointment_id', $this->appointment->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->amount)->toBe(75.00);
    expect($payment->status)->toBe('completed');
    expect($payment->method)->toBe('stripe');
});

it('can process balance payment when sufficient funds', function () {
    $paymentData = [
        'method' => 'balance'
    ];

    $result = $this->paymentService->processAppointmentPayment($this->appointment, $paymentData);

    expect($result->success)->toBeTrue();
    expect($result->newBalance)->toBe(25.00);

    // Check customer balance was deducted
    $this->customer->refresh();
    expect($this->customer->balance)->toBe(25.00);

    // Check payment record
    $payment = Payment::where('appointment_id', $this->appointment->id)->first();
    expect($payment->method)->toBe('balance');
    expect($payment->status)->toBe('completed');
});

it('fails balance payment when insufficient funds', function () {
    $this->appointment->update(['price' => 150.00]);

    $paymentData = [
        'method' => 'balance'
    ];

    $result = $this->paymentService->processAppointmentPayment($this->appointment, $paymentData);

    expect($result->success)->toBeFalse();
    expect($result->message)->toContain('Insufficient balance');
    expect($result->currentBalance)->toBe(100.00);
    expect($result->requiredAmount)->toBe(150.00);
});

it('can process balance topup with bonus', function () {
    // Mock Stripe for topup payment
    Http::fake([
        'api.stripe.com/*' => Http::response([
            'id' => 'pi_topup_123',
            'status' => 'succeeded'
        ], 200)
    ]);

    $paymentData = [
        'method' => 'stripe',
        'payment_method_id' => 'pm_test_456'
    ];

    $result = $this->paymentService->processBalanceTopup($this->customer, 200.00, $paymentData);

    expect($result->success)->toBeTrue();
    expect($result->bonusReceived)->toBe(25.00); // Based on bonus tiers

    // Check customer balance includes topup + bonus
    $this->customer->refresh();
    expect($this->customer->balance)->toBe(325.00); // 100 + 200 + 25

    // Check topup record
    $topup = BalanceTopup::where('customer_id', $this->customer->id)->first();
    expect($topup->amount)->toBe(200.00);
    expect($topup->bonus)->toBe(25.00);
    expect($topup->total_credit)->toBe(225.00);
});

it('can process full refund', function () {
    // Create completed payment
    $payment = Payment::factory()->create([
        'customer_id' => $this->customer->id,
        'appointment_id' => $this->appointment->id,
        'amount' => 75.00,
        'method' => 'stripe',
        'status' => 'completed',
        'transaction_id' => 'pi_test_789'
    ]);

    // Mock Stripe refund API
    Http::fake([
        'api.stripe.com/*' => Http::response([
            'id' => 'rf_test_123',
            'status' => 'succeeded',
            'amount' => 7500
        ], 200)
    ]);

    $result = $this->paymentService->processRefund($payment, null, 'Customer request');

    expect($result->success)->toBeTrue();
    expect($result->amount)->toBe(75.00);

    // Check payment status
    $payment->refresh();
    expect($payment->status)->toBe('refunded');
    expect($payment->refunded_amount)->toBe(75.00);
});

it('can process partial refund', function () {
    $payment = Payment::factory()->create([
        'customer_id' => $this->customer->id,
        'amount' => 100.00,
        'method' => 'stripe',
        'status' => 'completed',
        'transaction_id' => 'pi_test_partial'
    ]);

    Http::fake([
        'api.stripe.com/*' => Http::response([
            'id' => 'rf_partial_123',
            'status' => 'succeeded',
            'amount' => 3000
        ], 200)
    ]);

    $result = $this->paymentService->processRefund($payment, 30.00, 'Partial refund');

    expect($result->success)->toBeTrue();
    expect($result->amount)->toBe(30.00);

    $payment->refresh();
    expect($payment->status)->toBe('partially_refunded');
    expect($payment->refunded_amount)->toBe(30.00);
});

it('can handle paypal payment redirect', function () {
    // PayPal returns redirect URL for approval
    $paymentData = [
        'method' => 'paypal',
        'currency' => 'EUR'
    ];

    $result = $this->paymentService->processAppointmentPayment($this->appointment, $paymentData);

    expect($result->success)->toBeFalse();
    expect($result->requiresAction)->toBeTrue();
    expect($result->redirectUrl)->toContain('paypal');
});

it('tracks payment statistics correctly', function () {
    // Create various payments
    Payment::factory(5)->create([
        'method' => 'stripe',
        'status' => 'completed',
        'amount' => 100.00
    ]);

    Payment::factory(3)->create([
        'method' => 'paypal',
        'status' => 'completed',
        'amount' => 50.00
    ]);

    Payment::factory(2)->create([
        'method' => 'balance',
        'status' => 'refunded',
        'amount' => 75.00
    ]);

    $stats = $this->paymentService->getPaymentStatistics();

    expect($stats['total_revenue'])->toBe(800.00); // 5*100 + 3*50 + 2*75
    expect($stats['total_payments'])->toBe(10);
    expect($stats['average_payment'])->toBe(80.00);
});

it('saves payment methods for customer', function () {
    $methodData = [
        'type' => 'card',
        'provider' => 'stripe',
        'provider_id' => 'pm_test_saved',
        'last_four' => '4242',
        'brand' => 'Visa',
        'exp_month' => 12,
        'exp_year' => 2025,
        'is_default' => true
    ];

    $paymentMethod = $this->paymentService->savePaymentMethod($this->customer, $methodData);

    expect($paymentMethod->customer_id)->toBe($this->customer->id);
    expect($paymentMethod->last_four)->toBe('4242');
    expect($paymentMethod->is_default)->toBeTrue();

    // Check it's saved in database
    $this->assertDatabaseHas('payment_methods', [
        'customer_id' => $this->customer->id,
        'provider_id' => 'pm_test_saved'
    ]);
});

it('handles payment failures gracefully', function () {
    // Mock Stripe API failure
    Http::fake([
        'api.stripe.com/*' => Http::response([
            'error' => [
                'message' => 'Card was declined',
                'code' => 'card_declined'
            ]
        ], 402)
    ]);

    $paymentData = [
        'method' => 'stripe',
        'payment_method_id' => 'pm_declined'
    ];

    $result = $this->paymentService->processAppointmentPayment($this->appointment, $paymentData);

    expect($result->success)->toBeFalse();
    expect($result->message)->toContain('declined');

    // Check no payment record was created
    $payment = Payment::where('appointment_id', $this->appointment->id)->first();
    expect($payment)->toBeNull();

    // Check appointment payment status
    $this->appointment->refresh();
    expect($this->appointment->payment_status)->not->toBe('paid');
});

it('calculates topup bonuses correctly', function () {
    $testCases = [
        ['amount' => 50, 'expected_bonus' => 0],
        ['amount' => 100, 'expected_bonus' => 10],
        ['amount' => 200, 'expected_bonus' => 25],
        ['amount' => 500, 'expected_bonus' => 75],
        ['amount' => 1000, 'expected_bonus' => 200],
    ];

    foreach ($testCases as $case) {
        // Mock successful payment
        Http::fake([
            'api.stripe.com/*' => Http::response([
                'id' => 'pi_test_' . uniqid(),
                'status' => 'succeeded'
            ], 200)
        ]);

        $result = $this->paymentService->processBalanceTopup(
            $this->customer,
            $case['amount'],
            ['method' => 'stripe', 'payment_method_id' => 'pm_test']
        );

        expect($result->bonusReceived)->toBe($case['expected_bonus']);
    }
});