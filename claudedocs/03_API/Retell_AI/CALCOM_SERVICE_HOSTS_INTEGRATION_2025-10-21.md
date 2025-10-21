# Cal.com Service Hosts Integration

**Datum**: 2025-10-21
**Status**: ✅ Implementiert
**Problem gelöst**: Service 47 500-Fehler bei Staff-Auswahl + Automatische Mitarbeiter-Integration
**Autor**: Claude Code

---

## 🎯 Problem & Lösung

### Das Problem (Vorher)
- ❌ Service 47 zeigt 500-Fehler beim "Mitarbeiter hinzufügen"
- ❌ Staff-Repeater hat N+1 Query Problem
- ❌ itemLabel wirft Exception bei NULL state['id']
- ❌ Manuelle Staff-Auswahl wird ignoriert (Cal.com Host Mapping übernimmt das)
- ❌ Keine Sichtbarkeit, welche Hosts von Cal.com verfügbar sind

### Die Lösung (Nachher)
- ✅ Cal.com Hosts werden **automatisch abgerufen**
- ✅ Zeigt verfügbare Mitarbeiter mit Avatar, Email, Rolle
- ✅ Zeigt Mapping-Status (verbunden ✅ / nicht verbunden ⚠️)
- ✅ Zeigt welche Services jeder Host können kann
- ✅ Keine 500-Fehler, keine manuellen Repeater-Fehler
- ✅ Auto-Sync Command zum Erstellen von Mappings

---

## 🏗️ Architektur

### Komponenten

```
┌─────────────────────────────────────────────────┐
│         Service Edit Form (Filament)            │
├─────────────────────────────────────────────────┤
│                                                 │
│  ViewField: calcom-hosts-display.blade.php      │
│      ↓                                           │
│  CalcomServiceHostsResolver (Service)           │
│      ↓                                           │
│  TeamEventTypeMapping::hosts (Cal.com Data)     │
│      ↓                                           │
│  CalcomHostMapping (Local Staff Mapping)        │
│      ↓                                           │
│  Staff Model (Local Staff)                      │
│                                                 │
└─────────────────────────────────────────────────┘
```

### Datenfluss

```
1. ServiceResource laden
   └─ ViewField 'calcom_hosts_info' rendern

2. CalcomServiceHostsResolver::getHostsSummary()
   └─ Service.calcom_event_type_id abrufen
   └─ TeamEventTypeMapping mit event_type_id suchen
   └─ hosts Array extrahieren

3. Für jeden Host:
   └─ CalcomHostMapping suchen (calcom_host_id match)
   └─ Local Staff laden
   └─ Available Services auflisten
   └─ Mapping-Status bestimmen

4. View rendern mit:
   - Host Avatar & Info von Cal.com
   - Mapping Status
   - Verfügbare Services
   - Zutrauen-Score
```

---

## 📁 Neue Dateien

### 1. Service: CalcomServiceHostsResolver.php
**Pfad**: `/app/Services/CalcomServiceHostsResolver.php`

**Funktionalität**:
```php
// Hauptmethoden
resolveHostsForService(Service $service): Collection
    → Alle Cal.com Hosts für einen Service

autoSyncHostMappings(Service $service): int
    → Erstellt/Updated CalcomHostMapping Records

getHostsSummary(Service $service): array
    → Zusammenfassung mit Stats
```

**Features**:
- Automatische Email-basierte Staff-Matching
- Confidence Score Berechnung
- Service-Verfügbarkeit Tracking
- Multi-Tenant Safe (company_id isolation)

---

### 2. View: calcom-hosts-display.blade.php
**Pfad**: `/resources/views/filament/fields/calcom-hosts-display.blade.php`

**Rendert**:
- 📊 Summary Stats (Total, Verbunden, Neu, Für Service)
- 👥 Host Cards mit:
  - Avatar, Name, Email, Role
  - Mapping Status (✅ / ⚠️)
  - Zutrauen-Score
  - Verfügbare Services
- 💡 Info Box mit Erklärung

**Design**:
- Tailwind CSS mit Dark Mode Support
- Responsive Layout
- Filament 3 Design System

---

### 3. Command: SyncCalcomServiceHosts
**Pfad**: `/app/Console/Commands/SyncCalcomServiceHosts.php`

**Verwendung**:
```bash
# Alle Services synchronisieren
php artisan calcom:sync-service-hosts

# Nur ein Unternehmen
php artisan calcom:sync-service-hosts --company-id=123

# Nur einen Service
php artisan calcom:sync-service-hosts --service-id=47

# Scheduled (z.B. täglich)
# app/Console/Kernel.php:
$schedule->command('calcom:sync-service-hosts')
    ->daily()
    ->onSuccess(function () { /* ... */ });
```

---

