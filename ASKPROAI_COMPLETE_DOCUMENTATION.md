Perfekt! Jetzt habe ich alle notwendigen Informationen. Hier ist die vollständige, konsolidierte Dokumentation:

```bash
cat > /var/www/api-gateway/ASKPROAI_COMPLETE_DOCUMENTATION.md << 'EOF'
# 📚 AskProAI - Vollständige Projektdokumentation
**Version:** 2.0  
**Erstellt am:** $(date '+%d.%m.%Y %H:%M')  
**Projekt:** Middleware für Telefon-KI und Terminverwaltung

---

## 📋 Inhaltsverzeichnis

1. [Systemüberblick](#1-systemüberblick)
2. [Technologie-Stack](#2-technologie-stack)
3. [Integrationen & APIs](#3-integrationen--apis)
4. [Datenbankarchitektur](#4-datenbankarchitektur)
5. [Admin-Dashboard (Filament)](#5-admin-dashboard-filament)
6. [Sicherheit & Authentifizierung](#6-sicherheit--authentifizierung)
7. [Deployment & DevOps](#7-deployment--devops)
8. [Monitoring & Wartung](#8-monitoring--wartung)
9. [Umgebungskonfiguration](#9-umgebungskonfiguration)
10. [API-Dokumentation](#10-api-dokumentation)
11. [Bekannte Probleme & TODOs](#11-bekannte-probleme--todos)

---

## 1. Systemüberblick

**AskProAI** ist eine Laravel-basierte Middleware, die folgende Kernfunktionen bietet:

- **Telefon-KI Integration:** Verbindung mit Retell.ai für intelligente Anrufannahme
- **Terminverwaltung:** Automatisierte Terminbuchung über Cal.com
- **Multi-Tenant-Architektur:** Unterstützung mehrerer Unternehmen/Filialen
- **Admin-Dashboard:** Vollständige Verwaltung über Filament UI
- **Webhook-Verarbeitung:** Echtzeit-Datenverarbeitung von externen Services

### Workflow
1. Kunde ruft an → Retell.ai Agent nimmt Gespräch entgegen
2. KI extrahiert Termininformationen aus dem Gespräch
3. Webhook an AskProAI → Datenverarbeitung
4. Verfügbarkeitsprüfung bei Cal.com
5. Automatische Terminbuchung
6. Bestätigung an Kunde

### Projektstruktur
- **Repository:** git@github.com:fabianSp77/askproai-api.git
- **Branch:** feature/calcom-integration
- **Server:** Netcup VPS (IP: siehe .env)
- **Domain:** api.askproai.de

---

## 2. Technologie-Stack

### Backend
- **PHP:** 8.2.x
- **Framework:** Laravel 11.x
- **Admin UI:** Filament 3.3.14
- **Queue Management:** Laravel Horizon v5.31.2
- **Cache/Session:** Redis (aktiv und läuft)

### Frontend
- **Build Tool:** Vite
- **CSS Framework:** Tailwind CSS
- **JavaScript:** Alpine.js (via Filament)

### Infrastruktur
- **OS:** Debian/Linux
- **Webserver:** Nginx
- **Datenbank:** MariaDB/MySQL
- **Queue:** Redis + Horizon (6 Worker-Prozesse aktiv)
- **Mail:** SMTP über smtp.udag.de (SSL/465)

### Externe Services
- **Telefon-KI:** Retell.ai
- **Kalender:** Cal.com
- **Payment:** Stripe (vorbereitet)
- **Storage:** AWS S3 (optional)

---

## 3. Integrationen & APIs

### 3.1 Retell.ai (Telefon-KI)
- **API Key:** key_6ff998ba48e842092e04a5455d19
- **Webhook URL:** /api/retell/webhook
- **Verfügbare Agenten:**
  - Assistent für Fabian Spitzer Rechtliches
  - Musterfriseur Terminierung
  - Muster-Physiotherapie-Praxis
  - Tierarztpraxis Oldenburger Straße

### 3.2 Cal.com (Kalendersystem)
- **API Key:** cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da
- **Event Type ID:** 2026302
- **Team Slug:** askproai
- **Webhook Secret:** 6846aed4d55f6f3df70c40781e02d964...

### 3.3 Stripe (Payment - vorbereitet)
- **Secret Key:** sk_live_51QjozIEypZR52sur...

### 3.4 Webhook-Endpunkte
```
GET  /api/calcom/webhook    - Health Check
POST /api/calcom/webhook    - Event Handler
POST /api/retell/webhook    - Call Handler
```

---

## 4. Datenbankarchitektur

### Haupttabellen (95 Migrations)
- **calls** (120 Einträge) - Anrufdaten mit Transkripten
- **appointments** (520 Einträge) - Gebuchte Termine
- **customers** - Kundenstammdaten
- **companies** - Mandanten/Unternehmen
- **branches** - Filialen/Standorte
- **staff** - Mitarbeiter mit Services
- **services** - Dienstleistungen
- **calcom_bookings** - Cal.com spezifische Daten
- **retell_agents** - KI-Agent Konfigurationen

### Multi-Tenant-Struktur
- Alle Haupttabellen haben `tenant_id` Feld
- Unterstützt unbegrenzte Mandanten
- Datenisolierung auf Datenbankebene

---

## 5. Admin-Dashboard (Filament)

### Verfügbare Resources
- **Companies** - Firmenverwaltung
- **Customers** - Kundenverwaltung
- **Appointments** - Terminübersicht
- **Staff** - Mitarbeiterverwaltung
- **Services** - Dienstleistungen
- **Working Hours** - Arbeitszeiten
- **Billing** - Abrechnungen
- **CalcomEventTypes** - Kalender-Events

### Spezielle Features
- **Customer Onboarding Wizard** - Geführte Kundeneinrichtung
- **API Status Widget** - Live-Monitoring
- **System Status Widget** - Überwachung aller Services
- **Activity Log** - Vollständige Audit-Trail

### Widgets (5 aktiv)
- AnimatedStatusWidget
- ApiStatusWidget
- SystemStatus
- Weitere Dashboard-Widgets

---

## 6. Sicherheit & Authentifizierung

### API-Sicherheit
- OAuth2 via Laravel Passport
- Webhook-Signatur-Verifizierung (Cal.com)
- API Rate Limiting

### Admin-Panel
- Filament Shield (vorbereitet, derzeit deaktiviert)
- Role-Based Access Control (RBAC)
- Session-basierte Authentifizierung

### Sicherheitseinstellungen
```
APP_DEBUG=true                    # ⚠️ Für Produktion auf false setzen!
SESSION_SECURE_COOKIE=true        # ✅ Sichere Cookies
SESSION_SAME_SITE=lax            # ✅ CSRF-Schutz
SHIELD_ENABLED=false             # Shield derzeit deaktiviert
```

---

## 7. Deployment & DevOps

### Deployment-Prozess
1. Code Push zu GitHub
2. SSH zum Server
3. Git Pull im Projektverzeichnis
4. Migrations ausführen: `php artisan migrate --force`
5. Cache leeren: `php artisan optimize:clear`
6. Queue Worker neustarten: `php artisan horizon:terminate`

### Verfügbare Skripte
- **backup.sh** - Tägliches Backup (Cron: 0 2 * * *)
- **restore.sh** - Wiederherstellung
- **netcup-deploy.sh** - Deployment-Automatisierung
- **system_audit_v3.sh** - Systemprüfung
- **fix_env_and_cache.sh** - Umgebungsreparatur

### Backup-Strategie
- **Datenbank:** Tägliches MySQL-Backup (Cron: 5 3 * * *)
- **Dateisystem:** Tägliches Laravel-Backup
- **Aufbewahrung:** 14 Tage

---

## 8. Monitoring & Wartung

### Laravel Horizon
- **Status:** Aktiv mit 6 Worker-Prozessen
- **Queue:** Redis-basiert
- **Dashboard:** /horizon (geschützt)
- **Monitoring:** Automatische Worker-Neustart bei Fehlern

### Logging
- **Laravel Logs:** storage/logs/
- **Activity Logs:** Datenbankbasiert
- **API Health Logs:** Automatische Endpunkt-Überwachung
- **Aktive Logs:** 2 Dateien der letzten 7 Tage

### System-Monitoring
- Redis: Aktiv (PID: 3520914)
- Horizon: 6 aktive Worker
- Cron Jobs: 2 konfiguriert (Backups)

---

## 9. Umgebungskonfiguration

### Kritische Einstellungen
```env
# ⚠️ Produktion-Checkliste
APP_DEBUG=true                    # → false für Produktion
QUEUE_CONNECTION=sync             # → redis für Produktion
SESSION_SECURE_COOKIE=true        # ✅ OK
HORIZON_PREFIX=askproai           # ✅ OK

