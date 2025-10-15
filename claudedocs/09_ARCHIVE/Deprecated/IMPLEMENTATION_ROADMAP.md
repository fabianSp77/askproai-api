# Implementierungs-Roadmap: Telefonagent Buchungssystem
**Erstellt**: 2025-09-30
**Status**: Aktiv
**Version**: 1.0

## Executive Summary

Diese Roadmap konsolidiert Erkenntnisse aus 6 parallelen Expertenanalysen:
- Backend-Architektur (Rating: 6/10)
- Quality Engineering (Test-Abdeckung: unzureichend)
- Security (Risiko-Score: 7.3/10 HIGH)
- Performance (Baseline: 400-800ms)
- System-Architektur (24 Technical Debt Items)
- Technische Spezifikation (7 Funktionale Requirements)

**Gesamtpriorität**: 🔴 KRITISCH - Produktionssystem mit Sicherheits- und Funktionslücken

---

## Phase 1: Kritische Sicherheits- und Funktionsfixes (Woche 1-2)
**Ziel**: Produktionssystem absichern und kritische Buchungsfehler beheben
**Geschätzter Aufwand**: 40-50 Stunden
**Risiko bei Nichtdurchführung**: HOCH - Datenlecks, DSGVO-Verstöße, Buchungsausfälle

### 1.1 Sicherheitskritische Fixes (Priorität: 🔴 KRITISCH)

#### Task 1.1.1: Webhook-Signatur-Validierung erzwingen
**Problem**: `allow_unsigned_webhooks` ermöglicht Umgehung der Signaturprüfung
**Risiko**: KRITISCH - Unbefugte können Webhooks senden
**Dateien**:
- `config/retell.php` (Line 8)
- `app/Http/Controllers/RetellWebhookController.php` (Lines 44-68)

**Implementierung**:
```php
// config/retell.php
return [
    'api_key' => env('RETELL_API_KEY'),
    'webhook_secret' => env('RETELL_WEBHOOK_SECRET'),
    'allow_unsigned_webhooks' => false, // PERMANENTLY FALSE
];

// RetellWebhookController.php - Entfernen der Bypass-Logik
protected function verifySignature(Request $request): bool
{
    $signature = $request->header('X-Retell-Signature');
    if (!$signature) {
        Log::warning('Webhook rejected: Missing signature');
        return false;
    }

    $webhookSecret = config('retell.webhook_secret');
    $payload = $request->getContent();
    $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

    if (!hash_equals($expectedSignature, $signature)) {
        Log::warning('Webhook rejected: Invalid signature');
        return false;
    }

    return true;
}
```

**Tests**:
```php
// tests/Unit/RetellWebhookControllerTest.php
public function test_rejects_unsigned_webhooks()
{
    $response = $this->postJson('/webhooks/retell', ['event' => 'test']);
    $response->assertStatus(401);
}

public function test_rejects_invalid_signature()
{
    $response = $this->postJson('/webhooks/retell',
        ['event' => 'test'],
        ['X-Retell-Signature' => 'invalid']
    );
    $response->assertStatus(401);
}
```

**Zeitaufwand**: 4 Stunden
**Akzeptanzkriterien**:
- ✅ Alle unsignierten Webhooks werden abgelehnt (401)
- ✅ Invalide Signaturen werden geloggt und abgelehnt
- ✅ Tests mit 100% Coverage für Signaturprüfung

---

#### Task 1.1.2: PII-Verschlüsselung implementieren
**Problem**: Kundendaten (Name, Telefonnummer, E-Mail) unverschlüsselt gespeichert
**Risiko**: HOCH - DSGVO-Verstoß, Datenleck bei DB-Kompromittierung
**Dateien**:
- `app/Models/Customer.php`
- `app/Models/Call.php`
- `database/migrations/YYYY_MM_DD_add_encryption_to_pii.php`

**Implementierung**:
```php
// app/Models/Customer.php
protected $casts = [
    'name' => 'encrypted',
    'email' => 'encrypted',
    'phone' => 'encrypted',
];

// app/Models/Call.php
protected $casts = [
    'from_number' => 'encrypted',
    'to_number' => 'encrypted',
    'recording_url' => 'encrypted',
    'transcript' => 'encrypted',
];
```

