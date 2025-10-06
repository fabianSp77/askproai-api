# Project Constraints - Intelligent Appointment Management System

## 🔴 VERBINDLICHE BEDINGUNGEN (Tag 3-15)

**Genehmigt am**: 2025-10-02
**Gültig für**: Tag 3 bis Tag 15
**Status**: AKTIV

---

## 1️⃣ MySQL-Kompatibilität für ALLE Migrations

### Regel
**JEDE neue Migration MUSS MySQL-kompatibel sein**

### Anforderungen
- ✅ Teste Syntax-Unterschiede SQLite vs MySQL
- ✅ Dokumentiere MySQL-spezifische Features
- ✅ Vermeide SQLite-only Syntax
- ✅ Validiere auf BEIDEN Datenbanken vor commit

### Verbotene Patterns (SQLite-only)
```php
// ❌ VERBOTEN
DB::statement("COMMENT ON TABLE ...");  // PostgreSQL syntax
$table->autoIncrement = false;          // SQLite-specific

// ❌ VERBOTEN - Unterschiedliche Datetime-Formate
$table->timestamp('created_at')->useCurrent(); // SQLite OK, MySQL unterschiedlich
```

### Erlaubte Patterns (MySQL-kompatibel)
```php
// ✅ ERLAUBT
$table->timestamps(); // Standard Laravel
$table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Standard
$table->enum('status', ['pending', 'active']); // Beide DBs
$table->json('metadata')->nullable(); // Beide DBs

// ✅ ERLAUBT - Kommentare in PHPDoc
/**
 * Table: policy_configurations
 * Purpose: Hierarchical policy storage
 * Note: Staff can override preferences only, not business logic
 */
```

### Validierungs-Prozess
```bash
# 1. SQLite Test
DB_CONNECTION=sqlite DB_DATABASE=testing.sqlite php artisan migrate:fresh --force

# 2. MySQL Test (auf Staging, NICHT Production)
php artisan migrate:status
php artisan migrate --pretend  # Zeige SQL ohne Ausführung
```

### MySQL-Spezifische Features Dokumentation
| Feature | MySQL Syntax | SQLite Alternative | Status |
|---------|--------------|-------------------|---------|
| Table Comments | Via PHPDoc | Via PHPDoc | ✅ Standardized |
| JSON | `JSON` | `TEXT` (auto-cast) | ✅ Works both |
| ENUM | `ENUM('a','b')` | Check constraint | ✅ Laravel handles |
| Foreign Keys | Full support | Pragma required | ✅ Tested |

---

## 2️⃣ Extra Review für Kritischen Code

### Regel
**Kritischer Code MUSS extra reviewed werden**

### Kritische Komponenten

#### Day 4-5: PolicyEngine
**Review-Kriterien:**
- ✅ Quota-Check Logik (rolling 30-day window)
- ✅ Fee calculation accuracy
- ✅ Edge cases: timezone, DST, leap years
- ✅ Race conditions bei concurrent checks
- ⚠️ **STOP**: Wenn Logic unclear → fragen, nicht raten

**Review-Checkliste:**
```php
// PolicyEngine MUSS testen:
✅ canCancel() - Frist-Checks präzise?
✅ canReschedule() - Quota-Checks korrekt?
✅ calculateFee() - Staffelung exakt?
✅ Edge Case: Appointment um 23:59, Check um 00:01 (next day)
✅ Edge Case: DST-Umstellung während Frist
✅ Performance: O(1) durch materialized stats
```

#### Day 6-7: Event System
**Review-Kriterien:**
- ✅ Event-Listener Mapping korrekt
- ✅ Keine Memory Leaks bei Event-Firing
- ✅ Error handling in Listeners (fail gracefully)
- ✅ Queue-Verhalten bei hoher Last
- ⚠️ **STOP**: Wenn Event-Loop unklar → dokumentieren

**Review-Checkliste:**
```php
// Event System MUSS testen:
✅ AppointmentModified Event → Listener triggered
✅ Listener fails → Doesn't break main flow
✅ Multiple Listeners → Alle executed
✅ Queued events → Processed in order
✅ Event data → Immutable (keine side effects)
```

