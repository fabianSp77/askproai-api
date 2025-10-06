# Policy Quota Enforcement Analysis
**Datum**: 2025-10-03
**Analyst**: Claude Code
**Anfrage**: Teste ob max_cancellations_per_month enforced wird

---

## Executive Summary

❌ **POLICY QUOTA ENFORCEMENT FUNKTIONIERT NICHT**

**Root Causes**:
1. 🔴 **CRITICAL-001A**: DB-Schema vs Model Mismatch - Policies können nicht erstellt werden
2. 🔴 **CRITICAL-001B**: Keine Policies in Datenbank - 0 Konfigurationen existieren
3. 🔴 **CRITICAL-001C**: stat_type Bug - Materialized Stats werden nie gefunden
4. 🔴 **CRITICAL-001D**: MaterializedStatService fehlt - Service existiert nicht

**Impact**:
- ❌ Policy-basierte Quota-Limits (max_cancellations_per_month) werden NICHT durchgesetzt
- ❌ System fällt auf "No Policy = Allow" default zurück (AppointmentPolicyEngine.php:38)
- ❌ Umsatzrelevante Features (Stornierungsgebühren) nicht konfigurierbar
- ⚠️ Fallback auf O(n) real-time Counts funktioniert, aber ohne Policies = nutzlos

---

## Detaillierte Analyse

### 1. DB-Schema vs Model Mismatch (CRITICAL-001A)

#### **Tatsächliche DB-Struktur** (`DESCRIBE policy_configurations`):
```sql
Field                         Type
-----------------------------  ----------------------------------------
id                            bigint(20) unsigned
entity_type                   enum('company','branch','service','staff')  ← ALT
entity_id                     bigint(20) unsigned                     ← ALT
cancellation_hours            int(10) unsigned                        ← ALT (direkte Spalte)
reschedule_hours              int(10) unsigned                        ← ALT
cancellation_fee_type         enum('none','fixed','percentage')       ← ALT
cancellation_fee_amount       decimal(10,2)                           ← ALT
reschedule_fee_type           enum('none','fixed','percentage')       ← ALT
reschedule_fee_amount         decimal(10,2)                           ← ALT
max_cancellations_per_month   int(10) unsigned                        ← ALT
max_reschedules_per_month     int(10) unsigned                        ← ALT
created_at                    timestamp
updated_at                    timestamp
```

#### **Erwartete Struktur** (PolicyConfiguration Model):
```sql
Field                Type
------------------   ----------------------
id                   bigint unsigned
company_id           bigint unsigned        ← NEU
configurable_type    varchar(255)           ← NEU (polymorphisch)
configurable_id      varchar(255)           ← NEU
policy_type          enum(...)              ← NEU
config               json                   ← NEU (flexible Konfiguration)
is_override          boolean                ← NEU
overrides_id         bigint unsigned        ← NEU
created_at           timestamp
updated_at           timestamp
deleted_at           timestamp              ← NEU
```

#### **Migration Status**:
```bash
Migration 2025_10_01_060201_create_policy_configurations_table.php:
- Line 18-20: if (Schema::hasTable('policy_configurations')) return;
- Tabelle existiert bereits (alte Version) → Migration überspringt Erstellung
- Neues Schema wird NIE angewendet
```

#### **Code-Beispiel** (PolicyConfiguration.php:77-84):
```php
public function configurable(): MorphTo
{
    return $this->morphTo();  // Erwartet configurable_type, configurable_id
}
```

**DB hat**: `entity_type`, `entity_id`
**Code sucht**: `configurable_type`, `configurable_id`
**Result**: ❌ Relationship funktioniert nicht

---

### 2. Keine Policies in Datenbank (CRITICAL-001B)

#### **DB-Abfrage**:
```sql
SELECT COUNT(*) as total_policies FROM policy_configurations;
-- Result: 0

SELECT * FROM policy_configurations LIMIT 1;
-- Result: Empty set
```

**Konsequenz** (AppointmentPolicyEngine.php:34-38):
```php
$policy = $this->resolvePolicy($appointment, 'cancellation');

if (!$policy) {
    // No policy = default allow with no fee
    return PolicyResult::allow(fee: 0.0, details: ['policy' => 'default']);
}
```

❌ **IMMER "allow" zurück** weil keine Policy gefunden wird

---

### 3. stat_type Bug (CRITICAL-001C)

