# 🚨 AskProAI Disaster Recovery Playbook

## 🎯 Ziel
Dieses Playbook stellt sicher, dass wir bei Datenverlust, Systemausfall oder anderen Katastrophen schnell und vollständig wiederherstellen können.

## 📊 Daten-Architektur Übersicht

### Primäre Datenquellen:
1. **Retell.ai** - Anrufdaten, Transkripte, AI-Agenten
2. **Cal.com** - Termine, Event-Types, Verfügbarkeiten
3. **Lokale DB** - Kunden, Unternehmen, Filialen, Abrechnungen

### Backup-Strategie:
```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Retell.ai     │────▶│  Lokale Sync    │────▶│  Backup Files   │
│   (Externe API) │     │  (Stündlich)    │     │  (Komprimiert)  │
└─────────────────┘     └─────────────────┘     └─────────────────┘
         │                        │                        │
         │                        ▼                        ▼
         │              ┌─────────────────┐     ┌─────────────────┐
         │              │ Billing Snapshot│     │   S3/External   │
         │              │  (Unveränderbar)│     │    Storage      │
         │              └─────────────────┘     └─────────────────┘
         │                        │
         ▼                        ▼
┌─────────────────┐     ┌─────────────────┐
│    Cal.com      │────▶│   Audit Trail   │
│  (Externe API)  │     │  (Alle Änder.)  │
└─────────────────┘     └─────────────────┘
```

## 🛡️ Präventive Maßnahmen

### 1. Automatische Backups
- **Vollbackup**: Täglich 2:00 Uhr
- **Inkrementell**: Stündlich
- **Kritische Daten**: Alle 6 Stunden
- **Externe Sync**: Alle 30 Minuten

### 2. Daten-Redundanz
```bash
# Externe Daten lokal spiegeln
*/30 * * * * php artisan askproai:sync-external --verify

# Billing Snapshots (unveränderbar)
0 1 1 * * php artisan askproai:create-billing-snapshot --previous-month
```

### 3. Monitoring
- Database Health Check: Alle 5 Minuten
- Backup Verification: Täglich
- External API Status: Alle 10 Minuten

## 🚨 Disaster Recovery Szenarien

### Szenario 1: Lokaler Datenverlust (wie am 17.06.2025)

#### Symptome:
- Tabellen fehlen oder sind leer
- Admin Panel zeigt keine Daten
- Fehler beim Zugriff auf Daten

#### Recovery Steps:
```bash
# 1. Sofort stoppen
php artisan down

# 2. Letztes Backup identifizieren
ls -lah /var/www/api-gateway/storage/backups/database/
# Oder externe Backups checken

# 3. Backup wiederherstellen
gunzip askproai_full_YYYY-MM-DD_HH-mm-ss.sql.gz
mysql -u root -p askproai_db < askproai_full_YYYY-MM-DD_HH-mm-ss.sql

# 4. Externe Daten synchronisieren
php artisan askproai:sync-external --verify

# 5. Integrität prüfen
php artisan askproai:verify-integrity

# 6. System wieder online
php artisan up
```

### Szenario 2: Cal.com API Ausfall

#### Symptome:
- Keine neuen Termine buchbar
- Sync Fehler in Logs

#### Recovery Steps:
```bash
# 1. Auf lokale Backup-Daten umschalten
php artisan config:set calcom.fallback_mode true

# 2. Cached Event Types nutzen
php artisan calcom:use-cached-data

# 3. Monitoring für API-Rückkehr
watch -n 60 'php artisan calcom:check-api'

# 4. Nach Rückkehr: Full Sync
php artisan askproai:sync-external --source=calcom
```

### Szenario 3: Retell.ai Ausfall

#### Symptome:
- Keine Anrufe kommen durch
- Webhook Fehler

#### Recovery Steps:
```bash
# 1. Fallback auf manuelle Eingabe
php artisan retell:enable-manual-mode

# 2. Calls aus Backup laden
php artisan retell:load-from-backup

# 3. Alternative Nummer schalten
php artisan phone:switch-to-backup

# 4. Nach Rückkehr synchronisieren
php artisan askproai:sync-external --source=retell
```

### Szenario 4: Korrupte Abrechnungsdaten

#### Symptome:
- Rechnungen stimmen nicht
- Billing Snapshots fehlen

#### Recovery Steps:
```bash
# 1. Unveränderbare Snapshots prüfen
php artisan billing:verify-snapshots --month=2025-06

# 2. Aus Archiv wiederherstellen
php artisan billing:restore-from-archive --snapshot-id=123

# 3. Neu berechnen aus Rohdaten
php artisan billing:recalculate --company=85 --month=2025-06

# 4. Audit Trail prüfen
php artisan audit:check --entity=billing --period=2025-06
```

## 📋 Recovery Checkliste

### Vor dem Ernstfall:
- [ ] Backup-Skripte laufen (crontab -l)
- [ ] Externe Sync aktiv
- [ ] S3/FTP Backup konfiguriert
- [ ] Team kennt Playbook
- [ ] Recovery getestet (monatlich)

### Im Ernstfall:
- [ ] System offline nehmen
- [ ] Problem identifizieren
- [ ] Backup-Status prüfen
- [ ] Recovery ausführen
- [ ] Daten verifizieren
- [ ] System testen
- [ ] Online schalten
- [ ] Post-Mortem durchführen

## 🔧 Wichtige Befehle

### Status-Checks:
```bash
# Backup Status
php artisan askproai:backup-status

# Externe Sync Status
mysql -e "SELECT * FROM external_sync_logs ORDER BY created_at DESC LIMIT 5"

# Billing Integrity
php artisan billing:verify-all

# System Health
php artisan health:check --detailed
```

### Recovery Tools:
```bash
# Point-in-Time Recovery
php artisan recover:to-timestamp --timestamp="2025-06-17 14:00:00"

# Selective Recovery
php artisan recover:table --table=appointments --date=2025-06-17

# External Data Recovery
php artisan recover:from-external --source=calcom --date=2025-06-17
```

## 📞 Notfall-Kontakte

### Technisch:
- Server Admin: [Kontakt]
- Database Admin: [Kontakt]
- Retell.ai Support: support@retellai.com
- Cal.com Support: support@cal.com

### Business:
- CTO: [Kontakt]
- CEO: [Kontakt]

## 🎯 RTO/RPO Ziele

- **RTO (Recovery Time Objective)**: < 2 Stunden
- **RPO (Recovery Point Objective)**: < 1 Stunde Datenverlust

## 📝 Post-Mortem Template

Nach jedem Incident:
1. Was ist passiert?
2. Wann wurde es entdeckt?
3. Wie lange dauerte Recovery?
4. Welche Daten gingen verloren?
5. Was können wir verbessern?

---

**Letztes Update**: 18.06.2025
**Getestet am**: [Datum einfügen]
**Nächster Test**: [Monatlich planen]