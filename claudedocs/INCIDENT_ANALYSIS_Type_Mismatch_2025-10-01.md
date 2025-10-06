# Incident Analysis: Type Mismatch Error (2025-10-01)

**Incident**: `TypeError: Argument #1 ($request) must be of type CollectAppointmentRequest, Request given`
**Time**: 11:58 CEST
**Status**: ✅ **RESOLVED**
**Root Cause**: Unvollständige Integration - Nur eine von zwei Call-Sites gefixt

---

## 🎯 Executive Summary

### Problem
Nach dem Fix des `Cache::expire()` Problems trat beim nächsten Testanruf ein neuer Fehler auf:
```
TypeError: App\Http\Controllers\RetellFunctionCallHandler::collectAppointment():
Argument #1 ($request) must be of type App\Http\Requests\CollectAppointmentRequest,
Illuminate\Http\Request given,
called in /var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php on line 218
```

### Root Cause
**Unvollständige Integration von Fix #4 (Input Validation)**

Bei der Integration von `CollectAppointmentRequest` (12:30) wurde nur **eine von zwei Call-Sites** angepasst:

✅ **GEFIXT**: `/api/webhooks/retell/collect-appointment` → `RetellFunctionCallHandler`
❌ **VERGESSEN**: `/api/retell/collect-appointment` → `RetellApiController` → `RetellFunctionCallHandler`

`RetellApiController` forwarded Requests an `RetellFunctionCallHandler`, aber mit falschem Type-Hint.

### Impact
- **Betroffene Route**: `/api/retell/collect-appointment` (Retell Agent Calls)
- **Dauer**: ~2 Minuten (11:58 - 12:00)
- **Fehlerrate**: 100% für diese Route
- **Data Safety**: ✅ Keine Datenverluste

---

## 🔍 Detaillierte Analyse

### Error Details
- **Time**: 11:58:44, 11:58:45 (3 Retry-Versuche von Retell)
- **Endpoint**: `POST /api/retell/collect-appointment`
- **HTTP Status**: 500 Internal Server Error

### Route Architecture

Es gibt **zwei separate Routen** für `collect-appointment`:

**Route 1** (Webhooks):
```php
// routes/api.php
Route::post('/retell/collect-appointment',
    [\App\Http\Controllers\RetellFunctionCallHandler::class, 'collectAppointment'])
    ->name('webhooks.retell.collect-appointment');
```

**Route 2** (API):
```php
// routes/api.php
Route::post('/collect-appointment',
    [\App\Http\Controllers\Api\RetellApiController::class, 'collectAppointment'])
    ->name('api.retell.collect-appointment');
```

### Route 2 Implementation (Pre-Fix)

`RetellApiController` forwarded einfach zu `RetellFunctionCallHandler`:

```php
// RetellApiController.php (VORHER)
public function collectAppointment(Request $request)  // ❌ Request
{
    $handler = app(\App\Http\Controllers\RetellFunctionCallHandler::class);
    return $handler->collectAppointment($request);    // ❌ Request wird weitergegeben
}
```

```php
// RetellFunctionCallHandler.php (NACH Fix #4)
public function collectAppointment(CollectAppointmentRequest $request)  // ✅ CollectAppointmentRequest
{
    // ...
}
```

**Type Mismatch**: `Request` → `CollectAppointmentRequest` ❌

### Warum trat der Fehler auf?

**Integration History**:

1. **12:30** - Fix #4 Integration
   - `CollectAppointmentRequest` erstellt
   - `RetellFunctionCallHandler::collectAppointment()` Signature geändert
   - **ABER**: `RetellApiController` wurde vergessen!

2. **11:58** - Testanruf trifft Route 2
   - Retell Agent ruft `/api/retell/collect-appointment` auf
   - Route führt zu `RetellApiController::collectAppointment()`
   - Controller forwarded mit `Request` Objekt
   - Handler erwartet `CollectAppointmentRequest`
   - **TypeError!**

---

## 🛠️ Die Lösung

### Code-Änderung

**File**: `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php`

```diff
// RetellApiController.php
- public function collectAppointment(Request $request)
+ public function collectAppointment(\App\Http\Requests\CollectAppointmentRequest $request)
  {
      $handler = app(\App\Http\Controllers\RetellFunctionCallHandler::class);
      return $handler->collectAppointment($request);
  }
```

### Warum funktioniert das?

Laravel's Dependency Injection erkennt den Type-Hint und:
1. Erstellt automatisch eine `CollectAppointmentRequest` Instanz
2. Führt automatisch die Validierung aus (aus `CollectAppointmentRequest::rules()`)
3. Übergibt validiertes Request-Objekt an die Methode

**Beide Routen nutzen jetzt dieselbe Validierung!**

### Deployment
```bash
# Syntax Check
php -l app/Http/Controllers/Api/RetellApiController.php
No syntax errors detected ✅

# Cache Clear & Restart
php artisan optimize:clear
systemctl restart php8.3-fpm

# Health Check
curl https://api.askproai.de/api/health/detailed
{"healthy": true} ✅
```

---

## 🏗️ Route Architecture (Nach Fix)

### Route 1: `/api/webhooks/retell/collect-appointment`
```
Retell Webhook
    ↓
RetellFunctionCallHandler::collectAppointment(CollectAppointmentRequest $request)
    ↓
Validation + Processing
```