#### Day 8-9: Retell Integration
**Review-Kriterien:**
- ✅ Webhook signature validation
- ✅ Idempotency (duplicate webhooks handled)
- ✅ Error responses (Retell retry logic)
- ✅ Timeout handling (Cal.com API)
- ⚠️ **STOP**: Wenn Security unclear → Security review

**Review-Checkliste:**
```php
// Retell Integration MUSS testen:
✅ Webhook signature verified BEFORE processing
✅ Duplicate call_id → idempotent (no double-booking)
✅ Cal.com timeout → graceful degradation
✅ Invalid payload → 400 response (Retell stops retry)
✅ Valid payload → 200 response (Retell stops retry)
✅ Partial failure → Logged but not 5xx (Retell würde retry)
```

### Review-Prozess
1. **Code Complete** → Run tests
2. **Tests Green** → Self-review mit Checkliste
3. **Checkliste Complete** → Mark as "Ready for checkpoint"
4. **Checkpoint** → Validate gegen Kriterien
5. **Checkpoint FAIL** → STOP (siehe Bedingung 3)

---

## 3️⃣ Checkpoint Failure Protocol

### Regel
**Bei Checkpoint-Failure: SOFORT melden, nicht weitermachen**

### Checkpoint Schedule
| Day | Checkpoint | Criteria |
|-----|-----------|----------|
| 2 | Migrations + Models | All migrations run, models CRUD works |
| 5 | PolicyEngine Complete | All tests green, edge cases covered |
| 10 | Retell Flow Working | Integration test passes, idempotency verified |
| 15 | Production Ready | All systems validated, deployment plan finalized |

### Failure Response Protocol

#### Phase 1: Detection (Immediate)
```
🚨 CHECKPOINT FAILURE DETECTED
Component: [Name]
Day: [X]
Severity: [Critical/High/Medium]
```

#### Phase 2: STOP (Sofort)
- ⛔ **STOP all forward progress**
- ⛔ **DO NOT proceed to next task**
- ⛔ **DO NOT modify more code**

#### Phase 3: Analysis (15min)
```markdown
## Failure Analysis
- **Expected**: [What should happen]
- **Actual**: [What happened]
- **Root Cause**: [Why it failed]
- **Affected Components**: [List]
```

#### Phase 4: Rollback Decision
```
Option A: FIX (if quick, <1 hour)
  → Implement fix
  → Re-run checkpoint
  → Continue if passed

Option B: ROLLBACK (if complex, >1 hour)
  → Execute rollback plan
  → Restore to last green checkpoint
  → Re-plan approach
```

#### Phase 5: Report (Mandatory)
```markdown
## Checkpoint Failure Report
**Day**: X
**Component**: [Name]
**Status**: BLOCKED
**Root Cause**: [Technical details]
**Rollback Executed**: Yes/No
**Revised Plan**: [Next steps]
**ETA Recovery**: [Estimate]
```

### Rollback Plan Per Component

**Day 5 - PolicyEngine Failure:**
```bash
# Rollback to Day 4 state
git checkout HEAD~N -- app/Services/Policies/
php artisan cache:clear
# Re-validate Day 4 checkpoint
```

**Day 10 - Retell Integration Failure:**
```bash
# Rollback handlers
git checkout HEAD~N -- app/Services/Retell/
git checkout HEAD~N -- app/Http/Controllers/RetellWebhookController.php
# Re-validate Day 7 checkpoint
```

**Day 15 - Production Ready Failure:**
```bash
# DO NOT deploy to production
# Stay on testing.sqlite
# Document blockers
# Extend to buffer days (16-17)
```

---

## 4️⃣ Tag 14 Performance-Tests auf Production

### Regel
**AUF PRODUCTION testen (keine Alternative), aber sicher**

### Bedingungen
- ⏰ **Außerhalb Geschäftszeiten** (20:00-06:00)
- 💾 **Mit Backup VORHER**
- 📊 **Monitoring aktiv**
- 🚨 **Rollback-Plan bereit**

