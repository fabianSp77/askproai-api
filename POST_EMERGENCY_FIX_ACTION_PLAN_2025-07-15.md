# 🚨 Post-Emergency Fix Action Plan - 15. Juli 2025

## 📊 Aktueller Status nach Emergency Fix
- ✅ Kritische Sicherheitslücken behoben
- ✅ XSS/SQL Injection Protection aktiviert
- ✅ Rate Limiting implementiert
- ✅ Sensitive Daten verschlüsselt
- ⚠️ Route Cache Problem identifiziert
- ❌ Performance-Probleme nicht behoben
- ❌ Monitoring/Alerting fehlt

## 🔥 SOFORT (Nächste 30 Minuten) - Kritische Verifizierung

### 1. Funktionalitäts-Check (10 Min)
```bash
# Health Check durchführen
curl -I https://api.askproai.de/health
curl -I https://api.askproai.de/api/health

# Admin Panel testen
curl -I https://api.askproai.de/admin
curl -I https://api.askproai.de/admin/login

# Business Portal testen
curl -I https://api.askproai.de/portal
curl -I https://api.askproai.de/portal/login

# API Endpoints verifizieren
curl -X GET https://api.askproai.de/api/v2/dashboard \
  -H "Accept: application/json"
```

### 2. Login/Logout Funktionalität (10 Min)
```bash
# Test-Login Script
php public/test-login-functionality.php

# Session Verification
php artisan tinker
>>> Auth::check()
>>> session()->all()
```

### 3. Hauptfunktionen prüfen (10 Min)
```bash
# Queue Worker Status
php artisan queue:work --stop-when-empty

# Horizon Status
php artisan horizon:status

# Database Connection
php artisan db:show

# Cache funktioniert
php artisan cache:clear
php artisan config:cache
```

## 🚀 HEUTE (Nächste 4-8 Stunden) - Performance & Route Fix

### 1. Route Duplicate Problem lösen (30 Min)
```bash
# Analyse des Problems
php artisan route:list | grep -E "(staff\.index|duplicate)"

# Temporärer Fix
php artisan route:clear

# Permanent Fix benötigt:
# - Prüfe routes/web.php und routes/api.php für Duplikate
# - Suche nach mehrfachen Route::resource('staff', ...) Definitionen
```

### 2. Performance-Analyse (1 Stunde)
```bash
# Slow Query Log aktivieren
mysql -u root -p'V9LGz2tdR5gpDQz' -e "
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow-query.log';
"

# Laravel Debugbar für Analyse
composer require barryvdh/laravel-debugbar --dev
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"

# Query Performance Monitor
php artisan tinker
>>> DB::enableQueryLog();
>>> // Run operations
>>> dd(DB::getQueryLog());
```

### 3. Fehlende Indizes identifizieren (1 Stunde)
```sql
-- Missing Index Finder Script
SELECT 
    s.table_schema,
    s.table_name,
    s.column_name,
    s.seq_in_index
FROM information_schema.statistics s
LEFT JOIN (
    SELECT table_schema, table_name, column_name
    FROM information_schema.columns
    WHERE column_key = ''
) c ON s.table_schema = c.table_schema 
    AND s.table_name = c.table_name 
    AND s.column_name = c.column_name
WHERE s.table_schema = 'askproai_db'
ORDER BY s.table_schema, s.table_name;

-- Häufig genutzte WHERE Klauseln ohne Index
EXPLAIN SELECT * FROM calls WHERE company_id = 1 AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY);
EXPLAIN SELECT * FROM appointments WHERE branch_id = 'uuid' AND status = 'scheduled';
EXPLAIN SELECT * FROM customers WHERE phone = '+49123456789';
```

### 4. N+1 Query Probleme finden (1 Stunde)
```php
// Create monitoring script
// monitor-n1-queries.php
<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

DB::listen(function ($query) {
    if ($query->time > 100) { // Queries über 100ms
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time
        ]);
    }
});

// Telescope für detaillierte Analyse
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

## 🛡️ MORGEN - Sicherheit & Monitoring

### 1. Session Security verbessern
```php
// config/session.php
return [
    'secure' => true,              // HTTPS only
    'http_only' => true,           // No JS access
    'same_site' => 'lax',          // CSRF protection
    'expire_on_close' => false,
    'encrypt' => true,             // Encrypt session data
];

// Implement session fingerprinting
// app/Http/Middleware/SessionFingerprint.php
```

### 2. API Authentication härten
```bash
# Sanctum Token Expiry
php artisan tinker
>>> DB::table('personal_access_tokens')
>>>     ->where('created_at', '<', now()->subDays(30))
>>>     ->delete();