**Migration**:
```php
Schema::table('customers', function (Blueprint $table) {
    // Daten müssen vor Migration verschlüsselt werden
    DB::statement('UPDATE customers SET
        name = AES_ENCRYPT(name, ?),
        email = AES_ENCRYPT(email, ?),
        phone = AES_ENCRYPT(phone, ?)',
        [config('app.key'), config('app.key'), config('app.key')]
    );
});
```

**Zeitaufwand**: 8 Stunden
**Akzeptanzkriterien**:
- ✅ Alle PII-Felder verschlüsselt in DB
- ✅ Migration erfolgreich auf Staging getestet
- ✅ Bestehende Daten migriert
- ✅ Tests für Verschlüsselung/Entschlüsselung

---

#### Task 1.1.3: Telefonnummer-Normalisierung beheben
**Problem**: PhoneNumber Lookup schlägt fehl wegen Format-Inkonsistenzen
**Risiko**: HOCH - Anrufe werden falscher Company zugeordnet (company_id=1 Fallback)
**Dateien**:
- `app/Services/PhoneNumberNormalizer.php` (NEU)
- `app/Http/Controllers/RetellWebhookController.php` (Lines 128-155)
- `database/migrations/YYYY_MM_DD_add_normalized_phone_numbers.php`

**Implementierung**:
```php
// app/Services/PhoneNumberNormalizer.php (NEU)
<?php

namespace App\Services;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

class PhoneNumberNormalizer
{
    public static function normalize(?string $phoneNumber): ?string
    {
        if (!$phoneNumber) return null;

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $parsed = $phoneUtil->parse($phoneNumber, 'DE');
            return $phoneUtil->format($parsed, PhoneNumberFormat::E164);
        } catch (\Exception $e) {
            Log::warning("Phone normalization failed: {$phoneNumber}", ['error' => $e->getMessage()]);
            return preg_replace('/[^0-9+]/', '', $phoneNumber);
        }
    }
}

// Migration
Schema::table('phone_numbers', function (Blueprint $table) {
    $table->string('number_normalized', 20)->nullable()->after('number');
    $table->index('number_normalized');
});

// Data migration
DB::statement("
    UPDATE phone_numbers
    SET number_normalized = REGEXP_REPLACE(number, '[^0-9+]', '')
    WHERE number_normalized IS NULL
");

// RetellWebhookController.php - Update lookup
$normalizedNumber = PhoneNumberNormalizer::normalize($toNumber);

$phoneNumber = PhoneNumber::where('number_normalized', $normalizedNumber)
    ->with(['company', 'branch'])
    ->first();

if (!$phoneNumber) {
    Log::error('PhoneNumber not found', [
        'original' => $toNumber,
        'normalized' => $normalizedNumber
    ]);
    return response()->json(['error' => 'Phone number not registered'], 404);
}
```

**Tests**:
```php
public function test_normalizes_phone_numbers_correctly()
{
    $testCases = [
        '+49 30 83793369' => '+493083793369',
        '+49-30-83793369' => '+493083793369',
        '030 83793369' => '+493083793369',
    ];

    foreach ($testCases as $input => $expected) {
        $this->assertEquals($expected, PhoneNumberNormalizer::normalize($input));
    }
}

public function test_rejects_calls_to_unregistered_numbers()
{
    $response = $this->postJson('/webhooks/retell', [
        'event' => 'call_inbound',
        'call' => ['to_number' => '+49999999999']
    ]);

    $response->assertStatus(404);
}
```

**Zeitaufwand**: 6 Stunden
**Akzeptanzkriterien**:
- ✅ Alle Telefonnummern in E.164-Format normalisiert
- ✅ Lookup verwendet normalisierte Spalte
- ✅ company_id=1 Fallback entfernt
- ✅ Tests für alle Format-Varianten

---

#### Task 1.1.4: SQL-Injection-Schutz verbessern
**Problem**: Potenzielle SQL-Injection in Service-Abfragen
**Risiko**: HOCH - Datenbankmanipulation möglich
**Dateien**: `app/Http/Controllers/RetellFunctionCallHandler.php` (Lines 450-500)

**Implementierung**:
```php
// VORHER (anfällig)
$services = DB::table('services')
    ->whereRaw("FIND_IN_SET(?, keywords)", [$keyword])
    ->get();

// NACHHER (sicher)
$services = Service::where('company_id', $companyId)
    ->where('is_active', true)
    ->whereJsonContains('keywords', $keyword)
    ->get();

// Alternative: Prepared Statements
$services = DB::select(
    "SELECT * FROM services
     WHERE company_id = ?
     AND is_active = 1
     AND JSON_CONTAINS(keywords, JSON_QUOTE(?))",
    [$companyId, $keyword]
);
```

