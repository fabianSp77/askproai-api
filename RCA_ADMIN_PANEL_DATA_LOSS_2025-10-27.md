# ROOT CAUSE ANALYSIS: Admin Panel Data Loss
## Incident Report - 2025-10-27

---

## EXECUTIVE SUMMARY

**Incident**: Complete data loss in production admin panel - all menu items and data missing
**Discovery Time**: 2025-10-27 ~07:30 CET
**Root Cause**: Database migration executed on production environment (migrate:fresh or similar)
**Impact**: CRITICAL - Total data loss (all companies, appointments, services, staff, customers)
**Recovery**: Database backup from 2025-10-04 available with 5 companies and production data

---

## ROOT CAUSE

### What Happened
The production database underwent a **destructive migration** between 06:28 and 07:03 CET on 2025-10-27:

```
[2025-10-27 06:28:24] production.WARNING: SLOW QUERY DETECTED
{"sql":"drop table `activity_log`,`activity_logs`,...[200+ tables]","time_ms":2178.24}
```

**Evidence Chain**:
1. **06:28:24** - All tables dropped (2.2 second query)
2. **06:28:26** - Migrations table recreated
3. **06:28:27** - First 5 migrations re-executed (batch 1)
4. **06:57:20-06:59:02** - Additional migrations executed (batches 2-6)
5. **07:01:30-07:03:37** - Core tables recreated (empty)

### Database Metadata Confirms Timing
```sql
TABLE_NAME    CREATE_TIME          UPDATE_TIME         TABLE_ROWS
appointments  2025-10-27 06:59:02  NULL                0
companies     2025-10-27 07:03:37  2025-10-27 07:03:37 0
services      2025-10-27 06:59:01  2025-10-27 06:59:01 0
staff         2025-10-27 06:58:33  NULL                0
```

All core tables were **created this morning** with **zero rows**.

---

## VERIFICATION: DATA WAS PRESENT BEFORE

### Last Known Good State: 2025-10-04
Backup at `/var/www/backups/P4_pre_next_steps_20251004_112339/` contains:

**Companies**: 5 production companies
- ID 1: Krückeberg Servicegruppe (askproai team)
- ID 11: Demo Zahnarztpraxis
- ID 15: AskProAI
- ID 17: Premium Telecom Solutions GmbH (reseller)
- ID 18: Friseur Schmidt (client)

**Services, Appointments, Staff**: All tables had production data in backup

### Why Menu Items Are Missing

**Current State** (`AdminPanelProvider.php` line 53-56):
```php
// Resource discovery DISABLED
// ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
->resources([
    \App\Filament\Resources\CompanyResource::class, // Only this one
])
```

**36 Resource files exist** but only `CompanyResource` is registered manually.

**Comment says**: "Temporarily disabled to prevent badge errors"

### Guard Mismatch (Secondary Issue)

**Auth Configuration**:
- Admin user has role `super_admin` with guard `web`
- Admin panel uses guard `admin` (line 34)
- User role query: `SELECT * FROM roles WHERE guard_name = 'web'` (finds super_admin)
- Panel auth: Checks guard `admin` (different guard)

**Impact**: This may cause authorization/visibility issues but is NOT the root cause of data loss.

---

## TIMELINE

### Before October 4, 2025
- Production system operational with real data
- 5 companies, multiple services, appointments, staff records
- Admin panel fully functional with all resources visible

### October 4, 2025 11:23 CET
- **Backup created**: `/var/www/backups/P4_pre_next_steps_20251004_112339/`
- Database snapshot: 9.7 MB (askproai_db_backup.sql)
- Code snapshot: 103 MB (code_backup.tar.gz)
- Checksums and restore script included

### October 26, 2025 (Yesterday)
- **Multiple 500 errors** during admin login attempts
- **Fix applied**: Disabled resource discovery (commit cbc50336)
  - Comment: "Temporarily disabled to prevent badge errors"
  - Comment: "ALL WIDGETS DISABLED to prevent database schema errors"
- **Fix applied**: Widget discovery disabled
- **Fix applied**: Dashboard widgets disabled
- **Guard separation** implemented (admin vs web)

### October 27, 2025 06:00-07:03 CET (Today)
- **06:00:01** - Cron job: `php artisan optimize:clear`
- **06:00:01** - Cron job: `php artisan schedule:run`
- **06:28:24** - 🚨 **DROP TABLE executed** (200+ tables, 2.2 seconds)
- **06:28:26** - Migrations table recreated
- **06:28:27** - Migrations batch 1 executed (5 migrations)
- **06:57:20-06:59:02** - Additional migration batches (2-6)
- **06:58:33-07:03:37** - Core tables recreated (empty)

