# PRD: Billing System Quality Improvements

**Version**: 1.0
**Date**: 2026-01-12
**Source**: QC Report `QC_BILLING_SYSTEM_2026-01-12.md`
**Branch**: `ralph/billing-qc-fixes`

---

## Overview

Dieses PRD beschreibt alle notwendigen Fixes und Verbesserungen aus der 4-Wave Multi-Agent Quality Control Analyse des Billing Systems.

### Already Fixed (P0)
- ✅ P0-001: ServiceCase::markAsBilled() State Guard
- ✅ P0-002: Docblock "both" → "hybrid"
- ✅ P0-004: Webhook Handler Logging
- ✅ P0-005: MySQL Advisory Lock Try-Finally

### To Be Fixed (This PRD)
- P0-003: getMonthlyServicesData() N+1 Query
- P1-001: Stripe Webhook Event ID Tracking
- P1-002: Float Precision in Call Minutes
- P1-003: Missing Billing Indexes
- P1-004: Billing Architecture Documentation
- P2-003: BillingPeriod Value Object
- P2-004: Test Coverage Gaps

---

## User Stories

### US-001: Fix N+1 Query in getMonthlyServicesData()
**Priority**: P0-003 (Critical)
**Problem**: `MonthlyBillingAggregator::getMonthlyServicesData()` führt eine eigene Datenbankabfrage aus, obwohl `preloadBatchData()` die Daten bereits lädt. Dies verursacht N+1 Queries.

**Acceptance Criteria**:
- [ ] `getMonthlyServicesData()` nutzt `$this->getBatchServicePricings($company->id)` statt direkter Query
- [ ] Die direkte Query (Zeilen 416-427) wird entfernt oder in `getBatchServicePricings()` verschoben
- [ ] Logging bestätigt, dass kein zusätzlicher Query für monthly services ausgeführt wird
- [ ] Unit Test: Billing mit 10 Companies führt exakt 3 Queries aus (Calls, Pricings, ChangeFees)
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/pint --test` passes

**Dateien**:
- `app/Services/Billing/MonthlyBillingAggregator.php`
- `tests/Feature/Billing/MonthlyBillingAggregatorTest.php`

---

### US-002: Add Stripe Webhook Event ID Tracking
**Priority**: P1-001 (High)
**Problem**: Stripe Webhooks können replayed werden. Ohne Event-ID-Tracking werden dieselben Events mehrfach verarbeitet.

**Acceptance Criteria**:
- [ ] Migration: `stripe_event_id` VARCHAR(255) NULLABLE zu `aggregate_invoices` hinzufügen
- [ ] Index auf `stripe_event_id` für schnelle Lookups
- [ ] `handleInvoicePaid()`: Prüft ob Event-ID bereits verarbeitet wurde, loggt Warning und skipped
- [ ] `handleInvoiceFinalized()`: Speichert Event-ID nach erfolgreicher Verarbeitung
- [ ] `handleInvoiceVoided()`: Speichert Event-ID nach erfolgreicher Verarbeitung
- [ ] Model: `stripe_event_id` zu fillable und PHPDoc hinzufügen
- [ ] Unit Test: Doppelte Event-ID wird erkannt und ignoriert
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/pint --test` passes

**Dateien**:
- `database/migrations/2026_01_12_XXXXXX_add_stripe_event_id_to_aggregate_invoices.php`
- `app/Models/AggregateInvoice.php`
- `app/Services/Billing/StripeInvoicingService.php`
- `tests/Feature/Billing/StripeInvoicingServiceTest.php`

---

### US-003: Fix Float Precision in Call Minutes Calculation
**Priority**: P1-002 (High)
**Problem**: `duration_sec / 60` erzeugt Float-Werte. Bei vielen Calls können Rundungsfehler akkumulieren.

**Acceptance Criteria**:
- [ ] `getCallMinutesData()` berechnet Sekunden als Integer, Division nur am Ende
- [ ] Formel: `$totalMinutes = round($totalSeconds / 60, 2)` für finale Minutenberechnung
- [ ] Keine Float-Division in Loops
- [ ] `$totalAmountCents` wird als Integer berechnet: `(int) round($totalMinutes * $ratePerMinuteCents)`
- [ ] Unit Test: 1000 Calls mit jeweils 61 Sekunden = exakt 1016.67 Minuten (nicht 1016.666666...)
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/pint --test` passes

**Dateien**:
- `app/Services/Billing/MonthlyBillingAggregator.php`
- `tests/Feature/Billing/MonthlyBillingAggregatorTest.php`

---

### US-004: Add Missing Billing Indexes
**Priority**: P1-003 (High)
**Problem**: Billing-Queries auf `service_cases` sind nicht optimiert.

**Acceptance Criteria**:
- [ ] Migration erstellt Index `idx_service_cases_billing_query` auf `(company_id, billing_status, created_at)`
- [ ] Migration erstellt Index `idx_aggregate_invoices_partner` auf `(partner_company_id, status, billing_period_start)`
- [ ] Migration ist idempotent (prüft ob Index bereits existiert)
- [ ] `php artisan migrate` läuft erfolgreich durch
- [ ] EXPLAIN auf typische Billing-Query zeigt Index-Nutzung
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/pint --test` passes

