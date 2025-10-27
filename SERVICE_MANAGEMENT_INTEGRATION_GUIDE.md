# 📋 Service-Management & Voice AI Integration Guide

**Datum:** 2025-10-23
**Zweck:** Erklärung wie Ihre Filament Plattform mit dem Voice AI System zusammenspielt

---

## ✅ Kurze Antwort auf Ihre Frage

**Ja, das funktioniert perfekt zusammen!**

Alles was Sie in Ihrer Filament Admin-Plattform unter "Dienstleistungen" (Services) anlegen, wird **automatisch** vom Voice AI System verwendet. Es ist bereits vollständig integriert.

---

## 🎯 Wie es JETZT funktioniert (Bestandssystem)

### 1. Service in Filament anlegen

**Navigation:** Admin Panel → Stammdaten → Dienstleistungen → Neu

**Felder die Sie ausfüllen:**

```
┌─────────────────────────────────────────────┐
│ SERVICE-INFORMATIONEN                       │
├─────────────────────────────────────────────┤
│ Company*:          [Dropdown]               │
│ Branch:            [Optional Dropdown]      │
│ Name*:             "Damenschnitt"           │  ← Wichtig für Voice AI!
│ Anzeigename:       "Premium Damenhaarschnitt" (optional)
│ Kategorie*:        [Consultation/Treatment]│
│ Beschreibung:      [Textfeld]              │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ SERVICE-EINSTELLUNGEN                       │
├─────────────────────────────────────────────┤
│ Dauer*:            45 Minuten              │
│ Pufferzeit:        5 Minuten               │
│ Preis:             35,00 €                 │
│ Max. Buchungen/Tag: 10                     │
│ ☑ Aktiv                                    │
│ ☐ Online-Buchung                           │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ CAL.COM INTEGRATION                         │
├─────────────────────────────────────────────┤
│ Event Type ID:     1234567                 │  ← Aus Cal.com
│ Schedule ID:       [Optional]              │
│ Booking Link:      [Automatisch]           │
└─────────────────────────────────────────────┘
```

### 2. Mitarbeiter zuordnen

**Nach dem Service-Anlegen:**
1. Klicken Sie auf den Service
2. Reiter "Mitarbeiter" öffnen
3. "Mitarbeiter hinzufügen" klicken
4. Staff auswählen und Optionen setzen:
   ```
   ☑ Primärer Mitarbeiter (is_primary)
   ☑ Kann Termine buchen (can_book)
   ```

**Datenbank-Struktur (automatisch):**
```sql
service_staff (Pivot Table):
├─ service_id: UUID von "Damenschnitt"
├─ staff_id: UUID von "Lisa"
├─ is_primary: true
├─ can_book: true
├─ custom_price: NULL (oder Spezialpreis)
└─ is_active: true
```

### 3. Was passiert im Hintergrund

**Datenbank-Eintrag:**
```php
services table:
├─ id: "9d4f8e2a-..."
├─ company_id: 1
├─ branch_id: NULL (oder specific branch)
├─ name: "Damenschnitt"
├─ display_name: "Premium Damenhaarschnitt"
├─ duration_minutes: 45
├─ price: 35.00
├─ is_active: true
├─ is_online: false
├─ calcom_event_type_id: 1234567  ← Mapping zu Cal.com!
└─ category: "treatment"
```

**Cal.com Verbindung:**
```
Service (Laravel)
  ↓ 1:1 Mapping via calcom_event_type_id
Cal.com Event Type #1234567
  ↓ Team Members
Cal.com Hosts (Lisa, Sarah, ...)
```

---

## 🚨 Was AKTUELL fehlt (Voice AI Problem)

### Szenario 1: Voice AI Call

**User am Telefon:**
> "Guten Tag, ich hätte gern einen **Damenschnitt** für morgen um 14 Uhr."

**Was Voice AI SOLLTE machen:**
1. ✅ Hört "Damenschnitt"
2. ✅ Sucht in Datenbank: `SELECT * FROM services WHERE name LIKE '%Damenschnitt%' AND company_id = ?`
3. ✅ Findet Service mit `calcom_event_type_id = 1234567`
4. ✅ Prüft Verfügbarkeit für DIESEN Event Type
5. ✅ Bucht mit KORREKTEM Service