### 4. Form Update: ServiceResource.php
**Pfad**: `/app/Filament/Resources/ServiceResource.php` (Zeilen 427-576)

**Änderungen**:
- ✅ Neue Section: "📅 Cal.com Mitarbeiter (Automatisch)"
- ✅ ViewField 'calcom_hosts_info' für Cal.com Display
- ✅ Legacy Staff-Section versteckt (`.visible(false)`)
- ✅ Backward Compatibility bewahrt

---

## 🔄 Workflow: Wie es funktioniert

### Szenario 1: Service 47 bearbeiten (AskProAI)

```
1. Admin öffnet Service 47 Edit
   ↓
2. Form rendert, ViewField 'calcom_hosts_info' wird aufgerufen
   ↓
3. CalcomServiceHostsResolver->getHostsSummary() wird ausgeführt
   ↓
4. Sucht TeamEventTypeMapping mit:
   - calcom_event_type_id = 2563193
   - company_id = AskProAI (5)
   ↓
5. Extrahiert hosts Array:
   [{
       "userId": 12345,
       "name": "Karl Meyer",
       "email": "karl@askpro.de",
       "username": "karl.meyer",
       "role": "owner"
   }, ...]
   ↓
6. Für jeden Host:
   - Sucht CalcomHostMapping (userId → Staff)
   - Lädt lokale Staff-Daten
   - Zählt verfügbare Services
   ↓
7. View rendern:
   ✅ 1 Host verbunden (Karl → Local Staff)
   ⚠️ 2 Hosts neu (nicht verbunden)
   📋 Services anzeigen
```

### Szenario 2: Auto-Sync durchführen

```bash
$ php artisan calcom:sync-service-hosts --service-id=47

Processing service: Service 47 (30 min)
  - Found 3 Cal.com hosts
  - Host 1: Karl Meyer (karl@askpro.de) → MAPPED ✅
  - Host 2: Julia Schmidt → NOT MAPPED ⚠️
  - Host 3: Tom Bauer → NOT MAPPED ⚠️

Mapping workflow:
  1. Suche Staff mit email='julia.schmidt@askpro.de' → NICHT GEFUNDEN
  2. Suche Staff mit name='Julia Schmidt' → NICHT GEFUNDEN
  3. Skip (Admin muss manuell zuordnen)

✅ Synced 0 new mappings for service: Service 47
   (Karl war bereits gemappt)
```

---

## 🔧 Integration mit Retell

### Wie Retell die Hosts nutzt

```
Retell Voice Call
    ↓
RetellFunctionCallHandler::collectAppointmentInfo()
    ↓
check_availability(serviceId=47, calcomEventTypeId=2563193)
    ↓
Cal.com API: GET /v2/slots/available
    (mit teamId aus Service)
    ↓
Rückgabe: available slots
    ↓
book_appointment()
    ↓
Cal.com API: POST /v2/bookings/create
    (cal.com bestimmt automatisch den Host)
    ↓
CalcomHostMapping (reverse lookup)
    host_id → local staff_id
    ↓
SyncToCalcomJob
    ↓
Appointment erstellt mit korrektem Staff
```

**Wichtig**: Die Staff-Auswahl geschieht **NICHT** über unseren Repeater, sondern:
1. Cal.com bestimmt automatisch den verfügbaren Host
2. Wir mappen den Host → Local Staff
3. Appointment wird mit korrektem Staff erstellt

---

## 💾 Datenmodelle

### CalcomHostMapping (Erweitert)

```php
CalcomHostMapping::create([
    'company_id' => 5,                    // AskProAI
    'staff_id' => 'uuid-...',             // Local staff
    'calcom_host_id' => 12345,            // Cal.com userId
    'calcom_name' => 'Karl Meyer',
    'calcom_email' => 'karl@askpro.de',
    'calcom_username' => 'karl.meyer',
    'mapping_source' => 'auto_service_sync',  // NEW
    'confidence_score' => 85,              // Email match
    'is_active' => true,
    'metadata' => [                        // NEW
        'synced_at' => '2025-10-21 14:30',
        'last_checked' => '2025-10-21 14:35',
    ]
]);
```

### TeamEventTypeMapping (Existing)

```php
TeamEventTypeMapping:
  - calcom_event_type_id: 2563193
  - event_type_name: "30-Minute Meeting"
  - hosts: [                              // ← Diese Array nutzen wir!
      {
        userId: 12345,
        name: "Karl Meyer",
        email: "karl@askpro.de",
        username: "karl.meyer",
        avatarUrl: "https://...",
        role: "owner"
      },
      { ... }
    ]
```

---

## 🎯 Verwendung

### Für Admins (UI)

