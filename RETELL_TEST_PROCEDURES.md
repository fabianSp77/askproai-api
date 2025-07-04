# 🧪 Retell.ai Test-Prozeduren

## Übersicht
Dieses Dokument definiert standardisierte Test-Prozeduren für die Retell.ai Integration, um sicherzustellen, dass nach Änderungen alles weiterhin funktioniert.

## 🔍 Test-Kategorien

### 1. Unit Tests (Komponenten-Tests)
- **Data Extraction Test**: Prüft ob alle Felder korrekt extrahiert werden
- **Timestamp Parsing Test**: Testet verschiedene Timestamp-Formate
- **Branch Resolution Test**: Testet Phone-Number-zu-Branch Zuordnung

### 2. Integration Tests (End-to-End)
- **Webhook Reception Test**: Testet ob Webhooks ankommen
- **Database Write Test**: Prüft ob Calls in DB geschrieben werden
- **Queue Processing Test**: Testet asynchrone Verarbeitung

### 3. Live Tests (Production)
- **Real Call Test**: Echter Anruf mit Monitoring
- **Webhook Monitoring**: Live-Überwachung eingehender Webhooks
- **Performance Test**: Latenz und Durchsatz messen

## 📋 Standard Test-Prozedur

### VOR jeder Änderung:

#### 1. Baseline erstellen
```bash
# Aktuelle Funktionalität dokumentieren
./retell-backup-restore.sh backup
php test-retell-real-data.php > tests/baseline_$(date +%Y%m%d_%H%M%S).txt

# Status prüfen
php artisan horizon:status
php retell-health-check.php
```

#### 2. Metriken erfassen
```sql
-- Anzahl Calls heute
SELECT COUNT(*) as total_calls, 
       SUM(CASE WHEN call_status = 'ended' THEN 1 ELSE 0 END) as completed,
       SUM(CASE WHEN call_status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
FROM calls 
WHERE DATE(created_at) = CURDATE();

-- Durchschnittliche Verarbeitungszeit
SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_processing_time
FROM calls
WHERE created_at >= NOW() - INTERVAL 1 HOUR;
```

### NACH Änderungen:

#### 1. Component Test Suite
```bash
# Test 1: Data Extraction
php -r "
require 'vendor/autoload.php';
\$data = ['call_id' => 'test123', 'duration_ms' => 60000];
\$result = \App\Helpers\RetellDataExtractor::extractCallData(\$data);
assert(\$result['duration_sec'] === 60);
echo 'Data Extraction: ✅ PASS\n';
"

# Test 2: Webhook Controller
curl -X POST https://api.askproai.de/api/retell/webhook-simple \
  -H "Content-Type: application/json" \
  -d '{
    "event": "call_started",
    "call": {
      "call_id": "test_'$(date +%s)'",
      "from_number": "+491234567890",
      "to_number": "+4917636251546"
    }
  }'

# Test 3: Branch Resolution
php artisan tinker --execute="
\$branch = \App\Models\Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('phone_number', 'LIKE', '%7636251546%')
    ->first();
echo \$branch ? 'Branch found: ' . \$branch->name : 'No branch found';
"
```

#### 2. Integration Test
```bash
# Vollständiger Test mit echten Daten
php test-retell-real-data.php

# Vergleiche mit Baseline
diff tests/baseline_*.txt tests/current_test.txt
```

#### 3. Monitoring aktivieren
```bash
# Live Log Monitoring
tail -f storage/logs/laravel.log | grep -E "(Retell|webhook|call_)"

# In separatem Terminal: Queue Monitor
watch -n 5 'php artisan queue:monitor'

# In drittem Terminal: Database Monitor
watch -n 10 'mysql -u askproai_user -p"lkZ57Dju9EDjrMxn" askproai_db -e "SELECT call_id, call_status, created_at FROM calls ORDER BY created_at DESC LIMIT 5"'
```

## 🚨 Smoke Test (Schnelltest)

