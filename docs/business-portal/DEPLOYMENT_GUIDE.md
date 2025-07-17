# Business Portal Deployment Guide

## Overview

This guide covers the complete deployment process for the Business Portal, including pre-deployment checks, deployment steps, post-deployment verification, and rollback procedures.

## Prerequisites

### Server Requirements

- **OS**: Ubuntu 20.04+ or similar Linux distribution
- **PHP**: 8.3+ with required extensions
- **Node.js**: 18+ with npm
- **Database**: MariaDB 10.6+ or MySQL 8.0+
- **Redis**: 6.2+
- **Web Server**: Nginx 1.21+ or Apache 2.4+
- **RAM**: Minimum 4GB (8GB recommended)
- **Storage**: 20GB+ available

### Required PHP Extensions

```bash
# Check PHP extensions
php -m

# Required extensions:
- bcmath
- ctype
- curl
- dom
- fileinfo
- json
- mbstring
- openssl
- pdo
- pdo_mysql
- redis
- tokenizer
- xml
- zip
```

### Access Requirements

- SSH access to production server
- Database admin credentials
- Deploy keys for Git repository
- Access to external service dashboards (Retell, Cal.com, Stripe)

## Pre-Deployment Checklist

### 1. Code Review

```bash
# Ensure on correct branch
git checkout main
git pull origin main

# Run tests
php artisan test
npm run test

# Check code quality
composer quality
npm run lint

# Security audit
composer audit
npm audit
```

### 2. Environment Preparation

```bash
# Backup current .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Verify all required env variables
php artisan env:check

# Test external service connections
php artisan services:check
```

### 3. Database Backup

```bash
# Create full database backup
mysqldump -u root -p askproai_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup
mysql -u root -p askproai_db_test < backup_*.sql
```

## Deployment Process

### Step 1: Enable Maintenance Mode

```bash
# Enable maintenance mode with custom message
php artisan down --message="System upgrade in progress" --retry=60

# Or with specific allowed IPs
php artisan down --allow=192.168.1.1 --allow=10.0.0.1
```

### Step 2: Pull Latest Code

```bash
# Navigate to project directory
cd /var/www/api-gateway

# Pull latest changes
git fetch --all
git checkout main
git pull origin main

# Verify correct commit
git log -1 --oneline
```

### Step 3: Install Dependencies

```bash
# Install Composer dependencies (production)
composer install --no-dev --optimize-autoloader

# Install NPM dependencies
npm ci --production

# Or with legacy peer deps if needed
npm ci --production --legacy-peer-deps
```

### Step 4: Build Frontend Assets

```bash
# Build production assets
npm run build

# Verify build output
ls -la public/build/
```

### Step 5: Run Migrations

```bash
# Check pending migrations
php artisan migrate:status

# Run migrations (with force for production)
php artisan migrate --force

# If using seed data
php artisan db:seed --class=ProductionSeeder --force
```

### Step 6: Clear and Rebuild Caches

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild optimization caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Clear OPcache (if enabled)
php artisan opcache:clear
```

### Step 7: Update Queue Workers

```bash
# Restart Horizon gracefully
php artisan horizon:terminate

# Wait for current jobs to finish
sleep 10

# Start Horizon again (usually handled by supervisor)
php artisan horizon
```

### Step 8: Update Cron Jobs

```bash
# Check current crontab
crontab -l

# Edit if needed
crontab -e

# Ensure Laravel scheduler is present
* * * * * cd /var/www/api-gateway && php artisan schedule:run >> /dev/null 2>&1
```

### Step 9: Restart Services

```bash
# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart web server
sudo systemctl restart nginx

# Restart Redis if needed
sudo systemctl restart redis

# Restart supervisor for queue workers
sudo supervisorctl restart all
```

### Step 10: Disable Maintenance Mode

```bash
# Bring application back online
php artisan up

# Verify site is accessible
curl -I https://portal.askproai.de
```

## Post-Deployment Verification

### 1. Health Checks

```bash
# Run comprehensive health check
php artisan portal:health-check

# Check specific components
php artisan health:check database
php artisan health:check redis
php artisan health:check queue
```

### 2. Functionality Tests

```bash
# Test API endpoints
curl https://api.askproai.de/api/health
curl https://api.askproai.de/api/v2/portal/test

# Test authentication
php test-portal-login.php

# Test critical features
php artisan test:critical --production
```

### 3. Monitor Logs

```bash
# Check for errors
tail -f storage/logs/laravel.log | grep -i error

# Monitor nginx logs
tail -f /var/log/nginx/error.log

# Check queue failures
php artisan queue:failed
```

### 4. Performance Verification

```bash
# Check response times
ab -n 100 -c 10 https://portal.askproai.de/

# Monitor resource usage
htop
df -h
free -m
```

## Rollback Procedures

### Quick Rollback (< 5 minutes)

```bash
# 1. Enable maintenance mode
php artisan down

# 2. Revert code
git checkout HEAD~1

# 3. Restore dependencies
composer install --no-dev --optimize-autoloader
npm ci --production

# 4. Rebuild assets
npm run build

# 5. Clear caches
php artisan optimize:clear

# 6. Restart services
sudo systemctl restart php8.3-fpm nginx

# 7. Disable maintenance mode
php artisan up
```

### Database Rollback

```bash
# 1. Check migration history
php artisan migrate:status

# 2. Rollback last batch
php artisan migrate:rollback --force

# Or rollback specific steps
php artisan migrate:rollback --step=2 --force

