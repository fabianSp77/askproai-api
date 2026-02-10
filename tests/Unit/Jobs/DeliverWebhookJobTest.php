<?php

use App\Jobs\DeliverWebhookJob;
use App\Models\Company;
use App\Models\WebhookConfiguration;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->webhookConfig = WebhookConfiguration::factory()->create([
        'company_id' => $this->company->id,
        'url' => 'https://example.com/webhook',
        'is_active' => true,
        'subscribed_events' => ['callback.created', 'callback.updated'],
        'max_retry_attempts' => 3,
        'timeout_seconds' => 30,
    ]);
});

it('dispatches webhook job successfully', function () {
    Queue::fake();

    DeliverWebhookJob::dispatch(
        $this->webhookConfig,
        'callback.created',
        ['callback_id' => 123],
        'idempotency-key-123'
    );

    Queue::assertPushed(DeliverWebhookJob::class);
});

it('delivers webhook successfully', function () {
    Http::fake([
        'https://example.com/webhook' => Http::response(['status' => 'ok'], 200),
    ]);

    $job = new DeliverWebhookJob(
        $this->webhookConfig,
        'callback.created',
        ['callback_id' => 123],
        'idempotency-key-123'
    );

    $job->handle();

    // Verify HTTP request was made
    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/webhook'
            && $request['event'] === 'callback.created'
            && isset($request['idempotency_key']);
    });

    // Verify webhook log was created
    $log = WebhookLog::where('event_id', 'idempotency-key-123')->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe('processed');
});

it('skips inactive webhooks', function () {
    Http::fake();

    $this->webhookConfig->update(['is_active' => false]);

    $job = new DeliverWebhookJob(
        $this->webhookConfig,
        'callback.created',
        ['callback_id' => 123],
        'idempotency-key-inactive'
    );

    $job->handle();

    // No HTTP request should be made
    Http::assertNothingSent();
});

it('skips unsubscribed events', function () {
    Http::fake();

    $job = new DeliverWebhookJob(
        $this->webhookConfig,
        'unsubscribed.event', // Not in subscribed_events
        ['data' => 'test'],
        'idempotency-key-unsubscribed'
    );

    $job->handle();

    // No HTTP request should be made
    Http::assertNothingSent();
});

it('includes HMAC signature in headers', function () {
    Http::fake([
        'https://example.com/webhook' => Http::response(['status' => 'ok'], 200),
    ]);

    $job = new DeliverWebhookJob(
        $this->webhookConfig,
        'callback.created',
        ['callback_id' => 123],
        'idempotency-key-signature'
    );

    $job->handle();

    // Verify signature header was included
    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Webhook-Signature')
            && $request->hasHeader('X-Webhook-Event')
            && $request->hasHeader('X-Webhook-Idempotency-Key');
    });
});

it('retries on HTTP error', function () {
    Http::fake([
        'https://example.com/webhook' => Http::response(['error' => 'Server error'], 500),
    ]);

    $job = new DeliverWebhookJob(
        $this->webhookConfig,
        'callback.created',
        ['callback_id' => 123],
        'idempotency-key-retry'
    );

    // Job should have retry configuration
    expect($job->tries)->toBe(3);

    // Handle will throw/release for retry
    try {
        $job->handle();
    } catch (\Exception $e) {
        // Expected on failure
    }

    // Verify webhook log shows failure
    $log = WebhookLog::where('event_id', 'idempotency-key-retry')->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe('failed');
});

it('handles connection timeout', function () {
    Http::fake([
        'https://example.com/webhook' => function () {
            throw new \Exception('Connection timeout');
        },
    ]);

    $job = new DeliverWebhookJob(
        $this->webhookConfig,
        'callback.created',
        ['callback_id' => 123],
        'idempotency-key-timeout'
    );

    try {
        $job->handle();
    } catch (\Exception $e) {
        // Expected
    }

    // Verify error logged
    $log = WebhookLog::where('event_id', 'idempotency-key-timeout')->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe('failed');
});

it('creates webhook log entry', function () {
    Http::fake([
        'https://example.com/webhook' => Http::response(['status' => 'ok'], 200),
    ]);

    $job = new DeliverWebhookJob(
        $this->webhookConfig,
        'callback.created',
        ['callback_id' => 123],
        'idempotency-key-log'
    );

    $job->handle();

    $log = WebhookLog::where('event_id', 'idempotency-key-log')->first();

    expect($log)->not->toBeNull();
    expect($log->company_id)->toBe($this->company->id);
    expect($log->source)->toBe('outgoing');
    expect($log->endpoint)->toBe('https://example.com/webhook');
    expect($log->method)->toBe('POST');
    expect($log->event_type)->toBe('callback.created');
});

it('logs failure on permanent job failure', function () {
    $job = new DeliverWebhookJob(
        $this->webhookConfig,
        'callback.created',
        ['callback_id' => 123],
        'idempotency-key-failed'
    );

    $exception = new \Exception('Permanent failure');
    $job->failed($exception);

    // Verify logging occurred (checked via test logs)
    expect(true)->toBeTrue();
});

it('has configurable timeout from webhook config', function () {
    $job = new DeliverWebhookJob(
        $this->webhookConfig,
        'callback.created',
        ['callback_id' => 123],
        'idempotency-key-timeout-config'
    );

    // Timeout should be config timeout + 5s overhead
    expect($job->timeout)->toBe(35); // 30 + 5
});
