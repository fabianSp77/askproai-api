# 🔗 Retell Test Links - Übersicht

## 🌐 Web-basierte Tools (im Browser)

### 🎯 **Hauptseite - Retell Test Hub**
➜ **https://api.askproai.de/retell-test**
- Zentrale Übersicht mit allen Links
- Quick Test Buttons für Webhooks
- System Status Anzeige

### 📊 **Live Monitor Dashboard**
➜ **https://api.askproai.de/retell-monitor**
- Echtzeit-Dashboard mit automatischen Updates
- Statistiken (Anrufe, Termine, Webhooks heute)
- Live Webhook-Stream
- System Health Check
- Test-Buttons integriert

### ⚙️ **Admin Panel**
➜ **https://api.askproai.de/admin**
- Hauptverwaltung für alle Daten
- Anrufe, Termine, Kunden verwalten
- Retell Konfiguration

## 🖥️ Command-Line Tools

### Monitor starten:
```bash
cd /var/www/api-gateway
./monitor-retell-webhooks.php
```

### Dashboard anzeigen:
```bash
./retell-test-dashboard.php
```

### Tests ausführen:
```bash
# Call Started Event
./test-retell-webhook-call-started.php

# Call Ended Event (mit Termindaten)
./test-retell-webhook-call-ended.php

# Custom Functions
./test-retell-function-call.php

# Cal.com Integration
./test-calcom-integration.php
```

## 🔍 API Endpoints (für Entwicklung)

### Test Endpoints (nur in Development):
- `POST /api/retell/test-webhook` - Webhook mit Logging
- `POST /api/retell/test-function` - Function Call Test
- `GET /api/retell/test-status` - Test Status

### Monitor API:
- `GET /api/retell/monitor/stats` - Live Statistiken
- `GET /api/retell/monitor/calcom-status` - Cal.com Status
- `GET /api/retell/monitor/activity` - Aktivitätsdaten

## 🚀 Quick Start

1. **Öffne den Test Hub:**
   https://api.askproai.de/retell-test

2. **Klicke auf "Live Monitor"** für das Dashboard

3. **Verwende die Test-Buttons** um Webhooks zu simulieren

4. **Prüfe die Ergebnisse** im Live Monitor

## 📱 Mobile-Zugriff

Alle Web-Tools sind responsive und funktionieren auch auf Smartphones/Tablets:
- Test Hub: https://api.askproai.de/retell-test
- Monitor: https://api.askproai.de/retell-monitor

## 🔐 Zugangsdaten

Falls Authentifizierung erforderlich:
- Admin Panel erfordert Login
- Test-Tools sind öffentlich in Development-Umgebung
- Monitor zeigt anonymisierte Daten

## 📚 Dokumentation

- Vollständige Anleitung: `/var/www/api-gateway/RETELL_TELEFON_TEST_ANLEITUNG.md`
- Quick Reference: `/var/www/api-gateway/CLAUDE_QUICK_REFERENCE.md`