**Dateien**:
- `database/migrations/2026_01_12_XXXXXX_add_billing_indexes.php`

---

### US-005: Create Billing Architecture Documentation
**Priority**: P1-004 (High)
**Problem**: Keine Dokumentation für das Billing-System vorhanden.

**Acceptance Criteria**:
- [ ] Neue Datei: `claudedocs/02_BACKEND/Billing/BILLING_ARCHITECTURE.md`
- [ ] Abschnitt: Overview (MonthlyBillingAggregator, StripeInvoicingService, Models)
- [ ] Abschnitt: Invoice Lifecycle (draft → open → paid/void) mit ASCII-Diagram
- [ ] Abschnitt: Billing Modes (per_case, monthly_flat, none) mit Beispielen
- [ ] Abschnitt: Stripe Integration (Webhook Events, Idempotency)
- [ ] Abschnitt: Service Gateway Billing (Categories → OutputConfig → Pricing)
- [ ] Abschnitt: Partner Portal (PartnerInvoiceResource, Zugriffskontrolle)
- [ ] Abschnitt: Tax Calculation (German VAT, Discount before Tax)
- [ ] Abschnitt: Troubleshooting (Common Issues, Debug Commands)
- [ ] Index in `claudedocs/00_INDEX.md` aktualisiert
- [ ] Keine Code-Änderungen erforderlich

**Dateien**:
- `claudedocs/02_BACKEND/Billing/BILLING_ARCHITECTURE.md` (neu)
- `claudedocs/00_INDEX.md` (update)

---

### US-006: Create BillingPeriod Value Object
**Priority**: P2-003 (Medium)
**Problem**: Period-Query-Logik ist 7+ mal dupliziert im MonthlyBillingAggregator.

**Acceptance Criteria**:
- [ ] Neue Klasse: `app/ValueObjects/BillingPeriod.php`
- [ ] Immutable Value Object mit `Carbon $start`, `Carbon $end`
- [ ] Factory: `BillingPeriod::forMonth(int $year, int $month): self`
- [ ] Factory: `BillingPeriod::forPreviousMonth(): self`
- [ ] Methode: `toDateRange(): array` returns `[$start, $end]`
- [ ] Methode: `toWhereClause(Builder $query, string $column = 'created_at'): Builder`
- [ ] MonthlyBillingAggregator nutzt BillingPeriod statt inline Carbon-Berechnungen
- [ ] Unit Tests für alle Factory-Methoden und Randfälle (Jahresübergang)
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/pint --test` passes

**Dateien**:
- `app/ValueObjects/BillingPeriod.php` (neu)
- `app/Services/Billing/MonthlyBillingAggregator.php`
- `tests/Unit/ValueObjects/BillingPeriodTest.php` (neu)

---

### US-007: Add Service Gateway Billing Integration Tests
**Priority**: P2-004 (Medium)
**Problem**: Service Gateway Billing Flow hat keine Integration Tests.

**Acceptance Criteria**:
- [ ] Test: Per-Case Billing - ServiceCase mit per_case Config generiert korrekten Invoice Item
- [ ] Test: Monthly-Flat Billing - Config mit monthly_flat generiert korrekten monatlichen Posten
- [ ] Test: Mixed Billing - Company mit beiden Modi generiert beide Item-Typen
- [ ] Test: Billing Status Transition - unbilled → billed nach Invoice-Erstellung
- [ ] Test: Waived Cases werden nicht berechnet
- [ ] Test: Bereits billed Cases werden übersprungen (Idempotenz)
- [ ] Alle Tests nutzen RefreshDatabase Trait
- [ ] Tests nutzen Factories (ServiceCaseFactory, ServiceOutputConfigurationFactory)
- [ ] `php artisan test tests/Feature/Billing/` passes
- [ ] `./vendor/bin/pint --test` passes

**Dateien**:
- `tests/Feature/Billing/ServiceGatewayBillingTest.php` (neu)

---

### US-008: Add ServiceCase Billing State Machine Tests
**Priority**: P2-004 (Medium)
**Problem**: State Transitions für billing_status sind nicht getestet.

**Acceptance Criteria**:
- [ ] Test: unbilled → billed via markAsBilled() erfolgreich
- [ ] Test: billed → billed via markAsBilled() wirft InvalidArgumentException
- [ ] Test: waived → billed via markAsBilled() wirft InvalidArgumentException
- [ ] Test: unbilled → waived via waiveBilling() erfolgreich
- [ ] Test: waiveBilling() speichert Grund in ai_metadata
- [ ] Test: billed_amount_cents und billed_at werden korrekt gesetzt
- [ ] Test: invoice_item_id Relation funktioniert
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/pint --test` passes

