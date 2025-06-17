# 🎯 AskProAI - Master-Implementierungsplan

## 📊 Ausgangslage
- **Problem**: Überladenes System mit 119 Tabellen, redundanten Services, komplexem Setup
- **Ziel**: Stabiles, skalierbares SaaS-Produkt für automatische Terminbuchung via Telefon
- **Zeitrahmen**: 6-8 Wochen bis zur Marktreife

---

# 📈 PHASEN-ÜBERSICHT

```
Phase 1: Stabilisierung (Woche 1-2)
    ↓
Phase 2: Vereinfachung (Woche 3)
    ↓
Phase 3: Automatisierung (Woche 4)
    ↓
Phase 4: Monetarisierung (Woche 5)
    ↓
Phase 5: Skalierung (Woche 6+)
```

---

# 🔧 PHASE 1: STABILISIERUNG (Woche 1-2)
**Ziel: Der Kern-Flow muss zu 100% funktionieren**

## Woche 1: Kern-Funktionalität sichern

### Tag 1-2: Bestandsaufnahme & Quick Fixes
```bash
# 1. Vollständiger System-Check
- Test-Anrufe bei allen aktiven Branches
- Webhook-Logs analysieren
- Fehlerhafte Flows dokumentieren
```

**Sofort-Fixes:**
```php
// PhoneNumberResolver.php - Nur aktive Branches
$branch = Branch::where('phone_number', $normalizedNumber)
    ->where('active', true)  // KRITISCH!
    ->first();

// ProcessRetellCallEndedJob.php - Bessere Fehlerbehandlung
try {
    $this->processCallData();
} catch (\Exception $e) {
    Log::error('Call processing failed', [
        'call_id' => $this->data['call_id'],
        'error' => $e->getMessage()
    ]);
    // Nicht neu versuchen bei Config-Fehlern
    if ($e instanceof ConfigurationException) {
        $this->fail($e);
    }
}
```

### Tag 3-4: Datenintegrität sicherstellen
```sql
-- 1. Bereinigung falscher Daten
DELETE FROM staff WHERE company_id IS NULL;
DELETE FROM appointments WHERE branch_id NOT IN (SELECT id FROM branches);

-- 2. Foreign Keys hinzufügen (wo fehlend)
ALTER TABLE appointments 
ADD CONSTRAINT fk_appointments_branch 
FOREIGN KEY (branch_id) REFERENCES branches(id);

-- 3. Indices für Performance
CREATE INDEX idx_calls_branch_created ON calls(branch_id, created_at);
CREATE INDEX idx_appointments_starts_at ON appointments(starts_at);
```

### Tag 5: Monitoring & Alerting
```php
// app/Services/HealthCheckService.php
class HealthCheckService {
    public function checkCriticalServices() {
        $checks = [
            'database' => $this->checkDatabase(),
            'retell_api' => $this->checkRetellApi(),
            'calcom_api' => $this->checkCalcomApi(),
            'redis' => $this->checkRedis(),
        ];
        
        // Alert wenn etwas down ist
        if (in_array(false, $checks)) {
            $this->sendAlert($checks);
        }
    }
}

// Cron Job: */5 * * * *
```

## Woche 2: Testing & Fehlerbehandlung

### Tag 1-2: Automatisierte Tests
```php
// tests/Feature/CoreFlowTest.php
class CoreFlowTest extends TestCase {
    public function test_complete_call_to_appointment_flow() {
        // 1. Simuliere Webhook von Retell
        $response = $this->postJson('/api/retell/webhook', [
            'event' => 'call_ended',
            'call' => $this->mockCallData()
        ]);
        
        // 2. Prüfe Call wurde erstellt
        $this->assertDatabaseHas('calls', [...]);
        
        // 3. Prüfe Appointment wurde erstellt
        $this->assertDatabaseHas('appointments', [...]);
        
        // 4. Prüfe Cal.com Booking
        $this->assertCalcomBookingCreated();
    }
}
```

### Tag 3-4: Error Recovery
```php
// app/Services/ErrorRecoveryService.php
class ErrorRecoveryService {
    public function recoverFailedBookings() {
        // Finde Calls ohne Appointments
        $failedCalls = Call::whereNull('appointment_id')
            ->where('appointment_requested', true)
            ->where('created_at', '>', now()->subHours(24))
            ->get();
            
        foreach ($failedCalls as $call) {
            try {
                $this->retryBooking($call);
            } catch (\Exception $e) {
                $this->logUnrecoverable($call, $e);
            }
        }
    }
}
```

