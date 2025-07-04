# Services UI Fixes - Zusammenfassung

## Behobene Probleme

### 1. SQL Fehler: "Column 'price' cannot be null"
**Problem**: Beim Speichern kam ein SQL-Fehler, weil der Preis NULL war.

**Lösung**:
- Preis hat jetzt Default-Wert `0`
- Wird immer als `floatval()` gespeichert
- Klarer Hinweis: "0 = Kostenlos (z.B. für Beratungsgespräche)"

### 2. Toggle "Online buchbar" Verwirrung
**Problem**: Der Toggle war unklar - "Online buchbar" mit Hilfetext "Kann diese Dienstleistung telefonisch gebucht werden?"

**Lösung**:
- Label geändert zu: **"Per Telefon buchbar"**
- Klarere Hilfe: "JA = KI kann diese Dienstleistung am Telefon buchen | NEIN = Nur manuelle Buchung möglich"
- Farben: Grün (AN) = Buchbar, Rot (AUS) = Nicht buchbar

### 3. Automatische Datenübernahme von Event Types
**Problem**: Dauer und Beschreibung wurden nicht von Event Types übernommen.

**Lösung**:
- Bei Auswahl eines Event Types werden automatisch übernommen:
  - **Dauer** (duration_minutes)
  - **Beschreibung** (falls noch keine vorhanden)
- Beim Import von Event Types:
  - Dauer wird übernommen (Default: 30 Min)
  - Beschreibung wird übernommen
  - Preis aus Metadata (falls vorhanden, sonst 0)

### 4. Erweiterte Info-Box
Neue Erklärungen hinzugefügt:
- "Kostenlose Services = Preis auf 0€ setzen"
- "Dauer & Beschreibung = Werden automatisch vom Event Type übernommen"
- "Per Telefon buchbar = JA bedeutet, die KI kann diese Dienstleistung anbieten"

## Technische Details

### Preis-Handling
```php
'price' => floatval($serviceData['price'] ?? 0)
```

### Reaktive Event Type Auswahl
```php
->afterStateUpdated(function ($state, $set, $get) {
    if ($state) {
        $eventType = CalcomEventType::find($state);
        if ($eventType) {
            $set('duration_minutes', $eventType->duration_minutes ?? 30);
            if ($eventType->description && !$get('ai_description')) {
                $set('ai_description', $eventType->description);
            }
        }
    }
})
```

### Database Default
```sql
price decimal(10,2) NOT NULL DEFAULT '0.00'
```

## UI Verbesserungen
- Preis-Feld mit Step 0.01 für Cent-Beträge
- Minimum-Wert 0 (keine negativen Preise)
- Placeholder zeigt "0.00" für Klarheit
- Branch-ID wird automatisch gesetzt beim Speichern