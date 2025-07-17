# Migration Guide Template

> 📋 **Migration**: {FromVersion} → {ToVersion}  
> 📅 **Release Date**: {DATE}  
> ⏱️ **Estimated Duration**: {TIME_ESTIMATE}  
> 🚨 **Risk Level**: {LOW/MEDIUM/HIGH}

## Executive Summary

### What's Changing
Brief overview of major changes and why they're necessary.

### Impact Analysis
| Component | Impact Level | Downtime Required | Rollback Possible |
|-----------|--------------|-------------------|-------------------|
| Database | High | 30 minutes | Yes (backup required) |
| API | Medium | None (backward compatible) | Yes |
| UI | Low | None | Yes |
| Configuration | Medium | 5 minutes | Yes |

### Key Benefits
- 🚀 Performance improvement: X% faster
- 🔒 Security enhancements: New encryption
- ✨ New features: List major features
- 🐛 Bug fixes: Critical issues resolved

## Pre-Migration Checklist

### Prerequisites
- [ ] Current version is {RequiredVersion} or higher
- [ ] Full backup completed (less than 24 hours old)
- [ ] Maintenance window scheduled
- [ ] Team notified
- [ ] Rollback plan prepared
- [ ] Test environment validated

### System Requirements
| Component | Current | New Required | Action Needed |
|-----------|---------|--------------|---------------|
| PHP | 8.1 | 8.3 | Upgrade PHP |
| MySQL | 8.0 | 8.0.35+ | Minor update |
| Redis | 6.2 | 7.0 | Major upgrade |
| Node.js | 16 | 20 | Major upgrade |

### Backup Requirements
```bash
# Create full backup before migration
./scripts/backup-full.sh --tag pre-migration-v2

# Verify backup
./scripts/backup-verify.sh --tag pre-migration-v2

# Test restore process
./scripts/backup-restore-test.sh --tag pre-migration-v2
```

## Migration Steps

### Step 1: Preparation Phase (T-24 hours)

#### 1.1 Create Backups
```bash
# Database backup
mysqldump -u root -p --all-databases > backup_$(date +%Y%m%d_%H%M%S).sql

# Application files backup
tar -czf app_backup_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/api-gateway

# Configuration backup
cp -r /etc/nginx /backup/nginx_$(date +%Y%m%d)
cp .env .env.backup.$(date +%Y%m%d)
```

#### 1.2 Notify Users
```php
// Send notification to all users
php artisan migration:notify --template=scheduled-maintenance --time="2025-01-15 02:00"
```

#### 1.3 Test Migration
```bash
# Run migration in test environment
./scripts/test-migration.sh --version=v2.0.0 --env=staging
```

### Step 2: Pre-Migration Tasks (T-1 hour)

#### 2.1 Enable Maintenance Mode
```bash
# Enable with custom message
php artisan down --render="maintenance" \
    --message="System upgrade in progress. Back at 03:00 CET" \
    --retry=3600 \
    --secret="bypass-secret-key"
```

#### 2.2 Stop Background Jobs
```bash
# Gracefully stop workers
php artisan queue:restart
php artisan horizon:terminate

# Wait for jobs to complete
php artisan queue:monitor --wait-for-empty
```

#### 2.3 Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 3: Database Migration (30 minutes)

#### 3.1 Schema Updates
```bash
# Run migrations
php artisan migrate --force

# If using multiple databases
php artisan migrate --database=secondary --force
```

#### 3.2 Data Transformations
```php
// Run data migration command
php artisan migrate:data --version=2.0.0

// Example data migration
class MigrateUserDataV2 extends Command
{
    public function handle()
    {
        User::chunk(1000, function ($users) {
            foreach ($users as $user) {
                // Transform data
                $user->new_field = $this->transformOldField($user->old_field);
                $user->save();
            }
        });
    }
}
```