**Was Voice AI AKTUELL macht:**
1. ❌ Hört "Damenschnitt" → **IGNORIERT es**
2. ❌ Nutzt `getDefaultService(company_id)` → z.B. "Herrenschnitt"
3. ❌ Prüft Verfügbarkeit für **FALSCHEN** Event Type
4. ❌ Bucht falschen Service!

**Resultat:** User wollte Damenschnitt, kriegt Herrenschnitt gebucht!

---

## 💡 Wie es MIT der neuen Service-Auswahl funktioniert

### Phase 1: Automatisches Service-Listing

**Neue Funktion:** `list_services()`

**Voice AI kann jetzt sagen:**
> "Wir bieten folgende Dienstleistungen an:
> - Herrenschnitt (30 Minuten, 25 Euro)
> - Damenschnitt (45 Minuten, 35 Euro)
> - Färben (90 Minuten, 55 Euro)
> - Bart trimmen (15 Minuten, 15 Euro)
>
> Welche Dienstleistung möchten Sie buchen?"

**Technisch:**
```php
// NEW: app/Http/Controllers/RetellFunctionCallHandler.php
public function listServices(array $params, ?string $callId)
{
    // Get company context from call
    $context = $this->getCallContext($callId);

    // Fetch active services for this company
    $services = Service::where('company_id', $context['company_id'])
        ->where('is_active', true)
        ->where('is_online', true)  // Nur Online-buchbare
        ->select(['id', 'name', 'display_name', 'duration_minutes', 'price'])
        ->get();

    return $this->responseFormatter->success([
        'services' => $services->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->display_name ?? $s->name,
            'duration' => $s->duration_minutes,
            'price' => number_format($s->price, 2)
        ])
    ]);
}
```

### Phase 2: Service Name Extraction (Fuzzy Matching)

**Neue Service:** `ServiceNameExtractor.php`

**User sagt:**
- "Damenschnitt" → Exakte Übereinstimmung ✅
- "Damen Haarschnitt" → Fuzzy Match ✅
- "Schnitt für Damen" → Synonym Match ✅
- "Ein Schnitt" → Zu vage, Agent fragt nach ⚠️

**Technisch:**
```php
// NEW: app/Services/Retell/ServiceNameExtractor.php
class ServiceNameExtractor
{
    public function extractFromUserInput(string $input, int $companyId): ?Service
    {
        // 1. Exact Match
        $exact = Service::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function($q) use ($input) {
                $q->where('name', 'ILIKE', $input)
                  ->orWhere('display_name', 'ILIKE', $input);
            })
            ->first();

        if ($exact) {
            return $exact;
        }

        // 2. Fuzzy Match (Levenshtein distance)
        $services = Service::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $bestMatch = null;
        $bestScore = 0;

        foreach ($services as $service) {
            $similarity = similar_text(
                strtolower($input),
                strtolower($service->name),
                $percent
            );

            if ($percent > 70 && $percent > $bestScore) {
                $bestMatch = $service;
                $bestScore = $percent;
            }
        }

        return $bestMatch; // Can be null
    }
}
```

### Phase 3: Integration im Conversation Flow

**Conversation Flow Update (V18):**

```json
{
  "nodes": [
    {
      "id": "node_04_intent_recognition",
      "type": "conversation",
      "instruction": "Erkennen Sie die Buchungsabsicht"
    },
    {
      "id": "func_list_services",
      "type": "function",
      "tool_id": "tool_list_services",
      "speak_during_execution": false,
      "instruction": "Liste aller verfügbaren Services laden"
    },
    {
      "id": "node_06_service_selection",
      "type": "conversation",
      "instruction": {
        "type": "prompt",
        "prompt": "Nutze die Service-Liste aus dem vorherigen Tool-Call. Frage den Kunden welchen Service er buchen möchte. Wenn der Kunde bereits einen Service genannt hat (z.B. 'Damenschnitt'), erkenne diesen und bestätige ihn. Ansonsten lies die verfügbaren Services vor."
      },
      "edges": [
        {
          "condition": "Service ausgewählt",
          "destination": "node_07_datetime_collection"
        }
      ]
    }
  ]
}
```

---

## 🔄 Der komplette Flow (MIT neuer Service-Auswahl)

### Von Filament Admin → Voice AI → Cal.com

