# 📞 Telefonnummer → Termin: Der komplette Datenfluss

## 🎯 Die zentrale Frage von gestern: "Woher weiß das System, wo gebucht werden soll?"

### 🔄 Schritt-für-Schritt Erklärung

## 1️⃣ Kunde ruft an

```
Kunde wählt: +49 30 837 93 369
                    ↓
```

## 2️⃣ Retell.ai empfängt den Anruf

```json
{
  "call_id": "call_abc123",
  "from_number": "+49 151 12345678",  // Kunde
  "to_number": "+49 30 837 93 369",   // DIESE NUMMER IST DER SCHLÜSSEL!
  "agent_id": "agent_9a8202a740cd3120d96fcfda1e"
}
```

## 3️⃣ Webhook an AskProAI

```
POST https://askproai.de/api/retell/webhook
                    ↓
```

## 4️⃣ PhoneNumberResolver findet die Filiale

```php
// app/Services/PhoneNumberResolver.php

$phoneNumber = "+49 30 837 93 369";

// Suche in 3 Stellen:
// 1. phone_numbers Tabelle
$result = DB::table('phone_numbers')
    ->where('number', $phoneNumber)
    ->first();
// Ergebnis: branch_id = "7362c5a9-7d2b-46cd-9bcb-d69f6a60c73b"

// 2. branches Tabelle (Fallback)
$branch = Branch::where('phone_number', $phoneNumber)->first();
// Ergebnis: "AskProAI – Berlin"

// 3. Über Retell Agent ID (wenn vorhanden)
$branch = Branch::where('retell_agent_id', $agent_id)->first();
```

## 5️⃣ Branch gefunden → Company & Settings laden

```yaml
Branch: "AskProAI – Berlin"
  ├── company_id: 85 (AskProAI)
  ├── calcom_event_type_id: 2026361
  ├── retell_agent_id: "agent_9a8202a740cd3120d96fcfda1e"
  └── is_active: true
```

## 6️⃣ Cal.com Event Type bestimmen

```php
// Priorität der Event Type Auswahl:
1. Staff-spezifisch (wenn Kunde bestimmten Mitarbeiter will)
2. Branch Default (calcom_event_type_id: 2026361)
3. Company Default (falls Branch keinen hat)

// Beispiel:
$eventType = CalcomEventType::find(2026361);
// "AskProAI 30% mehr Umsatz durch KI"
```

## 7️⃣ Verfügbarkeit prüfen & Termin buchen

```php
// Cal.com API Aufruf
$availability = $calcomService->getAvailability([
    'eventTypeId' => 2026361,
    'dateFrom' => '2025-06-18',
    'dateTo' => '2025-06-25'
]);

// Termin buchen
$booking = $calcomService->createBooking([
    'eventTypeId' => 2026361,
    'start' => '2025-06-19T14:00:00',
    'name' => 'Max Mustermann',
    'email' => 'max@example.com',
    'phone' => '+49 151 12345678'
]);
```

## 8️⃣ Bestätigung an Kunde

```
- KI bestätigt mündlich während des Anrufs
- Email-Bestätigung wird gesendet
- Termin erscheint in Cal.com
- Termin erscheint im AskProAI Dashboard
```

---

## 🔧 Was muss konfiguriert sein?

### 1. Telefonnummer zuordnen
```sql
INSERT INTO phone_numbers (number, branch_id, type) VALUES
('+49 30 837 93 369', '7362c5a9-7d2b-46cd-9bcb-d69f6a60c73b', 'main');
```

### 2. Branch konfigurieren
```sql
UPDATE branches SET
    retell_agent_id = 'agent_9a8202a740cd3120d96fcfda1e',
    calcom_event_type_id = 2026361,
    is_active = true
WHERE id = '7362c5a9-7d2b-46cd-9bcb-d69f6a60c73b';
```

### 3. Cal.com Event Type importieren
- Admin → Event-Type Import → Import durchführen
- Oder: Manuell Event Type ID eintragen

### 4. Retell.ai Agent konfigurieren
- Bei Retell.ai: Agent erstellen
- Webhook URL: https://askproai.de/api/retell/webhook
- Agent ID in Branch speichern

---

## ⚠️ Häufige Fehlerquellen

1. **Filiale ist inaktiv** → Keine Buchung möglich
2. **Falsche Event Type ID** → Cal.com API Fehler
3. **Keine Telefonnummer-Zuordnung** → System weiß nicht wo buchen
4. **Retell Agent ID fehlt** → Webhook kann nicht verarbeitet werden

---

## ✅ So prüfen Sie die Konfiguration

```bash
# Test-Script ausführen
php check_askproai_berlin.php

# Sollte zeigen:
✓ Filiale aktiv
✓ Telefonnummer zugeordnet
✓ Cal.com Event Type vorhanden
✓ Retell Agent konfiguriert
```

**JETZT VERSTEHEN SIE DEN KOMPLETTEN FLOW! 🎉**