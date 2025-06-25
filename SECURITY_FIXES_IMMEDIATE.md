# SECURITY FIXES - SOFORT UMSETZBAR

## 1. API Key Encryption in RetellCustomerRecognitionController

### Problem
- Kundendaten inkl. VIP-Status werden unverschlüsselt im Cache gespeichert
- Keine Verschlüsselung sensibler Daten in API-Responses

### Sofortige Lösung

**Datei: `/var/www/api-gateway/app/Http/Controllers/Api/RetellCustomerRecognitionController.php`**

```php
// Zeile 112-118 ersetzen:
// ALT:
if (isset($data['call_id'])) {
    Cache::put(
        "retell:customer:{$data['call_id']}",
        $response,
        3600 // 1 Stunde
    );
}

// NEU:
if (isset($data['call_id'])) {
    // Verschlüssele sensitive Daten vor Cache-Speicherung
    $encryptedResponse = $response;
    $encryptedResponse['customer_name'] = encrypt($response['customer_name']);
    $encryptedResponse['notes'] = isset($response['notes']) ? encrypt($response['notes']) : null;
    
    Cache::put(
        "retell:customer:{$data['call_id']}",
        $encryptedResponse,
        3600 // 1 Stunde
    );
}
```

## 2. Webhook Signature Validation für Customer Recognition Endpoints

### Problem
- Neue Endpoints haben KEINE Signatur-Validierung
- Jeder kann diese Endpoints aufrufen

### Sofortige Lösung

**Datei: `/var/www/api-gateway/routes/api.php`**

```php
// Zeile 57-62 ersetzen:
// ALT:
Route::post('/identify-customer', [RetellCustomerRecognitionController::class, 'identifyCustomer'])
    ->name('api.retell.identify-customer');
Route::post('/save-preference', [RetellCustomerRecognitionController::class, 'savePreference'])
    ->name('api.retell.save-preference');
Route::post('/apply-vip-benefits', [RetellCustomerRecognitionController::class, 'applyVipBenefits'])
    ->name('api.retell.apply-vip-benefits');

// NEU:
Route::post('/identify-customer', [RetellCustomerRecognitionController::class, 'identifyCustomer'])
    ->middleware(['verify.retell.signature', 'throttle:60,1'])
    ->name('api.retell.identify-customer');
Route::post('/save-preference', [RetellCustomerRecognitionController::class, 'savePreference'])
    ->middleware(['verify.retell.signature', 'throttle:30,1'])
    ->name('api.retell.save-preference');
Route::post('/apply-vip-benefits', [RetellCustomerRecognitionController::class, 'applyVipBenefits'])
    ->middleware(['verify.retell.signature', 'throttle:30,1'])
    ->name('api.retell.apply-vip-benefits');
```

## 3. SQL Injection Schutz in EnhancedCustomerService

### Problem
- DB::raw() Verwendung ohne Parameterisierung
- Potenzielle SQL Injection bei Fuzzy-Search

### Sofortige Lösung

**Datei: `/var/www/api-gateway/app/Services/Customer/EnhancedCustomerService.php`**

```php
// Zeile 382 ersetzen:
// ALT:
'usage_count' => DB::raw('usage_count + 1'),

// NEU:
'usage_count' => DB::raw('usage_count + ?')->setBindings([1]),
```

**Zusätzlich neue Methode für sichere Fuzzy-Suche hinzufügen:**

```php
protected function fuzzyPhoneSearch(string $phoneNumber, int $companyId): ?Customer
{
    // Verwende Parameterisierte Query statt direkter String-Konkatenation
    $normalized = preg_replace('/[^0-9]/', '', $phoneNumber);
    
    return Customer::where('company_id', $companyId)
        ->where(function ($query) use ($normalized) {
            // Sichere LIKE-Suche mit Escaping
            $query->where('phone', 'LIKE', '%' . $normalized . '%')
                  ->orWhere('phone', 'LIKE', str_replace('+49', '0', $normalized))
                  ->orWhere('phone', 'LIKE', '+49' . ltrim($normalized, '0'));
        })
        ->first();
}
```

## 4. VIP-Daten Schutz (PII)

### Problem
- VIP-Status und persönliche Notizen werden unverschlüsselt übertragen
- Keine Maskierung in Logs