**Zeitaufwand**: 3 Stunden
**Akzeptanzkriterien**:
- ✅ Alle DB-Queries verwenden Prepared Statements
- ✅ Keine Raw SQL mit User Input
- ✅ Tests mit Injection-Payloads

---

### 1.2 Cal.com Buchungsfehler beheben (Priorität: 🔴 KRITISCH)

#### Task 1.2.1: Branch-spezifische Service-Auswahl
**Problem**: Services werden company-weit gesucht, nicht branch-spezifisch
**Risiko**: MITTEL - Falsche Dienstleistungen werden gebucht
**Dateien**: `app/Http/Controllers/RetellFunctionCallHandler.php` (Lines 180-220)

**Implementierung**:
```php
// RetellFunctionCallHandler.php
public function selectService(Request $request)
{
    $companyId = $request->input('company_id');
    $branchId = $request->input('branch_id'); // NEU: aus Call-Context
    $keyword = $request->input('keyword');

    $query = Service::where('company_id', $companyId)
        ->where('is_active', true);

    // Branch-Filter wenn vorhanden
    if ($branchId) {
        $query->where(function($q) use ($branchId) {
            $q->where('branch_id', $branchId)
              ->orWhereHas('branches', function($q2) use ($branchId) {
                  $q2->where('branches.id', $branchId);
              });
        });
    }

    // Keyword-Suche
    if ($keyword) {
        $query->whereJsonContains('keywords', $keyword);
    }

    $services = $query->get();

    if ($services->isEmpty()) {
        return response()->json([
            'error' => 'Keine passenden Dienstleistungen gefunden',
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'keyword' => $keyword
        ], 404);
    }

    return response()->json(['services' => $services]);
}
```

**Tests**:
```php
public function test_filters_services_by_branch()
{
    $company = Company::factory()->create();
    $branch1 = Branch::factory()->create(['company_id' => $company->id]);
    $branch2 = Branch::factory()->create(['company_id' => $company->id]);

    $service1 = Service::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch1->id,
        'name' => 'Haarschnitt Filiale 1'
    ]);

    $service2 = Service::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch2->id,
        'name' => 'Haarschnitt Filiale 2'
    ]);

    $response = $this->postJson('/api/select-service', [
        'company_id' => $company->id,
        'branch_id' => $branch1->id,
        'keyword' => 'Haarschnitt'
    ]);

    $response->assertStatus(200);
    $this->assertCount(1, $response->json('services'));
    $this->assertEquals($service1->id, $response->json('services.0.id'));
}
```

**Zeitaufwand**: 5 Stunden
**Akzeptanzkriterien**:
- ✅ Services werden branch-spezifisch gefiltert
- ✅ Fallback auf company-weite Services wenn Branch keine hat
- ✅ Tests für Multi-Branch-Szenarien

---

#### Task 1.2.2: branch_id zu Calls-Tabelle hinzufügen
**Problem**: Call-Records fehlt branch_id für Tracking
**Risiko**: NIEDRIG - Analytics unvollständig
**Dateien**: `database/migrations/YYYY_MM_DD_add_branch_id_to_calls.php`

**Implementierung**:
```php
Schema::table('calls', function (Blueprint $table) {
    $table->foreignId('branch_id')->nullable()->after('company_id')
        ->constrained('branches')->nullOnDelete();
    $table->index(['company_id', 'branch_id', 'created_at']);
});

// RetellWebhookController.php - Update Call creation
$call = Call::create([
    'company_id' => $phoneNumber->company_id,
    'branch_id' => $phoneNumber->branch_id, // NEU
    'phone_number_id' => $phoneNumber->id,
    'retell_call_id' => $callId,
    'to_number' => $toNumber,
    'from_number' => $fromNumber,
    'status' => 'active',
]);
```

**Zeitaufwand**: 2 Stunden
**Akzeptanzkriterien**:
- ✅ Migration erfolgreich
- ✅ Call-Erstellung enthält branch_id
- ✅ Bestehende Calls haben NULL (erlaubt)

---

