# Cal.com API Untersuchung - Team Members & Event Type Details

**Datum**: 2025-10-21  
**Projekt**: AskPro API Gateway  
**Status**: ERLEDIGT - Umfassende Analyse abgeschlossen

---

## EXECUTIVE SUMMARY

### Frage: Können wir von einem Cal.com Event Type die verfügbaren Hosts/Teams abrufen und anzeigen?

**KURZE ANTWORT: Teilweise - mit Einschränkungen**

- ✅ **Hosts/Team Members über Cal.com API abrufbar**: `/v2/teams/{teamId}/members`
- ✅ **Event Type Details abrufbar**: `/v2/teams/{teamId}/event-types/{id}` 
- ⚠️ **ABER**: Event Type Details enthalten **NICHT** die verfügbaren Hosts
- ❌ **Keine direkte API**: Cal.com bietet keine Endpoint um "welche Hosts können diesen Event Type buchen" zu fragen
- ✅ **WORKAROUND**: Hosts sind in `TeamEventTypeMapping.hosts` JSON Array gespeichert

---

## 1. CAL.COM API FÜR TEAM MEMBERS

### 1.1 Implementierte Endpoints

#### `GET /v2/teams/{teamId}/members` ✅ IMPLEMENTIERT

**Location**: `CalcomV2Service::fetchTeamMembers()`  
**File**: `/var/www/api-gateway/app/Services/CalcomV2Service.php:112-126`

```php
public function fetchTeamMembers(int $teamId): Response
{
    $fullUrl = $this->baseUrl . '/v2/teams/' . $teamId . '/members';
    
    return $this->httpClient()->get($fullUrl);
    // Returns: { "members": [ {...}, {...} ] }
}
```

**Response Structure**:
```json
{
  "members": [
    {
      "userId": 12345,
      "email": "thomas@example.com",
      "name": "Thomas Müller",
      "username": "thomas_mueller",
      "role": "owner",
      "accepted": true
    },
    {
      "userId": 12346,
      "email": "sara@example.com",
      "name": "Sara Schmidt",
      "username": "sara_schmidt",
      "role": "member",
      "accepted": true
    }
  ]
}
```

**Datenbank Speicherung**: `CalcomTeamMember` Model
- Speichert: userId, email, name, username, role, accepted, availability, is_active

---

### 1.2 Team Members Sync

#### Command: `calcom:sync-team-members` ✅ VORHANDEN

**Location**: `/var/www/api-gateway/app/Console/Commands/SyncCalcomTeamMembers.php`

```bash
php artisan calcom:sync-team-members
php artisan calcom:sync-team-members --company=1
```

**Was es tut**:
1. Abrufen aller Companies mit `calcom_team_id`
2. API Call: `fetchTeamMembers()` pro Company
3. Speichern in `calcom_team_members` Table
4. Auto-Link mit lokalen Staff-Membern via Email/Name Matching
5. Audit Trail für alle Änderungen

**Output Beispiel**:
```
📌 Processing: ACME GmbH (Team ID: 123)
  Found: 4 members
  ✅ Thomas Müller (thomas@example.com)
     → Linked to Staff Member
  ✅ Sara Schmidt (sara@example.com)
     → Linked to Staff Member
  ✅ Manuel Garcia (manuel@example.com)
  ✅ Lisa Weber (lisa@example.com)
     → Linked to Staff Member

✅ Sync Complete!
📊 Summary:
   • Total members synced: 4
   • Staff linked: 3
```

---

## 2. EVENT TYPE DETAILS & HOST MAPPING

### 2.1 Event Type Details API

#### `GET /v2/teams/{teamId}/event-types/{id}` ✅ IMPLEMENTIERT

**Location**: `CalcomV2Client::getEventType()`  
**File**: `/var/www/api-gateway/app/Services/CalcomV2Client.php:212-216`

```php
public function getEventType(int $eventTypeId): Response
{
    return Http::withHeaders($this->getHeaders())
        ->get($this->getTeamUrl("event-types/{$eventTypeId}"));
}
```

