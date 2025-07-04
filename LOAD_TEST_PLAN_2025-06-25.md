# 🚀 LOAD TESTING PLAN
**Stand: 25.06.2025 22:50 Uhr**

## 📊 LOAD TEST SCRIPT ERSTELLT

### Features des Load Test Scripts:
- ✅ Multi-Threading für echte Concurrent Users
- ✅ Gewichtete Szenarien (realistische Last-Verteilung)
- ✅ Response Time Tracking (P50, P95, P99)
- ✅ Automatische Performance-Bewertung
- ✅ Detaillierte JSON Reports

---

## 🧪 TEST SZENARIEN

### 1. **Health Check** (20% Last)
```http
GET /api/health
```
- Einfacher Endpoint
- Sollte < 50ms antworten

### 2. **List Agents** (30% Last)
```http
GET /api/mcp/retell/agents/1
```
- Datenbankzugriff
- API Call zu Retell (Circuit Breaker Test)

### 3. **Check Availability** (25% Last)
```http
POST /api/retell/check-intelligent-availability
```
- Komplexe Business Logic
- Multiple DB Queries

### 4. **Webhook Simulation** (25% Last)
```http
POST /api/test/webhook
```
- Queue Processing
- Async Operations

---

## 🎯 PERFORMANCE ZIELE

| Metrik | Ziel | Kritisch |
|--------|------|----------|
| **Success Rate** | > 99% | < 95% |
| **Response Time P95** | < 200ms | > 500ms |
| **Response Time P99** | < 500ms | > 1000ms |
| **Throughput** | > 50 req/s | < 20 req/s |
| **Error Rate** | < 1% | > 5% |

---

## 📋 TEST DURCHFÜHRUNG

### 1. **Baseline Test** (10 Users, 60s)
```bash
./load-test-script.php https://api.askproai.de 10 60
```
Erwartung: Alle Tests PASSED

### 2. **Normal Load** (100 Users, 300s)
```bash
./load-test-script.php https://api.askproai.de 100 300
```
Erwartung: Success Rate > 99%

### 3. **Peak Load** (500 Users, 300s)
```bash
./load-test-script.php https://api.askproai.de 500 300
```
Erwartung: System bleibt stabil

### 4. **Stress Test** (1000 Users, 600s)
```bash
./load-test-script.php https://api.askproai.de 1000 600
```
Erwartung: Graceful Degradation

### 5. **Circuit Breaker Test**
```bash
# Retell API offline simulieren
# Dann Load Test ausführen
./load-test-script.php https://api.askproai.de 100 60
```
Erwartung: Fallback funktioniert

---

## 🔍 MONITORING WÄHREND TEST

### 1. **Server Metrics**
```bash
# CPU & Memory
htop

# MySQL Connections
mysql -e "SHOW PROCESSLIST" | wc -l

# Redis Monitoring
redis-cli monitor

# Laravel Horizon
php artisan horizon:status
```

### 2. **Application Logs**
```bash
# Error Log
tail -f storage/logs/laravel.log | grep ERROR

# Circuit Breaker Events
tail -f storage/logs/laravel.log | grep "Circuit breaker"

# Queue Failures
php artisan queue:failed
```

### 3. **System Resources**
```bash
# Disk I/O
iostat -x 1

# Network
netstat -an | grep ESTABLISHED | wc -l

# Memory
free -m
```

---

## 📈 ERWARTETE ERGEBNISSE

### Bei 100 Concurrent Users:
- CPU: < 50%
- Memory: < 2GB
- Response Time P95: < 150ms
- Success Rate: > 99.5%

### Bei 500 Concurrent Users:
- CPU: < 80%
- Memory: < 4GB
- Response Time P95: < 300ms
- Success Rate: > 98%

### Bei 1000 Concurrent Users:
- Rate Limiting aktiviert
- Circuit Breaker kann öffnen
- Success Rate: > 95%
- Keine Crashes

---

## 🔧 OPTIMIERUNGEN (falls nötig)

### Quick Wins:
1. **Database Connection Pool erhöhen**
   ```env
   DB_POOL_MIN=10
   DB_POOL_MAX=100
   ```

2. **Redis Connection Pool**
   ```php
   'redis' => [
       'pool' => [
           'min_connections' => 10,
           'max_connections' => 50
       ]
   ]
   ```

3. **PHP-FPM Tuning**
   ```ini
   pm.max_children = 50
   pm.start_servers = 10
   pm.min_spare_servers = 5
   pm.max_spare_servers = 20
   ```

4. **OPcache Settings**
   ```ini
   opcache.memory_consumption=256
   opcache.max_accelerated_files=20000
   ```

---

## ✅ NEXT STEPS

1. **Heute Abend**: Baseline Test auf Production
2. **Morgen früh**: Normal Load Test
3. **Nach Staging Deploy**: Full Test Suite
4. **Vor Go-Live**: Final Stress Test

**Geschätzte Zeit**: 3-4 Stunden für komplette Test Suite

---

## 📊 REPORT TEMPLATE

Nach jedem Test wird automatisch ein Report erstellt:
```json
{
  "total_requests": 12000,
  "successful_requests": 11940,
  "failed_requests": 60,
  "success_rate": "99.5%",
  "avg_response_time": "125ms",
  "p95_response_time": "180ms",
  "p99_response_time": "420ms",
  "errors": {
    "timeout": 45,
    "connection_refused": 15
  }
}
```

**Bereit für Load Testing!** 🚀