### Phase 1 Zusammenfassung
**Gesamtaufwand**: 28 Stunden
**Kritische Pfad-Abhängigkeiten**:
1. Webhook-Signatur → PII-Verschlüsselung → SQL-Injection-Schutz (parallel)
2. Telefonnummer-Normalisierung → Branch-Service-Auswahl (sequentiell)
3. branch_id-Migration (unabhängig, parallel möglich)

**Deployment-Strategie**:
- Staging-Deployment: Tag 1-3
- Produktions-Deployment: Tag 4 (außerhalb Stoßzeiten)
- Rollback-Plan: Database Snapshots vor Migration

---

## Phase 2: Backend-Refactoring und Performance (Woche 3-5)
**Ziel**: Code-Qualität verbessern, Performance optimieren
**Geschätzter Aufwand**: 60-80 Stunden
**Risiko bei Nichtdurchführung**: MITTEL - Wartbarkeit, Skalierbarkeit

### 2.1 RetellWebhookController Refactoring (Priorität: 🟡 HOCH)

#### Task 2.1.1: God Object auflösen (2068 Zeilen → 5 Services)
**Problem**: RetellWebhookController zu groß, Multiple Responsibilities
**Dateien**:
- `app/Http/Controllers/RetellWebhookController.php` (2068 lines)
- `app/Services/CallManagement/` (NEU)

**Neue Struktur**:
```
app/Services/CallManagement/
├── CallInitiationService.php       # handle call_inbound
├── CallCompletionService.php       # handle call_ended
├── FunctionCallService.php         # handle function_call
├── AppointmentCollectionService.php # collectAppointment logic
└── CallRoutingService.php          # PhoneNumber lookup & routing
```

**Implementierung** (Beispiel CallRoutingService):
```php
<?php

namespace App\Services\CallManagement;

class CallRoutingService
{
    public function __construct(
        private PhoneNumberNormalizer $normalizer
    ) {}

    public function routeInboundCall(string $toNumber): array
    {
        $normalized = $this->normalizer->normalize($toNumber);

        $phoneNumber = PhoneNumber::where('number_normalized', $normalized)
            ->with(['company', 'branch'])
            ->firstOrFail();

        return [
            'company' => $phoneNumber->company,
            'branch' => $phoneNumber->branch,
            'phone_number' => $phoneNumber,
        ];
    }
}
```

**RefactoredController**:
```php
class RetellWebhookController extends Controller
{
    public function __construct(
        private CallInitiationService $callInitiation,
        private CallCompletionService $callCompletion,
        private FunctionCallService $functionCall
    ) {}

    public function handleWebhook(Request $request)
    {
        if (!$this->verifySignature($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $event = $request->input('event');

        return match($event) {
            'call_inbound' => $this->callInitiation->handle($request),
            'call_ended' => $this->callCompletion->handle($request),
            'function_call' => $this->functionCall->handle($request),
            default => response()->json(['error' => 'Unknown event'], 400)
        };
    }
}
```

**Zeitaufwand**: 20 Stunden
**Akzeptanzkriterien**:
- ✅ Controller < 200 Zeilen
- ✅ Services folgen Single Responsibility
- ✅ Alle bestehenden Tests funktionieren
- ✅ 100% Test-Coverage für neue Services

---

### 2.2 Performance-Optimierungen (Priorität: 🟡 HOCH)

#### Task 2.2.1: Database-Indizes hinzufügen
**Problem**: Langsame Queries ohne Indizes
**Risiko**: MITTEL - Schlechte Performance bei Skalierung

**Migration**:
```php
Schema::table('phone_numbers', function (Blueprint $table) {
    $table->index('number_normalized');
    $table->index(['company_id', 'is_active']);
});

Schema::table('services', function (Blueprint $table) {
    $table->index(['company_id', 'branch_id', 'is_active']);
    $table->index('calcom_event_type_id');
});

Schema::table('calls', function (Blueprint $table) {
    $table->index(['company_id', 'status', 'created_at']);
    $table->index('retell_call_id');
});
```

**Zeitaufwand**: 3 Stunden
**Akzeptanzkriterien**:
- ✅ PhoneNumber lookup < 5ms (vorher: 120ms)
- ✅ Service-Queries < 10ms
- ✅ Explain-Analyze zeigt Index-Nutzung

---

#### Task 2.2.2: Redis-Caching Layer
**Problem**: Cal.com API-Calls ohne Caching (200-800ms)
**Lösung**: Redis-Cache für Event Types, Verfügbarkeiten