#### **Der Bug** (AppointmentPolicyEngine.php:307-327):

**Line 310** - Falscher stat_type:
```php
private function getModificationCount(int $customerId, string $type, int $days): int
{
    // BUG: Normalisiert zu 'cancellation_count' oder 'reschedule_count'
    $statType = $type === 'cancel' ? 'cancellation_count' : 'reschedule_count';

    // Sucht in DB nach 'cancellation_count'
    $stat = AppointmentModificationStat::where('customer_id', $customerId)
        ->where('stat_type', $statType)  // ← Sucht nach 'cancellation_count'
        ->where('period_end', '>=', Carbon::now()->toDateString())
        ->first();

    if ($stat) {
        return $stat->count;  // ← NIE erreicht!
    }

    // IMMER Fallback zur Echtzeit-Berechnung
    return AppointmentModification::where('customer_id', $customerId)
        ->where('modification_type', $type)
        ->where('created_at', '>=', Carbon::now()->subDays($days))
        ->count();  // ← O(n) statt O(1)
}
```

#### **Erwartete stat_types** (AppointmentModificationStat.php:37-46):
```php
public const STAT_TYPE_CANCEL_30D = 'cancel_30d';         ← ERWARTET
public const STAT_TYPE_RESCHEDULE_30D = 'reschedule_30d'; ← ERWARTET
public const STAT_TYPE_CANCEL_90D = 'cancel_90d';         ← ERWARTET
public const STAT_TYPE_RESCHEDULE_90D = 'reschedule_90d'; ← ERWARTET

public const STAT_TYPES = [
    self::STAT_TYPE_CANCEL_30D,      // 'cancel_30d'
    self::STAT_TYPE_RESCHEDULE_30D,  // 'reschedule_30d'
    self::STAT_TYPE_CANCEL_90D,      // 'cancel_90d'
    self::STAT_TYPE_RESCHEDULE_90D,  // 'reschedule_90d'
];
```

**Code sucht**: `'cancellation_count'`
**DB hat**: `'cancel_30d'`, `'cancel_90d'`
**Result**: ❌ Materialized stats NIE gefunden → immer O(n) Fallback

---

### 4. DB-Struktur Mismatch für Stats (CRITICAL-001D)

#### **Tatsächliche DB-Struktur**:
```sql
DESCRIBE appointment_modification_stats;

Field              Type
-----------------  -------------------------------
id                 bigint(20) unsigned
appointment_id     bigint(20) unsigned            ← ALT (sollte nicht hier sein)
customer_id        bigint(20) unsigned
modification_type  enum('cancellation','reschedule')  ← ALT (sollte stat_type sein)
occurred_at        timestamp                      ← ALT
created_at         timestamp
updated_at         timestamp
```

#### **Erwartete Struktur** (Migration 2025_10_01_060400):
```sql
Field              Type
-----------------  -----------------------
id                 bigint unsigned
company_id         bigint unsigned        ← FEHLT in DB!
customer_id        bigint unsigned
stat_type          enum(...)              ← FEHLT (stattdessen: modification_type)
period_start       date                   ← FEHLT
period_end         date                   ← FEHLT
count              integer                ← FEHLT
calculated_at      timestamp              ← FEHLT
```

**Alte Tabelle**: Speichert einzelne Modifications (transaction log)
**Neue Tabelle**: Speichert aggregierte Stats (materialized view)
**Result**: ❌ Völlig verschiedene Konzepte!

---

## Code-Flow-Analyse

### Szenario: Customer will Termin stornieren mit max_cancellations_per_month = 3

#### **Schritt 1**: Policy Lookup (AppointmentPolicyEngine.php:29-38)
```php
public function canCancel(Appointment $appointment, ?Carbon $now = null): PolicyResult
{
    $policy = $this->resolvePolicy($appointment, 'cancellation');

    if (!$policy) {
        return PolicyResult::allow(fee: 0.0, details: ['policy' => 'default']);
    }
    // ... rest of checks
}
```

**Actual Result**:
- `resolvePolicy()` sucht in DB mit `configurable_type` (Model)
- DB hat nur `entity_type` (alte Struktur)
- **0 Policies gefunden** → return early mit "allow"
- ❌ Quota-Check wird NIE erreicht (Lines 58-75)