**PROBLEM**: Rückgabe enthält **NICHT** "welche Hosts können diesen Event Type buchen"

**Event Type Response Struktur** (V1 API Beispiel):
```json
{
  "event_type": {
    "id": 98765,
    "title": "Beratungsgespräch",
    "slug": "beratungsgespräch",
    "description": "60 Minuten Beratung",
    "length": 60,
    "schedulingType": "ROUND_ROBIN",
    "userId": 12345,
    "teamId": 123,
    "team": { "id": 123, "name": "ACME Team", ... },
    "users": [ { "id": 12345, "email": "thomas@..." }, ... ],
    "hosts": [ /* LEER in den meisten Fällen */ ],
    "metadata": { ... }
  }
}
```

---

### 2.2 Team Event Type Mapping (WORKAROUND)

#### Model: `TeamEventTypeMapping` ✅ VORHANDEN

**Datei**: `/var/www/api-gateway/app/Models/TeamEventTypeMapping.php`

**Wichtiges Feld**:
```php
protected $fillable = [
    'calcom_event_type_id',  // Event Type ID
    'event_type_name',       // z.B. "Beratungsgespräch"
    'hosts',                 // ← ARRAY mit verfügbaren Hosts!
    'is_team_event',         // Boolean
    // ...
];

protected $casts = [
    'hosts' => 'array',  // Stored as JSON
];
```

**Struktur des `hosts` JSON Arrays**:
```json
[
  {
    "userId": 12345,
    "name": "Thomas Müller",
    "email": "thomas@example.com",
    "username": "thomas_mueller",
    "avatarUrl": "..."
  },
  {
    "userId": 12346,
    "name": "Sara Schmidt",
    "email": "sara@example.com",
    "username": "sara_schmidt",
    "avatarUrl": "..."
  }
]
```

---

### 2.3 Wie wird `TeamEventTypeMapping.hosts` gefüllt?

#### Import Process: `importTeamEventTypes()` ✅ IMPLEMENTIERT

**Location**: `CalcomV2Service::importTeamEventTypes()`  
**File**: `/var/www/api-gateway/app/Services/CalcomV2Service.php:211-342`

**Workflow**:
```
1. API Abruf: GET /v2/event-types?teamId=123
   (Gibt alle Event Types des Teams zurück)

2. Per Event Type Auslesen:
   - title, slug, duration, locations, metadata
   - **ABER NICHT**: Hosts direkt von dieser API

3. Hosts kommen aus separat gespeicherten Daten:
   - Entweder aus Cal.com Team Members Sync
   - Oder aus Event Type V1 API mit full details
   - Oder aus TeamEventTypeMapping.hosts (wenn bereits gespeichert)
```

**Trigger Points**:
```php
// Job wird aufgerufen via:
ImportTeamEventTypesJob::dispatch($company, $syncMembers = true);

// Wird eingeplant wenn:
- Company.calcom_team_id wird gesetzt
- Manual via artisan command
- Filament UI Action
```

---

## 3. AKTUELLE CALCOMHOSTMAPPING

### 3.1 Model Struktur

**Datei**: `/var/www/api-gateway/app/Models/CalcomHostMapping.php`

**Zweck**: Mappt Cal.com Host IDs zu lokalen Staff IDs

**Felder**:
```php
$fillable = [
    'company_id',              // Tenant isolation
    'staff_id',                // Lokale Staff UUID
    'calcom_host_id',          // Cal.com User/Host ID
    'calcom_name',             // Speichert Host Namen
    'calcom_email',            // Host Email
    'calcom_username',         // Host Username
    'calcom_timezone',         // Host Timezone
    'mapping_source',          // 'auto_email', 'auto_name', 'manual'
    'confidence_score',        // 0-100, Matching Confidence
    'last_synced_at',          // Timestamp
    'is_active',               // Boolean
    'metadata',                // Additional data as JSON
];
```

### 3.2 Confidence Score Berechnung

