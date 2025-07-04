# Retell.ai Integration - Vollständige Anleitung (Stand: 2025-06-30)

## ✅ Durchgeführte Fixes

### 1. **FetchRetellCalls Command repariert**
- **Problem**: Command erwartete `{results: [...]}` Format, aber Retell API v2 gibt direkt Array zurück
- **Lösung**: Command aktualisiert in `/app/Console/Commands/FetchRetellCalls.php`
- **Status**: ✅ Funktioniert

### 2. **Retell für Company aktiviert**
- **Company ID 1**: retell_enabled = 1
- **API Key**: Konfiguriert
- **Phone Number**: +493083793369 mit Agent ID verknüpft
- **Status**: ✅ Aktiviert

### 3. **Cron-Job aktualisiert**
- **Alt**: Alle 15 Minuten
- **Neu**: Alle 5 Minuten
- **Command**: `*/5 * * * * /usr/bin/php /var/www/api-gateway/manual-retell-import.php`
- **Status**: ✅ Läuft

### 4. **Branch-Telefonnummer konfiguriert**
- **Branch**: Hauptfiliale
- **Phone**: +493083793369
- **Verknüpfung**: Branch ↔ Phone Number
- **Status**: ✅ Verknüpft

### 5. **Pending Webhooks verarbeitet**
- **3 Webhooks** zur Queue hinzugefügt
- **Horizon** verarbeitet diese automatisch
- **Status**: ✅ Verarbeitet

### 6. **Unified Setup Center erstellt**
- **Neuer Menüpunkt**: "Einrichtung" im Admin-Panel
- **Zentrale Anlaufstelle** für alle Konfigurationen
- **Fortschrittsanzeige** für Setup-Status
- **Status**: ✅ Verfügbar unter `/admin/setup`

## 📞 Retell Agent Konfiguration

Basierend auf Ihrer bereitgestellten JSON-Konfiguration:

### Agent Details
- **Agent Name**: "Online: Assistent für Fabian Spitzer Rechtliches/V33"
- **Webhook URL**: `https://api.askproai.de/api/retell/webhook` ✅ (Korrekt konfiguriert)
- **Sprache**: de-DE (Deutsch)
- **Model**: gemini-2.0-flash
- **Voice**: Custom Voice (eleven_turbo_v2_5)

### Custom Functions
1. **end_call** - Gespräch beenden
2. **transfer_call** - Anruf weiterleiten (+491604366218)
3. **current_time_berlin** - Aktuelle Zeit abrufen
4. **collect_appointment_data** - Termindaten sammeln
5. **check_customer** - Kunde prüfen
6. **check_availability** - Verfügbarkeit prüfen
7. **book_appointment** - Termin buchen
8. **cancel_appointment** - Termin stornieren
9. **reschedule_appointment** - Termin verschieben

## 🔧 Konfiguration im Portal

### 1. Unternehmen konfigurieren
- Gehen Sie zu: **Admin Panel → Einrichtung → Einrichtungszentrum**
- Vervollständigen Sie:
  - ✅ Unternehmensdaten
  - ✅ Filialen & Standorte (Hauptfiliale vorhanden)
  - ✅ Telefonnummern (+493083793369 konfiguriert)
  - ✅ KI-Telefonassistent (Retell aktiviert)
  - ⚠️ Mitarbeiter (noch zu konfigurieren)
  - ⚠️ Dienstleistungen (noch zu konfigurieren)
  - ⚠️ Kalenderintegration (Cal.com noch zu verbinden)
  - ⚠️ Benachrichtigungen (E-Mail/SMS Einstellungen)

### 2. Mitarbeiter hinzufügen
- **Admin Panel → Verwaltung → Mitarbeiter**
- Mindestens einen Mitarbeiter anlegen
- Arbeitszeiten definieren
- Mit Filiale verknüpfen

### 3. Dienstleistungen definieren
- **Admin Panel → Verwaltung → Dienstleistungen**
- Services anlegen (z.B. "Beratung", "Erstgespräch")
- Dauer und Preis festlegen
- Mit Mitarbeitern verknüpfen

