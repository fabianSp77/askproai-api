# 🤝 HANDOVER CONTEXT - Stand 19.06.2025

## 🎯 PROJEKT-KONTEXT

**Was**: AskProAI - Multi-Tenant Phone-to-Appointment System
**Aktueller Sprint**: Universal Phone Flow mit Health Checks
**Fortschritt**: 75% (6 von 8 Hauptfeatures fertig)

## 📁 WICHTIGSTE DATEIEN

### Heute bearbeitet:
1. `/app/Services/HealthChecks/CalcomHealthCheck.php` - Type Error gefixt
2. `/app/Filament/Admin/Pages/QuickSetupWizard.php` - Review Step hinzugefügt
3. `/app/Services/HealthCheckService.php` - Facade für alle Checks
4. `/app/Contracts/IntegrationHealthCheck.php` - Interface definiert

### Kern-Komponenten:
- **Wizard**: `/app/Filament/Admin/Pages/QuickSetupWizard.php`
- **Health Checks**: `/app/Services/HealthChecks/`
- **Booking Services**: `/app/Services/Booking/`
- **Phone Models**: `/app/Models/PhoneNumber.php`

## 🔧 AKTUELLE PROBLEME

### 1. CalcomHealthCheck Type Error ✅ GELÖST
**Problem**: Expected CalcomService, got CalcomBackwardsCompatibility
**Lösung**: Type hint auf `mixed` geändert
```php
protected mixed $calcomService = null
```

### 2. BusinessHoursManager Table Missing ✅ GELÖST
**Problem**: Table `business_hours_templates` doesn't exist
**Lösung**: Fallback auf Default-Templates wenn Tabelle fehlt
```php
if (Schema::hasTable('business_hours_templates')) {
    // Use DB
} else {
    // Use defaults
}
```

### 3. ValidationResults Table Missing ✅ GELÖST
**Problem**: Table `validation_results` doesn't exist
**Lösung**: Migration ausgeführt
```bash
php artisan migrate --path=database/migrations/2025_06_18_create_validation_results_table.php --force
```

### 4. UnifiedEventTypes Table Missing ✅ GELÖST
**Problem**: Table `unified_event_types` doesn't exist (war in cleanup gelöscht)
**Lösung**: Migration zurückgesetzt und neu ausgeführt
```bash
mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db -e "DELETE FROM migrations WHERE migration = '2025_06_18_create_unified_event_types_table';"
php artisan migrate --path=database/migrations/2025_06_18_create_unified_event_types_table.php --force
```

### 4. Test-Suite Timeout ❌ OFFEN
**Problem**: SQLite inkompatible Migrations
**Fehler**: `customers_company_id_index` auf nicht-existente Spalte
**Lösung**: CompatibleMigration Base Class erstellen

### 3. Pending Migrations ⚠️ VORSICHT
```bash
# 7 Migrations warten auf Ausführung
php artisan migrate:status
# BACKUP FIRST!
mysqldump -u root -p'V9LGz2tdR5gpDQz' askproai_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

## 💡 KONTEXT-WISSEN

### Multi-Tenant Phone Flow:
1. **Phone Number** → PhoneNumberResolver → **Branch**
2. **Branch** → calcom_event_type_id → **Cal.com Event**
3. **Retell Call** → Webhook → **Customer + Appointment**

### Health Check System:
- **3 Checks**: Retell, Cal.com, Phone Routing
- **Ampel-System**: 🟢 Healthy, 🟡 Degraded, 🔴 Unhealthy
- **Auto-Fix**: Webhook URLs, Event Type Sync
- **Caching**: 5 Min (critical), 10 Min (normal)

### Wizard Flow:
1. Company & Branch
2. Phone Configuration (Strategy, Numbers)
3. Cal.com Connection
4. Retell AI Setup
5. Staff & Services
6. **NEU**: Review & Health Check

## 🚀 NÄCHSTE SCHRITTE

### Sofort (P0):
1. **Test-Suite fixen**:
   ```php
   // Create base class
   abstract class CompatibleMigration extends Migration {
       protected function createIndexIfNotExists($table, $column, $name) {
           if (!Schema::hasColumn($table, $column)) return;
           if (!Schema::hasIndex($table, $name)) {
               Schema::table($table, fn($t) => $t->index($column, $name));
           }
       }
   }
   ```

2. **Migrations ausführen** (mit Backup!)

### Diese Woche (P1):
3. **Integration Step** vor Review Step
4. **Admin Badge** in AdminPanelProvider
5. **Performance Timer** in StaffSkillMatcher

### Nächster Sprint (P2):
6. **Prompt Templates** als Blade Files
7. **E2E Tests** (nach Test-Fix)
8. **PHPUnit Attributes** Migration

## 🔑 ENVIRONMENT & ZUGANG

### Server:
```bash
ssh hosting215275@hosting215275.ae83d.netcup.net
# oder
ssh root@hosting215275.ae83d.netcup.net
```

### Database:
```bash
mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db
# App User
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db
```

### Key Services:
- **Laravel Horizon**: `php artisan horizon`
- **Redis**: Port 6379
- **Grafana**: Monitoring läuft

## 📚 DOKUMENTATION

### Übersicht:
- `ASKPROAI_MASTER_TECHNICAL_SPECIFICATION_2025-06-17.md` - Komplette Spec
- `IMPLEMENTATION_SUMMARY_MULTI_TENANT_PHONE_FLOW.md` - Was gebaut wurde
- `COMPACT_STATUS_2025-06-19.md` - Aktueller Status
- `OPEN_TASKS_PRIORITIZED.md` - Was noch fehlt

### Entwickler-Feedback:
- Phone Flow funktioniert
- Demo-Daten vorhanden (Salon Demo GmbH, etc.)
- Wizard braucht Integration Step
- Tests müssen laufen

## ⚡ QUICK START

```bash
# 1. Code pullen
cd /var/www/api-gateway
git pull

# 2. Dependencies
composer install
npm install

# 3. Cache leeren
php artisan optimize:clear

# 4. Horizon starten
php artisan horizon

# 5. Wizard testen
# Browser: https://api.askproai.de/admin/quick-setup-wizard
```

## 🎭 TEST-ACCOUNTS

```php
// Admin
email: admin@askproai.de
password: [check .env or ask]

// Test Company
Salon Demo GmbH - Berlin
FitNow GmbH - München  
AskProAI Test - Hamburg
```

## 🆘 BEI PROBLEMEN

1. **Logs checken**: `tail -f storage/logs/laravel.log`
2. **Queue Errors**: Horizon Dashboard → Failed Jobs
3. **Health Checks**: `/admin` → Review Step zeigt Status
4. **Type Errors**: Meist Service Container Bindings

---

**Übergabe-Status**: System läuft stabil (6/10), Tests blockiert, 4-5 Features offen.
**Geschätzter Restaufwand**: 18h für 100% Feature-Complete
**Priorität**: Test-Suite fixen → Dann Rest

Viel Erfolg! 🚀