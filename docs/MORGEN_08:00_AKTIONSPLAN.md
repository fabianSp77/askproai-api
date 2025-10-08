# Morgen 08:00 - Aktionsplan: Test Setup mit Laravel Schema-Dump

**Datum**: 2025-10-03 08:00-10:00
**Ziel**: Tests lauffähig machen für Tag 4 Start
**Methode**: Option C+ - Laravel Schema-Dump

---

## Phase 1: Schema-Dump erstellen (08:00-08:30)

### Schritt 1: Production Schema exportieren
```bash
# Auf Production Server (oder lokal mit DB-Zugriff)
mysqldump -u root -p askproai_db \
  --no-data \
  --skip-add-drop-table \
  --skip-comments \
  --compact \
  > /tmp/askproai_schema.sql
```

**Erwartetes Ergebnis**: SQL-Datei mit ~100 CREATE TABLE statements

### Schritt 2: Schema in Laravel-Format konvertieren
```bash
# Download schema
cp /tmp/askproai_schema.sql database/schema/mysql-schema.sql

# Optional: Cleanup (entfernen problematischer tables falls vorhanden)
# Entfernen: migrations table (wird automatisch erstellt)
sed -i '/CREATE TABLE `migrations`/,/ENGINE=InnoDB/d' database/schema/mysql-schema.sql
```

**Erwartetes Ergebnis**: `database/schema/mysql-schema.sql` bereit

---

## Phase 2: Test-Datenbank einrichten (08:30-08:45)

### Schritt 3: MySQL Test-DB erstellen
```bash
# Lokale Test-DB erstellen
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS askproai_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON askproai_testing.* TO 'root'@'localhost';"
```

### Schritt 4: .env.testing konfigurieren
```bash
# .env.testing erstellen
cat > .env.testing << 'EOF'
APP_ENV=testing
APP_DEBUG=true
APP_KEY=base64:YOUR_KEY_HERE

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_testing
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
EOF

# APP_KEY generieren falls nötig
php artisan key:generate --env=testing
```

**Erwartetes Ergebnis**: Test-DB existiert, .env.testing konfiguriert

---

## Phase 3: RefreshDatabase mit Schema-Dump testen (08:45-09:00)

### Schritt 5: Schema laden + Migrations testen
```bash
# Test ob Schema-Dump funktioniert
php artisan test tests/Feature/ConfigurationHierarchyTest.php::it_resolves_company_level_policy --env=testing

# Was passiert:
# 1. RefreshDatabase lädt database/schema/mysql-schema.sql (2 Sekunden)
# 2. Führt nur neue Migrations aus (Tag 1-3 = 7 migrations, ~3 Sekunden)
# 3. Total: ~5 Sekunden pro Test statt >2 Minuten
```

**Erwartetes Ergebnis**: Test läuft in <10 Sekunden

### Schritt 6: Alle Tests ausführen
```bash
# Alle 20 Tests
php artisan test tests/Feature/ConfigurationHierarchyTest.php --env=testing
php artisan test tests/Unit/PolicyConfigurationServiceTest.php --env=testing

# ODER beide zusammen
php artisan test --testsuite=PolicyConfiguration --env=testing
```

**Erwartetes Ergebnis**:
```
PASS  Tests\Feature\ConfigurationHierarchyTest
✓ it resolves company level policy
✓ it resolves branch inherits from company
... (13 tests total)

PASS  Tests\Unit\PolicyConfigurationServiceTest
✓ it generates unique cache keys
✓ it handles null parent gracefully
... (7 tests total)

Tests:  20 passed
Time:   15s
```

---

## Phase 4: Cleanup & Dokumentation (09:00-09:15)

### Schritt 7: TestCase.php anpassen (falls nötig)
```php
// tests/TestCase.php
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    // WICHTIG: RefreshDatabase trait in einzelnen Tests verwenden
    // TestCase selbst braucht nichts spezielles
}
```

### Schritt 8: Test-Dateien säubern
```bash
# Entfernen der manuellen setUp() migrations aus Tests
# Tests sollten NUR RefreshDatabase trait verwenden
```

**In ConfigurationHierarchyTest.php UND PolicyConfigurationServiceTest.php:**
```php
class ConfigurationHierarchyTest extends TestCase
{
    use RefreshDatabase; // Das reicht!

    private PolicyConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PolicyConfigurationService();
        Cache::flush();
    }

    // Alle manuellen migrate:fresh und migrate calls ENTFERNEN
}
```

---

## Fallback-Plan (falls Schema-Dump Probleme macht)

### Alternative A: Schema manuell minimieren
Falls Schema-Dump zu groß oder Probleme:
```bash
# Nur benötigte Tabellen exportieren
mysqldump -u root -p askproai_db \
  companies branches services staff \
  customers appointments \
  --no-data > database/schema/mysql-schema-minimal.sql
```

### Alternative B: SQLite Fallback (LAST RESORT)
Falls MySQL lokal nicht verfügbar:
```bash
# .env.testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Problem: MySQL vs SQLite compatibility issues
# Nur wenn absolut kein MySQL verfügbar
```

---

## Erfolgskriterien (09:15 - CHECKPOINT)

✅ **MUSS erfüllt sein vor Tag 4 Start:**
1. `php artisan test --testsuite=PolicyConfiguration --env=testing` läuft
2. Alle 20 Tests sind GREEN
3. Test-Execution Zeit: <30 Sekunden (nicht >2 Minuten)
4. Keine Migration-Timeouts
5. RefreshDatabase funktioniert korrekt

❌ **Falls NICHT erfüllt:**
- STOP Tag 4 Start
- Problem dokumentieren
- User informieren
- Neue Lösung finden

---

## Tag 4 Start: 10:00 (falls Tests funktionieren)

**Nach erfolgreichem Test-Setup:**

```bash
# Bestätigung
echo "✅ Tests laufen: $(php artisan test --testsuite=PolicyConfiguration --env=testing | grep 'Tests:')"

# Tag 4 kann starten
# Focus: AppointmentPolicyEngine
# - canCancel() implementation
# - canReschedule() implementation
# - calculateFee() implementation
# - EXTRA REVIEW (CRITICAL component)
```

---

## Zeitplan Morgen

| Zeit | Task | Erwartete Dauer |
|------|------|-----------------|
| 08:00-08:30 | Schema-Dump erstellen | 30min |
| 08:30-08:45 | Test-DB einrichten | 15min |
| 08:45-09:00 | RefreshDatabase testen | 15min |
| 09:00-09:15 | Cleanup & Dokumentation | 15min |
| 09:15-09:30 | Checkpoint: Tests GREEN? | 15min |
| **09:30-10:00** | **Buffer / Problembehebung** | **30min** |
| **10:00** | **TAG 4 START** | - |

**Total Setup-Zeit**: 1h 30min (mit Buffer)

**Buffer-Usage**: 3.5h von 48h = akzeptabel

---

## Wichtige Dateien für morgen

### Bereit für Tag 4:
- ✅ app/Services/Policies/PolicyConfigurationService.php (242 lines)
- ✅ tests/Feature/ConfigurationHierarchyTest.php (20 tests)
- ✅ tests/Unit/PolicyConfigurationServiceTest.php (7 tests)
- ✅ All 7 Tag 1 migrations

### Zu erstellen morgen:
- 📝 database/schema/mysql-schema.sql (Schema-Dump)
- 📝 .env.testing (Test-Konfiguration)
- 📝 askproai_testing (MySQL Test-DB)

---

**Version**: 1.0
**Erstellt**: 2025-10-02 06:07
**Nächster Check**: 2025-10-03 09:15 (Test-Checkpoint)