### Sofortige Lösung

**Datei: `/var/www/api-gateway/app/Http/Controllers/Api/RetellCustomerRecognitionController.php`**

```php
// Nach Zeile 33 hinzufügen:
// Maskiere sensitive Daten in Logs
$maskedData = $data;
if (isset($maskedData['phone_number'])) {
    $maskedData['phone_number'] = substr($maskedData['phone_number'], 0, 3) . '****' . substr($maskedData['phone_number'], -2);
}

Log::info('Retell customer identification request', [
    'data' => $maskedData,  // Verwende maskierte Daten
    'call_id' => $data['call_id'] ?? null
]);

// Zeile 120-123 ersetzen:
Log::info('Customer identified successfully', [
    'customer_id' => $customerData['customer']['id'],
    'vip_status' => $customerData['vip_status']['status'],
    'has_notes' => !empty($customerData['personalization']['notes'])  // Keine echten Notizen loggen
]);
```

## 5. Rate Limiting für neue API Endpoints

### Problem
- Keine Rate Limits auf Customer Recognition Endpoints
- DDoS-Anfälligkeit

### Sofortige Lösung

**Bereits in Punkt 2 integriert mit throttle Middleware**

**Zusätzlich globales Rate Limiting konfigurieren:**

**Datei: `/var/www/api-gateway/app/Providers/RouteServiceProvider.php`**

```php
// In der boot() Methode hinzufügen:
RateLimiter::for('retell-functions', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});

RateLimiter::for('retell-vip', function (Request $request) {
    return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});
```

## 6. Zusätzliche Sicherheitsmaßnahmen

### Input Validation Middleware

**Neue Datei: `/var/www/api-gateway/app/Http/Middleware/ValidateRetellInput.php`**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ValidateRetellInput
{
    public function handle(Request $request, Closure $next)
    {
        $rules = [
            'args.phone_number' => 'nullable|string|max:20|regex:/^[+0-9\s\-()]+$/',
            'args.customer_id' => 'nullable|integer|exists:customers,id',
            'args.call_id' => 'nullable|string|max:100|alpha_dash',
            'args.preference_type' => 'nullable|string|in:time,weekday,staff,service',
            'args.preference_key' => 'nullable|string|max:50|alpha_dash',
            'args.preference_value' => 'nullable|string|max:255'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid input',
                'details' => $validator->errors()
            ], 422);
        }

        return $next($request);
    }
}
```

**In Kernel.php registrieren:**

```php
protected $routeMiddleware = [
    // ... andere Middleware
    'validate.retell' => \App\Http\Middleware\ValidateRetellInput::class,
];
```

**Routes updaten:**

```php
Route::middleware(['retell', 'validate.retell'])->group(function () {
    // Customer recognition endpoints hier
});
```

## Deployment-Schritte

1. **Backup erstellen**
   ```bash
   php artisan askproai:backup --type=critical --encrypt
   ```

2. **Code deployen**
   ```bash
   git add -A
   git commit -m "fix: Critical security fixes for customer recognition endpoints"
   git push origin main
   ```

3. **Cache leeren**
   ```bash
   php artisan cache:clear
   php artisan route:cache
   php artisan config:cache
   ```

4. **Monitoring aktivieren**
   ```bash
   tail -f storage/logs/laravel.log | grep -E "Retell|Security|Error"
   ```

## Verifizierung

```bash
# Test mit curl (sollte 401 zurückgeben ohne Signatur)
curl -X POST https://api.askproai.de/api/retell/identify-customer \
  -H "Content-Type: application/json" \
  -d '{"args":{"phone_number":"+491234567890"}}'

# Test Rate Limiting
for i in {1..100}; do
  curl -X POST https://api.askproai.de/api/retell/identify-customer \
    -H "Content-Type: application/json" \
    -H "X-Retell-Signature: test" \
    -d '{"test":"data"}'
done
```

## Geschätzte Zeit
- Implementierung: 1-2 Stunden
- Testing: 30 Minuten
- Deployment: 15 Minuten

**WICHTIG**: Diese Fixes adressieren die kritischsten Sicherheitslücken ohne Breaking Changes. Weitere umfassende Security-Maßnahmen sollten in Phase 2 folgen.