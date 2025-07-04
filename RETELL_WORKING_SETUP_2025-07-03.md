# 🔒 RETELL.AI FUNKTIONIERENDES SETUP - STAND 2025-07-03

## ⚠️ WICHTIG: DIESE KONFIGURATION FUNKTIONIERT - NICHT ÄNDERN!

Dieses Dokument dokumentiert das **aktuell funktionierende** Retell.ai Setup. Die Telefondaten werden korrekt übertragen. Bei zukünftigen Änderungen MUSS diese Konfiguration als Referenz verwendet werden.

## 🎯 Übersicht

### Funktionsstatus
- ✅ **Webhook-Empfang**: Funktioniert
- ✅ **Datenextraktion**: Alle Felder werden korrekt extrahiert
- ✅ **Branch-Zuordnung**: Telefonnummern werden korrekt zugeordnet
- ✅ **Automatischer Import**: Läuft alle 15 Minuten
- ✅ **Bereinigung**: Alte Calls werden automatisch aufgeräumt

### Kritische URLs
```
Webhook URL: https://api.askproai.de/api/retell/webhook-simple
API Base: https://api.retellai.com
```

## 📂 Kritische Dateien (NICHT ÄNDERN!)

### 1. **Webhook Controller**
**Datei**: `app/Http/Controllers/Api/RetellWebhookWorkingController.php`

**Kritische Features**:
- Handhabt nested structure: `{ "event": "...", "call": { ... } }`
- TenantScope Bypass mit `withoutGlobalScope()`
- Flexibles Timestamp-Parsing (ISO 8601 + numeric milliseconds)
- Branch-Fallback-Logik

**Kritischer Code-Abschnitt**:
```php
// CRITICAL FIX: Handle nested structure from Retell
if (isset($data['call']) && is_array($data['call'])) {
    $callData = $data['call'];
    $data = array_merge($callData, [
        'event' => $data['event'] ?? $data['event_type'] ?? null,
        'event_type' => $data['event'] ?? $data['event_type'] ?? null
    ]);
}
```

### 2. **Data Extractor Helper**
**Datei**: `app/Helpers/RetellDataExtractor.php`

**Kritische Features**:
- Extrahiert ALLE Retell-Felder
- Flexible Timestamp-Konvertierung
- Speichert raw_data für Debugging
- Handhabt custom_analysis_data korrekt

**Wichtige Felder die extrahiert werden**:
- Basic: call_id, from_number, to_number, duration_sec
- Analysis: user_sentiment, call_successful, custom_analysis_data
- Meta: transcript, recording_url, public_log_url
- Cost: retell_cost, llm_usage
- Performance: latency_metrics, end_to_end_latency

### 3. **Route Definition**
**Datei**: `routes/api.php`

```php
Route::post('/retell/webhook-simple', [\App\Http\Controllers\Api\RetellWebhookWorkingController::class, 'handle'])
    ->name('retell.webhook.simple');
```

**WICHTIG**: Diese Route hat KEINE Middleware! Das ist Absicht für Webhook-Kompatibilität.

## 🔄 Datenfluss

```
1. Retell.ai Call → 
2. POST https://api.askproai.de/api/retell/webhook-simple →
3. RetellWebhookWorkingController::handle() →
4. RetellDataExtractor::extractCallData() →
5. Branch-Zuordnung (Phone Number Lookup) →
6. Call-Record in DB erstellt/aktualisiert
```

## 🛠️ Konfiguration

### Environment Variables (.env)
```bash
# Retell API Konfiguration
DEFAULT_RETELL_API_KEY=key_e973c8962e09d6a34b3b1cf386
DEFAULT_RETELL_AGENT_ID=agent_dda1c8962e09d6a34b3b1c
RETELL_WEBHOOK_SECRET=Hqj8iGCaWxGXdoKCqQQFaHsUjFKHFjUO
RETELL_BASE=https://api.retellai.com
RETELL_VERIFY_SIGNATURE=true
RETELL_DEBUG_MODE=false
RETELL_ASYNC_PROCESSING=true
```