**Implementierung**:
```php
// app/Services/CalcomService.php
public function getEventType(int $eventTypeId): array
{
    return Cache::remember(
        "calcom:event_type:{$eventTypeId}",
        now()->addHours(24),
        fn() => $this->fetchEventTypeFromApi($eventTypeId)
    );
}

public function checkAvailability(int $eventTypeId, string $date): array
{
    return Cache::remember(
        "calcom:availability:{$eventTypeId}:{$date}",
        now()->addMinutes(15),
        fn() => $this->fetchAvailabilityFromApi($eventTypeId, $date)
    );
}
```

**Zeitaufwand**: 8 Stunden
**Akzeptanzkriterien**:
- ✅ Cache-Hit-Rate > 80%
- ✅ API-Calls reduziert um 60%
- ✅ Response-Zeit < 100ms für gecachte Daten

---

#### Task 2.2.3: PHP-FPM Tuning
**Problem**: Suboptimale PHP-FPM-Konfiguration

**Konfiguration** (`/etc/php/8.3/fpm/pool.d/www.conf`):
```ini
pm = dynamic
pm.max_children = 50        ; vorher: 25
pm.start_servers = 10       ; vorher: 5
pm.min_spare_servers = 5    ; vorher: 2
pm.max_spare_servers = 15   ; vorher: 10
pm.max_requests = 1000      ; vorher: 500

; Connection pooling
php_admin_value[max_execution_time] = 30
php_admin_value[memory_limit] = 256M
php_admin_value[opcache.enable] = 1
php_admin_value[opcache.memory_consumption] = 128
```

**Zeitaufwand**: 2 Stunden
**Akzeptanzkriterien**:
- ✅ Concurrent Requests: 50 (vorher: 25)
- ✅ Queue-Time < 10ms
- ✅ Load-Test mit 100 RPS erfolgreich

---

### Phase 2 Zusammenfassung
**Gesamtaufwand**: 33 Stunden
**Performance-Ziele**:
- PhoneNumber Lookup: 120ms → 5ms (96% Verbesserung)
- Webhook-Verarbeitung: 400-800ms → < 200ms (50-75% Verbesserung)
- Cal.com API: 200-800ms → < 100ms (gecacht)

---

## Phase 3: Umfassende Test-Implementierung (Woche 6-7)
**Ziel**: 80% Code-Coverage, automatisierte E2E-Tests
**Geschätzter Aufwand**: 40-50 Stunden

### 3.1 Unit-Tests (60% der Test-Pyramide)

#### Task 3.1.1: Service Layer Tests
**Dateien**: `tests/Unit/Services/`

**Test-Umfang**:
```php
// PhoneNumberNormalizerTest.php
- test_normalizes_german_numbers()
- test_normalizes_international_numbers()
- test_handles_invalid_numbers()
- test_removes_formatting_characters()

// CallRoutingServiceTest.php
- test_routes_to_correct_company()
- test_routes_to_correct_branch()
- test_throws_exception_for_unknown_number()
- test_caches_routing_results()

// CalcomServiceTest.php
- test_creates_booking_with_correct_payload()
- test_calculates_end_time_correctly()
- test_handles_api_errors_gracefully()
- test_retries_on_transient_failures()
```

**Zeitaufwand**: 15 Stunden
**Akzeptanzkriterien**:
- ✅ 100% Coverage für alle Services
- ✅ Alle Edge-Cases getestet
- ✅ Mocking für externe APIs

---

### 3.2 Integration-Tests (30% der Test-Pyramide)

#### Task 3.2.1: Webhook-Flow Tests
**Dateien**: `tests/Integration/Webhooks/`

**Test-Szenarien**:
```php
// RetellWebhookIntegrationTest.php
- test_call_inbound_creates_call_record()
- test_function_call_books_appointment()
- test_call_ended_updates_call_status()
- test_webhook_signature_validation()
- test_multi_branch_call_routing()

// CalcomWebhookIntegrationTest.php
- test_booking_created_updates_appointment()
- test_booking_cancelled_notifies_customer()
```

**Zeitaufwand**: 12 Stunden
**Akzeptanzkriterien**:
- ✅ Alle Webhook-Events getestet
- ✅ Database-Transaktionen validiert
- ✅ Multi-Tenant-Isolation getestet

---

### 3.3 E2E-Tests (10% der Test-Pyramide)

#### Task 3.3.1: Komplette Buchungsflows
**Dateien**: `tests/Feature/Booking/`