**Location**: `CalcomHostMappingService`  
**File**: `/var/www/api-gateway/app/Services/CalcomHostMappingService.php`

**Matching Strategies**:

```php
protected array $strategies = [
    EmailMatchingStrategy,      // Priority 100
    NameMatchingStrategy,       // Priority 50
];
```

**Email Strategy**:
- **Score**: 95 (exakt matching)
- **Logik**: Local Staff.email == Cal.com Host.email
- **Fallback**: Fuzzymatch + domain validation

**Name Strategy**:
- **Score**: 75 (fuzzy matching)
- **Logik**: Levenshtein distance oder ähnliche Namen
- **Threshold**: 75% Confidence required

**Auto-Threshold Config**:
```php
$autoThreshold = config('booking.staff_matching.auto_threshold', 75);
// Falls > 75%, wird automatisch gemappt und gespeichert
```

### 3.3 Host Name Speicherung

**JA** ✅ - Wird gespeichert in 3 Formaten:

```
1. CalcomHostMapping.calcom_name
   → "Thomas Müller" (String)

2. CalcomTeamMember.name
   → "Thomas Müller" (String)

3. TeamEventTypeMapping.hosts (Array)
   → [
       {
         "name": "Thomas Müller",
         "email": "thomas@...",
         "userId": 12345,
         ...
       }
     ]
```

---

## 4. SERVICE INTEGRATION

### 4.1 Wo werden Cal.com Hosts synchronisiert?

#### Entry Points:

**1. Command: `calcom:sync-team-members`**
```
app/Console/Commands/SyncCalcomTeamMembers.php
→ CalcomV2Service::fetchTeamMembers()
→ CalcomTeamMember::updateOrCreate()
→ CalcomHostMappingService::linkStaffToTeamMember()
```

**2. Job: `ImportTeamEventTypesJob`**
```
app/Jobs/ImportTeamEventTypesJob.php
→ CalcomV2Service::importTeamEventTypes()
→ CalcomV2Service::syncTeamMembers()
→ TeamEventTypeMapping::create/update mit hosts Array
```

**3. Manual via Filament UI**
```
CompanyResource → Action "Sync Team Members"
→ Dispatch ImportTeamEventTypesJob
```

### 4.2 Sync Jobs Übersicht

| Job | Purpose | Queue | Retry |
|-----|---------|-------|-------|
| `ImportTeamEventTypesJob` | Importiere Event Types von Cal.com Team | calcom-sync | 3x |
| `SyncAppointmentToCalcomJob` | Sync Appointment zu Cal.com | default | 3x |
| `UpdateCalcomEventTypeJob` | Update Event Type Details | calcom-sync | 3x |

**Timing**:
- ImportTeamEventTypesJob: Bei Company Save + Manual
- SyncAppointmentToCalcomJob: Nach Appointment Create/Update
- UpdateCalcomEventTypeJob: Bei Service Edit

---

## 5. VERFÜGBARKEITS-LOGIK

### 5.1 WeeklyAvailabilityService

**Datei**: `/var/www/api-gateway/app/Services/Appointments/WeeklyAvailabilityService.php`

**Funktion**: Ruft verfügbare Slots pro Service pro Woche ab

```php
public function getWeekAvailability(string $serviceId, Carbon $weekStart): array
{
    $service = Service::with('company')->findOrFail($serviceId);
    
    // KRITISCH: Benötigt:
    // 1. Service.calcom_event_type_id
    // 2. Company.calcom_team_id
    
    $response = $this->calcomService->getAvailableSlots(
        eventTypeId: $service->calcom_event_type_id,
        startDate: $weekStart->format('Y-m-d'),
        endDate: $weekEnd->format('Y-m-d'),
        teamId: $teamId  // ← Team Context
    );
    
    // Rückgabe:
    return [
        'monday' => [
            ['time' => '09:00', 'full_datetime' => '2025-10-14T09:00:00+02:00'],
            ['time' => '09:30', 'full_datetime' => '2025-10-14T09:30:00+02:00'],
        ],
        'tuesday' => [...],
        // ...
    ];
}
```

