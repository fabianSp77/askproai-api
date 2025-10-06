# Cal.com Event Type Konfiguration für Telefonbuchungen

**Erstellt**: 2025-09-30
**Zweck**: Fehlerfreie Buchungen über Retell AI Telefonagent

---

## 🚨 Kritische Fehler und deren Ursachen

### Fehler 1: "Invalid event length"
**Ursache**: Cal.com erhielt kein `end`-Feld oder falsches Format
**Status**: ✅ **BEHOBEN** durch CalcomService.php Update
**Lösung**:
- `end` wird jetzt automatisch berechnet: `start + duration`
- Duration wird aus Service-Datenbank gelesen
- Default: 60 Minuten

---

### Fehler 2: "fixed_hosts_unavailable_for_booking"
**Ursache**: Event Type ist auf einen FESTEN Host konfiguriert, der nicht verfügbar ist
**Status**: ⚠️ **CAL.COM KONFIGURATION ERFORDERLICH**

**Erforderliche Schritte in Cal.com Dashboard**:

1. **Event Type öffnen** (z.B. ID 2563193)
2. **Hosting-Einstellungen ändern**:
   ```
   Aktuell: "Fixed Host" (einzelner Host zugewiesen)
   ↓
   Ändern zu: "Round Robin" oder "Collective"
   ```

3. **Round Robin Konfiguration** (empfohlen):
   - Verteilt Termine automatisch auf verfügbare Team-Mitglieder
   - Berücksichtigt Working Hours automatisch
   - Maximale Verfügbarkeit

4. **Working Hours prüfen**:
   - Jeder Host muss Working Hours konfiguriert haben
   - Format: Mo-Fr 09:00-18:00 (Beispiel)
   - Zeitzone: Europe/Berlin

---

### Fehler 3: "no_available_users_found_error"
**Ursache**: Keine Hosts zum gewünschten Zeitpunkt verfügbar
**Status**: ⚠️ **CAL.COM KONFIGURATION + CODE VERBESSERUNG**

**Cal.com Konfiguration**:
1. Working Hours für alle Hosts setzen
2. Mindestens 2-3 Hosts zum Event Type zuweisen
3. Buffer Time prüfen (nicht zu lang setzen)

**Code Verbesserung** (bereits implementiert):
- Verfügbarkeitsprüfung VOR Buchungsversuch
- Alternative Zeitslots vorschlagen
- Benutzerfreundliche Fehlermeldung am Telefon

---

## ✅ Event Type Konfiguration Checkliste

### Basis-Einstellungen
- [x] **Event Type Duration**: 30-60 Minuten (je nach Service)
- [x] **Event Title**: Beschreibender Name (z.B. "Beratungsgespräch")
- [x] **Location**: Online/Telefon/Vor Ort
- [x] **Buffer Time**: 5-10 Minuten zwischen Terminen

### Hosting-Einstellungen
- [ ] **Assignment**: Round Robin (NICHT Fixed Host!)
- [ ] **Hosts**: Mindestens 2 Team-Mitglieder zugewiesen
- [ ] **Working Hours**: Für jeden Host konfiguriert
- [ ] **Availability**: Überprüft für kommende Woche

### API-Einstellungen
- [x] **API Access**: Aktiviert
- [x] **Webhooks**: Konfiguriert für BOOKING events
- [x] **Timezone**: Europe/Berlin

### Buchungs-Einstellungen
- [ ] **Minimum Notice**: 1-2 Stunden
- [ ] **Max Bookings per Day**: 8-10 (je nach Kapazität)
- [ ] **Booking Window**: 30-60 Tage in die Zukunft
- [ ] **Confirmation**: Automatisch (kein manuelles Approval)

---

## 🔧 Service-Datenbank Konfiguration

Jeder Service in der Datenbank muss folgende Felder korrekt haben:

```sql
SELECT
    id,
    name,
    duration,                    -- IN MINUTEN! (z.B. 60)
    calcom_event_type_id,        -- Cal.com Event Type ID
    is_active,                   -- TRUE
    requires_confirmation        -- FALSE für Telefon-Buchungen
FROM services
WHERE is_active = TRUE;
```

**Wichtig**:
- `duration` ist in **MINUTEN** (nicht Stunden!)
- `calcom_event_type_id` muss mit Cal.com übereinstimmen

---

## 📊 Datenfluss bei Buchung