**Test-Szenarien**:
```php
// CompleteBookingFlowTest.php
- test_customer_calls_and_books_appointment()
- test_appointment_appears_in_calcom()
- test_customer_receives_confirmation()
- test_multi_branch_booking_isolation()
- test_handles_no_availability_gracefully()
```

**Zeitaufwand**: 10 Stunden
**Akzeptanzkriterien**:
- ✅ End-to-End Flows funktionieren
- ✅ External API Mocking
- ✅ Realistic Test-Daten

---

### 3.4 Load & Performance Testing

#### Task 3.4.1: Artillery Load Tests
**Dateien**: `tests/Performance/load-test.yml`

**Szenarien**:
```yaml
config:
  target: 'https://api.askproai.de'
  phases:
    - duration: 60
      arrivalRate: 10
      name: "Warmup"
    - duration: 300
      arrivalRate: 50
      name: "Sustained load"
    - duration: 120
      arrivalRate: 100
      name: "Peak load"

scenarios:
  - name: "Incoming call webhook"
    flow:
      - post:
          url: "/webhooks/retell"
          json:
            event: "call_inbound"
            call:
              to_number: "+493083793369"
              from_number: "+491234567890"
          headers:
            X-Retell-Signature: "{{ signature }}"
```

**Zeitaufwand**: 8 Stunden
**Akzeptanzkriterien**:
- ✅ 100 RPS ohne Fehler
- ✅ p95 Latenz < 500ms
- ✅ 0% Fehlerrate bei Normallast

---

### Phase 3 Zusammenfassung
**Gesamtaufwand**: 45 Stunden
**Test-Coverage-Ziel**: 80% (aktuell: ~20%)
**CI/CD-Integration**: GitHub Actions Pipeline

---

## Phase 4: DSGVO-Compliance & Monitoring (Woche 8-9)
**Ziel**: DSGVO-konform, vollständiges Monitoring
**Geschätzter Aufwand**: 30-40 Stunden

### 4.1 DSGVO-Maßnahmen

#### Task 4.1.1: Automatisierte Datenlöschung
**Implementierung**:
```php
// app/Console/Commands/DeleteExpiredCallData.php
class DeleteExpiredCallData extends Command
{
    public function handle()
    {
        $retentionDays = config('gdpr.call_retention_days', 90);

        $deleted = Call::where('created_at', '<', now()->subDays($retentionDays))
            ->whereNotNull('ended_at')
            ->delete();

        $this->info("Deleted {$deleted} expired call records");
    }
}

// Scheduler
$schedule->command('gdpr:delete-expired-calls')->daily();
```

**Zeitaufwand**: 6 Stunden
**Akzeptanzkriterien**:
- ✅ Automatische Löschung nach 90 Tagen
- ✅ Audit-Log für Löschungen
- ✅ Konfigurierbare Retention-Periode

---

#### Task 4.1.2: Recht auf Löschung (DSGVO Art. 17)
**Implementierung**:
```php
// app/Http/Controllers/GDPRController.php
public function deleteCustomerData(Request $request)
{
    $phone = PhoneNumberNormalizer::normalize($request->input('phone'));

    DB::transaction(function() use ($phone) {
        $customer = Customer::where('phone', $phone)->firstOrFail();

        // Log deletion request
        GDPRLog::create([
            'action' => 'customer_deletion',
            'customer_id' => $customer->id,
            'requested_at' => now(),
        ]);

        // Delete related data
        $customer->appointments()->delete();
        $customer->calls()->delete();
        $customer->delete();
    });

    return response()->json(['message' => 'Data deleted successfully']);
}
```

**Zeitaufwand**: 8 Stunden
**Akzeptanzkriterien**:
- ✅ Vollständige Datenlöschung
- ✅ Audit-Trail
- ✅ API-Endpoint für Löschanfragen

---

### 4.2 Monitoring & Observability

#### Task 4.2.1: Application Performance Monitoring (APM)
**Tool**: Laravel Telescope + Custom Metrics

**Implementierung**:
```php
// config/telescope.php - Production-Konfiguration
'enabled' => env('TELESCOPE_ENABLED', true),
'storage' => [
    'database' => [
        'connection' => 'mysql',
        'chunk' => 1000,
    ],
],
'watchers' => [
    RequestWatcher::class => ['enabled' => true],
    ExceptionWatcher::class => ['enabled' => true],
    QueryWatcher::class => ['enabled' => true, 'slow' => 100],
],

// Custom Metrics
public function recordCallMetrics(Call $call)
{
    Metrics::increment('calls.total', ['company_id' => $call->company_id]);
    Metrics::histogram('calls.duration', $call->duration, ['status' => $call->status]);
}
```