# API Rate Limiting per User
// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### 3. Error Tracking Setup
```bash
# Sentry Installation
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_DSN

# Konfiguration
SENTRY_LARAVEL_DSN=your-dsn-here
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### 4. Monitoring Stack
```bash
# Prometheus + Grafana Setup
docker-compose -f docker-compose.monitoring.yml up -d

# Laravel Metrics Package
composer require spatie/laravel-prometheus
php artisan vendor:publish --tag="prometheus-config"

# Custom Metrics
// app/Services/MetricsService.php
Prometheus::histogram('http_request_duration_seconds')
    ->observe($duration);
```

## 📈 DIESE WOCHE - Langfristige Verbesserungen

### 1. Automated Monitoring & Alerting
```yaml
# prometheus/alerts.yml
groups:
  - name: laravel
    rules:
      - alert: HighErrorRate
        expr: rate(laravel_errors_total[5m]) > 0.05
        for: 5m
        annotations:
          summary: "High error rate detected"
      
      - alert: SlowResponseTime
        expr: histogram_quantile(0.95, laravel_http_request_duration_seconds) > 1
        for: 5m
        annotations:
          summary: "95th percentile response time > 1s"
```

### 2. Performance Optimierung
```php
// Database Query Optimization
// app/Providers/AppServiceProvider.php
public function boot()
{
    // Prevent N+1 queries in production
    if ($this->app->isProduction()) {
        Model::preventLazyLoading();
    }
    
    // Log slow queries
    DB::whenQueryingForLongerThan(500, function (Connection $connection) {
        Log::warning('Long running query detected', [
            'sql' => $connection->getQueryLog()
        ]);
    });
}

// Implement Query Caching
Cache::remember('expensive-query', 3600, function () {
    return DB::table('complex_query')->get();
});
```

### 3. Automated Security Scans
```bash
# Security Audit Cron
0 2 * * * /usr/bin/php /var/www/api-gateway/artisan askproai:security-audit --email

# Dependency Check
0 3 * * * cd /var/www/api-gateway && composer audit

# Laravel Security Check
0 4 * * * cd /var/www/api-gateway && php artisan security:check
```

## 🎯 Erfolgskriterien & Metriken

### Sofort (30 Min)
- ✅ Alle Hauptfunktionen arbeiten
- ✅ Login/Logout funktioniert
- ✅ Keine kritischen Fehler in Logs
- ✅ API antwortet normal

### Heute (8 Stunden)
- ✅ Route Cache Problem gelöst
- ✅ Top 10 Slow Queries identifiziert
- ✅ Fehlende Indizes dokumentiert
- ✅ N+1 Probleme gefunden
- ✅ Performance Baseline etabliert

### Diese Woche
- ✅ Response Time < 200ms (p95)
- ✅ Error Rate < 0.1%
- ✅ Uptime > 99.9%
- ✅ Alle Sicherheitslücken geschlossen
- ✅ Monitoring & Alerting aktiv

## ⚠️ Risiken & Mitigationen

### 1. Route Cache Problem
**Risiko**: Application könnte nicht starten
**Mitigation**: 
- Route Cache deaktiviert lassen
- Duplicate Routes manuell fixen
- Rollback Plan bereit

### 2. Performance Fixes
**Risiko**: Neue Indizes könnten Write-Performance beeinträchtigen
**Mitigation**:
- Indizes einzeln testen
- Off-Peak Zeiten nutzen
- Online DDL verwenden

### 3. Security Hardening
**Risiko**: Zu strenge Rules könnten legitime User blockieren
**Mitigation**:
- Schrittweise Einführung
- Whitelist für bekannte IPs
- Monitoring der False Positives

## 📋 Konkrete nächste Schritte

1. **JETZT**: Führe Funktionalitäts-Checks durch (30 Min)
2. **IN 1 STUNDE**: Starte Performance-Analyse
3. **HEUTE NACHMITTAG**: Route Problem permanent lösen
4. **MORGEN FRÜH**: Security Review Meeting
5. **DIESE WOCHE**: Monitoring Stack deployen

## 🚀 Deployment Checkliste

- [ ] Backup erstellt
- [ ] Funktionalitäts-Tests durchgeführt
- [ ] Performance Baseline dokumentiert
- [ ] Route Problem gelöst
- [ ] Monitoring aktiviert
- [ ] Team informiert
- [ ] Rollback Plan getestet

## 📞 Eskalation

Bei kritischen Problemen:
1. Sofort Rollback durchführen
2. Team Lead informieren
3. Incident Report erstellen
4. Root Cause Analysis planen

---

**Erstellt**: 15. Juli 2025, 09:30 Uhr
**Nächstes Review**: 15. Juli 2025, 14:00 Uhr
**Verantwortlich**: DevOps Team