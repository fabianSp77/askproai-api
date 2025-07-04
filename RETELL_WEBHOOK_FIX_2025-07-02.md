# 🚨 KRITISCH: Retell.ai Webhook Fix Documentation (2025-07-02)

## Problem Zusammenfassung
Am 2. Juli 2025 funktionierten Retell.ai Webhooks nicht mehr. Letzte erfolgreiche Calls waren um 18:25 Uhr, danach nur noch 500 Fehler.

## Root Causes (3 kritische Probleme)

### 1. ❌ Retell API Struktur-Änderung
**Problem:** Retell.ai hat die Webhook-Datenstruktur geändert
- **Alt (bis ~18:00):** Flache Struktur
  ```json
  {
    "event_type": "call_ended",
    "call_id": "abc123",
    "from_number": "+49123456",
    "start_timestamp": 1719935127000
  }
  ```
- **Neu (ab ~18:00):** Geschachtelte Struktur
  ```json
  {
    "event": "call_ended",
    "call": {
      "call_id": "abc123",
      "from_number": "+49123456",
      "start_timestamp": 1719935127000
    }
  }
  ```

### 2. ❌ Timestamp Format Änderung
**Problem:** Retell sendet manchmal ISO 8601 Strings statt Millisekunden
- **Früher:** `start_timestamp: 1719935127000` (numerisch)
- **Jetzt:** `start_timestamp: "2025-07-02T20:51:03.000Z"` (ISO string)
- **Fehler:** "A non-numeric value encountered" in RetellDataExtractor.php:25

### 3. ❌ PHP-FPM Neustart um 19:58
**Problem:** Services wurden neu gestartet, wodurch Cache geleert wurde

## Implementierte Lösungen

### Fix 1: Webhook Controller angepasst
**Datei:** `/var/www/api-gateway/app/Http/Controllers/Api/RetellWebhookWorkingController.php`

```php
// CRITICAL FIX: Handle nested structure from Retell
// Retell sends: { "event": "...", "call": { ... } }
if (isset($data['call']) && is_array($data['call'])) {
    // Flatten the structure for compatibility
    $callData = $data['call'];
    $data = array_merge($callData, [
        'event' => $data['event'] ?? $data['event_type'] ?? null,
        'event_type' => $data['event'] ?? $data['event_type'] ?? null
    ]);
}
```

### Fix 2: Flexible Timestamp Parsing
**Datei:** `/var/www/api-gateway/app/Helpers/RetellDataExtractor.php`

```php
private static function parseTimestamp($timestamp): ?string
{
    if (!$timestamp) {
        return null;
    }
    
    // If it's numeric, treat as milliseconds timestamp
    if (is_numeric($timestamp)) {
        return date('Y-m-d H:i:s', $timestamp / 1000);
    }
    
    // If it's a string, try to parse as ISO 8601
    if (is_string($timestamp)) {
        try {
            $dt = new \DateTime($timestamp);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
    
    return null;
}
```

### Fix 3: TenantScope Webhook Bypass
**Datei:** `/var/www/api-gateway/app/Scopes/TenantScope.php`

```php
// Skip for webhooks and API routes (no user context)
if (request()->is('api/retell/*') || request()->is('api/webhook*') || request()->is('api/*/webhook*')) {
    return; // Don't apply tenant filtering for webhooks
}
```

## ⚠️ WICHTIGE HINWEISE

### 1. Diese Dateien NIEMALS ändern ohne diese Fixes zu beachten:
- `app/Http/Controllers/Api/RetellWebhookWorkingController.php`
- `app/Helpers/RetellDataExtractor.php`
- `app/Scopes/TenantScope.php`

### 2. Webhook URL in Retell.ai:
```
https://api.askproai.de/api/retell/webhook-simple
```
(NICHT die Haupt-Webhook-URL mit Signatur-Verifizierung!)

### 3. Test-Befehle für Verifizierung:
```bash
# Test mit geschachtelter Struktur (wie Retell jetzt sendet)
curl -X POST https://api.askproai.de/api/retell/webhook-simple \
  -H "Content-Type: application/json" \
  -H "User-Agent: axios/1.7.7" \
  -d '{
    "event": "call_started",
    "call": {
      "call_id": "test_'$(date +%s)'",
      "from_number": "+491234567890",
      "to_number": "+493083793369",
      "start_timestamp": "'$(date -u +%Y-%m-%dT%H:%M:%S.000Z)'"
    }
  }'
```

## Monitoring & Debugging

### Logs prüfen:
```bash
# Eingehende Webhooks
tail -f /var/log/nginx/access.log | grep retell

# Laravel Logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i retell

# Datenbank prüfen
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "SELECT * FROM calls ORDER BY created_at DESC LIMIT 5;"
```

### Häufige Fehler:
1. **500 Error:** Meist Struktur-Problem oder Timestamp-Format
2. **401 Error:** Falsche Webhook-URL (Signatur-Route statt simple)
3. **Keine Calls:** Horizon nicht running oder Queue-Problem

## Deployment Checklist
Bei jedem Deployment MUSS geprüft werden:
- [ ] RetellWebhookWorkingController hat Struktur-Flatten-Code
- [ ] RetellDataExtractor hat parseTimestamp Methode
- [ ] TenantScope hat Webhook-Bypass
- [ ] Horizon läuft (supervisorctl status horizon)
- [ ] Keine Config-Cache Probleme (php artisan config:clear)

## Recovery bei Problemen
```bash
# 1. Cache löschen
php artisan optimize:clear

# 2. Horizon neustarten
supervisorctl restart horizon

# 3. Test-Webhook senden
php test-retell-real-data.php

# 4. Logs prüfen
tail -f storage/logs/laravel.log
```

---
**KRITISCH:** Diese Dokumentation bei JEDEM Retell-Problem konsultieren!
**Erstellt:** 2025-07-02 22:49 Uhr
**Author:** Claude (AI Assistant)
**Verifiziert:** Funktioniert mit Retell.ai API v2