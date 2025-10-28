# Cal.com Team Event Types - Korrekte Implementierung

**Datum:** 2025-10-27
**Problem:** Services wurden auf User-Level statt Team-Level erstellt
**Lösung:** Team-spezifischer Endpoint mit korrektem Payload

---

## Problem

Bei der Synchronisation von Friseur 1 Services zu Cal.com wurden Event Types erstellt, die NICHT im Team "Friseur" (ID: 34209) erschienen, sondern auf User-Level.

**Fehlerhafte Methode:**
```php
// ❌ FALSCH - Erstellt Event Types auf User-Level
POST /event-types
{
    "teamId": 34209,
    "title": "Service Name",
    "length": 30
}
```

**Ergebnis:** Event Type wird erstellt, aber `teamId` in der Response ist `null` → Service erscheint NICHT im Team.

---

## Lösung

### Endpoint

```
POST https://api.cal.com/v2/teams/{teamId}/event-types
```

**Wichtig:** Der Team-spezifische Endpoint `/teams/{teamId}/event-types` verwenden, NICHT `/event-types` mit teamId Parameter!

### Required Payload Fields

```php
[
    'title' => 'Service Name',
    'slug' => 'service-name',
    'description' => 'Service Description',
    'lengthInMinutes' => 30,         // ⚠️ NOT 'length'!
    'schedulingType' => 'COLLECTIVE'  // ⚠️ REQUIRED: ROUND_ROBIN | COLLECTIVE | MANAGED
]
```

**Kritisch:**
- ⚠️ **`lengthInMinutes`** - NICHT `length`!
- ⚠️ **`schedulingType`** - PFLICHTFELD für Team Events!
  - Optionen: `ROUND_ROBIN`, `COLLECTIVE`, `MANAGED`
  - Für Friseur Services: `COLLECTIVE` (alle Team-Mitglieder können buchen)

### Optional Fields

```php
[
    'price' => 50,        // Preis in kleinster Währungseinheit (Cent)
    'currency' => 'EUR',  // Währung
]
```

---

## Vollständiges Code-Beispiel

```php
<?php

use App\Models\Service;
use App\Models\Branch;
use Illuminate\Support\Facades\Http;

$branch = Branch::where('name', 'LIKE', '%Friseur 1%')->first();
$baseUrl = config('services.calcom.base_url');
$apiKey = config('services.calcom.api_key');
$teamId = 34209;

// Team-spezifischer Endpoint
$teamEndpoint = $baseUrl . '/teams/' . $teamId . '/event-types';

// Services ohne Cal.com ID (fehlgeschlagene)
$services = Service::where('company_id', $branch->company_id)
    ->where('is_active', true)
    ->whereNull('calcom_event_type_id')
    ->get();

foreach ($services as $service) {
    // KORREKTE PAYLOAD STRUCTURE
    $payload = [
        'title' => $service->name,
        'slug' => \Illuminate\Support\Str::slug($service->name),
        'description' => $service->description ?? "Service: " . $service->name,
        'lengthInMinutes' => $service->duration_minutes ?? 30,
        'schedulingType' => 'COLLECTIVE',
    ];

    // Optional: Preis hinzufügen
    if ($service->price) {
        $payload['price'] = $service->price;
        $payload['currency'] = 'EUR';
    }

    // API Request mit Retry
    $response = Http::timeout(15)
        ->retry(3, function ($attempt) {
            return 1000 * pow(2, $attempt - 1);  // Exponential backoff
        }, throw: false)
        ->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json'
        ])
        ->acceptJson()
        ->post($teamEndpoint, $payload);

    if ($response->successful()) {
        $data = $response->json();
        $eventTypeId = $data['data']['id'] ?? null;
        $teamIdResponse = $data['data']['teamId'] ?? null;

        // Verifiziere Team-Zuordnung
        if ($eventTypeId && $teamIdResponse == $teamId) {
            $service->calcom_event_type_id = $eventTypeId;
            $service->save();
            echo "✅ Service '{$service->name}' erstellt in Team {$teamId}\n";
        }
    }

    usleep(300000);  // 300ms delay zwischen Requests
}
```

---

## Validation Error Details

Falls HTTP 400 mit Validation Errors:

```json
{
  "error": {
    "details": {
      "errors": [
        {
          "property": "schedulingType",
          "constraints": {
            "isEnum": "schedulingType must be one of: ROUND_ROBIN, COLLECTIVE, MANAGED"
          }
        },
        {
          "property": "lengthInMinutes",
          "constraints": {
            "min": "lengthInMinutes must not be less than 1",
            "isInt": "lengthInMinutes must be an integer number"
          }
        }
      ]
    }
  }
}
```

**Lösung:** `lengthInMinutes` und `schedulingType` hinzufügen (siehe oben).

---

## Verifikation

Nach erfolgreicher Erstellung:

1. **Cal.com UI prüfen:**
   ```
   https://app.cal.com/event-types?teamId=34209
   ```
   → Service muss hier erscheinen!

2. **Response prüfen:**
   ```php
   $teamIdResponse = $data['data']['teamId'] ?? null;
   if ($teamIdResponse == 34209) {
       echo "✅ Service korrekt im Team 34209!";
   }
   ```

3. **Datenbank prüfen:**
   ```sql
   SELECT name, calcom_event_type_id
   FROM services
   WHERE calcom_event_type_id IS NOT NULL;
   ```

---

## Retry Strategy für API Errors

Cal.com API kann instabil sein (HTTP 502/503). Empfohlene Retry-Strategie:

```php
Http::timeout(15)
    ->retry(3, function ($attempt) {
        return 1000 * pow(2, $attempt - 1);  // 1s, 2s, 4s delays
    }, throw: false)
    ->post($teamEndpoint, $payload);
```

**Delays zwischen Requests:**
- Minimum: 200ms (`usleep(200000)`)
- Empfohlen bei Problemen: 300ms (`usleep(300000)`)

---

## Scripts

**Alle Services erstellen:**
```bash
php artisan tinker /tmp/create_all_team_services_FINAL.php
```

**Nur fehlgeschlagene Services (Retry):**
```bash
php artisan tinker /tmp/retry_failed_services.php
```

**Fortschritt prüfen:**
```bash
php artisan tinker
>>> Service::whereNotNull('calcom_event_type_id')->count()
```

---

## Lessons Learned

1. ✅ **Team Endpoint verwenden:** `/teams/{teamId}/event-types` statt `/event-types`
2. ✅ **Payload Fields beachten:** `lengthInMinutes` statt `length`
3. ✅ **schedulingType ist Pflicht** für Team Events
4. ✅ **Immer mit EINEM Service testen** vor Bulk-Operationen
5. ✅ **Team ID in Response verifizieren** (`teamId == 34209`)
6. ✅ **Retry-Logik implementieren** wegen Cal.com API Instabilität

---

## Related Documentation

- Cal.com API v2: https://cal.com/docs/api-reference/v2
- Team Event Types: https://cal.com/docs/api-reference/v2/event-types/create-team-event-type
- Testing Script: `/tmp/test_team_endpoint_correct.php`
- Production Script: `/tmp/create_all_team_services_FINAL.php`
- Retry Script: `/tmp/retry_failed_services.php`

---

**Status:** ✅ Methode validiert und dokumentiert
**Erstellt von:** Claude (Sonnet 4.5)
**Kontext:** Friseur 1 Branch (Team ID: 34209) Service Synchronisation