### Pre-Test Checklist
```bash
# 1. Database Backup (KRITISCH)
mysqldump -u root -p askproai_db > backup_performance_test_$(date +%Y%m%d_%H%M%S).sql

# 2. Verify Backup
mysql -u root -p -e "SOURCE backup_performance_test_*.sql" test_db
mysql -u root -p test_db -e "SELECT COUNT(*) FROM companies;"

# 3. Redis Backup
redis-cli SAVE
cp /var/lib/redis/dump.rdb /var/lib/redis/dump_backup_$(date +%Y%m%d_%H%M%S).rdb

# 4. Monitor Setup
# - Start `htop` in separate terminal
# - Start `redis-cli MONITOR` in separate terminal
# - Start MySQL slow query log
mysql -u root -p -e "SET GLOBAL slow_query_log = 'ON'; SET GLOBAL long_query_time = 1;"
```

### Performance Tests (Non-Destructive)

#### Test 1: Configuration Hierarchy (Read-Only)
```php
// Load 100 companies, measure getEffectivePolicyConfig()
$companies = Company::limit(100)->get();
foreach ($companies as $company) {
    $config = $company->getEffectivePolicyConfig('cancellation');
}
// Measure: Avg time, cache hit rate, memory usage
```

#### Test 2: Appointment Modification Stats (Read-Only)
```php
// Query materialized stats for 100 customers
$customers = Customer::limit(100)->get();
foreach ($customers as $customer) {
    $count = AppointmentModificationStat::getCountForCustomer($customer->id, 'cancellation_count');
}
// Measure: Query time (should be O(1))
```

#### Test 3: Notification Configuration Lookup (Read-Only)
```php
// Lookup notification configs for 50 branches
$branches = Branch::limit(50)->get();
foreach ($branches as $branch) {
    $config = $branch->getEffectiveNotificationConfig('booking_confirmed', 'email');
}
// Measure: Avg time, cache effectiveness
```

#### Test 4: Callback Request Queries (Read-Only)
```php
// Query overdue callbacks across all branches
$overdue = CallbackRequest::overdue()->get();
// Measure: Query time, index usage (EXPLAIN)
```

### Monitoring During Tests
```bash
# Terminal 1: System resources
watch -n 1 'ps aux | grep php | head -20'

# Terminal 2: Redis monitoring
redis-cli MONITOR | grep "policy_config\|notif_config"

# Terminal 3: MySQL slow queries
tail -f /var/log/mysql/slow-query.log

# Terminal 4: Application logs
tail -f /var/www/api-gateway/storage/logs/laravel.log
```

### Success Criteria
- ✅ All queries < 100ms (p95)
- ✅ Cache hit rate > 90%
- ✅ Memory usage < +50MB baseline
- ✅ No errors in logs
- ✅ No production impact (verified via monitoring)

### Failure Response
```bash
# If ANY test fails or impacts production:
# 1. STOP tests immediately
# 2. Verify production still healthy
# 3. Analyze failure
# 4. Rollback if needed (DB restore)
```

### Post-Test Validation
```bash
# 1. Verify production data unchanged
mysql -u root -p askproai_db -e "SELECT COUNT(*) FROM companies;"
# (Should match pre-test count)

# 2. Verify Redis healthy
redis-cli PING

# 3. Check error logs
tail -100 /var/www/api-gateway/storage/logs/laravel.log

# 4. Disable slow query log
mysql -u root -p -e "SET GLOBAL slow_query_log = 'OFF';"
```

---

## 5️⃣ Tag 15 Detailed Deployment Plan

### Regel
**DETAILED step-by-step mit Rollback-Punkten und Backup**

### Pre-Deployment Phase (Tag 15 Morning)

#### Step 1: Final Validation on Staging (08:00-10:00)
```bash
# 1. Fresh staging DB
mysql -u root -p -e "DROP DATABASE IF EXISTS staging_db; CREATE DATABASE staging_db;"

# 2. Copy production schema
mysqldump -u root -p askproai_db --no-data | mysql -u root -p staging_db

# 3. Run ALL migrations
DB_DATABASE=staging_db php artisan migrate:fresh --force --seed

# 4. Verify schema
mysql -u root -p staging_db -e "SHOW TABLES LIKE 'policy_%';"
mysql -u root -p staging_db -e "SHOW TABLES LIKE 'callback_%';"
mysql -u root -p staging_db -e "SHOW TABLES LIKE 'notification_%';"

# 5. Test data seeding
mysql -u root -p staging_db -e "SELECT COUNT(*) FROM notification_event_mappings;"
# Expected: 13 events
```

