# Retell Collect-Data Endpoint Update (2025-07-04)

## Änderungen

### Problem
Der `/api/retell/collect-data` Endpoint erwartete zwingend eine `call_id`, die aber von Retell nicht mehr in den Custom Function Calls gesendet wird.

### Lösung
Die `call_id` wurde optional gemacht. Der Controller sucht nun an mehreren Stellen nach der ID und generiert eine temporäre ID, falls keine gefunden wird.

### Geänderte Dateien
1. `app/Http/Controllers/Api/RetellDataCollectionController.php`
   - Call-ID aus verschiedenen Quellen suchen (Header, Request, call.call_id)
   - Temporäre ID generieren wenn keine vorhanden
   - Daten aus `args` Objekt mit korrekten Parameternamen lesen
   - Neuen Call-Record erstellen wenn kein existierender gefunden wird

2. `config/services.php`
   - Default Company und Branch IDs hinzugefügt für standalone Datenerfassung

### Call-ID Quellen (in dieser Reihenfolge)
1. HTTP Header: `X-Retell-Call-Id`
2. Request Parameter: `call_id`
3. Nested: `call.call_id`
4. Fallback: Temporäre ID generieren (`temp-YYYYMMDD-HHMMSS-RANDOM`)

### Parameter Mapping
Die Retell Parameter werden nun korrekt gemappt:
- `vorname` + `nachname` → `full_name`
- `firma` → `company`
- `kundennummer` → `customer_number`
- `telefon_primaer` → `phone_primary`
- `telefon_sekundaer` → `phone_secondary`
- `email` → `email`
- `anliegen` → `request`
- `weitere_notizen` → `notes`
- `einverstaendnis_datenspeicherung` → `consent`

### Standalone Datenerfassung
Wenn kein Call-Record gefunden wird, wird automatisch ein neuer erstellt mit:
- Status: `completed`
- Duration: 0
- Metadata Flag: `standalone_collection: true`
- Company/Branch IDs werden aus Konfiguration oder Phone-Mapping ermittelt

## Testing
```bash
# Test-Skript ausführen
php test-retell-collect-data.php

# Logs prüfen
grep "RetellDataCollection" storage/logs/laravel-$(date +%Y-%m-%d).log | tail -n 20
```

## Konfiguration
Neue Environment-Variablen (optional):
```env
RETELL_DEFAULT_COMPANY_ID=1
RETELL_DEFAULT_BRANCH_ID=1
```

## Wichtig
Diese Änderung ist rückwärtskompatibel. Wenn eine `call_id` gesendet wird, funktioniert alles wie bisher. Nur wenn keine `call_id` vorhanden ist, greift die neue Logik.