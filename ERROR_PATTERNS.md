# 🚨 AskProAI Error Patterns & Solutions

> **Ziel**: Jeder Fehler nur EINMAL lösen. Danach Copy-Paste Solution!

## 🔥 Error Code System

### Prefixes:
- **RETELL_XXX** - Retell.ai Integration Errors
- **CALCOM_XXX** - Cal.com Integration Errors  
- **WEBHOOK_XXX** - Webhook Processing Errors
- **AUTH_XXX** - Authentication/Authorization Errors
- **DB_XXX** - Database Connection/Query Errors
- **QUEUE_XXX** - Queue/Job Processing Errors

---

## 🔴 KRITISCHE FEHLER (Sofort beheben!)

### RETELL_001: "No calls imported" / "Es werden keine Anrufe eingespielt"
**Stack Pattern**: `RetellService::fetchCalls() returns empty`
```bash
# LÖSUNG:
1. php artisan horizon                    # Queue Worker starten
2. Admin Panel → "Anrufe abrufen" Button  # Manueller Import
3. Prüfe: Company->retell_api_key nicht null
4. Manueller Import: php import-retell-calls.php
```

### DB_001: "Access denied for user 'askproai_user'@'localhost'"
**Stack Pattern**: `SQLSTATE[HY000] [1045]`
```bash
# LÖSUNG:
rm -f bootstrap/cache/config.php
php artisan config:cache
sudo systemctl restart php8.3-fpm
```

### WEBHOOK_001: "Invalid signature"
**Stack Pattern**: `VerifyRetellSignature middleware rejection`
```bash
# LÖSUNG:
1. Check .env: RETELL_WEBHOOK_SECRET=key_xxx
2. Verify webhook URL in Retell Dashboard
3. Test: curl -X POST ... -H "x-retell-signature: $(echo -n $payload | openssl dgst -sha256 -hmac $secret)"
```

### CALCOM_001: "Event type not found"
**Stack Pattern**: `CalcomV2Service::getEventType() returns 404`
```bash
# LÖSUNG:
1. Verify branch.calcom_event_type_id exists
2. Check: CalcomEventType where id = X
3. Re-sync: php artisan calcom:sync-event-types
```

### RETELL_002: "Webhook signature verification failed"
**Stack Pattern**: `VerifyRetellSignature::handle() returns 401`
```bash
# LÖSUNG:
1. API Key = Webhook Secret (gleicher Wert!)
2. Format: v=timestamp,d=signature
3. Test: php trigger-simple-webhook.php
```

### RETELL_003: "Branch ID is null after webhook"
**Stack Pattern**: `Call::create() branch_id => null`
```bash
# LÖSUNG:
1. Check: SELECT * FROM phone_numbers WHERE number LIKE '%XXX%';
2. Verify branch_id is set in phone_numbers table
3. Run: php test-phone-resolution.php
```

### RETELL_004: "Wrong timezone in calls (UTC instead of Berlin)"
**Stack Pattern**: `start_timestamp shows 2 hours behind`
```bash
# LÖSUNG:
1. Already fixed in ProcessRetellCallEndedJob
2. Manual fix for old calls: php fix-call-timestamps.php
3. All new calls auto-convert UTC → Berlin (+2h)
```

### RETELL_005: "Laravel Scheduler only loads 1 task"
**Stack Pattern**: `schedule:list shows only knowledge:watch`
```bash
# LÖSUNG (Workaround):
1. Direct cron entries:
   */15 * * * * /usr/bin/php /var/www/api-gateway/manual-retell-import.php
   */5 * * * * /usr/bin/php /var/www/api-gateway/cleanup-stale-calls.php
2. Check cron: crontab -l
```

---

## 🟡 HÄUFIGE FEHLER

### AUTH_001: "Unauthenticated"
**Pattern**: API calls without proper auth
```php
// LÖSUNG:
// Add to request header:
'Authorization' => 'Bearer ' . $user->createToken('api')->plainTextToken
```

### QUEUE_001: "Job failed after X attempts"
**Pattern**: ProcessRetellCallEndedJob timeout
```bash
# LÖSUNG:
1. Increase timeout in config/horizon.php
2. Check Redis memory: redis-cli INFO memory
3. Clear failed jobs: php artisan queue:flush
```

### DB_002: "Too many connections"
**Pattern**: `SQLSTATE[HY000] [1040]`
```bash
# LÖSUNG:
1. Check active connections: SHOW PROCESSLIST;
2. Increase max_connections in my.cnf
3. Enable connection pooling (see DatabaseServiceProvider)
```

---

## 🟢 PERFORMANCE ISSUES

### PERF_001: "Slow API Response > 1s"
**Pattern**: AppointmentController@index timeout
```php
// LÖSUNG:
// Add eager loading:
$appointments = Appointment::with(['customer', 'staff', 'service'])
    ->paginate(20);
```

### PERF_002: "N+1 Query detected"
**Pattern**: Multiple queries in loop
```php
// PROBLEM:
foreach ($branches as $branch) {
    $branch->appointments; // N+1!
}

// LÖSUNG:
$branches = Branch::with('appointments')->get();
```

---

## 🔧 DEBUG COMMANDS

### Schnelle Diagnose:
```bash
# Letzte Fehler
tail -n 100 storage/logs/laravel.log | grep ERROR

# Webhook Issues
tail -f storage/logs/laravel.log | grep -E "(webhook|retell|calcom)"

# Performance Issues
php artisan telescope:prune
php artisan telescope
# Visit: /telescope/queries
```

### Test Connections:
```bash
# Database
php artisan tinker
>>> DB::connection()->getPdo();

# Redis
>>> Redis::ping();

# Retell API
>>> app(RetellV2Service::class)->testConnection();

# Phone Resolution
php test-phone-resolution.php

# Webhook Status
php verify-webhook-status.php
```

---

## 📊 Error Tracking Matrix

| Error Code | Frequency | Avg Resolution Time | Auto-fixable |
|------------|-----------|-------------------|--------------|
| DB_001     | Daily     | 2 min            | ✅ Script    |
| RETELL_001 | Weekly    | 5 min            | ✅ Button    |
| RETELL_002 | Daily     | 10 min           | ❌ Manual    |
| RETELL_003 | Fixed     | N/A              | ✅ Migration |
| RETELL_004 | Fixed     | N/A              | ✅ Code      |
| RETELL_005 | Once      | 5 min            | ⚠️ Workaround|
| WEBHOOK_001| Monthly   | 15 min           | ❌ Manual    |
| CALCOM_001 | Weekly    | 10 min           | ⚠️ Semi-auto |

---

## 🚀 Automation Scripts

### auto-fix-common.sh
```bash
#!/bin/bash
case "$1" in
  "db-access")
    rm -f bootstrap/cache/config.php
    php artisan config:cache
    sudo systemctl restart php8.3-fpm
    echo "✅ DB Access fixed"
    ;;
  "import-calls")
    php artisan horizon
    sleep 2
    curl -X POST http://localhost/admin/retell/import
    echo "✅ Call import triggered"
    ;;
esac
```

---

## 📝 Contributing

**Neuen Fehler gefunden?**
1. Assign Error Code (nächste freie Nummer)
2. Document Pattern (Stack trace)
3. Add Solution (tested!)
4. Update Frequency Matrix

> 💡 **Goal**: Reduce average resolution time from 30-60min to <5min!