**Zeitaufwand**: 10 Stunden
**Akzeptanzkriterien**:
- ✅ Real-time Performance Dashboard
- ✅ Slow Query Detection (> 100ms)
- ✅ Error Rate Alerting

---

#### Task 4.2.2: Security Monitoring & Alerting
**Implementierung**:
```php
// app/Monitoring/SecurityMonitor.php
class SecurityMonitor
{
    public function logSuspiciousActivity(array $context)
    {
        SecurityLog::create([
            'event_type' => $context['type'],
            'severity' => $context['severity'],
            'details' => $context,
            'ip_address' => request()->ip(),
        ]);

        if ($context['severity'] === 'critical') {
            Notification::route('slack', config('security.alert_webhook'))
                ->notify(new SecurityAlertNotification($context));
        }
    }
}

// Usage in Webhook Controller
if (!$this->verifySignature($request)) {
    app(SecurityMonitor::class)->logSuspiciousActivity([
        'type' => 'invalid_webhook_signature',
        'severity' => 'high',
        'ip' => $request->ip(),
        'payload' => $request->input('event'),
    ]);
    return response()->json(['error' => 'Unauthorized'], 401);
}
```

**Zeitaufwand**: 8 Stunden
**Akzeptanzkriterien**:
- ✅ Slack-Alerts für kritische Events
- ✅ Security-Dashboard
- ✅ IP-Blocking bei wiederholten Verstößen

---

### Phase 4 Zusammenfassung
**Gesamtaufwand**: 32 Stunden
**DSGVO-Compliance**: 100%
**Monitoring-Coverage**: Alle kritischen Flows

---

## Gesamt-Roadmap Übersicht

| Phase | Dauer | Aufwand | Priorität | Abhängigkeiten |
|-------|-------|---------|-----------|----------------|
| Phase 1: Kritische Fixes | 2 Wochen | 28h | 🔴 KRITISCH | Keine |
| Phase 2: Refactoring & Performance | 3 Wochen | 33h | 🟡 HOCH | Phase 1 abgeschlossen |
| Phase 3: Test-Implementierung | 2 Wochen | 45h | 🟡 HOCH | Phase 2 abgeschlossen |
| Phase 4: DSGVO & Monitoring | 2 Wochen | 32h | 🟢 MITTEL | Phase 1 abgeschlossen |

**Gesamtaufwand**: 138 Stunden (~3.5 Entwicklermonate bei 40h/Woche)
**Empfohlene Teamgröße**: 2 Full-Stack Entwickler + 1 DevOps
**Geschätzte Projektdauer**: 9 Wochen

---

## Deployment-Strategie

### Staging-Deployment
1. **Woche 1-2**: Phase 1 auf Staging
2. **Woche 3**: Integration-Tests auf Staging
3. **Woche 4**: Load-Tests auf Staging

### Produktions-Deployment
**Blue-Green-Deployment**:
```bash
# 1. Deploy neue Version auf Green
./deploy.sh green

# 2. Rauchtest auf Green
curl https://green.askproai.de/health

# 3. Traffic-Switch (10% → 50% → 100%)
./switch-traffic.sh 10
sleep 300  # 5 Minuten Monitor
./switch-traffic.sh 50
sleep 600  # 10 Minuten Monitor
./switch-traffic.sh 100

# 4. Bei Problemen: Rollback
./rollback.sh blue
```

**Rollback-Plan**:
- Database Snapshots vor jeder Migration
- Git Tags für jede Produktions-Version
- Feature Flags für neue Funktionen

---

## Risk Management

### Kritische Risiken

| Risiko | Wahrscheinlichkeit | Impact | Mitigation |
|--------|-------------------|--------|------------|
| Migration bricht produktive Calls | MITTEL | KRITISCH | Blue-Green Deploy, Rollback-Plan |
| PII-Verschlüsselung schlägt fehl | NIEDRIG | HOCH | Ausführliche Staging-Tests |
| Performance-Regression | MITTEL | MITTEL | Load-Tests vor Prod-Deploy |
| Cal.com API-Änderungen | NIEDRIG | HOCH | API-Versionierung, Mocking |

