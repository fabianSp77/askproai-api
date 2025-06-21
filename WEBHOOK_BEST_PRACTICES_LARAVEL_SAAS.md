# 🚀 Webhook Best Practices für Laravel SaaS - AskProAI

## 📋 Inhaltsverzeichnis
1. [Aktuelle Implementierung](#aktuelle-implementierung)
2. [Best Practice Implementierung](#best-practice-implementierung)
3. [Security Guidelines](#security-guidelines)
4. [Multi-Tenancy Handling](#multi-tenancy-handling)
5. [Error Handling & Recovery](#error-handling--recovery)
6. [Performance Optimization](#performance-optimization)
7. [Monitoring & Debugging](#monitoring--debugging)

## Aktuelle Implementierung

### ❌ Probleme der aktuellen Implementierung

1. **Inkonsistente Signatur-Verifikation**
   - 3 verschiedene Verify-Middlewares mit unterschiedlichen Implementierungen
   - Signatur-Algorithmus von Retell nicht korrekt implementiert
   - Debug-Endpoints ohne jegliche Sicherheit

2. **Fehlende Multi-Tenancy**
   - Keine automatische Tenant-Zuordnung
   - TenantScope Konflikte bei Webhook-Verarbeitung
   - Manuelle Company-Zuordnung erforderlich

3. **Synchrone Verarbeitung**
   - Webhooks werden synchron verarbeitet
   - Keine Queue-Nutzung
   - Timeout-Risiko bei langsamen Operationen

## Best Practice Implementierung

### 1. Unified Webhook Handler mit Middleware Pipeline

```php
<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Services\Webhook\WebhookProcessor;
use App\Services\Webhook\WebhookValidator;
use Illuminate\Support\Facades\Log;

class UnifiedWebhookController extends Controller
{
    public function __construct(
        private WebhookProcessor $processor,
        private WebhookValidator $validator
    ) {}

    public function handle(Request $request, string $provider)
    {
        // 1. Validate webhook source
        if (!$this->validator->isValidProvider($provider)) {
            abort(404);
        }

        // 2. Log incoming webhook
        $webhookId = $this->logIncomingWebhook($request, $provider);

        // 3. Queue for async processing
        dispatch(new ProcessWebhookJob($webhookId, $provider, $request->all()))
            ->onQueue('webhooks');

        // 4. Return immediate acknowledgment
        return response()->json([
            'status' => 'acknowledged',
            'webhook_id' => $webhookId
        ], 200);
    }

    private function logIncomingWebhook(Request $request, string $provider): string
    {
        $webhook = WebhookLog::create([
            'provider' => $provider,
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'ip_address' => $request->ip(),
            'status' => 'pending'
        ]);

        return $webhook->id;
    }
}
```

### 2. Robust Signature Verification Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Webhook\SignatureVerifier;
use Illuminate\Support\Facades\Log;

class VerifyWebhookSignature
{
    public function __construct(
        private SignatureVerifier $verifier
    ) {}

    public function handle(Request $request, Closure $next, string $provider): Response
    {
        // Skip in local/testing
        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        try {
            $isValid = $this->verifier->verify($provider, $request);
            
            if (!$isValid) {
                Log::warning('Webhook signature verification failed', [
                    'provider' => $provider,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl()
                ]);
                
                abort(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            Log::error('Webhook signature verification error', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            abort(500, 'Verification error');
        }

        return $next($request);
    }
}
```

### 3. Signature Verifier Service

```php
<?php

namespace App\Services\Webhook;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SignatureVerifier
{
    private array $verifiers = [
        'retell' => RetellSignatureVerifier::class,
        'calcom' => CalcomSignatureVerifier::class,
        'stripe' => StripeSignatureVerifier::class,
    ];

    public function verify(string $provider, Request $request): bool
    {
        if (!isset($this->verifiers[$provider])) {
            throw new \InvalidArgumentException("Unknown provider: {$provider}");
        }

        $verifierClass = $this->verifiers[$provider];
        $verifier = app($verifierClass);

        // Implement rate limiting
        $cacheKey = "webhook_verify:{$provider}:{$request->ip()}";
        $attempts = Cache::get($cacheKey, 0);
        
        if ($attempts > 10) {
            throw new \Exception('Too many verification attempts');
        }
        
        Cache::put($cacheKey, $attempts + 1, 300); // 5 minutes

        return $verifier->verify($request);
    }
}
```

### 4. Provider-Specific Verifier (Retell Example)

```php
<?php

namespace App\Services\Webhook\Verifiers;

use Illuminate\Http\Request;

class RetellSignatureVerifier implements WebhookVerifierInterface
{
    public function verify(Request $request): bool
    {
        $signature = $request->header('X-Retell-Signature');
        $timestamp = $request->header('X-Retell-Timestamp');
        
        if (!$signature || !$timestamp) {
            return false;
        }

        // Prevent replay attacks
        if ($this->isExpired($timestamp)) {
            return false;
        }

        $secret = config('services.retell.webhook_secret');
        $payload = $request->getContent();
        
        // Retell specific: timestamp.payload
        $signaturePayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signaturePayload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    private function isExpired(string $timestamp): bool
    {
        $webhookTime = is_numeric($timestamp) ? (int)$timestamp : strtotime($timestamp);
        $currentTime = time();
        
        // 5 minute window
        return abs($currentTime - $webhookTime) > 300;
    }
}
```

### 5. Async Webhook Processing Job

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Webhook\WebhookProcessor;
use App\Models\WebhookLog;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(
        private string $webhookId,
        private string $provider,
        private array $payload
    ) {}

    public function handle(WebhookProcessor $processor)
    {
        $webhook = WebhookLog::find($this->webhookId);
        
        if (!$webhook) {
            return;
        }

        try {
            // Process with tenant context
            $result = $processor->process($this->provider, $this->payload);
            
            $webhook->update([
                'status' => 'success',
                'processed_at' => now(),
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            $webhook->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'error_count' => $webhook->error_count + 1
            ]);
            
            // Rethrow for retry
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        WebhookLog::where('id', $this->webhookId)->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage()
        ]);
        
        // Alert monitoring
        Log::critical('Webhook processing failed permanently', [
            'webhook_id' => $this->webhookId,
            'provider' => $this->provider,
            'error' => $exception->getMessage()
        ]);
    }
}
```

### 6. Multi-Tenant Webhook Processor

```php
<?php

namespace App\Services\Webhook;

use App\Services\TenantResolver;
use Illuminate\Support\Facades\DB;

class WebhookProcessor
{
    private array $processors = [
        'retell' => RetellWebhookProcessor::class,
        'calcom' => CalcomWebhookProcessor::class,
        'stripe' => StripeWebhookProcessor::class,
    ];

    public function __construct(
        private TenantResolver $tenantResolver
    ) {}

    public function process(string $provider, array $payload): array
    {
        if (!isset($this->processors[$provider])) {
            throw new \InvalidArgumentException("Unknown provider: {$provider}");
        }

        // Resolve tenant context
        $tenant = $this->tenantResolver->resolveFromWebhook($provider, $payload);
        
        if (!$tenant) {
            throw new \Exception('Unable to resolve tenant context');
        }

        // Process within tenant scope
        return DB::transaction(function () use ($provider, $payload, $tenant) {
            // Set tenant context
            app()->instance('current_tenant', $tenant);
            
            $processorClass = $this->processors[$provider];
            $processor = app($processorClass);
            
            return $processor->process($payload);
        });
    }
}
```

### 7. Tenant Resolver Service

```php
<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\Cache;

class TenantResolver
{
    public function resolveFromWebhook(string $provider, array $payload): ?Company
    {
        $resolver = match($provider) {
            'retell' => $this->resolveFromRetell($payload),
            'calcom' => $this->resolveFromCalcom($payload),
            'stripe' => $this->resolveFromStripe($payload),
            default => null
        };

        return $resolver;
    }

    private function resolveFromRetell(array $payload): ?Company
    {
        // Try phone number resolution
        $toNumber = $payload['call']['to_number'] ?? null;
        
        if ($toNumber) {
            $cacheKey = "tenant:phone:{$toNumber}";
            
            return Cache::remember($cacheKey, 3600, function () use ($toNumber) {
                // Check branches
                $branch = Branch::where('phone_number', $toNumber)
                    ->where('is_active', true)
                    ->first();
                    
                return $branch?->company;
            });
        }

        // Try agent resolution
        $agentId = $payload['call']['agent_id'] ?? null;
        if ($agentId) {
            return $this->resolveFromAgentId($agentId);
        }

        return null;
    }

    private function resolveFromCalcom(array $payload): ?Company
    {
        $eventTypeId = $payload['eventTypeId'] ?? null;
        
        if ($eventTypeId) {
            $branch = Branch::where('calcom_event_type_id', $eventTypeId)->first();
            return $branch?->company;
        }

        return null;
    }

    private function resolveFromStripe(array $payload): ?Company
    {
        $customerId = $payload['data']['object']['customer'] ?? null;
        
        if ($customerId) {
            return Company::where('stripe_customer_id', $customerId)->first();
        }

        return null;
    }
}
```

## Security Guidelines

### 1. Webhook URL Security
```php
// Use unguessable webhook URLs
Route::post('/webhook/{provider}/{token}', [WebhookController::class, 'handle'])
    ->where('token', '[a-zA-Z0-9]{32}')
    ->middleware(['webhook.signature:{provider}']);
```

### 2. IP Whitelisting (Optional)
```php
namespace App\Http\Middleware;

class WebhookIpWhitelist
{
    private array $whitelist = [
        'retell' => ['34.102.136.180', '35.245.238.5'],
        'stripe' => ['3.18.12.63', '3.130.192.231'],
    ];

    public function handle(Request $request, Closure $next, string $provider)
    {
        if (!app()->environment('production')) {
            return $next($request);
        }

        $clientIp = $request->ip();
        $allowedIps = $this->whitelist[$provider] ?? [];

        if (!in_array($clientIp, $allowedIps)) {
            Log::warning('Webhook from unauthorized IP', [
                'provider' => $provider,
                'ip' => $clientIp
            ]);
            
            abort(403);
        }

        return $next($request);
    }
}
```

### 3. Rate Limiting
```php
// In RouteServiceProvider
Route::middleware(['webhook.signature', 'throttle:webhooks'])
    ->prefix('webhook')
    ->group(function () {
        Route::post('/{provider}', [WebhookController::class, 'handle']);
    });

// Custom rate limiter
RateLimiter::for('webhooks', function (Request $request) {
    return Limit::perMinute(100)->by($request->ip());
});
```

## Multi-Tenancy Handling

### 1. Bypass Global Scopes for Webhooks
```php
// In WebhookProcessor
Branch::withoutGlobalScope(TenantScope::class)
    ->where('phone_number', $phoneNumber)
    ->first();
```

### 2. Set Tenant Context
```php
// In middleware or processor
app()->instance('current_company_id', $company->id);
app()->instance('current_tenant', $company);
```

## Error Handling & Recovery

### 1. Idempotency
```php
// Store processed webhook IDs
Schema::create('processed_webhooks', function (Blueprint $table) {
    $table->string('webhook_id')->primary();
    $table->string('provider');
    $table->timestamp('processed_at');
    $table->index(['provider', 'processed_at']);
});

// Check before processing
if (ProcessedWebhook::where('webhook_id', $webhookId)->exists()) {
    return ['status' => 'already_processed'];
}
```

### 2. Retry Failed Webhooks
```php
// Artisan command
php artisan webhooks:retry --provider=retell --hours=24
```

## Performance Optimization

### 1. Database Indexing
```php
// Migration
Schema::table('webhook_logs', function (Blueprint $table) {
    $table->index(['provider', 'status', 'created_at']);
    $table->index(['correlation_id']);
});
```

### 2. Chunked Processing
```php
// For bulk webhooks
collect($webhooks)->chunk(100)->each(function ($chunk) {
    ProcessWebhookBatch::dispatch($chunk)->onQueue('webhooks');
});
```

## Monitoring & Debugging

### 1. Structured Logging
```php
Log::channel('webhooks')->info('Webhook received', [
    'provider' => $provider,
    'event' => $payload['event'] ?? 'unknown',
    'correlation_id' => $correlationId,
    'tenant_id' => $tenant?->id,
    'duration_ms' => $duration
]);
```

### 2. Health Check Endpoint
```php
Route::get('/webhook/health', function () {
    $stats = Cache::remember('webhook_health', 60, function () {
        return [
            'last_24h' => WebhookLog::where('created_at', '>=', now()->subDay())->count(),
            'success_rate' => WebhookLog::where('created_at', '>=', now()->subDay())
                ->where('status', 'success')
                ->count() / WebhookLog::where('created_at', '>=', now()->subDay())->count() * 100,
            'providers' => WebhookLog::select('provider', DB::raw('count(*) as count'))
                ->where('created_at', '>=', now()->subDay())
                ->groupBy('provider')
                ->get()
        ];
    });
    
    return response()->json($stats);
});
```

### 3. Webhook Testing Tool
```bash
# Create test command
php artisan make:command TestWebhook

# Usage
php artisan webhook:test retell --payload=test-data.json
```

## Migration Plan von Current zu Best Practice

### Phase 1: Vorbereitung (1 Tag)
1. Backup aller webhook_logs
2. Deploy neue Webhook-Tables
3. Parallel-Betrieb vorbereiten

### Phase 2: Parallel-Betrieb (1 Woche)
1. Neue Endpoints aktivieren
2. Alte Endpoints weiterleiten
3. Monitoring beider Systeme

### Phase 3: Migration (1 Tag)
1. Webhook-URLs in externen Systemen umstellen
2. Alte Endpoints deaktivieren
3. Cleanup alter Code

### Phase 4: Optimierung (Ongoing)
1. Performance-Metriken analysieren
2. Retry-Strategien optimieren
3. Monitoring ausbauen

## Beispiel-Implementation für Retell

```php
// routes/api.php
Route::post('/webhook/retell/{token}', [WebhookController::class, 'handle'])
    ->defaults('provider', 'retell')
    ->middleware(['webhook.signature:retell', 'webhook.ratelimit']);

// .env
RETELL_WEBHOOK_TOKEN=random_32_character_string_here
RETELL_WEBHOOK_SECRET=key_from_retell_dashboard

// config/webhooks.php
return [
    'providers' => [
        'retell' => [
            'token' => env('RETELL_WEBHOOK_TOKEN'),
            'secret' => env('RETELL_WEBHOOK_SECRET'),
            'timeout' => 5000, // 5 seconds
            'retry_times' => 3,
            'events' => ['call_started', 'call_ended', 'call_analyzed']
        ]
    ]
];
```

Diese Best Practices sorgen für:
- ✅ Sichere Webhook-Verarbeitung
- ✅ Skalierbare Architektur
- ✅ Fehlertoleranz
- ✅ Multi-Tenant-Fähigkeit
- ✅ Einfache Wartung und Debugging