### 5.2 Verfügbarkeits Cache

**Pattern**: `week_availability:{teamId}:{serviceId}:{weekStartDate}`  
**TTL**: 60 Sekunden

```
Cache Key:
week_availability:123:service-uuid:2025-10-21

Double Cache Invalidation:
Layer 1: CalcomService.clearAvailabilityCacheForEventType()
Layer 2: AppointmentAlternativeFinder cache (separate pattern)
```

### 5.3 FEHLER: Host/Staff wird NICHT angezeigt

**Problem**: `getWeekAvailability()` gibt nur Slots zurück - NICHT welcher Host/Staff diese Slots hat

**Code**: In `transformToWeekStructure()` werden nur Zeit-Daten transformiert:

```php
return [
    'time' => $localTime->format('H:i'),           // "09:00"
    'full_datetime' => $localTime->toIso8601String(), // Full ISO
    'date' => $localTime->format('Y-m-d'),
    'day_name' => $localTime->translatedFormat('l'),
    'is_morning' => $localTime->hour < 12,
    'is_afternoon' => ...,
    'is_evening' => ...,
    'hour' => $localTime->hour,
    'minute' => $localTime->minute,
    // ← MISSING: Host/Staff Info!
];
```

---

## 6. SCHLÜSSELERKENNTISSE

### ✅ Was EXISTIERT und funktioniert:

1. **Team Members API**: Vollständig implementiert
   - Abruf via `CalcomV2Service::fetchTeamMembers()`
   - Speicherung in `CalcomTeamMember` Table
   - Auto-Link mit Staff via Email/Name

2. **Host Mapping**: Ausgereiftes System
   - `CalcomHostMapping` mit Confidence Scores
   - Multiple Matching Strategies
   - Audit Trail für alle Änderungen
   - Speichert Host Name + Email + Timezone

3. **Team Event Types**: Importierbar
   - `ImportTeamEventTypesJob` kann alle Event Types importieren
   - `TeamEventTypeMapping` speichert Host-Arrays
   - Host-Information aus Team Member Sync vorhanden

4. **Weekly Availability**: Cachiert und optimiert
   - 60s Cache pro Service pro Woche
   - Timezone Handling (UTC → Europe/Berlin)
   - Double Layer Cache Invalidation

### ❌ Was FEHLT (für volle Host-Anzeige):

1. **Slot-to-Host Mapping**: Cal.com API liefert NICHT
   - Welcher Host/Organizer welche Slots hat
   - Erst ab `scheduling_type: ROUND_ROBIN` sichtbar nach Booking

2. **Event Type Host Details**: Müssen manuell gemappt werden
   - Cal.com API sagt nicht: "Event Type X hat Host Y, Z"
   - Nur beim Round Robin erkennbar nach Booking

3. **Host Availability Indicator**: Nicht implementiert
   - Zeigt an welche Hosts für einen Slot verfügbar sind
   - Würde separate API Calls pro Host brauchen

---

## 7. ANTWORT AUF ORIGINAL FRAGEN

### Frage 1: Ist Team Members API implementiert?
**JA** ✅ - `GET /v2/teams/{teamId}/members`
- Implementiert in: `CalcomV2Service::fetchTeamMembers()`
- Speichert in: `CalcomTeamMember` Model
- Sync Command: `calcom:sync-team-members`

### Frage 2: Wie werden Event Types zu Team Members gemappt?
**Mehrschicht-Ansatz**:
```
Layer 1: Team Members Sync
  ├─ CalcomV2Service::fetchTeamMembers()
  ├─ Speichern in CalcomTeamMember
  └─ Link zu Staff via Email/Name

Layer 2: Event Type Import
  ├─ CalcomV2Service::importTeamEventTypes()
  ├─ Speichern in TeamEventTypeMapping
  └─ hosts Array wird gefüllt aus Team Member Daten

Layer 3: Booking Resolution
  ├─ CalcomHostMappingService::resolveStaffForHost()
  ├─ Matching Strategy: Email > Name
  └─ Confidence Score: 75-100
```