### Tag 5: Dokumentation Core-Flow
```markdown
# Core Flow Dokumentation
1. Webhook empfangen → 200ms
2. Call Record erstellen → 50ms  
3. Customer finden/erstellen → 100ms
4. Verfügbarkeit prüfen → 200ms
5. Appointment erstellen → 50ms
6. Cal.com Sync → 500ms
7. Email senden → async
Total: < 1.2 Sekunden
```

---

# 🧹 PHASE 2: VEREINFACHUNG (Woche 3)
**Ziel: Technische Schulden abbauen**

## Tag 1-2: Datenbank-Bereinigung

### Backup & Analyse
```bash
# 1. Production Backup
mysqldump -u root -p askproai > backup_production_$(date +%Y%m%d).sql

# 2. Analyse unnötiger Tabellen
mysql -e "SELECT table_name, table_rows, data_length/1024/1024 as size_mb 
FROM information_schema.tables 
WHERE table_schema = 'askproai' 
ORDER BY table_rows DESC;"
```

### Tabellen-Konsolidierung
```sql
-- 1. Daten-Migration
INSERT INTO customers SELECT * FROM kunden;
INSERT INTO companies SELECT * FROM tenants WHERE id NOT IN (SELECT id FROM companies);

-- 2. Alte Tabellen entfernen
DROP TABLE IF EXISTS kunden;
DROP TABLE IF EXISTS tenants;
DROP TABLE IF EXISTS dummy_companies;
-- ... (Liste aus vorheriger Analyse)

-- 3. Vereinfachte Struktur
CREATE TABLE api_credentials (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    service ENUM('retell', 'calcom', 'stripe', 'twilio'),
    credentials JSON,
    is_active BOOLEAN DEFAULT true,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
```

## Tag 3: Service-Konsolidierung

### Neue Service-Struktur
```php
// app/Services/Integrations/CalcomService.php (NUR EINE!)
class CalcomService {
    private string $apiKey;
    private string $apiUrl = 'https://api.cal.com/v2';
    
    public function __construct(Company $company) {
        $this->apiKey = $company->getCalcomApiKey();
    }
    
    // Alle Cal.com Operationen hier
}

// app/Services/Integrations/RetellService.php (NUR EINE!)
class RetellService {
    private string $apiKey;
    
    public function __construct(Company $company) {
        $this->apiKey = $company->getRetellApiKey();
    }
    
    // Alle Retell Operationen hier
}

// Alte Services löschen
rm app/Services/Calcom*.php  # außer dem neuen
rm app/Services/Retell*.php  # außer dem neuen
```

## Tag 4-5: Migration bereinigen

### Neue saubere Migrations
```bash
# 1. Alle Migrations in eine konsolidieren
php artisan make:migration create_askproai_schema_v2

# 2. Saubere Struktur
- 001_create_core_tables.php (companies, branches, staff, customers)
- 002_create_appointment_tables.php (appointments, services)
- 003_create_communication_tables.php (calls, webhooks, notifications)
- 004_create_integration_tables.php (api_credentials, event_mappings)
- 005_create_billing_tables.php (subscriptions, usage_logs, invoices)

# 3. Alte archivieren
mv database/migrations/*.php database/migrations/_archive_v1/
```

---

# 🤖 PHASE 3: AUTOMATISIERUNG (Woche 4)
**Ziel: Setup von 2h auf 10min reduzieren**

## Tag 1-2: Retell Agent Automation

```php
// app/Services/Setup/RetellSetupService.php
class RetellSetupService {
    public function setupCompany(Company $company): void {
        // 1. Agent erstellen
        $agent = $this->createAgent($company);
        
        // 2. Webhook registrieren
        $this->registerWebhook($agent['agent_id']);
        
        // 3. Phone Number zuweisen
        $this->assignPhoneNumber($agent['agent_id'], $company->branches->first());
        
        // 4. In DB speichern
        $company->branches->first()->update([
            'retell_agent_id' => $agent['agent_id']
        ]);
    }
    
    private function createAgent(Company $company): array {
        $prompt = $this->generatePrompt($company);
        
        return $this->retellApi->post('/agents', [
            'agent_name' => $company->name . ' - Agent',
            'voice_id' => 'eleven_multilingual_v2',
            'language' => 'de',
            'llm_websocket_url' => config('app.url') . '/api/retell/llm',
            'prompt' => $prompt,
            'tools' => [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'collect_appointment_data',
                        'parameters' => $this->getAppointmentSchema()
                    ]
                ]
            ]
        ]);
    }
}
```

## Tag 3: Cal.com Setup Automation