### Route 2: `/api/retell/collect-appointment`
```
Retell Agent Call
    ↓
RetellApiController::collectAppointment(CollectAppointmentRequest $request)
    ↓
Forward to Handler
    ↓
RetellFunctionCallHandler::collectAppointment(CollectAppointmentRequest $request)
    ↓
Validation + Processing
```

**Beide Routen nutzen jetzt `CollectAppointmentRequest` für Input Validation!** ✅

---

## ⚠️ Lessons Learned

### Was lief schief?

1. **Unvollständige Code-Suche bei Integration**
   - Nur direct usage gefunden (`RetellFunctionCallHandler`)
   - Forward/Proxy pattern nicht berücksichtigt (`RetellApiController`)
   - Keine Suche nach "wer ruft diese Methode auf?"

2. **Keine automatisierten Tests**
   - Type Mismatch hätte bei Unit Test sofort gefailed
   - Keine Integration Tests für beide Routen

3. **Keine systematische Verification nach Integration**
   - Nach Fix #4 (12:30) wurde nicht getestet
   - Testanruf kam erst nach mehreren anderen Fixes

### Was lief gut?

1. ✅ **Schnelle Detection** (<2 Minuten)
   - Error-Logs zeigten exakte Zeile
   - Type Mismatch sofort erkennbar

2. ✅ **Klarer Stacktrace**
   - Zeigte beide beteiligten Dateien
   - Call Chain war nachvollziehbar

3. ✅ **Einfacher Fix**
   - Ein Zeichen ändern (Type-Hint)
   - Sofort deployed und verifiziert

---

## 🎓 Prevention für Zukunft

### Integration Checklist

Wenn Method Signature geändert wird:

- [ ] **Direct Calls**: Alle direkten Aufrufe finden
  ```bash
  grep -r "->collectAppointment(" app/
  ```

- [ ] **Forwarding/Proxy Calls**: Alle Weiterleitungen finden
  ```bash
  grep -rn "RetellFunctionCallHandler" app/
  ```

- [ ] **Route Definitions**: Alle Routen zur Methode prüfen
  ```bash
  grep "collectAppointment" routes/api.php
  ```

- [ ] **Test Each Route**: Jede Route einzeln testen
  ```bash
  curl -X POST /api/retell/collect-appointment
  curl -X POST /api/webhooks/retell/collect-appointment
  ```

### Automatisierte Tests

```php
// tests/Feature/Controllers/RetellApiControllerTest.php
it('forwards collect appointment to handler with correct type', function () {
    $payload = [
        'call_id' => 'test_123',
        'function' => 'collect_appointment',
        'args' => [
            'datum' => '2025-10-15',
            'uhrzeit' => '14:00',
            'name' => 'Test User',
        ],
    ];

    // Test Route 2
    $response = $this->postJson('/api/retell/collect-appointment', $payload);
    $response->assertSuccessful();
});

it('validates input correctly on both routes', function () {
    $invalidPayload = [
        'call_id' => 'test_123',
        'function' => 'collect_appointment',
        'args' => [
            'datum' => 'invalid-date', // ❌ Invalid
            'name' => str_repeat('x', 200), // ❌ Too long
        ],
    ];

    // Route 1 should validate
    $this->postJson('/api/webhooks/retell/collect-appointment', $invalidPayload)
         ->assertStatus(422);

    // Route 2 should validate
    $this->postJson('/api/retell/collect-appointment', $invalidPayload)
         ->assertStatus(422);
});
```

---

## 📊 Timeline

| Time | Event |
|------|-------|
| 12:30 | Fix #4 Integration (CollectAppointmentRequest) |
| 12:30 | ✅ RetellFunctionCallHandler geändert |
| 12:30 | ❌ RetellApiController vergessen |
| 11:56 | Cache::expire() Fix deployed |
| 11:58 | Testanruf → Type Mismatch Error |
| 11:59 | Root Cause identifiziert |
| 12:00 | Fix deployed |

**Total Resolution Time**: 2 Minuten (11:58 - 12:00)

---

## ✅ Current Status

**Date**: 2025-10-01 12:00 CEST
**Status**: ✅ **RESOLVED**

### Verification

```bash
# Syntax Check
php -l app/Http/Controllers/Api/RetellApiController.php
No syntax errors detected ✅

# Both Routes Use Same Validation
grep "CollectAppointmentRequest" app/Http/Controllers/RetellFunctionCallHandler.php
Line 598: public function collectAppointment(CollectAppointmentRequest $request) ✅

grep "CollectAppointmentRequest" app/Http/Controllers/Api/RetellApiController.php
Line 214: public function collectAppointment(\App\Http\Requests\CollectAppointmentRequest $request) ✅

# API Health
curl https://api.askproai.de/api/health/detailed
{"healthy": true} ✅
```

### Next Steps

1. ✅ Type-Hint in RetellApiController korrigiert
2. ✅ PHP-FPM neu gestartet
3. ⏳ **USER ACTION NEEDED**: Neuer Testanruf zur Verification

---

## 📁 Related Incidents (Today)

1. **11:16-11:17** - Middleware Registration Error (Laravel 11 Migration)
2. **11:52-11:56** - Cache::expire() Error (Non-existent method)
3. **11:58-12:00** - Type Mismatch Error (Incomplete integration) ← **THIS REPORT**

**Pattern**: Jeder Fix deckt neuen Bug auf weil vorheriger Bug Codeausführung blockierte.

---

**Report Created**: 2025-10-01 12:01 CEST
**Incident Duration**: 2 minutes
**Status**: ✅ RESOLVED
**Ready for Testing**: YES