### Frage 3: Können wir User/Host von Event Type abrufen?
**JA, aber mit Einschränkung**:
- ✅ Event Type Details abrufbar: `GET /v2/event-types/{id}`
- ⚠️ ABER: Hosts nicht in Response enthalten
- ✅ WORKAROUND: `TeamEventTypeMapping.hosts` Array verwenden
- ✅ ODER: Bei ROUND_ROBIN nach Booking erkennbar

### Frage 4: Können wir verfügbare Hosts pro Event Type anzeigen?
**NEIN, nicht direkt** ❌

Cal.com API bietet keine Endpoint für "welche Hosts können diesen Event Type buchen"

**WORKAROUND-Optionen**:
```
Option 1: TeamEventTypeMapping.hosts verwenden
  → Zeigt alle Team Member des Event Types
  
Option 2: Verfügbarkeits Check pro Host
  → Für jeden Host: getAvailableSlots() aufrufen
  → Performance: N API Calls statt 1
  
Option 3: Round Robin Analyze
  → Nach mehreren Bookings: Welcher Host wurde am meisten aufgerufen
  → Indirekt erkennbar aus Booking History
```

---

## 8. RECOMMENDATIONS

### Für volle Host-Anzeige pro Service:

**Priorität 1: Nutze existierende Data**
```php
// 1. Service laden
$service = Service::find($serviceId);

// 2. TeamEventTypeMapping laden
$mapping = TeamEventTypeMapping::where(
    'calcom_event_type_id',
    $service->calcom_event_type_id
)->first();

// 3. Hosts auslesen
$hosts = $mapping->hosts ?? [];  // Array von Team Members

// 4. Mit Staff linken
$staffMembers = Staff::whereIn(
    'calcom_user_id',
    array_column($hosts, 'userId')
)->get();
```

**Priorität 2: Verfügbarkeit pro Host**
```php
// Für Round Robin Events:
$eventType = $this->calcomService->getEventType($eventTypeId);

if ($eventType->schedulingType === 'ROUND_ROBIN') {
    // Cal.com verteilt automatisch
    // Nach jedem Booking sichtbar wer angenommen hat
    $booking = $this->calcomService->getBooking($bookingId);
    $assignedHost = $booking->organizer;  // Host der dies angenommen hat
}
```

**Priorität 3: UI Enhancement**
```blade
<!-- Zeige alle verfügbaren Hosts für einen Service -->
@foreach ($service->teamEventTypeMapping->hosts ?? [] as $host)
    <div class="host-card">
        <h4>{{ $host['name'] }}</h4>
        <p>{{ $host['email'] }}</p>
        
        <!-- Optional: Verfügbarkeitsanzeige -->
        @if($host['availability'])
            <span class="badge badge-success">
                Verfügbar {{ $host['availability']['hours'] ?? 'N/A' }}h
            </span>
        @endif
    </div>
@endforeach
```

---

## 9. DATEI-REFERENZEN

| Aspekt | Hauptdatei | Backup |
|--------|-----------|--------|
| Team Members API | `CalcomV2Service.php:112-126` | `CalcomV2Client.php` |
| Team Member Sync | `SyncCalcomTeamMembers.php` | `ImportTeamEventTypesJob.php` |
| Host Mapping | `CalcomHostMapping.php` | `CalcomHostMappingService.php` |
| Event Type Import | `CalcomV2Service.php:211-342` | - |
| Availability | `WeeklyAvailabilityService.php` | `CalcomService.php:182-305` |
| Models | `CalcomTeamMember.php`, `TeamEventTypeMapping.php`, `CalcomEventMap.php` | - |

---

**Analyse erstellt**: 2025-10-21  
**API Version**: Cal.com V2 (mit V1 Fallback)  
**Zuletzt aktualisiert**: 2025-10-21