```php
// app/Services/Setup/CalcomSetupService.php
class CalcomSetupService {
    public function setupCompany(Company $company): void {
        // 1. Team prüfen/erstellen
        $team = $this->ensureTeam($company);
        
        // 2. Standard Event Types
        $eventTypes = $this->createDefaultEventTypes($team);
        
        // 3. Webhook registrieren
        $this->registerWebhooks();
        
        // 4. Staff verknüpfen
        $this->linkStaffMembers($company->staff);
    }
    
    private function createDefaultEventTypes($team): array {
        $defaults = [
            ['title' => '30 Min Beratung', 'slug' => '30min', 'length' => 30],
            ['title' => '60 Min Termin', 'slug' => '60min', 'length' => 60],
        ];
        
        $created = [];
        foreach ($defaults as $eventType) {
            $created[] = $this->calcomApi->post('/event-types', 
                array_merge($eventType, ['teamId' => $team['id']])
            );
        }
        
        return $created;
    }
}
```

## Tag 4-5: Setup Wizard 2.0

```php
// app/Filament/Admin/Pages/SetupWizardV2.php
class SetupWizardV2 extends Page {
    protected array $steps = [
        'company' => CompanyDetailsStep::class,
        'verify' => PhoneVerificationStep::class,
        'connect' => AutoConnectStep::class,
        'test' => TestCallStep::class,
    ];
    
    // Total: 4 Steps, 10 Minuten!
}

// app/Services/Setup/QuickSetupService.php
class QuickSetupService {
    public function setupNewCustomer(array $data): Company {
        DB::transaction(function() use ($data) {
            // 1. Company & Branch (30s)
            $company = $this->createCompany($data);
            
            // 2. Auto-Setup Retell (60s)
            $this->retellSetup->setupCompany($company);
            
            // 3. Auto-Setup Cal.com (60s)
            $this->calcomSetup->setupCompany($company);
            
            // 4. Test-Daten (30s)
            $this->createTestData($company);
            
            return $company;
        });
    }
}
```

---

# 💰 PHASE 4: MONETARISIERUNG (Woche 5)
**Ziel: Bezahlsystem live**

## Tag 1-2: Stripe Integration

```php
// app/Services/Billing/StripeService.php
class StripeService {
    public function createCustomer(Company $company): string {
        $customer = $this->stripe->customers->create([
            'email' => $company->billing_email,
            'name' => $company->name,
            'metadata' => [
                'company_id' => $company->id,
                'tenant' => $company->subdomain
            ]
        ]);
        
        return $customer->id;
    }
    
    public function createSubscription(Company $company): Subscription {
        // Basis-Abo 99€/Monat
        $subscription = $this->stripe->subscriptions->create([
            'customer' => $company->stripe_customer_id,
            'items' => [
                ['price' => config('billing.base_price_id')]
            ],
            'metadata' => [
                'company_id' => $company->id
            ]
        ]);
        
        return Subscription::create([
            'company_id' => $company->id,
            'stripe_subscription_id' => $subscription->id,
            'status' => 'active',
            'current_period_end' => Carbon::createFromTimestamp($subscription->current_period_end)
        ]);
    }
}
```

## Tag 3: Usage Tracking

```php
// app/Services/Billing/UsageTracker.php
class UsageTracker {
    public function trackCall(Call $call): void {
        UsageLog::create([
            'company_id' => $call->company_id,
            'type' => 'call',
            'quantity' => 1,
            'unit_price' => 0.50,
            'metadata' => [
                'duration' => $call->duration_sec,
                'call_id' => $call->id
            ]
        ]);
    }
    
    public function trackAppointment(Appointment $appointment): void {
        UsageLog::create([
            'company_id' => $appointment->company_id,
            'type' => 'appointment',
            'quantity' => 1,
            'unit_price' => 2.00,
            'metadata' => [
                'appointment_id' => $appointment->id
            ]
        ]);
    }
}
```

## Tag 4-5: Invoice Generation

```php
// app/Console/Commands/GenerateMonthlyInvoices.php
class GenerateMonthlyInvoices extends Command {
    public function handle() {
        $companies = Company::active()->get();
        
        foreach ($companies as $company) {
            $invoice = $this->createInvoice($company);
            $this->stripeService->createInvoice($invoice);
            $this->emailService->sendInvoice($invoice);
        }
    }
    
    private function createInvoice(Company $company): Invoice {
        $usage = UsageLog::where('company_id', $company->id)
            ->whereBetween('created_at', [
                now()->startOfMonth()->subMonth(),
                now()->startOfMonth()
            ])
            ->get();
            
        return Invoice::create([
            'company_id' => $company->id,
            'subtotal' => 99.00 + $usage->sum('total'),
            'tax_rate' => 0.19,
            'total' => (99.00 + $usage->sum('total')) * 1.19,
            'due_date' => now()->addDays(14),
            'line_items' => $this->generateLineItems($usage)
        ]);
    }
}
```

---

# 🚀 PHASE 5: SKALIERUNG (Woche 6+)
**Ziel: Bereit für Wachstum**