# Mail-Konfiguration
MAIL_MAILER=smtp
MAIL_HOST=smtp.udag.de
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

### Datenbank
- Host: 127.0.0.1
- Datenbank: askproai_db
- Benutzer: askproai_user

---

## 10. API-Dokumentation

### Webhook-Empfang (Retell.ai)
```bash
POST /api/retell/webhook
Content-Type: application/json

{
  "call_id": "unique_id",
  "phone_number": "+49xxx",
  "_datum__termin": "2025-06-01",
  "_uhrzeit__termin": "14:00",
  "_dienstleistung": "Service-Name",
  "_name": "Kundenname",
  "_email": "kunde@example.com"
}
```

### Test-Befehle
```bash
# Test Retell Webhook
curl -X POST http://localhost/api/retell/webhook \
  -H "Content-Type: application/json" \
  -d '{"call_id":"test_123","phone_number":"+491234567890"}'

# Logs überwachen
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
```

---

## 11. Bekannte Probleme & TODOs

### ⚠️ Sofortige Aktionen für Produktion
1. **APP_DEBUG auf false setzen**
2. **QUEUE_CONNECTION auf redis umstellen**
3. **Webhook-Signatur-Verifizierung aktivieren**
4. **Filament Shield aktivieren für Zugriffskontrolle**