### October 27, 2025 ~07:30 CET
- **User reports**: Admin panel shows only "Companies" menu item
- **User reports**: No data visible despite yesterday's data being present

---

## IMPACT ASSESSMENT

### Data Loss Scope: TOTAL

**Lost Data**:
- ✅ All companies (5 → 0)
- ✅ All appointments (unknown count → 0)
- ✅ All services (unknown count → 0)
- ✅ All staff records (unknown count → 0)
- ✅ All customers (unknown count → 0)
- ✅ All call history (0 records in `calls` table)
- ✅ All Telescope monitoring data (0 records)

**Preserved Data**:
- ❌ None - Complete database wipe

**Migration State**:
- Current batch: 6 (should be much higher for production)
- Pending migrations: 96 migrations not yet executed
- Missing tables: `retell_call_sessions` and others (schema incomplete)

### Functional Impact

**Broken**:
1. Admin panel navigation (only Companies visible)
2. All data access (companies, appointments, services, staff)
3. Retell AI integration (no call sessions table)
4. Cal.com integration (no event mappings with data)
5. Customer portal (no customer data)
6. Billing system (no calls, transactions)
7. Analytics/reporting (no historical data)

**Still Working**:
1. Authentication (admin user exists)
2. Basic panel structure (Filament loads)
3. Code deployment (no code changes)

---

## ROOT CAUSE DETERMINATION

### Primary Root Cause: Database Migration Command

**Most Likely Scenario**: Someone executed `php artisan migrate:fresh` or similar

**Evidence**:
1. Complete table drop at 06:28:24 (all 200+ tables)
2. Migrations restarted from batch 1
3. Only core migrations executed (batch 6, should be 30+)
4. Timing coincides with scheduled tasks (06:00 cron)

**Possible Triggers**:
1. Manual command execution via SSH
2. Scheduled task misconfiguration
3. Deployment script with wrong environment flag
4. Testing script run against production database

**Commands that cause this**:
```bash
php artisan migrate:fresh         # Drops all tables, reruns all migrations
php artisan migrate:fresh --seed  # Drops all + seeds data
php artisan migrate:reset         # Rolls back all migrations
php artisan db:wipe              # Drops all tables (Laravel 8+)
```

### Secondary Contributing Factor: Resource Discovery Disabled

**Why only "Companies" shows**:
- Resource auto-discovery disabled (line 53 commented)
- Only `CompanyResource` manually registered (line 56)
- 35 other resources exist but not loaded

**Why this happened**:
- Yesterday's fix for badge errors
- Widget errors causing 500 responses
- Emergency mitigation to restore panel access

**This is NOT data loss** - just visibility issue that can be fixed by re-enabling discovery.

### Tertiary Issue: Guard Mismatch

**Guard configuration**:
- User role: `super_admin` on guard `web`
- Panel guard: `admin`

**Potential impact**:
- Authorization checks may fail
- Policies may not resolve correctly
- Some resources may be hidden even after discovery enabled

**This is NOT data loss** - configuration issue that may affect permissions.

---

## RECOVERY PLAN

### Phase 1: IMMEDIATE - Restore Database (Priority: CRITICAL)

**Available Backup**: `/var/www/backups/P4_pre_next_steps_20251004_112339/`

**Restore Steps**:
```bash
# 1. Stop application (prevent concurrent writes)
php artisan down --render="errors::503" --secret="recovery-token-2025"

# 2. Backup current (empty) state as reference
mysqldump -u askproai_user -paskproai_secure_pass_2024 askproai_db > /var/www/backups/empty_state_20251027_073500.sql

# 3. Restore from October 4 backup
mysql -u askproai_user -paskproai_secure_pass_2024 askproai_db < /var/www/backups/P4_pre_next_steps_20251004_112339/askproai_db_backup.sql

# 4. Run pending migrations (careful - only those after Oct 4)
php artisan migrate --force

# 5. Clear all caches
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
redis-cli FLUSHDB

# 6. Bring application back up
php artisan up
```

**Data Loss Window**: October 4 - October 27 (23 days)
- New appointments in this period: LOST
- New customers in this period: LOST
- New services in this period: LOST
- Configuration changes in this period: LOST

**Risk**: Medium
- Migrations may fail if schema conflicts exist
- Need to carefully review which migrations to run
- Some October 26 changes may need reapplication

### Phase 2: URGENT - Fix Resource Discovery (Priority: HIGH)

**Re-enable resources in AdminPanelProvider**:

```php
// /var/www/api-gateway/app/Providers/Filament/AdminPanelProvider.php

// BEFORE (line 53-56):
// ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
->resources([
    \App\Filament\Resources\CompanyResource::class,
])

// AFTER:
->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
// Remove manual resources array if discovery works
```

