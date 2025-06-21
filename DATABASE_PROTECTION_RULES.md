# 🛡️ Datenbank-Schutz Regeln für AskProAI

## 1. **Niemals Tabellen löschen ohne Backup**
```bash
# IMMER vorher:
mysqldump -u root -p askproai_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

## 2. **Gefährliche Migrations doppelt prüfen**
```php
// In der Migration IMMER eine Sicherheitsabfrage:
public function up()
{
    if (app()->environment('production')) {
        throw new Exception('Diese Migration darf nicht in Production laufen!');
    }
    
    // Oder mit Bestätigung:
    if (!$this->confirm('Willst du wirklich Tabelle X löschen?')) {
        return;
    }
}
```

## 3. **Automatische tägliche Backups**
```bash
# Crontab eintrag (bereits aktiv):
0 2 * * * /var/www/api-gateway/scripts/backup.sh
```

## 4. **Vor jeder Migration**
```bash
# Backup-Script erstellen:
php artisan make:command BackupBeforeMigration

# In der Command:
$this->info('Erstelle Backup...');
exec('mysqldump -u root -p askproai_db > pre_migration_' . date('Y-m-d_H-i-s') . '.sql');
```

## 5. **Niemals "cleanup" Migrations ohne Review**
- Alle Migrations mit DROP, TRUNCATE oder DELETE brauchen Review
- Nutze --pretend Flag: `php artisan migrate --pretend`
- Teste IMMER erst auf Staging

## 6. **Recovery Plan**
1. Tägliche Backups behalten (min. 30 Tage)
2. Vor großen Änderungen manuelles Backup
3. Backup-Test monatlich durchführen
4. Recovery-Prozedur dokumentiert halten

## 7. **Migration Namenskonvention**
```
❌ SCHLECHT: cleanup_redundant_tables
✅ GUT: DESTRUCTIVE_drop_unused_legacy_tables_NEEDS_REVIEW
```

## 8. **Schutz in Production**
```php
// In AppServiceProvider:
if (app()->environment('production')) {
    DB::statement("SET sql_safe_updates = 1");
}
```

## 9. **Audit Trail**
```php
// Für kritische Tabellen:
Schema::table('appointments', function ($table) {
    $table->softDeletes(); // Niemals hart löschen!
});
```

## 10. **Team-Regeln**
- Keine DROP/TRUNCATE ohne Team-Review
- Backup-Verantwortlicher benennen
- Monatliche Backup-Tests
- Incident-Response-Plan

---

**Diese Regeln hätten den Datenverlust verhindert!**