#### **Schritt 2**: Quota Check (würde ausgeführt wenn Policy existiert)
```php
$maxPerMonth = $policy['max_cancellations_per_month'] ?? null;
if ($maxPerMonth !== null) {
    $recentCount = $this->getModificationCount($appointment->customer_id, 'cancel', 30);

    if ($recentCount >= $maxPerMonth) {
        return PolicyResult::deny(reason: "Quota exceeded", ...);
    }
}
```

**Hypothetical Result** (wenn Policy existiert würde):
- `getModificationCount()` wird aufgerufen
- Line 310: normalisiert zu `'cancellation_count'`
- Line 313: Sucht nach `stat_type = 'cancellation_count'` in DB
- DB hat nur `'cancel_30d'` oder `'cancel_90d'`
- **$stat ist NULL** (nicht gefunden)
- Fallback zu Line 322: Echtzeit-Count via AppointmentModification
- ✅ Count wird korrekt berechnet (aber O(n) statt O(1))
- ✅ Quota-Enforcement würde funktionieren (aber nur mit O(n) Performance)

---

## Root Cause Summary

### **Problem 1**: Migration Conflict
```
Alte Migration erstellt policy_configurations mit entity_type/entity_id
↓
Neue Migration (2025_10_01) prüft: if (Schema::hasTable(...)) return;
↓
Neue Struktur (configurable_type/id) wird NIE angewendet
↓
Code kann keine Policies lesen/schreiben
```

### **Problem 2**: Keine Policies konfiguriert
```
0 rows in policy_configurations
↓
resolvePolicy() returned null
↓
PolicyResult::allow() early return
↓
Quota-Check wird nie erreicht
```

### **Problem 3**: stat_type Mismatch
```
Code sucht: 'cancellation_count'
↓
DB hat: 'cancel_30d', 'cancel_90d'
↓
Materialized Stat nie gefunden
↓
IMMER Fallback zu O(n) Echtzeit-Count
```

### **Problem 4**: Tabellen-Struktur Mismatch
```
Alte stats: Transaction log (appointment_id, modification_type, occurred_at)
↓
Neue stats: Aggregated view (stat_type, period_start/end, count, calculated_at)
↓
Völlig verschiedene Konzepte
↓
Migration kann nicht laufen (Tabelle existiert bereits)
```

---

## Lösungsvorschläge

### **Option 1**: Migration Rework (Empfohlen für Production)

**Schritt 1**: Rename alte Tabellen
```sql
RENAME TABLE policy_configurations TO policy_configurations_old;
RENAME TABLE appointment_modification_stats TO appointment_modification_stats_old;
```

**Schritt 2**: Neue Migrations ausführen
```bash
php artisan migrate
# 2025_10_01_060201 erstellt neue policy_configurations
# 2025_10_01_060400 erstellt neue appointment_modification_stats
```

**Schritt 3**: Daten migrieren
```php
// PolicyConfiguration Migration
DB::table('policy_configurations_old')->get()->each(function ($old) {
    PolicyConfiguration::create([
        'company_id' => $old->company_id,
        'configurable_type' => match($old->entity_type) {
            'company' => Company::class,
            'branch' => Branch::class,
            'service' => Service::class,
            'staff' => Staff::class,
        },
        'configurable_id' => $old->entity_id,
        'policy_type' => 'cancellation', // Ableiten aus vorhandenen Feldern
        'config' => [
            'hours_before' => $old->cancellation_hours,
            'fee_type' => $old->cancellation_fee_type,
            'fee_amount' => $old->cancellation_fee_amount,
            'max_cancellations_per_month' => $old->max_cancellations_per_month,
        ],
    ]);

    // Reschedule Policy wenn Daten vorhanden
    if ($old->reschedule_hours > 0 || $old->max_reschedules_per_month) {
        PolicyConfiguration::create([
            'company_id' => $old->company_id,
            'configurable_type' => /* same */,
            'configurable_id' => $old->entity_id,
            'policy_type' => 'reschedule',
            'config' => [
                'hours_before' => $old->reschedule_hours,
                'max_reschedules_per_month' => $old->max_reschedules_per_month,
            ],
        ]);
    }
});
```

**Schritt 4**: Alte Tabellen droppen
```sql
DROP TABLE policy_configurations_old;
DROP TABLE appointment_modification_stats_old;
```

**Aufwand**: 3-4 Stunden
**Risk**: Niedrig (mit Backup)

---

### **Option 2**: Fix stat_type Bug (Quick Win)