```bash
#!/bin/bash
# smoke-test.sh

echo "🔥 Retell.ai Smoke Test"
echo "======================="

# 1. Route exists?
if grep -q "webhook-simple" routes/api.php; then
    echo "✅ Route exists"
else
    echo "❌ Route missing!"
    exit 1
fi

# 2. Controller exists?
if [ -f "app/Http/Controllers/Api/RetellWebhookWorkingController.php" ]; then
    echo "✅ Controller exists"
else
    echo "❌ Controller missing!"
    exit 1
fi

# 3. Can connect to DB?
php artisan db:show > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Database connection OK"
else
    echo "❌ Database connection failed!"
    exit 1
fi

# 4. Horizon running?
php artisan horizon:status | grep -q "Horizon is running"
if [ $? -eq 0 ]; then
    echo "✅ Horizon is running"
else
    echo "⚠️  Horizon not running (queues won't process)"
fi

echo ""
echo "Smoke test completed!"
```

## 📊 Performance Benchmarks

### Erwartete Werte:
- **Webhook Response Time**: < 200ms
- **Data Extraction Time**: < 50ms
- **Database Write Time**: < 100ms
- **Total Processing Time**: < 500ms

### Performance Test:
```php
// performance-test.php
$iterations = 100;
$times = [];

for ($i = 0; $i < $iterations; $i++) {
    $start = microtime(true);
    
    // Simuliere Webhook
    $testData = generateTestData();
    $extracted = \App\Helpers\RetellDataExtractor::extractCallData($testData);
    
    $end = microtime(true);
    $times[] = ($end - $start) * 1000; // Convert to ms
}

$avg = array_sum($times) / count($times);
$max = max($times);
$min = min($times);

echo "Performance Results ($iterations iterations):\n";
echo "Average: " . round($avg, 2) . "ms\n";
echo "Max: " . round($max, 2) . "ms\n";
echo "Min: " . round($min, 2) . "ms\n";
```

## 🔄 Rollback-Prozedur

Falls Tests fehlschlagen:

```bash
# 1. Sofort rollback
./retell-backup-restore.sh restore [TIMESTAMP]

# 2. Cache leeren
php artisan optimize:clear

# 3. Services neustarten
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx

# 4. Verify rollback
php test-retell-real-data.php
```

## 📝 Test-Checkliste

### Vor Deployment:
- [ ] Backup erstellt mit `./retell-backup-restore.sh backup`
- [ ] Baseline Test durchgeführt
- [ ] Alle Unit Tests bestanden
- [ ] Integration Test erfolgreich
- [ ] Performance innerhalb der Benchmarks
- [ ] Keine Fehler in Logs

### Nach Deployment:
- [ ] Smoke Test bestanden
- [ ] Live Monitoring für 30 Minuten
- [ ] Mindestens 5 echte Calls verarbeitet
- [ ] Keine Fehler in Production Logs
- [ ] Performance Metriken normal

### Bei Problemen:
- [ ] Rollback durchgeführt
- [ ] Root Cause dokumentiert
- [ ] Fix in Test-Umgebung validiert
- [ ] Erneuter Deployment-Versuch

## 🔧 Debug-Tools

### Log Analysis:
```bash
# Retell-spezifische Logs
grep -i retell storage/logs/laravel.log | tail -50

# Fehler der letzten Stunde
grep "ERROR" storage/logs/laravel.log | grep "$(date +'%Y-%m-%d %H')"

# Webhook Events
grep "webhook" storage/logs/laravel.log | grep -v "health"
```

### Database Queries:
```sql
-- Calls ohne Company ID (Problem!)
SELECT * FROM calls WHERE company_id IS NULL;

-- Duplicate Calls
SELECT call_id, COUNT(*) as count 
FROM calls 
GROUP BY call_id 
HAVING count > 1;

-- Failed Webhooks
SELECT * FROM webhook_events 
WHERE status = 'failed' 
AND provider = 'retell'
ORDER BY created_at DESC;
```

## 📌 Wichtige Kontakte

- **Retell Support**: support@retellai.com
- **Webhook URL**: https://api.askproai.de/api/retell/webhook-simple
- **Dashboard**: https://beta.retellai.com/dashboard

---

**REMEMBER**: Immer testen bevor Änderungen in Production gehen!