#### Step 2: Rollback Plan Documentation (10:00-11:00)
Create `ROLLBACK_PROCEDURES.md` with:
- Exact git commits for each component
- Database restore scripts
- Cache clear procedures
- Verification steps

#### Step 3: Deployment Sequence Planning (11:00-12:00)
Create `DEPLOYMENT_SEQUENCE.md` with:
- Migration execution order (7 migrations)
- Rollback point after EACH migration
- Validation query after EACH migration
- Time estimate for each step
- Go/No-Go decision points

### Deployment Phase (Tag 15 Afternoon - 14:00-18:00)

#### 🔒 LOCK Phase (14:00 - 5min)
```bash
# 1. Enable maintenance mode
php artisan down --message="System upgrade in progress" --retry=60

# 2. Verify no active users
mysql -u root -p askproai_db -e "SELECT COUNT(*) FROM sessions WHERE last_activity > UNIX_TIMESTAMP(NOW() - INTERVAL 5 MINUTE);"

# 3. Stop queue workers
supervisorctl stop laravel-worker:*
```

#### 💾 BACKUP Phase (14:05 - 10min)
```bash
# 1. Full database backup
mysqldump -u root -p askproai_db > /backups/pre_deployment_$(date +%Y%m%d_%H%M%S).sql

# 2. Verify backup size (should be >10MB)
ls -lh /backups/pre_deployment_*.sql

# 3. Test restore to temp DB
mysql -u root -p -e "CREATE DATABASE test_restore;"
mysql -u root -p test_restore < /backups/pre_deployment_*.sql
mysql -u root -p test_restore -e "SELECT COUNT(*) FROM companies;"
mysql -u root -p -e "DROP DATABASE test_restore;"

# 4. Redis backup
redis-cli SAVE
cp /var/lib/redis/dump.rdb /backups/redis_backup_$(date +%Y%m%d_%H%M%S).rdb

# 5. Code backup (git tag)
git tag -a deployment_$(date +%Y%m%d_%H%M%S) -m "Pre-deployment backup"
git push origin --tags
```

**🛑 ROLLBACK POINT 0: Pre-Migration State**

#### 📋 MIGRATION Phase (14:15 - 30min)

##### Migration 1: notification_configurations
```bash
# Execute
php artisan migrate --path=database/migrations/2025_10_01_060100_create_notification_configurations_table.php --force

# Verify
mysql -u root -p askproai_db -e "DESCRIBE notification_configurations;"
mysql -u root -p askproai_db -e "SELECT COUNT(*) FROM notification_configurations;"

# Rollback script (if needed)
echo "DROP TABLE IF EXISTS notification_configurations;" > rollback_mig1.sql
```

**🛑 ROLLBACK POINT 1: After notification_configurations**

##### Migration 2: callback_requests
```bash
# Execute
php artisan migrate --path=database/migrations/2025_10_01_060200_create_callback_requests_table.php --force

# Verify
mysql -u root -p askproai_db -e "DESCRIBE callback_requests;"
mysql -u root -p askproai_db -e "SHOW INDEX FROM callback_requests;"

# Rollback script
echo "DROP TABLE IF EXISTS callback_requests;" > rollback_mig2.sql
```

**🛑 ROLLBACK POINT 2: After callback_requests**

##### Migration 3: notification_event_mappings
```bash
# Execute
php artisan migrate --path=database/migrations/2025_10_01_060200_create_notification_event_mappings_table.php --force

# Verify seeded data
mysql -u root -p askproai_db -e "SELECT COUNT(*) FROM notification_event_mappings;"
# Expected: 13

mysql -u root -p askproai_db -e "SELECT event_type, event_label FROM notification_event_mappings LIMIT 5;"

# Rollback script
echo "DROP TABLE IF EXISTS notification_event_mappings;" > rollback_mig3.sql
```