**File**: `app/Services/Policies/AppointmentPolicyEngine.php`

**Line 310** - Current:
```php
$statType = $type === 'cancel' ? 'cancellation_count' : 'reschedule_count';
```

**Fix**:
```php
// Match AppointmentModificationStat::STAT_TYPES
$statType = $type === 'cancel'
    ? AppointmentModificationStat::STAT_TYPE_CANCEL_30D   // 'cancel_30d'
    : AppointmentModificationStat::STAT_TYPE_RESCHEDULE_30D; // 'reschedule_30d'
```

**Alternative** (wenn 30/90 Tage variabel):
```php
// Bestimme Window basierend auf $days Parameter
$window = $days <= 30 ? '30d' : '90d';
$statType = $type === 'cancel' ? "cancel_{$window}" : "reschedule_{$window}";
```

**Aufwand**: 15 Minuten
**Impact**: Materialized stats werden gefunden (sobald Service läuft)

---

### **Option 3**: MaterializedStatService implementieren (CRITICAL-001 Fix)

**File**: `app/Services/Policies/MaterializedStatService.php` (NEU)

```php
<?php

namespace App\Services\Policies;

use App\Models\AppointmentModification;
use App\Models\AppointmentModificationStat;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MaterializedStatService
{
    /**
     * Refresh stats for a single customer
     */
    public function refreshCustomerStats(Customer $customer): void
    {
        // Bind service context for Model protection
        app()->bind('materializedStatService.updating', fn() => true);

        $windows = [
            ['days' => 30, 'suffix' => '30d'],
            ['days' => 90, 'suffix' => '90d'],
        ];

        foreach ($windows as $window) {
            $periodStart = Carbon::now()->subDays($window['days']);
            $periodEnd = Carbon::now();

            // Cancellation count
            $cancelCount = AppointmentModification::where('customer_id', $customer->id)
                ->where('modification_type', 'cancel')
                ->where('created_at', '>=', $periodStart)
                ->count();

            AppointmentModificationStat::updateOrCreate(
                [
                    'company_id' => $customer->company_id,
                    'customer_id' => $customer->id,
                    'stat_type' => "cancel_{$window['suffix']}",
                    'period_start' => $periodStart->toDateString(),
                ],
                [
                    'period_end' => $periodEnd->toDateString(),
                    'count' => $cancelCount,
                    'calculated_at' => Carbon::now(),
                ]
            );

            // Reschedule count
            $rescheduleCount = AppointmentModification::where('customer_id', $customer->id)
                ->where('modification_type', 'reschedule')
                ->where('created_at', '>=', $periodStart)
                ->count();

            AppointmentModificationStat::updateOrCreate(
                [
                    'company_id' => $customer->company_id,
                    'customer_id' => $customer->id,
                    'stat_type' => "reschedule_{$window['suffix']}",
                    'period_start' => $periodStart->toDateString(),
                ],
                [
                    'period_end' => $periodEnd->toDateString(),
                    'count' => $rescheduleCount,
                    'calculated_at' => Carbon::now(),
                ]
            );
        }

        app()->bind('materializedStatService.updating', fn() => false);
    }

    /**
     * Refresh stats for all customers (batch processing)
     */
    public function refreshAllStats(): void
    {
        Customer::chunk(100, function ($customers) {
            foreach ($customers as $customer) {
                $this->refreshCustomerStats($customer);
            }
        });
    }

    /**
     * Clean up old stats (>90 days)
     */
    public function cleanupOldStats(): void
    {
        AppointmentModificationStat::where('period_end', '<', Carbon::now()->subDays(90))
            ->delete();
    }
}
```

**Scheduled Job** (`app/Console/Kernel.php`):
```php
protected function schedule(Schedule $schedule): void
{
    // Refresh stats hourly
    $schedule->call(function () {
        app(MaterializedStatService::class)->refreshAllStats();
    })->hourly();

    // Cleanup daily at 3am
    $schedule->call(function () {
        app(MaterializedStatService::class)->cleanupOldStats();
    })->dailyAt('03:00');
}
```

**Aufwand**: 4-6 Stunden
**Impact**: O(1) Quota-Checks statt O(n)

---

### **Option 4**: Test Policy erstellen (für Validation)

