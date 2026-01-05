# Webhooks

AskPro API Gateway uses webhooks for real-time event notifications and integrations.

## Overview

Webhooks enable:
- Real-time event notifications
- Third-party integrations
- Asynchronous processing
- System-to-system communication

## Incoming Webhooks

### Retell.ai Webhooks

Endpoint: `POST /webhooks/retell/{event}`

| Event | Endpoint | Description |
|-------|----------|-------------|
| Call Started | `/webhooks/retell/call-started` | Voice call initiated |
| Call Ended | `/webhooks/retell/call-ended` | Voice call completed |
| Call Analyzed | `/webhooks/retell/call-analyzed` | Post-call analysis ready |

See [Retell Integration](/guide/retell) for details.

### Cal.com Webhooks

Endpoint: `POST /webhooks/calcom`

| Event | Description |
|-------|-------------|
| `BOOKING_CREATED` | New booking made |
| `BOOKING_RESCHEDULED` | Booking time changed |
| `BOOKING_CANCELLED` | Booking cancelled |
| `BOOKING_CONFIRMED` | Booking confirmed |

See [Cal.com Integration](/guide/calcom) for details.

## Outgoing Webhooks (Service Gateway)

The Service Gateway can send webhooks to external systems when service cases are created or updated.

### Configuration

```php
// Service Output Configuration
ServiceOutputConfiguration::create([
    'company_id' => $company->id,
    'category_id' => $category->id,
    'output_type' => 'webhook',
    'webhook_url' => 'https://example.com/webhook',
    'webhook_method' => 'POST',
    'webhook_headers' => [
        'Authorization' => 'Bearer token123',
        'Content-Type' => 'application/json',
    ],
    'webhook_template' => '{"ticket": {"title": "{{subject}}", "body": "{{description}}"}}',
]);
```

### Webhook Presets

AskPro includes presets for popular systems:

| System | Preset Name |
|--------|-------------|
| Jira Service Management | `jira` |
| ServiceNow | `servicenow` |
| OTRS | `otrs` |
| Zendesk | `zendesk` |
| Freshdesk | `freshdesk` |
| Slack | `slack` |
| Microsoft Teams | `teams` |
| Generic REST | `generic` |

### Template Variables

Available variables in webhook templates:

| Variable | Description |
|----------|-------------|
| `{{case_id}}` | Service case ID |
| `{{subject}}` | Case subject |
| `{{description}}` | Full description |
| `{{category}}` | Category name |
| `{{priority}}` | Priority level |
| `{{customer_name}}` | Customer name |
| `{{customer_phone}}` | Customer phone |
| `{{customer_email}}` | Customer email |
| `{{created_at}}` | Creation timestamp |
| `{{call_recording_url}}` | Recording URL if available |

### Example Payloads

#### Jira

```json
{
  "fields": {
    "project": {"key": "SUPPORT"},
    "summary": "{{subject}}",
    "description": "{{description}}\n\nCustomer: {{customer_name}}\nPhone: {{customer_phone}}",
    "issuetype": {"name": "Service Request"},
    "priority": {"name": "{{priority}}"}
  }
}
```

#### Slack

```json
{
  "text": "New support case: {{subject}}",
  "blocks": [
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "*{{subject}}*\n{{description}}"
      }
    },
    {
      "type": "context",
      "elements": [
        {"type": "mrkdwn", "text": "Customer: {{customer_name}}"},
        {"type": "mrkdwn", "text": "Priority: {{priority}}"}
      ]
    }
  ]
}
```

## Webhook Security

### Signature Verification

All incoming webhooks should include signature verification:

```php
// app/Http/Middleware/VerifyWebhookSignature.php
public function handle($request, $next, $provider)
{
    $signature = $request->header("X-{$provider}-Signature");
    $secret = config("services.{$provider}.webhook_secret");

    $expected = hash_hmac('sha256', $request->getContent(), $secret);

    if (!hash_equals($expected, $signature)) {
        Log::warning("Invalid webhook signature from {$provider}");
        abort(401);
    }

    return $next($request);
}
```