```
┌──────────────┐
│   Kunde      │ "Ich möchte am 1. Oktober um 16 Uhr einen Termin"
│  am Telefon  │
└──────┬───────┘
       │
       ↓
┌──────────────────────────────────────────────────────┐
│  Retell AI Agent                                      │
│  - Versteht: Datum (01.10.), Zeit (16:00)           │
│  - Extrahiert: Service-Typ ("Beratung")             │
│  - Sendet Webhook an API Gateway                     │
└──────┬───────────────────────────────────────────────┘
       │
       ↓
┌──────────────────────────────────────────────────────┐
│  API Gateway (RetellFunctionCallHandler)             │
│                                                       │
│  1. Parse Datum/Zeit → 2025-10-01 16:00             │
│  2. Finde Service → "Beratung" (ID 15)              │
│  3. Hole duration → 60 Minuten                       │
│  4. Berechne end → 2025-10-01 17:00                 │
└──────┬───────────────────────────────────────────────┘
       │
       ↓
┌──────────────────────────────────────────────────────┐
│  CalcomService                                        │
│                                                       │
│  Payload:                                            │
│  {                                                   │
│    "eventTypeId": 2563193,                          │
│    "start": "2025-10-01T16:00:00+02:00",           │
│    "end": "2025-10-01T17:00:00+02:00",  ← FIX!     │
│    "timeZone": "Europe/Berlin",                     │
│    "responses": {                                    │
│      "name": "Hans Schulze",                        │
│      "email": "termin@askproai.de",                 │
│      "attendeePhoneNumber": "+491234567890"         │
│    }                                                │
│  }                                                  │
└──────┬───────────────────────────────────────────────┘
       │
       ↓
┌──────────────────────────────────────────────────────┐
│  Cal.com API                                          │
│                                                       │
│  ✅ Prüft Verfügbarkeit                              │
│  ✅ Prüft Host Working Hours                         │
│  ✅ Erstellt Buchung                                 │
│  ✅ Sendet Bestätigung                               │
└──────┬───────────────────────────────────────────────┘
       │
       ↓
┌──────────────────────────────────────────────────────┐
│  Response an Retell AI                                │
│                                                       │
│  Success:                                            │
│  "Perfekt! Ihr Termin am 1. Oktober um 16 Uhr      │
│   ist bestätigt. Sie erhalten eine SMS."            │
│                                                       │
│  Error:                                              │
│  "Zu diesem Zeitpunkt haben wir leider keine        │
│   Verfügbarkeit. Hätten Sie auch zu einem          │
│   anderen Zeitpunkt Zeit?"                          │
└──────────────────────────────────────────────────────┘
```

---

## 🧪 Test-Szenarien

### Test 1: Erfolgreiche Buchung
**Vorbereitung**:
- Host hat Working Hours von 09:00-18:00
- Gewünschter Termin: 14:00 (innerhalb Working Hours)

**Erwartetes Ergebnis**: ✅ Buchung erfolgreich

---

### Test 2: Host nicht verfügbar
**Vorbereitung**:
- Host hat nur 09:00-12:00 Working Hours
- Gewünschter Termin: 14:00 (außerhalb Working Hours)

**Erwartetes Ergebnis**:
- ❌ "no_available_users_found_error"
- Agent bietet Alternative an (z.B. 10:00 am selben Tag)

---

### Test 3: Zeitslot bereits gebucht
**Vorbereitung**:
- Manuell Termin um 14:00 in Cal.com erstellen
- Agent versucht gleichen Slot zu buchen

**Erwartetes Ergebnis**:
- ❌ "fixed_hosts_unavailable_for_booking"
- Agent schlägt 15:00 oder 13:00 vor

---

## 🔍 Debugging-Befehle

### Logs in Echtzeit verfolgen
```bash
tail -f /var/www/api-gateway/storage/logs/calcom-$(date +%Y-%m-%d).log
```

### Letzte Buchungsfehler anzeigen
```bash
grep "Cal.com booking failed" /var/www/api-gateway/storage/logs/laravel.log | tail -5
```

### Service-Konfiguration prüfen
```bash
php artisan tinker
>>> App\Models\Service::where('is_active', true)->get(['id', 'name', 'duration', 'calcom_event_type_id']);
```

### Cal.com Connection testen
```bash
php artisan tinker
>>> $service = new \App\Services\CalcomService();
>>> $service->testConnection();
```

---

## 📞 Support & Kontakt

**Bei Problemen**:
1. Logs prüfen (siehe oben)
2. Cal.com Dashboard Event Types überprüfen
3. Working Hours aller Hosts validieren
4. Bei persistenten Fehlern: Event Type neu erstellen

**Cal.com Dashboard**: https://app.cal.com/event-types
**API Dokumentation**: https://cal.com/docs/api-reference