**If badge errors reoccur**: Fix badges instead of disabling discovery
- Badge errors are usually from counting queries on missing relationships
- Add `->badge(fn () => 0)` as temporary fix
- Or wrap badge callbacks in try-catch

**Risk**: Low
- May cause badge errors again
- 500 errors if schema mismatches exist
- Need to test each resource individually

### Phase 3: IMPORTANT - Fix Guard Configuration (Priority: MEDIUM)

**Option A: Align everything to 'admin' guard** (Recommended):
```bash
# Update user role to use 'admin' guard
php artisan tinker
$admin = User::where('email', 'admin@askproai.de')->first();
$admin->syncRoles([]); // Remove old roles
$admin->assignRole('super_admin'); // Reassign on correct guard
```

**Option B: Change panel to use 'web' guard**:
```php
// AdminPanelProvider.php line 34
->authGuard('web') // Change from 'admin' to 'web'
```

**Verify guards match**:
```bash
php artisan tinker
$admin = User::where('email', 'admin@askproai.de')->first();
echo "User roles guard: " . $admin->roles->first()->guard_name;
echo "\nPanel guard: admin"; // Should match
```

### Phase 4: CRITICAL - Prevent Future Incidents (Priority: CRITICAL)

#### A. Disable Destructive Commands in Production

**Add to** `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Prevent destructive commands in production
    if (app()->environment('production')) {
        $dangerousCommands = [
            'migrate:fresh',
            'migrate:reset',
            'db:wipe',
            'migrate:refresh'
        ];

        foreach ($dangerousCommands as $cmd) {
            Artisan::command($cmd, function() {
                $this->error('❌ BLOCKED: This command is disabled in production');
                $this->error('Use manual migration review process instead');
                return 1;
            });
        }
    }
}
```

#### B. Implement Automated Backups

**Daily backup cron** (add to `/etc/crontab`):
```bash
# Daily production database backup at 03:00 CET
0 3 * * * root /var/www/api-gateway/scripts/backup-production-database.sh >> /var/log/database-backup.log 2>&1
```

**Create backup script** `/var/www/api-gateway/scripts/backup-production-database.sh`:
```bash
#!/bin/bash
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/www/backups/daily"
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

# Dump database
mysqldump -u askproai_user -paskproai_secure_pass_2024 \
    --single-transaction \
    --routines \
    --triggers \
    askproai_db | gzip > "$BACKUP_DIR/askproai_db_$TIMESTAMP.sql.gz"

# Delete backups older than 30 days
find "$BACKUP_DIR" -name "askproai_db_*.sql.gz" -mtime +$RETENTION_DAYS -delete

# Log success
echo "[$(date)] ✅ Backup completed: askproai_db_$TIMESTAMP.sql.gz"
```

#### C. Add Migration Safety Checks

**Create** `app/Console/Commands/SafeMigrate.php`:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SafeMigrate extends Command
{
    protected $signature = 'migrate:safe {--force}';
    protected $description = 'Run migrations with safety checks';

    public function handle()
    {
        if (app()->environment('production')) {
            if (!$this->option('force')) {
                $this->error('❌ Production migrations require explicit --force flag');
                $this->warn('⚠️  This will modify the production database');
                $this->warn('⚠️  Ensure you have a recent backup first!');
                return 1;
            }

            if (!$this->confirm('Have you verified the backup exists and is recent?')) {
                $this->error('Migration aborted');
                return 1;
            }
        }

        $this->call('migrate', ['--force' => true]);
    }
}
```

**Usage**:
```bash
# Safe way to run migrations in production
php artisan migrate:safe --force

# Dangerous commands are blocked
php artisan migrate:fresh  # ❌ Blocked
```

#### D. Add Database Monitoring

**Monitor table row counts** (add to monitoring):
```bash
# /var/www/api-gateway/scripts/monitor-data-integrity.sh
#!/bin/bash

TABLES=("companies" "appointments" "services" "staff" "customers")
ALERT_THRESHOLD=1  # Alert if any table drops below this

for table in "${TABLES[@]}"; do
    count=$(mysql -u askproai_user -paskproai_secure_pass_2024 \
        -N -e "SELECT COUNT(*) FROM askproai_db.$table")

    if [ "$count" -lt "$ALERT_THRESHOLD" ]; then
        echo "[$(date)] 🚨 ALERT: Table $table has only $count rows!" >> /var/log/data-integrity.log
        # TODO: Send email/Slack notification
    fi
done
```

**Run every 5 minutes** (add to cron):
```bash
*/5 * * * * root /var/www/api-gateway/scripts/monitor-data-integrity.sh
```

---

## VERIFICATION STEPS AFTER RECOVERY

### 1. Database Integrity
```bash
# Check table counts
php artisan tinker
echo "Companies: " . \App\Models\Company::count();
echo "Services: " . \App\Models\Service::count();
echo "Staff: " . \App\Models\Staff::count();
echo "Appointments: " . \App\Models\Appointment::count();
echo "Customers: " . \App\Models\Customer::count();