### Outgoing Webhook Authentication

Configure authentication for outgoing webhooks:

```php
// Basic Auth
'webhook_headers' => [
    'Authorization' => 'Basic ' . base64_encode('user:pass'),
]

// Bearer Token
'webhook_headers' => [
    'Authorization' => 'Bearer your-api-token',
]

// API Key
'webhook_headers' => [
    'X-API-Key' => 'your-api-key',
]
```

## Retry Logic

### Outgoing Webhook Retries

Failed webhooks are retried with exponential backoff:

| Attempt | Delay |
|---------|-------|
| 1 | Immediate |
| 2 | 60 seconds |
| 3 | 120 seconds |
| 4 | 300 seconds |
| 5 | 600 seconds |

```php
// app/Jobs/ServiceGateway/DeliverCaseOutputJob.php
public $tries = 5;
public $backoff = [60, 120, 300, 600];

public function handle()
{
    $response = Http::timeout(30)
        ->withHeaders($this->config->webhook_headers)
        ->post($this->config->webhook_url, $this->payload);

    if (!$response->successful()) {
        throw new WebhookDeliveryException($response->status());
    }
}
```

### Failure Notifications

After all retries fail:

```php
public function failed(Throwable $exception)
{
    // Notify admin
    Notification::send(
        User::admins()->get(),
        new WebhookDeliveryFailed($this->serviceCase, $exception)
    );

    // Update case status
    $this->serviceCase->update([
        'delivery_status' => 'failed',
        'delivery_error' => $exception->getMessage(),
    ]);
}
```

## Webhook Logs

### Exchange Log

All webhook exchanges are logged:

```php
ServiceGatewayExchangeLog::create([
    'service_case_id' => $case->id,
    'direction' => 'outbound',
    'endpoint' => $webhookUrl,
    'request_payload' => $payload,
    'response_status' => $response->status(),
    'response_body' => $response->body(),
    'duration_ms' => $duration,
]);
```

### Viewing Logs

Access webhook logs in the admin panel:
- Navigate to Service Gateway → Exchange Logs
- Filter by case, status, or date range
- Export logs for debugging

## Testing Webhooks

### Local Development

Use ngrok for local testing:

```bash
ngrok http 8000

# Configure webhook URL in external service:
# https://xxxxx.ngrok.io/webhooks/retell/call-ended
```

### Webhook Testing Endpoint

```php
// Test outgoing webhook configuration
Route::post('/admin/test-webhook', function (Request $request) {
    $config = ServiceOutputConfiguration::findOrFail($request->config_id);

    $testPayload = [
        'test' => true,
        'timestamp' => now()->toIso8601String(),
        'message' => 'AskPro webhook test',
    ];

    $response = Http::timeout(10)
        ->withHeaders($config->webhook_headers ?? [])
        ->post($config->webhook_url, $testPayload);

    return [
        'success' => $response->successful(),
        'status' => $response->status(),
        'body' => $response->body(),
    ];
})->middleware('auth');
```

### PHPUnit Tests

```php
// tests/Feature/WebhookTest.php
public function test_outgoing_webhook_delivery()
{
    Http::fake([
        'example.com/webhook' => Http::response(['success' => true], 200),
    ]);

    $case = ServiceCase::factory()->create();
    $config = ServiceOutputConfiguration::factory()->create([
        'output_type' => 'webhook',
        'webhook_url' => 'https://example.com/webhook',
    ]);

    dispatch(new DeliverCaseOutputJob($case, $config));

    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/webhook';
    });
}
```

## Best Practices

1. **Always verify signatures** for incoming webhooks
2. **Use HTTPS** for all webhook endpoints
3. **Implement idempotency** - handle duplicate deliveries gracefully
4. **Set reasonable timeouts** (30 seconds recommended)
5. **Log all exchanges** for debugging
6. **Monitor failure rates** and alert on anomalies
7. **Use queues** for outgoing webhooks to avoid blocking