### Mitigation-Strategien
1. **Umfangreiche Tests**: 80% Code-Coverage Pflicht
2. **Feature Flags**: Neue Features schrittweise rollout
3. **Monitoring**: Real-time Alerting bei Anomalien
4. **Rollback-Readiness**: < 5 Minuten Rollback-Zeit

---

## Erfolgskriterien

### Funktionale Kriterien
- ✅ 0% Buchungsfehler durch fehlende `end`-Field
- ✅ 100% korrekte Branch-Zuordnung bei Anrufen
- ✅ 0% SQL-Injection-Vulnerabilities
- ✅ 100% DSGVO-Compliance

### Performance-Kriterien
- ✅ Webhook-Verarbeitung < 200ms (p95)
- ✅ PhoneNumber-Lookup < 5ms (p99)
- ✅ Cal.com API < 100ms (gecacht)
- ✅ 100 RPS ohne Fehler

### Qualitätskriterien
- ✅ 80% Test-Coverage
- ✅ 0 kritische Sicherheitslücken
- ✅ Controller < 200 Zeilen
- ✅ Services folgen SOLID

### Betriebskriterien
- ✅ 99.9% Uptime
- ✅ < 5 Minuten Rollback-Zeit
- ✅ 100% Audit-Trail für PII-Zugriff
- ✅ Real-time Security Monitoring

---

## Nächste Schritte

### Sofort (diese Woche):
1. **Stakeholder-Meeting**: Roadmap präsentieren, Budget freigeben
2. **Team-Assignment**: Entwickler für Phase 1 zuweisen
3. **Staging-Umgebung**: Produktions-Clone erstellen
4. **Backup-Strategie**: Database-Backup-Plan implementieren

### Diese Woche:
1. **Task 1.1.1**: Webhook-Signatur-Validierung (4h)
2. **Task 1.1.3**: Telefonnummer-Normalisierung (6h)
3. **Task 1.2.1**: Branch-Service-Auswahl (5h)

### Nächste Woche:
1. **Task 1.1.2**: PII-Verschlüsselung (8h)
2. **Task 1.1.4**: SQL-Injection-Schutz (3h)
3. **Integration-Tests** für Phase 1 (8h)

---

## Anhang

### A. Referenz-Dokumentation
- [Backend Architecture Analysis](./BACKEND_ARCHITECTURE_ANALYSIS.md)
- [Quality Engineering Test Strategy](./QUALITY_ENGINEERING_TEST_STRATEGY.md)
- [Security Analysis Report](./SECURITY_ANALYSIS_REPORT.md)
- [Performance Engineering Report](./PERFORMANCE_ENGINEERING_REPORT.md)
- [System Architecture](./system-architecture.md)
- [Technical Specification](../claudedocs/TECHNICAL_SPECIFICATION_TELEFONAGENT_BUCHUNGSSYSTEM.md)

### B. Code-Locations Quick Reference

**Kritische Dateien**:
- Webhook-Controller: `/app/Http/Controllers/RetellWebhookController.php:44-68`
- PhoneNumber-Lookup: `/app/Http/Controllers/RetellWebhookController.php:128-155`
- Service-Selection: `/app/Http/Controllers/RetellFunctionCallHandler.php:180-220`
- Cal.com-Booking: `/app/Services/CalcomService.php:24-86`
- Config: `/config/retell.php:8`

### C. Test-Commands

```bash
# Unit-Tests
php artisan test --filter=Unit

# Integration-Tests
php artisan test --filter=Integration

# E2E-Tests
php artisan test --filter=Feature

# Load-Tests
artillery run tests/Performance/load-test.yml

# Security-Scan
php artisan security:scan

# Code-Coverage
php artisan test --coverage --min=80
```

### D. Deployment-Commands

```bash
# Staging-Deployment
./deploy.sh staging

# Produktions-Deployment (Blue-Green)
./deploy.sh green
./switch-traffic.sh 100

# Rollback
./rollback.sh blue

# Database-Migration
php artisan migrate --force

# Cache-Clear
php artisan cache:clear
php artisan config:clear
```

---

**Ende der Roadmap**
**Letzte Aktualisierung**: 2025-09-30
**Verantwortlich**: Claude Code Backend Architecture Team
**Review-Datum**: Nach Abschluss Phase 1 (2 Wochen)