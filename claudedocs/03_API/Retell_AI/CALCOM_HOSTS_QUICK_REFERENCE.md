# Cal.com Hosts & Team Members - Quick Reference

**Status**: ✅ FULLY ANALYZED (2025-10-21)

---

## TL;DR - Die 3 wichtigsten Erkenntnisse

### 1. Team Members abrufen
```php
// ✅ Funktioniert!
$calcomService = app(CalcomV2Service::class);
$response = $calcomService->fetchTeamMembers($teamId);
// Returns: { "members": [...] }
```

### 2. Hosts eines Event Types anzeigen
```php
// ✅ Daten vorhanden in TeamEventTypeMapping!
$mapping = TeamEventTypeMapping::where(
    'calcom_event_type_id', 
    $eventTypeId
)->first();

$hosts = $mapping->hosts ?? [];  // Array mit Hosts
foreach ($hosts as $host) {
    echo "{$host['name']} ({$host['email']})";
}
```

### 3. Host Mapping zu Staff
```php
// ✅ Automatisch matched!
$mapping = CalcomHostMapping::where(
    'calcom_host_id', 
    $hostId
)->first();

$staff = $mapping->staff;  // Lokale Staff Person
```

---

## Datenbankmodelle

| Modell | Zweck | Wichtige Felder |
|--------|-------|-----------------|
| `CalcomTeamMember` | Speichert Team Members | userId, email, name, role, accepted |
| `TeamEventTypeMapping` | Speichert Event Types + Hosts | calcom_event_type_id, **hosts** (JSON Array) |
| `CalcomHostMapping` | Host-zu-Staff Mapping | calcom_host_id, staff_id, confidence_score |

---

## Sync Commands

```bash
# Sync alle Team Members einer Company
php artisan calcom:sync-team-members

# Sync nur eine specific Company
php artisan calcom:sync-team-members --company=1

# Importiere Event Types + Members
# (Wird automatisch als Job dispatched)
```

---

## API Endpoints

| Endpoint | Implementiert | Response |
|----------|---------------|----------|
| `GET /v2/teams/{id}/members` | ✅ Yes | Array von Team Members |
| `GET /v2/teams/{id}/event-types` | ✅ Yes | Array von Event Types |
| `GET /v2/teams/{id}/event-types/{id}` | ✅ Yes | Event Type Details (OHNE Hosts) |

---

## Key Insight: Wo sind die Hosts?

### Cal.com API gibt NICHT direkt:
```
"Welche Hosts können diesen Event Type buchen?"
```

### ABER wir haben sie lokal gespeichert in:
```
TeamEventTypeMapping::find($eventTypeId)->hosts
→ [
  { "userId": 123, "name": "Thomas", "email": "...", ... },
  { "userId": 456, "name": "Sara", "email": "...", ... }
]
```

---

## Matching & Confidence

```
CalcomHostMapping Status:
├─ Email Match: 95% confidence ✅ Auto-linked
├─ Name Match: 75% confidence ⚠️ Manual review
└─ No Match: 0% ❌ Requires manual mapping

Auto-Threshold: 75% (configurable)
```

---

## Cache Strategy

```
Availability Cache:
Key: week_availability:{teamId}:{serviceId}:{weekStart}
TTL: 60 seconds

Double-Layer Invalidation:
1. CalcomService.slots cache
2. AppointmentAlternativeFinder cache
```

---

## Verwendungsbeispiel

```php
// Service + Hosts laden
$service = Service::with('company')->find($serviceId);
$mapping = TeamEventTypeMapping::where(
    'calcom_event_type_id', 
    $service->calcom_event_type_id
)->first();

// Zeige alle Hosts
foreach ($mapping->hosts ?? [] as $host) {
    // Host info vorhanden:
    // - $host['name']
    // - $host['email']
    // - $host['userId']
    // - $host['username']
    // - $host['avatarUrl']
}
```

---

## Dokumentation Links

| Thema | Datei |
|-------|-------|
| Vollständige Analyse | `CALCOM_API_TEAM_MEMBERS_INVESTIGATION_2025-10-21.md` |
| Cal.com Integration | `claudedocs/02_BACKEND/Calcom/` |
| Availability Logic | `WEEKLY_AVAILABILITY_LOGIC.md` |
| Host Mapping | `CALCOM_HOSTMAPPING_ARCHITECTURE.md` |

---

**Zuletzt aktualisiert**: 2025-10-21  
**Autor**: API Research Task  
**Status**: ✅ VERIFIED
