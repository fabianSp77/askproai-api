# Performance Critical Indexes Migration

## Overview
Diese Migration fügt alle fehlenden Performance-kritischen Indizes zur Datenbank hinzu. Die Indizes wurden basierend auf einer Analyse der häufigsten Query-Patterns in der Anwendung identifiziert.

## Warum diese Indizes wichtig sind

### 1. Tenant Isolation (company_id)
- **Grund**: Fast jede Query verwendet `company_id` aufgrund des TenantScope
- **Auswirkung**: Ohne Index würde jede Query einen Full Table Scan durchführen
- **Betroffene Tabellen**: Alle tenant-spezifischen Tabellen

### 2. Zeitbasierte Queries
- **Grund**: Viele Features filtern nach Datum/Zeit (heute, kommende Termine, etc.)
- **Auswirkung**: Sortierung und Filterung nach Zeit ist extrem häufig
- **Wichtige Felder**: `starts_at`, `ends_at`, `created_at`

### 3. Phone Number Lookups
- **Grund**: Kundenidentifikation bei eingehenden Anrufen
- **Auswirkung**: Ohne Index müsste bei jedem Anruf die gesamte customers Tabelle durchsucht werden
- **Felder**: `phone` in customers, `from_number`/`to_number` in calls

### 4. Status Filtering
- **Grund**: Viele Views filtern nach Status (bestätigt, abgesagt, etc.)
- **Auswirkung**: Häufig in Kombination mit anderen Filtern verwendet
- **Felder**: `status` in appointments und calls

### 5. Composite Indexes
- **Grund**: Optimierung für häufige Query-Kombinationen
- **Beispiele**:
  - `(company_id, starts_at)` - Termine eines Unternehmens nach Zeit
  - `(status, starts_at)` - Bestätigte zukünftige Termine
  - `(company_id, phone)` - Duplicate Detection

## Migration ausführen

```bash
# Migration ausführen
php artisan migrate

# Bei großen Datenbanken mit --force
php artisan migrate --force

# Performance vorher/nachher analysieren
php artisan askproai:analyze-performance
```

## Performance-Verbesserungen

### Erwartete Verbesserungen:
- **Appointment Listings**: 50-90% schneller
- **Customer Phone Lookup**: 80-95% schneller
- **Dashboard Statistics**: 60-80% schneller
- **Call History**: 70-85% schneller

### Speicherplatz:
- Die Indizes benötigen zusätzlichen Speicherplatz (ca. 20-30% der Tabellengröße)
- Bei 100k Appointments: ~10-15 MB zusätzlich
- Bei 1M Calls: ~100-150 MB zusätzlich

## Monitoring

Nach der Migration sollten Sie folgende Metriken überwachen:

1. **Query Performance**:
   ```sql
   -- Slow Queries überwachen
   SHOW VARIABLES LIKE 'slow_query_log';
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 1;
   ```

2. **Index Usage**:
   ```sql
   -- Prüfen ob Indizes verwendet werden
   EXPLAIN SELECT * FROM appointments 
   WHERE company_id = 1 
   AND starts_at > NOW() 
   AND status = 'confirmed';
   ```

3. **Table Statistics**:
   ```bash
   # Mit dem Analyze Command
   php artisan askproai:analyze-performance --show-queries
   ```

## Rollback

Falls Probleme auftreten:

```bash
# Migration rückgängig machen
php artisan migrate:rollback

# Nur diese spezifische Migration
php artisan migrate:rollback --step=1
```

## Best Practices

1. **Führen Sie die Migration außerhalb der Hauptgeschäftszeiten aus**
   - Index-Erstellung kann bei großen Tabellen Zeit in Anspruch nehmen

2. **Backup vor der Migration**
   ```bash
   php artisan askproai:backup --type=full
   ```

3. **Überwachen Sie die Performance nach der Migration**
   - Stellen Sie sicher, dass die Indizes wie erwartet funktionieren

4. **Regelmäßige Wartung**
   ```sql
   -- Statistiken aktualisieren
   ANALYZE TABLE appointments;
   ANALYZE TABLE calls;
   ANALYZE TABLE customers;
   ```

## Troubleshooting

### Migration dauert zu lange
- Bei sehr großen Tabellen kann die Index-Erstellung Zeit benötigen
- Verwenden Sie `pt-online-schema-change` für Zero-Downtime-Migrationen

### Duplicate Key Errors
- Die Migration prüft bereits existierende Indizes
- Falls dennoch Fehler auftreten, prüfen Sie manuell mit `SHOW INDEX FROM table_name`

### Performance verschlechtert sich
- Prüfen Sie mit EXPLAIN ob die richtigen Indizes verwendet werden
- Möglicherweise müssen Query Hints angepasst werden

## Weitere Optimierungen

Nach dieser Migration können Sie weitere Optimierungen in Betracht ziehen:

1. **Partitionierung**: Für sehr große Tabellen (>10M Zeilen)
2. **Read Replicas**: Für Read-Heavy Workloads
3. **Query Cache**: Für häufig identische Queries
4. **Denormalisierung**: Für komplexe Aggregationen