#### 3.3 Index Optimization
```sql
-- Add new indexes
CREATE INDEX idx_appointments_date ON appointments(appointment_date, status);
CREATE INDEX idx_users_email_verified ON users(email, email_verified_at);

-- Remove obsolete indexes
DROP INDEX idx_old_unused ON table_name;
```

### Step 4: Application Update (15 minutes)

#### 4.1 Deploy New Code
```bash
# Pull new version
git fetch --tags
git checkout tags/v2.0.0

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run production

# Copy new assets
php artisan storage:link
php artisan vendor:publish --tag=public --force
```

#### 4.2 Environment Updates
```bash
# Update .env file
cp .env.v2.example .env.new
./scripts/merge-env.sh .env .env.new > .env.updated
mv .env.updated .env

# Validate configuration
php artisan config:cache
php artisan route:cache
```

#### 4.3 Service Restarts
```bash
# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart web server
sudo systemctl restart nginx

# Start queue workers
php artisan horizon
```

### Step 5: Post-Migration Tasks (15 minutes)

#### 5.1 Health Checks
```bash
# Run comprehensive health check
php artisan health:check --detailed

# Verify critical features
php artisan migrate:verify --checklist=v2.0.0

# Test API endpoints
./scripts/api-smoke-test.sh
```

#### 5.2 Cache Warming
```bash
# Warm application caches
php artisan cache:warmup

# Pre-generate common views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

#### 5.3 Disable Maintenance Mode
```bash
# Bring application back online
php artisan up

# Verify site is accessible
curl -I https://api.askproai.de/health
```

## Data Migration Details

### Schema Changes
```sql
-- Added columns
ALTER TABLE users ADD COLUMN preferences JSON DEFAULT NULL;
ALTER TABLE appointments ADD COLUMN metadata JSON DEFAULT NULL;

-- Modified columns
ALTER TABLE customers MODIFY phone VARCHAR(20);

-- Renamed columns
ALTER TABLE staff CHANGE old_name new_name VARCHAR(255);

-- Dropped columns (after data migration)
ALTER TABLE legacy_table DROP COLUMN deprecated_field;
```

### Data Transformations
```php
// Example: Migrate phone numbers to new format
class MigratePhoneNumbers extends Migration
{
    public function up()
    {
        Customer::chunk(1000, function ($customers) {
            foreach ($customers as $customer) {
                $customer->phone = $this->formatPhoneNumber($customer->phone);
                $customer->save();
            }
        });
    }
    
    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if missing
        if (!str_starts_with($phone, '49')) {
            $phone = '49' . $phone;
        }
        
        return '+' . $phone;
    }
}
```

## Breaking Changes

### API Changes
| Endpoint | Old | New | Migration Required |
|----------|-----|-----|-------------------|
| GET /api/users | Returns array | Returns paginated object | Update client parsing |
| POST /api/appointments | `date` field | `appointment_date` field | Update field name |
| DELETE /api/resource/{id} | Hard delete | Soft delete | Update expectations |

### Code Changes
```php
// Old way (deprecated)
$user = User::findByEmail($email);

// New way
$user = User::where('email', $email)->first();

// Old config access
config('app.feature.enabled');

// New config access
config('features.app.enabled');
```

### Configuration Changes
```env
# Renamed variables
OLD_API_KEY → NEW_SERVICE_API_KEY
CACHE_DRIVER → CACHE_STORE

# New required variables
QUEUE_BATCH_SIZE=1000
RATE_LIMIT_PER_MINUTE=60
FEATURE_FLAGS_ENABLED=true

# Deprecated variables (remove)
LEGACY_MODE=false
OLD_SYSTEM_COMPAT=false
```

## Rollback Procedure

### Quick Rollback (< 5 minutes)
```bash
# 1. Enable maintenance mode
php artisan down

# 2. Restore code
git checkout tags/v1.x.x

# 3. Restore dependencies
composer install --no-dev
npm ci && npm run production

# 4. Rollback migrations
php artisan migrate:rollback --step=5