**🛑 ROLLBACK POINT 3: After notification_event_mappings**

##### Migration 4-7: [Similar pattern for remaining migrations]
```bash
# Each migration follows same pattern:
# 1. Execute with --path and --force
# 2. Verify with DESCRIBE and SELECT COUNT(*)
# 3. Create rollback script
# 4. Mark rollback point
```

**🛑 ROLLBACK POINT 7: All Migrations Complete**

#### ✅ VALIDATION Phase (14:45 - 15min)

##### System Health Check
```bash
# 1. All migrations ran
php artisan migrate:status | grep "2025_10_01"
# Expected: All show "Ran"

# 2. Seeded data present
mysql -u root -p askproai_db -e "SELECT COUNT(*) FROM notification_event_mappings;"
# Expected: 13

# 3. Model autoloading
php artisan tinker --execute="
echo 'PolicyConfiguration: ' . (class_exists('App\Models\PolicyConfiguration') ? 'OK' : 'FAIL') . PHP_EOL;
echo 'CallbackRequest: ' . (class_exists('App\Models\CallbackRequest') ? 'OK' : 'FAIL') . PHP_EOL;
"

# 4. Trait integration
php artisan tinker --execute="
\$company = App\Models\Company::first();
echo 'Trait method: ' . (method_exists(\$company, 'getEffectivePolicyConfig') ? 'OK' : 'FAIL') . PHP_EOL;
"

# 5. Cache working
php artisan tinker --execute="
Cache::put('deployment_test', 'ok', 60);
echo 'Cache: ' . (Cache::get('deployment_test') === 'ok' ? 'OK' : 'FAIL') . PHP_EOL;
Cache::forget('deployment_test');
"
```

**🛑 ROLLBACK POINT 8: Post-Migration Validation**

#### 🔓 UNLOCK Phase (15:00 - 5min)
```bash
# 1. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Rebuild optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Restart queue workers
supervisorctl start laravel-worker:*

# 4. Disable maintenance mode
php artisan up
```

#### 📊 MONITORING Phase (15:05-18:00 - 3 hours)

##### Immediate Monitoring (First 15min)
```bash
# Watch logs in real-time
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i "error\|exception"

# Monitor Redis
redis-cli MONITOR | grep "policy_config"

# Watch system resources
watch -n 5 'ps aux | grep php | head -10'
```

##### Extended Monitoring (3 hours)
- ✅ Check error rate every 15min
- ✅ Monitor response times
- ✅ Verify no 500 errors
- ✅ Check queue processing
- ✅ Validate user activity normal

### Rollback Triggers
Execute full rollback if ANY of:
- ❌ Migration fails
- ❌ Validation fails
- ❌ Error rate > 1%
- ❌ Response time > 2x baseline
- ❌ Any 500 errors
- ❌ User reports critical issues

### Full Rollback Procedure
```bash
# 1. Enable maintenance mode
php artisan down

# 2. Rollback migrations
php artisan migrate:rollback --step=7

# 3. Restore database
mysql -u root -p askproai_db < /backups/pre_deployment_*.sql

# 4. Restore Redis
redis-cli FLUSHALL
cp /backups/redis_backup_*.rdb /var/lib/redis/dump.rdb
systemctl restart redis

# 5. Restore code (if modified)
git checkout [previous_tag]

# 6. Clear caches
php artisan cache:clear
php artisan config:clear

# 7. Restart services
supervisorctl restart all

# 8. Disable maintenance mode
php artisan up

# 9. Verify rollback
php artisan migrate:status | grep "2025_10_01"
# Expected: All show "Pending"
```

---

## 📞 Emergency Contacts & Escalation

### Response Times
- **Critical** (Production down): Immediate
- **High** (Feature broken): < 1 hour
- **Medium** (Performance issue): < 4 hours
- **Low** (Minor bug): Next day

### Escalation Path
1. **Checkpoint Failure** → Stop & Report
2. **Migration Failure** → Rollback & Report
3. **Production Impact** → Immediate Rollback & Report

---

**Document Status**: ACTIVE
**Review Date**: Tag 15 (before deployment)
**Next Update**: After deployment (lessons learned)