# 3. If complete restore needed
mysql -u root -p askproai_db < backup_20250110_120000.sql
```

## Deployment Automation

### GitHub Actions Workflow

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to server
        uses: appleboy/ssh-action@v0.1.5
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/api-gateway
            php artisan down
            git pull origin main
            composer install --no-dev --optimize-autoloader
            npm ci --production
            npm run build
            php artisan migrate --force
            php artisan optimize
            php artisan up
```

### Deployment Script

```bash
#!/bin/bash
# deploy.sh

set -e

echo "Starting deployment..."

# Configuration
APP_DIR="/var/www/api-gateway"
BACKUP_DIR="/var/backups/askproai"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Functions
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Pre-deployment backup
log "Creating backup..."
cd $APP_DIR
mysqldump -u root -p'password' askproai_db > "$BACKUP_DIR/db_$TIMESTAMP.sql"
tar -czf "$BACKUP_DIR/app_$TIMESTAMP.tar.gz" --exclude=node_modules --exclude=vendor .

# Enable maintenance mode
log "Enabling maintenance mode..."
php artisan down --message="System upgrade in progress" --retry=60

# Pull latest code
log "Pulling latest code..."
git fetch --all
git checkout main
git pull origin main

# Install dependencies
log "Installing dependencies..."
composer install --no-dev --optimize-autoloader
npm ci --production

# Build assets
log "Building assets..."
npm run build

# Run migrations
log "Running migrations..."
php artisan migrate --force

# Clear and rebuild caches
log "Optimizing application..."
php artisan optimize:clear
php artisan optimize

# Restart services
log "Restarting services..."
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
php artisan horizon:terminate
sleep 5

# Disable maintenance mode
log "Bringing application online..."
php artisan up

# Post-deployment checks
log "Running health checks..."
php artisan health:check

log "Deployment completed successfully!"
```

## Environment-Specific Configurations

### Production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.askproai.de

LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Staging

```env
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://staging.askproai.de

LOG_LEVEL=debug
SESSION_SECURE_COOKIE=true

# Use separate databases
DB_DATABASE=askproai_staging
REDIS_PREFIX=staging_
```

## Monitoring Post-Deployment

### Set Up Monitoring

```bash
# Health check endpoint
curl https://portal.askproai.de/api/health

# Monitor with cron
*/5 * * * * /usr/local/bin/check-portal-health.sh
```

### Key Metrics to Monitor

1. **Response Times**: < 200ms for API calls
2. **Error Rate**: < 0.1%
3. **Queue Size**: < 1000 pending jobs
4. **Memory Usage**: < 80% of available
5. **Disk Space**: > 20% free
6. **Database Connections**: < 80% of max

### Alert Configuration

```php
// config/monitoring.php
return [
    'alerts' => [
        'response_time' => ['threshold' => 500, 'unit' => 'ms'],
        'error_rate' => ['threshold' => 1, 'unit' => '%'],
        'queue_size' => ['threshold' => 5000, 'unit' => 'jobs'],
        'disk_space' => ['threshold' => 10, 'unit' => '%'],
    ],
    'channels' => ['slack', 'email'],
];
```

## Security Considerations

### Post-Deployment Security

1. **Verify File Permissions**
   ```bash
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chmod -R 775 storage bootstrap/cache
   ```

2. **Check SSL Certificate**
   ```bash
   openssl s_client -connect portal.askproai.de:443 -servername portal.askproai.de
   ```

3. **Review Security Headers**
   ```bash
   curl -I https://portal.askproai.de
   ```

4. **Rotate Secrets if Needed**
   ```bash
   php artisan key:generate
   php artisan config:cache
   ```

## Troubleshooting Deployment Issues

### Common Issues

1. **White Screen / 500 Error**
   ```bash
   # Check logs
   tail -f storage/logs/laravel.log
   tail -f /var/log/nginx/error.log
   
   # Check permissions
   chown -R www-data:www-data storage bootstrap/cache
   ```

2. **Assets Not Loading**
   ```bash
   # Rebuild assets
   npm run build
   
   # Check manifest
   cat public/build/manifest.json
   ```

3. **Queue Not Processing**
   ```bash
   # Check Horizon status
   php artisan horizon:status
   
   # Restart supervisor
   sudo supervisorctl restart all
   ```

4. **Database Connection Failed**
   ```bash
   # Test connection
   php artisan tinker
   >>> DB::connection()->getPdo();
   
   # Check credentials
   grep DB_ .env
   ```

## Best Practices

1. **Always deploy during low-traffic periods**
2. **Have a rollback plan ready**
3. **Test deployment process on staging first**
4. **Monitor closely for 24 hours post-deployment**
5. **Document any manual steps or exceptions**
6. **Keep deployment logs for audit trail**
7. **Notify team members before and after deployment**

## Deployment Checklist Template

```markdown
## Deployment Checklist - [Date]

### Pre-Deployment
- [ ] Code reviewed and approved
- [ ] Tests passing
- [ ] Staging deployment successful
- [ ] Database backup created
- [ ] Team notified

### Deployment
- [ ] Maintenance mode enabled
- [ ] Code deployed
- [ ] Dependencies installed
- [ ] Assets built
- [ ] Migrations run
- [ ] Caches cleared
- [ ] Services restarted
- [ ] Maintenance mode disabled

### Post-Deployment
- [ ] Health checks passing
- [ ] Critical features tested
- [ ] No errors in logs
- [ ] Performance acceptable
- [ ] Monitoring alerts configured
- [ ] Team notified of completion

### Notes
- Deployed commit: 
- Duration: 
- Issues encountered: 
- Rollback required: No
```

---

*For more information, see the [main documentation](./BUSINESS_PORTAL_COMPLETE_DOCUMENTATION.md)*