### 4. Cal.com Integration
- **Admin Panel → Einrichtung → Event-Type Konfiguration**
- Cal.com API Key hinterlegen
- Event Types synchronisieren
- Mit Dienstleistungen verknüpfen

## 🧪 Test der Terminbuchung

### 1. Voraussetzungen prüfen
```bash
# Retell Calls manuell importieren
php artisan retell:fetch-calls --company=1 --limit=20

# Horizon Status prüfen
php artisan horizon:status

# Webhook Events prüfen
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db \
  -e "SELECT * FROM webhook_events WHERE provider='retell' ORDER BY created_at DESC LIMIT 10;"
```

### 2. Testanruf durchführen
1. Rufen Sie **+493083793369** an
2. Der KI-Assistent sollte sich melden
3. Sagen Sie: "Ich möchte einen Termin vereinbaren"
4. Folgen Sie den Anweisungen des Assistenten

### 3. Anruf im System prüfen
- **Admin Panel → Täglicher Betrieb → Anrufe**
- Ihr Anruf sollte innerhalb von 5 Minuten erscheinen
- Prüfen Sie Transcript und Details

## 🚨 Troubleshooting

### Anruf wird nicht angezeigt
1. **Prüfen Sie den Import**:
   ```bash
   php artisan retell:fetch-calls --company=1 --limit=10
   ```

2. **Prüfen Sie Horizon**:
   ```bash
   php artisan horizon
   ```

3. **Prüfen Sie Webhook Events**:
   ```bash
   tail -f storage/logs/laravel.log | grep -i retell
   ```

### Webhook Signature Fehler
- Bereits behoben! Die Signature Verification funktioniert korrekt
- Falls weiterhin Probleme: Prüfen Sie `X-Retell-Signature` Header

### Keine Termine werden gebucht
1. Mitarbeiter müssen angelegt sein
2. Dienstleistungen müssen definiert sein
3. Cal.com muss konfiguriert sein
4. Arbeitszeiten müssen hinterlegt sein

## 📊 Monitoring

### Dashboard Widgets
- **Live Calls Widget**: Zeigt aktive Anrufe
- **Phone Agent Status**: Zeigt Retell Agent Status
- **Recent Calls**: Zeigt letzte Anrufe

### Logs
- Laravel Log: `storage/logs/laravel.log`
- Retell Import: `storage/logs/retell-call-import.log`
- Webhook Events: In Datenbank `webhook_events` Tabelle

## 🎯 Nächste Schritte

1. **Mitarbeiter anlegen** (Admin Panel → Verwaltung → Mitarbeiter)
2. **Dienstleistungen definieren** (Admin Panel → Verwaltung → Dienstleistungen)
3. **Cal.com verbinden** (Admin Panel → Einrichtung → Event-Type Konfiguration)
4. **Testanruf durchführen** und Terminbuchung testen
5. **E-Mail Benachrichtigungen** konfigurieren

## 📞 Support

Bei Problemen:
1. Prüfen Sie diese Dokumentation
2. Schauen Sie in die Logs
3. Nutzen Sie das Admin Panel → System → Troubleshooting
4. Kontaktieren Sie support@askproai.de

## ⚠️ WICHTIG: Nach Context Reset

### Quick Fix (1 Befehl):
```bash
./retell-quick-setup.sh
```

### Oder manuell:
```bash
# 1. Health Check
php retell-health-check.php

# 2. Sync Calls
php sync-retell-calls.php

# 3. Start Horizon
php artisan horizon

# 4. Test Monitor
curl https://api.askproai.de/retell-monitor/stats
```

## Bekannte Fixes für häufige Probleme

### Problem: API v2 Format
**Gelöst**: FetchRetellCalls erwartet jetzt korrektes Array Format

### Problem: Company Context
**Gelöst**: ProcessRetellWebhookJob verwendet CompanyAwareJob trait korrekt

### Problem: Webhook Signature
**Gelöst**: VerifyRetellSignature mit erweitertem Logging und korrekter Verifikation

### Problem: Phone Number Resolution
**Gelöst**: branches.phone_number korrekt verknüpft

Diese Dokumentation wurde am 2025-06-30 aktualisiert und enthält alle notwendigen Informationen für die Retell.ai Integration.