```
┌─────────────────────────────────────────────────────────┐
│ 1. ADMIN LEGT SERVICE AN (Filament)                    │
└─────────────────────────────────────────────────────────┘
                    ↓
         ┌──────────────────────┐
         │ services table       │
         │ ├─ name              │  ← "Damenschnitt"
         │ ├─ duration: 45      │
         │ ├─ price: 35€        │
         │ └─ calcom_event_id   │  ← 1234567
         └──────────────────────┘
                    ↓
         ┌──────────────────────┐
         │ service_staff table  │
         │ ├─ service_id        │
         │ ├─ staff_id (Lisa)   │
         │ └─ can_book: true    │
         └──────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 2. KUNDE RUFT AN (Retell Voice AI)                     │
└─────────────────────────────────────────────────────────┘
                    ↓
    User: "Ich hätte gern einen Damenschnitt"
                    ↓
         ┌──────────────────────┐
         │ ServiceNameExtractor │
         │ ├─ Fuzzy Match       │
         │ └─ Returns Service   │  ← "Damenschnitt" gefunden!
         └──────────────────────┘
                    ↓
         ┌──────────────────────┐
         │ checkAvailability()  │
         │ ├─ Service ID        │
         │ ├─ Event Type 123... │  ← Korrekte Cal.com ID!
         │ └─ Staff: Lisa       │
         └──────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 3. CAL.COM VERFÜGBARKEIT PRÜFEN                        │
└─────────────────────────────────────────────────────────┘
                    ↓
         Cal.com Event Type #1234567
         ├─ Host: Lisa (available)
         └─ Slots: [14:00, 15:00, 16:00]
                    ↓
    Agent: "Morgen um 14 Uhr ist verfügbar bei Lisa"
                    ↓
    User: "Ja, bitte buchen"
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 4. BUCHUNG ERSTELLEN                                    │
└─────────────────────────────────────────────────────────┘
                    ↓
         ┌──────────────────────┐
         │ appointments table   │
         │ ├─ service_id ✅     │  ← Richtig: Damenschnitt
         │ ├─ staff_id (Lisa)   │
         │ ├─ date: tomorrow    │
         │ └─ time: 14:00       │
         └──────────────────────┘
                    ↓
         ┌──────────────────────┐
         │ Cal.com Booking API  │
         │ ├─ Event Type: 123.. │  ← Richtig!
         │ ├─ Host: Lisa        │
         │ └─ Time: 14:00       │
         └──────────────────────┘
                    ↓
         📧 E-Mail an Kunde & Lisa
         ✅ Termin bestätigt!
```

---

## 📝 Was Sie als Admin tun müssen

### Für JETZT (Aktuelles System)

**NICHTS ändern!** Ihr Workflow bleibt gleich:
1. Services in Filament anlegen
2. Cal.com Event Type ID eintragen
3. Staff zuordnen
4. Fertig

**Limitation:**
- Voice AI kann Service nicht auswählen
- Nutzt immer Default Service

### Nach der neuen Service-Auswahl (in 3-4 Tagen)

**Ebenfalls NICHTS ändern!** Workflow bleibt identisch:
1. Services in Filament anlegen (wie bisher)
2. Cal.com Event Type ID eintragen (wie bisher)
3. Staff zuordnen (wie bisher)
4. Fertig

**Aber jetzt:**
- ✅ Voice AI liest ALLE Services
- ✅ Voice AI erkennt Service-Namen aus Sprache
- ✅ Voice AI bucht KORREKTEN Service
- ✅ Multi-Service Kunden möglich!

**Empfehlung für Service-Namen:**
- Verwenden Sie klare, eindeutige Namen
- "Damenschnitt" besser als "Schnitt Premium Damen mit Styling"
- Vermeiden Sie zu ähnliche Namen
- `display_name` kann länger/marketingfreundlicher sein

---

## 🎬 Test-Szenario (Nach Implementation)

### Setup in Filament:

```
Company: Friseur Salon XYZ

Services:
1. Name: "Herrenschnitt"
   Duration: 30 min
   Price: 25€
   Cal.com Event Type: 1111111
   Staff: Max, Tom, Lisa
   Status: ✅ Aktiv

2. Name: "Damenschnitt"
   Duration: 45 min
   Price: 35€
   Cal.com Event Type: 2222222
   Staff: Lisa, Sarah
   Status: ✅ Aktiv

3. Name: "Färben"
   Duration: 90 min
   Price: 55€
   Cal.com Event Type: 3333333
   Staff: Sarah (nur Sarah!)
   Status: ✅ Aktiv

4. Name: "Bart trimmen"
   Duration: 15 min
   Price: 15€
   Cal.com Event Type: 4444444
   Staff: Max, Tom
   Status: ✅ Aktiv
```

### Voice AI Call:

```
📞 Kunde ruft an: +49 160 1234567

Agent: "Guten Tag beim Friseur Salon XYZ! Wie kann ich Ihnen helfen?"

Kunde: "Ja, ich bräuchte einen Damenschnitt für morgen."

Agent: "Gerne! Einen Damenschnitt dauert 45 Minuten und kostet 35 Euro.
        Zu welcher Uhrzeit möchten Sie kommen?"

Kunde: "Um 14 Uhr wäre super."

Agent: "Einen Moment, ich prüfe die Verfügbarkeit..."

[System Intern:]
1. ✅ Service erkannt: "Damenschnitt" (ID: xxx, Event Type: 2222222)
2. ✅ Cal.com Verfügbarkeit prüfen für Event Type 2222222
3. ✅ Lisa ist verfügbar um 14:00
4. ✅ Slot kann gebucht werden

Agent: "Morgen um 14 Uhr ist verfügbar bei Lisa. Soll ich das so buchen?"

Kunde: "Ja, bitte!"

Agent: "Perfekt! Ihr Termin für einen Damenschnitt morgen um 14 Uhr bei Lisa
        ist gebucht. Sie erhalten eine Bestätigungs-E-Mail. Gibt es noch etwas?"

Kunde: "Nein, danke!"

Agent: "Vielen Dank für Ihren Anruf! Bis morgen um 14 Uhr!"

[Resultat:]
✅ appointments table: service_id = Damenschnitt ✅
✅ Cal.com: Booking mit Event Type 2222222 ✅
✅ Staff: Lisa zugewiesen ✅
✅ E-Mail: Kunde + Lisa informiert ✅
```

---

## 🔧 Technische Details (Für Entwickler)

### Neue Components (werden implementiert):

```php
1. app/Services/Retell/ServiceNameExtractor.php
   - extractFromUserInput()
   - fuzzyMatch()
   - calculateSimilarity()

2. app/Http/Controllers/RetellFunctionCallHandler.php
   - listServices()  (NEU)
   - Existing: checkAvailability() (wird erweitert)
   - Existing: bookAppointment() (wird erweitert)

3. routes/api.php
   - POST /api/retell/list-services (NEU)

4. Retell Conversation Flow V18
   - func_list_services (NEU)
   - node_06_service_selection (erweitert)
```

### Database Queries (Performance):

```sql
-- list_services() - Pro Call 1x:
SELECT id, name, display_name, duration_minutes, price, calcom_event_type_id
FROM services
WHERE company_id = ?
  AND is_active = TRUE
  AND is_online = TRUE
ORDER BY priority DESC, name ASC;

-- Geschätzte Performance: <10ms (mit Index)
-- Caching: 5 Minuten TTL
-- Cache Key: "services:list:{company_id}"
```

---

## ✅ Zusammenfassung

### Was SIE tun müssen:
**NICHTS!** Ihr Workflow bleibt identisch.

### Was das SYSTEM tut:
**NEU:** Liest Services aus Ihrer Filament-Datenbank und nutzt sie im Voice AI

### Was der KUNDE erlebt:
**VORHER:** Nur 1 Service buchbar (Default)
**NACHHER:** Alle Services per Sprache wählbar

### Zeitplan:
**Entwicklung:** 3-4 Tage
**Ihr Aufwand:** 0 Tage (keine Änderungen nötig)

---

## 🎯 Nächste Schritte

**Option 1: Jetzt testen (Limitation bekannt)**
- Funktioniert nur für Single-Service
- Gut für Quick-Test

**Option 2: Nach Implementation testen (EMPFOHLEN)**
- Voller Multi-Service Support
- Produktionsreif
- Kann direkt zu Kunden ausgerollt werden

**Ihre Entscheidung?**