**Dateien**:
- `tests/Unit/Models/ServiceCaseBillingTest.php` (neu)

---

### US-009: Add AggregateInvoice Number Generation Concurrency Test
**Priority**: P2-004 (Medium)
**Problem**: `generateInvoiceNumber()` Race Condition nicht getestet.

**Acceptance Criteria**:
- [ ] Test: 10 parallele Invoice-Erstellungen generieren 10 unique Nummern
- [ ] Test verwendet Coroutines oder separate Prozesse für echte Parallelität
- [ ] Test prüft Format: AGG-YYYY-MM-NNN (z.B. AGG-2026-01-001)
- [ ] Test prüft aufsteigende Sequenz (001, 002, 003...)
- [ ] Fallback: Wenn Parallelität nicht möglich, sequentieller Test mit Erwartung
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/pint --test` passes

**Dateien**:
- `tests/Feature/Billing/InvoiceNumberGenerationTest.php` (neu)

---

### US-010: Add ServiceOutputConfiguration Pricing Tests
**Priority**: P2-004 (Medium)
**Problem**: `calculateCasePrice()` Logic nicht getestet.

**Acceptance Criteria**:
- [ ] Test: per_case Mode mit base_price + email_price = korrekte Summe
- [ ] Test: per_case Mode mit base_price + webhook_price = korrekte Summe
- [ ] Test: per_case Mode mit hybrid (email + webhook) = beide Fees addiert
- [ ] Test: monthly_flat Mode gibt 0 für calculateCasePrice() zurück
- [ ] Test: none Mode gibt 0 für calculateCasePrice() zurück
- [ ] Test: Default-Werte (50 cents email, 50 cents webhook, 2900 monthly)
- [ ] Test: getCasePriceEurAttribute() konvertiert korrekt (cents → EUR)
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/pint --test` passes

**Dateien**:
- `tests/Unit/Models/ServiceOutputConfigurationBillingTest.php` (neu)

---

## Execution Order

Ralph sollte die Stories in dieser Reihenfolge abarbeiten:

1. **US-001** - N+1 Fix (P0, kritisch für Performance)
2. **US-002** - Stripe Event ID (P1, Sicherheit)
3. **US-003** - Float Precision (P1, Datenintegrität)
4. **US-004** - Indexes (P1, Performance)
5. **US-005** - Documentation (P1, Wissenstransfer)
6. **US-006** - BillingPeriod VO (P2, DRY)
7. **US-007** - Service Gateway Tests (P2, Coverage)
8. **US-008** - State Machine Tests (P2, Coverage)
9. **US-009** - Concurrency Tests (P2, Coverage)
10. **US-010** - Pricing Tests (P2, Coverage)

---

## Success Criteria

- Alle 10 User Stories implementiert und Tests grün
- N+1 Query in getMonthlyServicesData() eliminiert
- Stripe Webhook Replay-Angriffe verhindert
- Float-Precision-Probleme behoben
- Billing-Queries nutzen Indexes
- Vollständige Dokumentation vorhanden
- Test-Coverage für alle kritischen Billing-Pfade
- `php artisan test` passes (alle Tests)
- `./vendor/bin/pint --test` passes (Code Style)
- Keine neuen P0/P1 Issues in Post-Implementation QC

---

## Context Files

Ralph muss diese Dateien kennen:

### Core Billing Files
```
app/Services/Billing/MonthlyBillingAggregator.php    # 700+ Zeilen, Hauptlogik
app/Services/Billing/StripeInvoicingService.php      # Stripe API, Webhooks
```

### Models
```
app/Models/AggregateInvoice.php                      # Invoice mit Number Generation
app/Models/AggregateInvoiceItem.php                  # Line Items
app/Models/ServiceCase.php                           # billing_status, markAsBilled()
app/Models/ServiceOutputConfiguration.php            # billing_mode, calculateCasePrice()
```

### Existing Tests
```
tests/Feature/Billing/MonthlyBillingAggregatorTest.php
tests/Feature/Billing/StripeInvoicingServiceTest.php
tests/Feature/Billing/AggregateInvoicePolicyTest.php
```

### QC Report
```
claudedocs/08_REFERENCE/QC_BILLING_SYSTEM_2026-01-12.md
```