### 📋 Geplante Erweiterungen
- SMS/WhatsApp-Integration (Twilio)
- Google Calendar als Fallback
- Erweiterte Analytics & Reporting
- Mobile App für Kunden
- Automatische Fehlerbenachrichtigungen
- Erweiterte Multi-Agent-Unterstützung

### 🐛 Bekannte Probleme
- Horizon zeigt "inactive" Status trotz laufender Worker
- Einige Backup-Dateien ohne Restore-Tests
- Debug-Modus in Produktion aktiv

---

## 📞 Support & Kontakt

**Bei Fragen oder Problemen:**
- GitHub Issues: github.com/fabianSp77/askproai-api
- Logs prüfen: /var/www/api-gateway/storage/logs/
- Horizon Dashboard: /horizon
- Filament Admin: /admin

---

*Dokumentation generiert am $(date '+%d.%m.%Y %H:%M') für AskProAI v2.0*
EOF

echo "✅ Dokumentation erfolgreich erstellt!"
echo "📄 Datei: /var/www/api-gateway/ASKPROAI_COMPLETE_DOCUMENTATION.md"
echo ""
echo "Sie können die Dokumentation anzeigen mit:"
echo "cat /var/www/api-gateway/ASKPROAI_COMPLETE_DOCUMENTATION.md"
```

Diese Dokumentation enthält nun:
- ✅ Alle Ihre bisherigen Informationen
- ✅ Die Ergänzungen des Kollegen
- ✅ Die neuen Erkenntnisse aus den Systemabfragen
- ✅ Konkrete Handlungsanweisungen für Produktion
- ✅ Vollständige Übersicht aller Komponenten
