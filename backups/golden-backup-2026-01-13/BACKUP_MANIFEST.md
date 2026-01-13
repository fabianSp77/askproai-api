# Golden Backup Manifest

**Backup-Datum:** 2026-01-13 19:07 UTC+1
**Erstellt von:** Claude Code (automatisiert)
**Backup-Typ:** Vollständige Systemsicherung

---

## Git-Status

| Feld | Wert |
|------|------|
| **HEAD Commit** | `02abf126282e7a7569c9123594becc12ed9cf800` |
| **Branch** | `main` |
| **Remote** | `git@github.com:fabianSp77/askproai-api.git` |
| **Tag** | `golden-backup-oct7-restored-20251009-114703-347-g02abf1262` |

---

## Backup-Inhalt

| Datei | Beschreibung | Größe |
|-------|--------------|-------|
| `database-complete.sql.gz` | MySQL Dump (askproai_db) | 21 MB |
| `code-complete.tar.gz` | Quellcode (app/, config/, routes/, etc.) | 10 MB |
| `claudedocs.tar.gz` | Dokumentation | 3.4 MB |
| `git-log-50.txt` | Letzte 50 Commits | < 1 KB |
| `git-status.txt` | Git Status zum Zeitpunkt | < 1 KB |
| `git-unstaged.patch` | Unstaged Änderungen | 0 B |
| `git-staged.patch` | Staged Änderungen | 0 B |
| `RESTORE.sh` | Wiederherstellungs-Script | < 1 KB |

**Gesamt:** ~35 MB

---

## Wichtige Änderungen seit letztem Backup

### Features (Januar 2026)

1. **Webhook Alert System**
   - Semantische Fehlererkennung (HTTP 200 + Error in Body)
   - E-Mail-Benachrichtigungen bei Webhook-Fehlern
   - Claude Debug Prompts in E-Mails
   - Datei: `app/Notifications/WebhookDeliveryFailedNotification.php`

2. **Partner Billing System**
   - Partner-Rechnungs-E-Mail-Template
   - Tax Compliance (USt-IdNr, W-IdNr)
   - CC-E-Mail-Funktion
   - Dateien: `app/Services/Billing/StripeInvoicingService.php`

3. **Service Gateway Improvements**
   - State-of-the-Art UI für Exchange Logs
   - Retry-Statistiken
   - Fehler-Kategorisierung (semantic/http/exception)

4. **VisionaryData Integration**
   - HMAC-signierte Webhooks
   - ticket_id Extraktion gehärtet
   - Backup-E-Mail an ticket-support@visionarydata.de

### Letzte 10 Commits

```
02abf1262 chore: Add env.testing and Serena memories
529aa11f5 feat: Golden Backup - Webhook Alerts, Partner Billing & Service Gateway
e20759238 style(email): Lighter webhook error email matching ticket design
01a84867a fix(filament): Sections default to expanded (open) state
46a08b51b fix(filament): Use explicit collapsed(true) for sections
19f4cde24 fix(filament): Dark mode & collapsed sections for ExchangeLog detail view
24d79b8a4 style(service-gateway): State-of-the-art UI/UX for webhook monitoring
ae0df58d8 feat(service-gateway): Semantic error detection and email alerts
346baa3c2 feat(filament): Partner UI improvements for Company list
5c88fbfab feat(filament): Add ManagedCompaniesRelationManager for partners
```

---

## Datenbank-Info

| Feld | Wert |
|------|------|
| **Datenbank** | `askproai_db` |
| **Host** | `127.0.0.1` |
| **Benutzer** | `askproai_user` |
| **Engine** | MySQL/MariaDB |

---

## Wiederherstellung

### Schnellstart

```bash
cd /var/www/api-gateway/backups/golden-backup-2026-01-13
chmod +x RESTORE.sh
./RESTORE.sh
```

### Manuell

**Nur Code:**
```bash
cd /var/www/api-gateway
tar -xzf backups/golden-backup-2026-01-13/code-complete.tar.gz
composer install
npm install
php artisan migrate
```

**Nur Datenbank:**
```bash
gunzip -c database-complete.sql.gz | mysql -u askproai_user -p askproai_db
```

**Vollständig:**
```bash
./RESTORE.sh full
```

---

## Verifizierung

Nach Wiederherstellung prüfen:

- [ ] `php artisan migrate:status` - Alle Migrationen angewendet
- [ ] `php artisan config:cache` - Konfiguration funktioniert
- [ ] `php artisan route:list` - Routes geladen
- [ ] Filament Admin erreichbar: `/admin`
- [ ] Webhook-Test: Exchange Log erstellen

---

## Wichtige Dateien im Backup

### Notifications
- `app/Notifications/WebhookDeliveryFailedNotification.php`

### Services
- `app/Services/Billing/StripeInvoicingService.php`
- `app/Services/ServiceGateway/ResponseBodyAnalyzer.php`

### Models
- `app/Models/Company.php` (Partner Billing)
- `app/Models/Invoice.php`
- `app/Models/AggregateInvoice.php`

### Views
- `resources/views/emails/webhook-delivery-failed.blade.php`
- `resources/views/emails/partner-invoice.blade.php`

### Tests
- `tests/Unit/ServiceGateway/ResponseBodyAnalyzerTest.php`
- `tests/Unit/Models/CompanyPartnerRelationshipTest.php`

---

## Kontakt

Bei Fragen zum Backup:
- Repository: https://github.com/fabianSp77/askproai-api
- Erstellt: 2026-01-13 von Claude Code

---

*Dieses Backup wurde automatisch erstellt und verifiziert.*