### Cron Jobs
```bash
# Anrufe importieren (alle 15 Minuten)
*/15 * * * * /usr/bin/php /var/www/api-gateway/manual-retell-import.php

# Alte in_progress Anrufe bereinigen (alle 5 Minuten)
*/5 * * * * /usr/bin/php /var/www/api-gateway/cleanup-stale-calls.php
```

## 🔍 Debugging & Testing

### Test Commands
```bash
# Test mit echter Retell-Struktur
php test-retell-real-data.php

# Horizon Status prüfen
php artisan horizon:status

# Logs überwachen
tail -f storage/logs/laravel.log | grep -i retell

# Manuelle Call-Imports
php artisan retell:fetch-calls --limit=50

# Health Check
php retell-health-check.php
```

### Wichtige Log-Einträge
```
📞 Retell Webhook empfangen
✅ Call erfolgreich erstellt
✅ Call aktualisiert
❌ Fehler bei Webhook-Verarbeitung
```

## ⚠️ Bekannte Probleme & Lösungen

### Problem 1: Nested Call Structure
**Symptom**: Webhook-Daten kommen in verschachtelter Struktur
**Lösung**: Bereits implementiert in Zeilen 23-32 des Controllers

### Problem 2: TenantScope blockiert Webhook
**Symptom**: Call wird nicht gefunden/erstellt
**Lösung**: `withoutGlobalScope(TenantScope::class)` verwenden

### Problem 3: Timestamp-Formate
**Symptom**: Verschiedene Timestamp-Formate (ISO 8601 vs numeric)
**Lösung**: Flexibles Parsing in `RetellDataExtractor::parseTimestamp()`

### Problem 4: Branch-Zuordnung
**Symptom**: Calls werden keiner Branch zugeordnet
**Lösung**: Fallback-Logik mit Default-Branch

## 🚨 NIEMALS ÄNDERN

1. **Route URL**: `/api/retell/webhook-simple` muss bleiben
2. **Keine Middleware**: Route darf KEINE Middleware haben
3. **Nested Structure Handling**: Zeilen 23-32 im Controller
4. **TenantScope Bypass**: `withoutGlobalScope()` Aufrufe
5. **Timestamp Parsing**: Flexible Logik beibehalten

## 📋 Backup-Strategie

### Bei Änderungen:
1. **Backup erstellen**:
   ```bash
   cp app/Http/Controllers/Api/RetellWebhookWorkingController.php \
      app/Http/Controllers/Api/RetellWebhookWorkingController.php.backup_$(date +%Y%m%d_%H%M%S)
   ```

2. **Test durchführen**:
   ```bash
   php test-retell-real-data.php
   ```

3. **Rollback bei Problemen**:
   ```bash
   cp app/Http/Controllers/Api/RetellWebhookWorkingController.php.backup_20250703_120000 \
      app/Http/Controllers/Api/RetellWebhookWorkingController.php
   ```

## 🧪 Test-Prozedur

### Vor JEDER Änderung:
1. **Baseline Test**:
   ```bash
   php test-retell-real-data.php > baseline.txt
   ```

2. **Nach Änderung**:
   ```bash
   php test-retell-real-data.php > after-change.txt
   diff baseline.txt after-change.txt
   ```

3. **Live Test**:
   - Testanruf durchführen
   - In Admin-Panel prüfen ob Call erscheint
   - Logs auf Fehler prüfen

## 📞 Notfall-Kontakte

Bei Problemen mit Retell.ai Integration:
- **Primary**: Fabian (Technischer Lead)
- **Retell Support**: support@retellai.com
- **Dokumentation**: https://docs.retellai.com

---

**LETZTE FUNKTIONIERENDE VERSION**: 2025-07-03 10:30 Uhr
**GETESTET MIT**: Retell API v2
**STATUS**: ✅ VOLL FUNKTIONSFÄHIG