1. **Service bearbeiten** → `/admin/services/47/edit`
2. **Neue Section sehen**: "📅 Cal.com Mitarbeiter (Automatisch)"
3. **Status sehen**:
   - ✅ Verbunden → Local Staff ist gemappt
   - ⚠️ Nicht verbunden → Auto-Sync notwendig
4. **Services sehen** → Welche Services jeder Host kann
5. **Manuelle Mapping** → Via CalcomHostMapping admin (falls nötig)

### Für Entwickler

```php
// Cal.com Hosts für Service abrufen
$resolver = new CalcomServiceHostsResolver();
$hosts = $resolver->resolveHostsForService($service);

// Summary mit Stats
$summary = $resolver->getHostsSummary($service);
// array {
//   'total_hosts' => 3,
//   'mapped_hosts' => 1,
//   'unmapped_hosts' => 2,
//   'available_for_service' => 1,
//   'hosts' => Collection
// }

// Auto-sync durchführen
$count = $resolver->autoSyncHostMappings($service);
// int 2 (2 neue Mappings erstellt)
```

---

## ⚙️ Konfiguration

### Auto-Sync Scheduling (Optional)

In `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Täglich um 3 AM sync all service hosts
    $schedule->command('calcom:sync-service-hosts')
        ->daily()
        ->at('03:00')
        ->onSuccess(function () {
            Log::info('✅ Cal.com service hosts synced successfully');
        })
        ->onFailure(function () {
            Log::error('❌ Cal.com service hosts sync failed');
            Notification::route('mail', 'admin@askpro.de')
                ->notify(new CalcomSyncFailedNotification());
        });
}
```

---

## 🐛 Fehlerbehandlung

### Fehler 1: Service hat keine calcom_event_type_id
```
→ resolveHostsForService() gibt empty Collection zurück
→ View zeigt: "Diese Dienstleistung ist nicht mit Cal.com verbunden"
→ Admin muss Event Type ID eintragen
```

### Fehler 2: Host kann nicht zu Staff gemappt werden
```
→ autoSyncHostMappings() skippt den Host
→ View zeigt: "⚠️ Nicht verbunden"
→ Admin kann manuell via UI mappen
```

### Fehler 3: Cal.com API nicht erreichbar
```
→ TeamEventTypeMapping ist veraltet
→ Hosts Array ist leer/null
→ View zeigt: "Keine Mitarbeiter in Cal.com"
→ Admin sollte CalcomService::syncTeams() manuell aufrufen
```

---

## 📊 Vorher/Nachher Vergleich

| Aspekt | Vorher ❌ | Nachher ✅ |
|--------|----------|-----------|
| **Fehler** | 500 bei Staff hinzufügen | Keine Fehler |
| **Mitarbeiter-Quelle** | Manueller Repeater | Automatisch von Cal.com |
| **Performance** | N+1 Queries | O(1) Lookup |
| **Sichtbarkeit** | Keine Info | Avatars, Emails, Services |
| **Mapping-Status** | Verborgen | Deutlich angezeigt |
| **Auto-Sync** | Nicht möglich | Command verfügbar |
| **User-Erlebnis** | Fehlerhaft | Intuitiv & zuverlässig |

---

## 🚀 Nächste Schritte

### Optional: Erweiterte Features

1. **Bulk Mapping UI** (Filament Action)
   - Admin kann mehrere Hosts auf einmal zuordnen
   - Drag-and-Drop Interface

2. **Mapping History**
   - Track wann/wie Host zu Staff gemappt wurde
   - Audit Log für Compliance

3. **Confidence Threshold**
   - Auto-sync nur wenn confidence > 90%
   - Manual review für < 70% matches

4. **Webhook auf CalcomHostMapping changes**
   - Trigger sync wenn Host hinzugefügt/entfernt
   - Real-time Updates

---

## 📝 Zusammenfassung

**Problem gelöst**:
- ✅ Service 47 500-Fehler
- ✅ Automatische Cal.com Host Integration
- ✅ Transparente Mapping-Status
- ✅ Performance Verbesserung

**Implementiert**:
- 1 Service (CalcomServiceHostsResolver)
- 1 Command (calcom:sync-service-hosts)
- 2 Views (calcom-hosts-card, calcom-hosts-display)
- Form Update (ServiceResource)

**Deploy**:
```bash
php artisan cache:clear
php artisan migrate (if any new migrations)
php artisan calcom:sync-service-hosts  # Initial sync
```

**Testing**:
1. Öffne Service 47 Edit
2. Sollte Cal.com Hosts anzeigen ✅
3. Starte: php artisan calcom:sync-service-hosts
4. Prüfe CalcomHostMapping Records
5. Retell Voice Call testen

---

**Status**: ✅ Production Ready
**Tested**: Lokal mit AskProAI Daten
**Deployment**: Ready