# Expected (from backup):
# Companies: 5
# Services: >0
# Staff: >0
# Appointments: >0 (historical data)
# Customers: >0
```

### 2. Admin Panel Access
```bash
# Login as admin@askproai.de
# Verify all menu items visible:
# - Companies ✅
# - Appointments ✅
# - Services ✅
# - Staff ✅
# - Customers ✅
# - Calls ✅
# - Phone Numbers ✅
# - Branches ✅
# ... (all 36 resources)
```

### 3. Resource Navigation
- Click through each resource
- Verify data displays correctly
- Check that badges don't cause errors
- Confirm filters and actions work

### 4. Integration Health
```bash
# Check Cal.com mappings
php artisan tinker
echo "Cal.com Event Mappings: " . \App\Models\CalcomEventMap::count();

# Check Retell data (table may need recreation)
# May show error if table doesn't exist - that's expected
```

### 5. Permission Verification
```bash
# Verify admin has correct roles
php artisan tinker
$admin = \App\Models\User::where('email', 'admin@askproai.de')->first();
echo "Roles: " . $admin->getRoleNames();
echo "Guard: " . $admin->roles->first()->guard_name;
echo "Can access admin panel: " . ($admin->hasRole('super_admin') ? 'Yes' : 'No');
```

---

## LESSONS LEARNED

### What Went Wrong

1. **No destructive command protection** in production environment
2. **No automated daily backups** (last backup 23 days old)
3. **No data integrity monitoring** to detect immediate data loss
4. **No migration safety checks** before execution
5. **Emergency fixes** (disabled discovery) masked underlying issues instead of fixing root cause

### What Went Right

1. **Backup existed** from October 4 (23 days old but recoverable)
2. **Code repository intact** (no code loss, only data)
3. **User reported quickly** (within hours of incident)
4. **Auth system survived** (admin user still exists)

### Preventive Measures Needed

1. ✅ Block destructive commands in production
2. ✅ Implement daily automated backups (30-day retention)
3. ✅ Add data integrity monitoring (alert on table drops)
4. ✅ Implement migration safety wrapper (`migrate:safe`)
5. ✅ Document recovery procedures
6. ✅ Fix badge errors properly (don't disable discovery)
7. ✅ Align authentication guards (web vs admin)
8. ✅ Test recovery process periodically

---

## APPENDIX A: Command Reference

### Safe Database Operations
```bash
# View pending migrations (safe)
php artisan migrate:status

# Run pending migrations (safe in production)
php artisan migrate:safe --force

# Rollback last batch (use with caution)
php artisan migrate:rollback --step=1

# Create backup before changes
mysqldump -u askproai_user -p askproai_db | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### NEVER Run in Production
```bash
❌ php artisan migrate:fresh      # Drops all tables
❌ php artisan migrate:fresh --seed
❌ php artisan migrate:reset       # Rolls back everything
❌ php artisan migrate:refresh     # Reset + migrate
❌ php artisan db:wipe             # Drops all tables
```

### Cache Management
```bash
# Clear all caches (safe)
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Redis flush (use with caution)
redis-cli FLUSHDB  # Clears current DB
redis-cli FLUSHALL # Clears all Redis DBs
```

---

## APPENDIX B: Contact Information

**Incident Owner**: Claude (Root Cause Analyst)
**Reported By**: admin@askproai.de
**Incident Date**: 2025-10-27 06:28:24 CET
**Report Date**: 2025-10-27 07:31:00 CET
**Document Version**: 1.0

**For Recovery Assistance**:
- Backup Location: `/var/www/backups/P4_pre_next_steps_20251004_112339/`
- Restore Script: `/var/www/backups/P4_pre_next_steps_20251004_112339/restore.sh`
- This RCA: `/var/www/api-gateway/RCA_ADMIN_PANEL_DATA_LOSS_2025-10-27.md`

---

## APPROVAL

**Status**: AWAITING USER DECISION

**Options**:
1. **Execute Recovery Plan** - Restore from October 4 backup (23 days data loss)
2. **Investigate Further** - Check if more recent backups exist elsewhere
3. **Accept Data Loss** - Start fresh and rebuild from scratch

**Recommended Action**: Execute Phase 1 (Restore Database) IMMEDIATELY

⚠️ **WARNING**: Every minute of delay increases risk of:
- User confusion
- Lost business (appointments can't be booked)
- Integration failures (Retell, Cal.com)
- Customer trust erosion

---

**END OF ROOT CAUSE ANALYSIS**
