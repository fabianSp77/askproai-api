# Deployment Plan - Intelligent Appointment Management System

## 🚨 CRITICAL: Production Environment Status

**Current State (2025-10-02):**
- Environment: `APP_ENV=production`
- Database: `askproai_db` (Production MySQL)
- URL: `api.askproai.de`
- Status: **Code ready, DB migrations NOT executed**

## ✅ Validated Components (testing.sqlite)

### Migrations (7 tables)
- ✅ `policy_configurations` - 4-level hierarchy
- ✅ `appointment_modifications` - Audit trail
- ✅ `appointment_modification_stats` - Materialized view
- ✅ `callback_requests` - Callback management
- ✅ `callback_escalations` - SLA tracking
- ✅ `notification_configurations` - Hierarchical notifications
- ✅ `notification_event_mappings` - 13 seeded events

### Models (7 models + 1 trait)
- ✅ PolicyConfiguration, AppointmentModification, AppointmentModificationStat
- ✅ CallbackRequest, CallbackEscalation
- ✅ NotificationConfiguration, NotificationEventMapping
- ✅ HasConfigurationInheritance trait (integrated into Company, Branch, Service, Staff)

### Features Validated
- ✅ CRUD operations working
- ✅ Relationships working (polymorphic, belongsTo, hasMany)
- ✅ Redis cache working (5min TTL)
- ✅ getEffectivePolicyConfig() with cache
- ✅ Model methods (markContacted, resolve, escalate)

## 📋 Pre-Deployment Checklist

### 1. Staging Environment (REQUIRED)
- [ ] Create staging database
- [ ] Run migrations on staging
- [ ] Test with production-like data
- [ ] Validate all relationships
- [ ] Performance testing (100+ records)

### 2. Production Preparation
- [ ] Database backup BEFORE migration
- [ ] Rollback plan documented
- [ ] Maintenance window scheduled
- [ ] Stakeholder notification sent

### 3. Migration Fixes Applied
- ✅ Removed `DB::statement("COMMENT ON TABLE...")` (SQLite incompatible)
- ✅ All foreign key constraints reviewed
- ✅ Indexes optimized for query patterns

## 🚀 Production Deployment Steps

### Step 1: Database Backup (CRITICAL)
```bash
# Backup production database
mysqldump -u root -p askproai_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup
mysql -u root -p -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='askproai_db';"
```

### Step 2: Execute Migrations (WITH --force)
```bash
cd /var/www/api-gateway

# Check migration status
php artisan migrate:status

# Execute new migrations (PRODUCTION)
php artisan migrate --force

# Verify tables created
php artisan migrate:status | grep "2025_10_01"
```

### Step 3: Verify Seeded Data
```bash
# Check notification_event_mappings seeded
php artisan tinker --execute="echo App\Models\NotificationEventMapping::count() . ' events seeded';"
```

### Step 4: Verify Model Integration
```bash
# Verify trait on Company model
php artisan tinker --execute="
\$company = App\Models\Company::first();
echo 'Trait loaded: ' . (method_exists(\$company, 'getEffectivePolicyConfig') ? 'Yes' : 'No');
"
```

### Step 5: Cache Warmup (Optional)
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔄 Rollback Plan

### If Migration Fails
```bash
# Rollback last batch
php artisan migrate:rollback --step=7

# Restore from backup
mysql -u root -p askproai_db < backup_TIMESTAMP.sql
```

### If Models Fail
```bash
# Revert model files
git checkout HEAD~1 -- app/Models/

# Remove trait from existing models
git checkout HEAD~1 -- app/Models/Company.php app/Models/Branch.php app/Models/Service.php app/Models/Staff.php
```

## 📊 Post-Deployment Validation

### 1. Migration Status
```bash
php artisan migrate:status | grep "2025_10_01"
# Expected: All 7 migrations show "Ran"
```

### 2. Table Verification
```bash
mysql -u root -p askproai_db -e "
SHOW TABLES LIKE 'policy_configurations';
SHOW TABLES LIKE 'callback_requests';
SHOW TABLES LIKE 'notification_event_mappings';
"
```

### 3. Seeded Data
```bash
mysql -u root -p askproai_db -e "
SELECT COUNT(*) as event_count FROM notification_event_mappings;
SELECT event_type, event_label FROM notification_event_mappings LIMIT 5;
"
```

### 4. Model Test
```bash
php artisan tinker --execute="
\$company = App\Models\Company::first();
\$policy = new App\Models\PolicyConfiguration();
\$policy->configurable_type = get_class(\$company);
\$policy->configurable_id = \$company->id;
\$policy->policy_type = 'cancellation';
\$policy->config = ['hours_before' => 24, 'fee' => 10.00];
\$policy->save();
echo 'Test policy created: ID ' . \$policy->id;
\$policy->delete();
"
```

### 5. Cache Test
```bash
php artisan tinker --execute="
\$company = App\Models\Company::first();
\$config = \$company->getEffectivePolicyConfig('cancellation');
echo 'getEffectivePolicyConfig() works: ' . (is_array(\$config) || is_null(\$config) ? 'Yes' : 'No');
"
```

## ⚠️ Known Issues & Limitations

### SQLite vs MySQL Differences
- **Fixed**: `COMMENT ON TABLE` statements removed (SQLite incompatible)
- **Note**: Comments preserved in migration file PHPDoc blocks

### Foreign Key Constraints
- `callback_requests` requires `branches`, `customers`, `services`, `staff` tables
- `appointment_modifications` requires `appointments`, `customers` tables
- **Testing**: Used `PRAGMA foreign_keys = OFF` in SQLite tests
- **Production**: All parent tables exist, no issues expected

### Production Environment Constraints
- No direct `php artisan migrate` (requires `--force`)
- Maintenance window recommended for deployment
- Monitor Redis memory usage after deployment

## 📅 Deployment Schedule

### Recommended Timeline
- **Day 15**: Final validation on staging
- **Day 16**: Production deployment (during low-traffic window)
- **Day 17**: Post-deployment monitoring

### Rollback Window
- First 24 hours: Full rollback capability
- After 24 hours: Data migration required for rollback

## 📞 Emergency Contacts

**If deployment fails:**
1. Stop execution immediately
2. Execute rollback plan
3. Notify stakeholders
4. Document error details

---

**Document Version**: 1.0
**Last Updated**: 2025-10-02
**Status**: Ready for Staging Deployment
