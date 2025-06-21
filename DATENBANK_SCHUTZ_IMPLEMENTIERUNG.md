# 🛡️ Datenbank-Schutz Implementierung für AskProAI

## Übersicht
Ich habe ein umfassendes Datenbank-Schutzsystem implementiert, um zukünftige Datenverluste zu verhindern.

## 1. **Automatische Backups** 
```bash
# Tägliches vollständiges Backup (2:00 Uhr)
0 2 * * * php /var/www/api-gateway/artisan askproai:backup --type=full --compress --encrypt

# Stündliches inkrementelles Backup
0 * * * * php /var/www/api-gateway/artisan askproai:backup --type=incremental

# Kritische Daten alle 6 Stunden
0 */6 * * * php /var/www/api-gateway/artisan askproai:backup --type=critical --compress
```

### Backup-Typen:
- **Full**: Komplette Datenbank mit allen Tabellen
- **Incremental**: Nur geänderte Daten seit letztem Backup
- **Critical**: Nur geschäftskritische Tabellen (Kunden, Termine, etc.)

### Features:
- ✅ Automatische Komprimierung (gzip)
- ✅ Verschlüsselung (AES-256)
- ✅ Retention Policy (30 Tage Standard)
- ✅ Automatische Bereinigung alter Backups
- ✅ Backup-Verifizierung
- ✅ Fehler-Benachrichtigungen

## 2. **Sichere Migrationen**
```bash
# Statt: php artisan migrate
# Nutze: php artisan migrate:safe --backup

# Features:
- Automatisches Backup vor Migration
- Erkennung destruktiver Operationen
- Rollback bei Fehlern
- Dry-Run Modus
```

## 3. **Migration Guard**
Verhindert versehentliches Löschen wichtiger Tabellen:

### Geschützte Tabellen:
- users
- companies
- branches
- customers
- appointments
- calls
- staff
- services
- calcom_event_types
- migrations

### Schutz-Mechanismen:
1. **Pre-Migration Validation**: Prüft Migrationen auf gefährliche Operationen
2. **Safety Backups**: Automatische Backups vor kritischen Operationen
3. **Table Protection**: SQL Trigger verhindern versehentliches Löschen

## 4. **Best Practices für Entwickler**

### ❌ NIEMALS:
```php
// GEFÄHRLICH - Niemals in Production!
Schema::dropIfExists('customers');
DB::table('appointments')->truncate();
DB::statement('DROP TABLE users');
```

### ✅ STATTDESSEN:
```php
// Soft Deletes verwenden
Schema::table('customers', function ($table) {
    $table->softDeletes();
});

// Archivierung statt Löschen
Schema::create('customers_archive', function ($table) {
    // Archive alte Daten statt sie zu löschen
});

// Explizite Bestätigung für gefährliche Operationen
if ($this->confirm('Wirklich alle Testdaten löschen?')) {
    // Operation
}
```

## 5. **Monitoring & Alerts**

### Backup-Status prüfen:
```bash
php artisan askproai:backup-status
```

### Letzte Backups anzeigen:
```sql
SELECT * FROM backup_logs 
ORDER BY created_at DESC 
LIMIT 10;
```

## 6. **Recovery-Prozedur**

### Bei Datenverlust:
1. **Sofort stoppen**: Keine weiteren Änderungen
2. **Letztes Backup identifizieren**:
   ```bash
   ls -la /var/www/api-gateway/storage/backups/database/
   ```
3. **Backup wiederherstellen**:
   ```bash
   # Entschlüsseln (falls verschlüsselt)
   openssl enc -d -aes-256-cbc -in backup.sql.gz.enc -out backup.sql.gz -k [APP_KEY]
   
   # Entpacken
   gunzip backup.sql.gz
   
   # Wiederherstellen
   mysql -u root -p askproai_db < backup.sql
   ```

## 7. **Cron-Jobs einrichten**
```bash
# Crontab öffnen
crontab -e

# Folgende Zeilen hinzufügen:
# Tägliches Backup um 2 Uhr
0 2 * * * cd /var/www/api-gateway && php artisan askproai:backup --type=full --compress --encrypt >> /var/log/askproai-backup.log 2>&1

# Stündliches inkrementelles Backup
0 * * * * cd /var/www/api-gateway && php artisan askproai:backup --type=incremental >> /var/log/askproai-backup.log 2>&1

# Kritische Daten alle 6 Stunden
0 */6 * * * cd /var/www/api-gateway && php artisan askproai:backup --type=critical --compress >> /var/log/askproai-backup.log 2>&1

# Backup-Monitoring täglich
30 8 * * * cd /var/www/api-gateway && php artisan askproai:backup-monitor >> /var/log/askproai-backup.log 2>&1
```

## 8. **Sofort-Maßnahmen**

1. **Migration ausführen**:
   ```bash
   php artisan migrate --force
   ```

2. **Erstes manuelles Backup**:
   ```bash
   php artisan askproai:backup --type=full --compress --encrypt
   ```

3. **Cron-Jobs aktivieren**:
   ```bash
   sudo service cron restart
   ```

## 9. **Zusätzliche Empfehlungen**

1. **Externe Backups**: Kopiere Backups auf externen Server/S3
2. **Backup-Tests**: Monatlich Recovery-Test durchführen
3. **Dokumentation**: Alle Datenbank-Änderungen dokumentieren
4. **Access Control**: Nur autorisierte Personen für Migrationen
5. **Change Log**: Alle Schema-Änderungen in CHANGELOG.md

---

**Diese Maßnahmen verhindern zukünftige Datenverluste wie den vom 17. Juni 2025!**