# 5. Clear caches
php artisan optimize:clear

# 6. Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx

# 7. Disable maintenance mode
php artisan up
```

### Full Rollback (30 minutes)
```bash
# 1. Restore database
mysql -u root -p < backup_20250115_020000.sql

# 2. Restore application files
tar -xzf app_backup_20250115_020000.tar.gz -C /

# 3. Restore configuration
cp .env.backup.20250115 .env

# 4. Clear all caches
redis-cli FLUSHALL

# 5. Restart all services
./scripts/restart-all-services.sh

# 6. Verify rollback
php artisan health:check
```

## Testing & Validation

### Automated Tests
```bash
# Run migration tests
php artisan test --testsuite=Migration

# Run smoke tests
./scripts/smoke-tests.sh

# Run integration tests
php artisan test --group=integration
```

### Manual Test Checklist
- [ ] User login/logout
- [ ] Create new appointment
- [ ] View existing data
- [ ] API authentication
- [ ] Payment processing
- [ ] Email notifications
- [ ] Background jobs
- [ ] Third-party integrations

### Performance Validation
```bash
# Benchmark key operations
php artisan benchmark:run --compare=v1.x.x

# Load test
ab -n 1000 -c 10 https://api.askproai.de/api/health

# Database performance
php artisan db:analyze --slow-queries
```

## Monitoring

### Key Metrics to Watch
| Metric | Normal Range | Alert Threshold | Action |
|--------|--------------|-----------------|--------|
| Response Time | < 200ms | > 500ms | Check logs |
| Error Rate | < 0.1% | > 1% | Investigate |
| CPU Usage | < 60% | > 80% | Scale up |
| Memory Usage | < 70% | > 85% | Optimize |
| Queue Depth | < 100 | > 1000 | Add workers |

### Post-Migration Monitoring
```bash
# Real-time monitoring
watch -n 5 'php artisan monitor:dashboard'

# Log analysis
tail -f storage/logs/laravel.log | grep -E "ERROR|CRITICAL"

# Performance tracking
php artisan metrics:collect --interval=1m --duration=1h
```

## Communication Plan

### Internal Communication
- **T-1 week**: Initial announcement to team
- **T-1 day**: Final reminder with details
- **T-0**: Live updates in Slack
- **T+1 day**: Post-migration report

### Customer Communication
```php
// Notification templates
class MigrationNotifications
{
    public function preMaintenanceNotice()
    {
        return [
            'subject' => 'Scheduled Maintenance - System Upgrade',
            'body' => 'We will be upgrading our systems on {date} from {start} to {end}...',
        ];
    }
    
    public function postMigrationNotice()
    {
        return [
            'subject' => 'System Upgrade Complete',
            'body' => 'Our system upgrade is complete. New features include...',
        ];
    }
}
```

## Troubleshooting

### Common Issues

#### Database Connection Errors
```bash
# Check connection
php artisan db:ping

# Reset connections
php artisan db:reconnect

# Verify credentials
php artisan tinker
>>> DB::connection()->getPdo();
```

#### Cache Issues
```bash
# Clear all caches
php artisan cache:clear
redis-cli FLUSHALL

# Rebuild caches
php artisan config:cache
php artisan route:cache
```

#### Permission Problems
```bash
# Fix permissions
chown -R www-data:www-data storage/
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

## Support Resources

### Documentation
- [Version 2.0 Release Notes](./releases/v2.0.0.md)
- [API Migration Guide](./api-migration-v2.md)
- [Database Changes](./database-changes-v2.md)

### Contact Points
- **Migration Support**: migration-support@askproai.de
- **Emergency Hotline**: +49-XXX-MIGRATION
- **Slack Channel**: #migration-v2-support

---

> 🔄 **Auto-Updated**: This documentation is automatically checked for updates. Last verification: {TIMESTAMP}  
> ⏰ **Next Review**: Before next major migration