# Business Portal Call-Liste Update

**Datum**: 2025-07-05  
**Status**: ✅ Implementiert

## Problem

Die Call-Liste im Business Portal zeigte nur grundlegende Informationen an (Telefonnummer, Dauer, Status) aber keine der extrahierten Daten aus den Telefonaten.

## Lösung

### 1. **API Response erweitert**

Die Call-Liste API (`/business/api/calls`) gibt jetzt zusätzliche Felder zurück:
- `extracted_name` - Extrahierter Kundenname
- `datum_termin` - Termin Datum
- `uhrzeit_termin` - Termin Uhrzeit
- `dienstleistung` - Gewünschte Dienstleistung
- `summary` - Anruf-Zusammenfassung
- `appointment_requested` - Flag ob Termin angefragt wurde
- `appointment_made` - Flag ob Termin gebucht wurde

### 2. **UI Verbesserungen in der Call-Liste**

Die Tabelle zeigt jetzt:
- **Kundenname**: Zeigt `extracted_name` oder `customer_name`
- **Termininformationen**: 📅 Datum und Uhrzeit wenn vorhanden
- **Dienstleistung**: 🏥 Gewünschte Dienstleistung
- **Status-Badges**: 
  - "📅 Termin angefragt" wenn `appointment_requested`
  - "✅ Termin gebucht" wenn `appointment_made`

### 3. **Navigation Fix**

Der "Details" Button nutzt jetzt React Router Navigation statt Page-Reload.

## Visuelle Darstellung

```
Anrufer                        | Status
-------------------------------|------------------
+49 123 456789                | Beendet
Max Mustermann                 | 📅 Termin angefragt
📅 2025-07-15 um 14:30        |
🏥 Zahnreinigung              |
```

## Nächste Schritte

### 1. **Bestehende Daten verarbeiten**
```bash
# Führe das Reprocessing Script aus
php /var/www/api-gateway/reprocess-call-data.php
```

### 2. **Verifizierung**
- Öffne das Business Portal
- Navigiere zur Call-Liste
- Prüfe ob die zusätzlichen Informationen angezeigt werden
- Klicke auf "Details" um sicherzustellen dass die Navigation funktioniert

### 3. **Performance**
Bei vielen Calls mit extrahierten Daten könnte die Liste langsamer werden. 
Überlege:
- Pagination auf 25 Einträge begrenzen
- Lazy Loading für Details
- Caching der API Responses

## Technische Details

**Geänderte Dateien:**
- `/app/Http/Controllers/Portal/Api/CallApiController.php` - API Response erweitert
- `/resources/js/Pages/Portal/Calls/Index.jsx` - UI Komponente verbessert

**Neue Features:**
- Anzeige extrahierter Kundendaten
- Visuelle Hervorhebung von Termininformationen
- Status-Badges für Terminanfragen
- React Router Navigation

## Debugging

Falls Daten nicht angezeigt werden:
```bash
# Prüfe ob Daten in der DB vorhanden sind
php artisan tinker
>>> Call::whereNotNull('extracted_name')->count();
>>> Call::whereNotNull('datum_termin')->count();

# Prüfe API Response
curl -X GET https://api.askproai.de/business/api/calls \
  -H "Cookie: [SESSION_COOKIE]" \
  -H "Accept: application/json" | jq .

# Logs prüfen
tail -f storage/logs/laravel.log
```