## Architektur für zukünftige Features

### 1. Customer Portal (Vorbereitung)
```php
// app/Services/Portal/PortalService.php
interface PortalService {
    public function getCompanyDashboard(Company $company): array;
    public function getAppointments(Company $company): Collection;
    public function getCalls(Company $company): Collection;
    public function getInvoices(Company $company): Collection;
}

// Datenbank vorbereitet für:
- customer_portal_access (Login für Kunden)
- portal_permissions (Wer darf was sehen)
- audit_logs (Wer hat was gemacht)
```

### 2. SMS/WhatsApp (Vorbereitung)
```php
// app/Services/Notifications/NotificationChannels.php
interface NotificationChannel {
    public function send(Customer $customer, string $message): bool;
}

class EmailChannel implements NotificationChannel {}
class SmsChannel implements NotificationChannel {}    // Später
class WhatsAppChannel implements NotificationChannel {} // Später

// Datenbank vorbereitet für:
- notification_preferences (Kunde wählt Kanal)
- notification_logs (Was wurde wann gesendet)
- message_templates (Mehrsprachige Vorlagen)
```

### 3. Multi-Language (Vorbereitung)
```php
// database/migrations/add_language_support.php
Schema::table('companies', function($table) {
    $table->string('default_language')->default('de');
    $table->json('supported_languages')->default('["de"]');
});

Schema::table('branches', function($table) {
    $table->string('language')->default('de');
});

// Prompt-Templates mehrsprachig
Schema::create('prompt_templates', function($table) {
    $table->string('language');
    $table->string('type'); // greeting, appointment, etc.
    $table->text('template');
});
```

---

# 📊 ERFOLGS-METRIKEN

## Nach jeder Phase messen:

### Phase 1 (Stabilisierung):
- ✅ Erfolgsrate Anruf→Termin > 90%
- ✅ Fehlerrate < 5%
- ✅ Response Time < 2 Sekunden

### Phase 2 (Vereinfachung):
- ✅ Tabellen reduziert von 119 auf ~40
- ✅ Services reduziert von 12 auf 4
- ✅ Code-Zeilen um 50% reduziert

### Phase 3 (Automatisierung):
- ✅ Setup-Zeit < 10 Minuten
- ✅ Manuelle Schritte von 20 auf 4
- ✅ Fehlerrate bei Setup < 1%

### Phase 4 (Monetarisierung):
- ✅ Erste Rechnung erstellt
- ✅ Payment Success Rate > 95%
- ✅ Churn Rate < 5%

### Phase 5 (Skalierung):
- ✅ 10 zahlende Kunden
- ✅ 1000 Anrufe/Tag verarbeitet
- ✅ Uptime > 99.9%

---

# 🎯 KRITISCHER PFAD

Diese Aufgaben MÜSSEN in dieser Reihenfolge erfolgen:

1. **Kern stabilisieren** → Ohne das macht nichts Sinn
2. **Vereinfachen** → Reduziert Fehlerquellen
3. **Automatisieren** → Ermöglicht Skalierung  
4. **Monetarisieren** → Generiert Einnahmen
5. **Skalieren** → Wachstum ermöglichen

**WICHTIG**: Keine Phase überspringen! Jede baut auf der vorherigen auf.

---

# ⚡ QUICK WINS (Sofort umsetzbar)

1. **PhoneNumberResolver Fix** (1 Zeile Code)
2. **Doppelte Mitarbeiter löschen** (1 SQL Query)
3. **Test-Dateien löschen** (1 Bash Command)
4. **Error Logging verbessern** (30 Min)
5. **Backup-Script** (1 Stunde)

---

# 🚫 NICHT MACHEN (Ablenkungen)

- ❌ Neue Features vor Phase 5
- ❌ Perfektes UI/UX Design
- ❌ Micro-Optimierungen
- ❌ Große Refactorings
- ❌ Feature Requests von Nicht-Kunden

---

# 💡 ENTSCHEIDUNGSHILFE

Bei jeder Entscheidung fragen:
1. **Hilft es dem Kern-Flow?** → JA machen
2. **Reduziert es Komplexität?** → JA machen
3. **Bringt es zahlende Kunden?** → JA machen
4. **Ist es "nice to have"?** → NEIN, später

---

# 📅 ZEITPLAN-ÜBERSICHT

```
KW 1-2: Stabilisierung ✓ Kern funktioniert
KW 3:   Vereinfachung ✓ System ist sauber  
KW 4:   Automatisierung ✓ Setup ist einfach
KW 5:   Monetarisierung ✓ Geld kommt rein
KW 6+:  Skalierung ✓ Wachstum möglich
```

**Ende**: Ein stabiles, einfaches, profitables SaaS-Produkt!