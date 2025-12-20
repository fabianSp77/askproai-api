# Regression Safety Note: Friseur/Appointment Flow Schutz

**Datum**: 20. Dezember 2025
**Autor**: Claude Code (Automated Analysis)
**Status**: Verifiziert

---

## Executive Summary

Der Service Gateway (IT-Ticketing) ist **vollstaendig isoliert** vom Appointment/Friseur Flow. Es gibt keine gemeinsamen Code-Pfade, die zu versehentlichem Routing fuehren koennten.

**Risikobewertung**: SEHR NIEDRIG

---

## Isolationsmechanismen

### 1. Feature Flag: GATEWAY_MODE_ENABLED

**Datei**: `config/gateway.php`

```php
'mode_enabled' => env('GATEWAY_MODE_ENABLED', false),
'default_mode' => env('GATEWAY_DEFAULT_MODE', 'appointment'),
'hybrid_fallback' => env('GATEWAY_HYBRID_FALLBACK', 'appointment'),
```

**Verhalten**:
- `GATEWAY_MODE_ENABLED=false` (Default): Gateway-Logik wird nie ausgefuehrt
- `GATEWAY_MODE_ENABLED=true`: Gateway-Logik moeglich, aber benoetigt Company Policy

### 2. Company-Level Policy

**Datei**: `app/Models/PolicyConfiguration.php`

```php
const POLICY_TYPE_GATEWAY_MODE = 'gateway_mode';
```

**Verhalten**:
- Jede Company braucht explizite PolicyConfiguration mit `mode: 'service_desk'`
- Ohne Policy → Fallback zu `appointment` Mode
- Friseur-Companies haben keine solche Policy → unberuehrt

### 3. Mode Resolver

**Datei**: `app/Services/Gateway/GatewayModeResolver.php`

```php
public function resolve(int $companyId, array $context = []): GatewayModeResult
{
    // 1. Feature Flag Check
    if (!config('gateway.mode_enabled', false)) {
        return GatewayModeResult::appointment('feature_disabled');
    }

    // 2. Policy Lookup
    $policy = $this->getPolicyForCompany($companyId);
    if (!$policy) {
        return GatewayModeResult::appointment('no_policy');
    }

    // 3. Mode Determination
    return $this->determineMode($policy, $context);
}
```

**Sicherheitsgarantie**:
- Kein Policy = Appointment Mode
- Feature disabled = Appointment Mode
- Hybrid Mode Fallback = Appointment Mode

### 4. Handler Isolation

**Appointment Flow**:
- `RetellFunctionCallHandler.php` (Zeile 797-833)
- Funktionen: `check_availability`, `book_appointment`, `reschedule`, `cancel`

**Service Desk Flow**:
- `ServiceDeskHandler.php`
- Funktionen: `finalize_ticket`, `collect_issue_details`

**Verifiziert**: `grep -c "book_appointment\|check_availability" ServiceDeskHandler.php` = 0

---

## Test-Abdeckung

### Unit Tests

**Datei**: `tests/Unit/Gateway/GatewayModeResolverTest.php`

| Test | Beschreibung | Status |
|------|--------------|--------|
| Feature Flag Rollback | Wenn disabled, immer appointment | ✅ |
| Multi-Tenant Isolation | Company A ≠ Company B | ✅ |
| Default Fallback | Ohne Policy → appointment | ✅ |

### Feature Tests

**Datei**: `tests/Feature/Gateway/GatewayRoutingTest.php`

| Test | Beschreibung | Status |
|------|--------------|--------|
| End-to-End Routing | Kompletter Flow | ✅ |
| Default Behavior | Appointment ohne Policy | ✅ |

### Service Case Tests

**Datei**: `tests/Feature/ServiceGateway/FinalizeTicketTest.php`

| Test | Beschreibung | Status |
|------|--------------|--------|
| CRIT-002 Validation | Cross-Tenant Schutz | ✅ |
| Idempotency | Doppelte Aufrufe | ✅ |

---

## Notfall-Rollback

### Sofort (30 Sekunden)

```bash
# .env andern
GATEWAY_MODE_ENABLED=false

# Config neu laden
php artisan config:clear
php artisan cache:clear
```

### Company-spezifisch

```sql
-- Gateway-Mode fuer spezifische Company deaktivieren
UPDATE policy_configurations
SET config = '{"mode": "appointment"}'
WHERE policy_type = 'gateway_mode'
  AND company_id = ?;
```

---

## Smoke Tests: Minimal Required

### 1. Appointment Booking (Friseur)

```bash
# Verfuegbarkeit pruefen
curl -X POST /api/v1/retell/function-call \
  -d '{"function_name": "check_availability", "company_id": 1}'

# Erwartung: Verfuegbare Slots zurueck
```

### 2. Gateway Mode Isolation

```bash
# Service Desk Funktion fuer Friseur-Company
curl -X POST /api/v1/retell/function-call \
  -d '{"function_name": "finalize_ticket", "company_id": 1}'

# Erwartung: Funktion nicht gefunden / kein Routing
```

### 3. Existierender Appointment Test

```bash
# Wenn vorhanden
php artisan test tests/Feature/Appointments/BookingFlowTest.php
```

---

## Risiko-Matrix

| Risiko | Wahrscheinlichkeit | Impact | Mitigation |
|--------|-------------------|--------|------------|
| Gateway beeinflusst Friseur | SEHR NIEDRIG | KRITISCH | Feature Flag + Policy Check |
| Mode Resolver crasht | NIEDRIG | HOCH | Fallback zu appointment |
| Cross-Tenant Leak | SEHR NIEDRIG | KRITISCH | CRIT-002 Validierung |
| ServiceDesk fuer Friseur | UNMOEGLICH | N/A | Handler-Isolation |

---

## Konfiguration im .env

**Aktuelle Werte** (Production):

```bash
# Gateway Feature Flag
GATEWAY_MODE_ENABLED=true  # Aktiviert, aber Policy-abhaengig

# Defaults (sicher)
GATEWAY_DEFAULT_MODE=appointment
GATEWAY_HYBRID_FALLBACK=appointment
```

**Wichtig**: `GATEWAY_MODE_ENABLED=true` allein aktiviert NICHT das IT-Ticketing. Es erlaubt nur die Pruefung der Company-Policy. Ohne Policy → Appointment.

---

## Fazit

Der Appointment/Friseur Flow ist durch mehrere Schichten geschuetzt:

1. ✅ Feature Flag (global aus/ein)
2. ✅ Company Policy (explizite Opt-in)
3. ✅ Handler Separation (verschiedene Klassen)
4. ✅ Function Namespace (verschiedene Funktionsnamen)
5. ✅ Default Fallback (immer appointment)
6. ✅ Tests (Unit + Feature)

**Keine Aenderungen am Appointment Flow erforderlich.**