**Seeder**: `database/seeders/TestPolicySeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PolicyConfiguration;
use Illuminate\Database\Seeder;

class TestPolicySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        if (!$company) {
            $this->command->error('No company found. Run CompanySeeder first.');
            return;
        }

        // Company-level cancellation policy
        PolicyConfiguration::create([
            'company_id' => $company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $company->id,
            'policy_type' => 'cancellation',
            'config' => [
                'hours_before' => 24,
                'max_cancellations_per_month' => 3,
                'fee_percentage' => 50,
            ],
            'is_override' => false,
        ]);

        // Company-level reschedule policy
        PolicyConfiguration::create([
            'company_id' => $company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $company->id,
            'policy_type' => 'reschedule',
            'config' => [
                'hours_before' => 12,
                'max_reschedules_per_month' => 5,
                'max_reschedules_per_appointment' => 2,
            ],
            'is_override' => false,
        ]);

        $this->command->info('Test policies created successfully.');
    }
}
```

**Run**:
```bash
php artisan db:seed --class=TestPolicySeeder
```

---

## Implementierungs-Prioritäten

### **Sprint 1 (CRITICAL)**: Schema Fix + Service (2 Wochen)
1. ✅ **Option 1**: Migration Rework (8-10h)
   - Rename old tables
   - Run new migrations
   - Migrate data
   - Drop old tables
   - Test with PolicyQuotaEnforcementTest

2. ✅ **Option 3**: MaterializedStatService (4-6h)
   - Implement service
   - Add scheduled jobs
   - Test with real data

3. ✅ **Option 2**: Fix stat_type Bug (15min)
   - Change line 310 in AppointmentPolicyEngine
   - Use AppointmentModificationStat::STAT_TYPE_CANCEL_30D

4. ✅ **Option 4**: Test Policy Seeder (1h)
   - Create seeder
   - Run and validate
   - Document in ADMIN_GUIDE.md

**Total Effort**: 14-18 Stunden
**Result**: Policy Quota Enforcement funktioniert mit O(1) Performance

### **Sprint 2**: PolicyConfigurationResource UI (1 Woche)
- Siehe IMPROVEMENT_ROADMAP.md Sprint 1 Task 1.2

---

## Validierung

### **Test 1**: Policy Enforcement (Manual)
```bash
# 1. Create test policy
php artisan db:seed --class=TestPolicySeeder

# 2. Create test appointments + modifications
php artisan tinker
>> $customer = Customer::first();
>> AppointmentModification::factory()->count(3)->create(['customer_id' => $customer->id, 'modification_type' => 'cancel']);

# 3. Test quota check
>> $appointment = Appointment::factory()->create(['customer_id' => $customer->id]);
>> $engine = app(AppointmentPolicyEngine::class);
>> $result = $engine->canCancel($appointment);
>> dd($result);  // Should be DENIED (quota exceeded)
```

### **Test 2**: Materialized Stats Performance
```bash
php artisan tinker
>> $service = app(MaterializedStatService::class);
>> Benchmark::dd(fn() => $service->refreshCustomerStats(Customer::first()));  // Should be <100ms

>> $engine = app(AppointmentPolicyEngine::class);
>> Benchmark::dd(fn() => $engine->canCancel(Appointment::first()));  // Should use O(1) lookup
```

### **Test 3**: Automated Test
```bash
php artisan test tests/Feature/PolicyQuotaEnforcementTest.php
# Expected: PASS (after fixes)
```

---

## Zusammenfassung

**Frage**: Funktioniert max_cancellations_per_month Enforcement?

**Antwort**: ❌ NEIN

**Gründe**:
1. DB-Schema vs Model Mismatch → Policies können nicht erstellt/gelesen werden
2. 0 Policies in DB → System gibt immer "allow" zurück
3. stat_type Bug → Materialized stats werden nie gefunden
4. MaterializedStatService fehlt → Service existiert nicht

**Konsequenz**:
- Policy-basierte Quota-Limits werden NICHT durchgesetzt
- System verhält sich als hätte es keine Policies (default allow)
- Umsatzrelevante Features (Stornierungsgebühren) nicht nutzbar

**Lösung**:
- Sprint 1: Migration Rework + MaterializedStatService + stat_type Fix
- Aufwand: 14-18 Stunden
- Result: Voll funktionales Policy-System mit O(1) Performance

**Nächster Schritt**: Sprint 1 Task 1.1 starten (siehe IMPROVEMENT_ROADMAP.md)
