# AskProAI Deployment Guide

**Version**: 1.0  
**Date**: 2025-06-18  
**Status**: Production Ready

## Table of Contents

1. [Overview](#overview)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Deployment Process](#deployment-process)
4. [Rollback Procedures](#rollback-procedures)
5. [Monitoring & Verification](#monitoring--verification)
6. [Backup & Recovery](#backup--recovery)
7. [Troubleshooting](#troubleshooting)
8. [Emergency Contacts](#emergency-contacts)

## Overview

This guide provides step-by-step instructions for deploying AskProAI to production. The deployment process is designed to be zero-downtime with automatic rollback capabilities.

### Key Features
- **Zero-downtime deployment** using maintenance mode
- **Automatic health checks** after deployment
- **Rollback capability** with automated backup
- **Comprehensive monitoring** during and after deployment
- **Automated backup system** with retention policies

## Pre-Deployment Checklist

### 1. Run Pre-Deployment Check Script

```bash
cd /var/www/api-gateway
./deploy/pre-deploy-check.sh
```

This script verifies:
- ✅ Environment requirements (PHP, Node, Redis, MySQL)
- ✅ Application configuration
- ✅ Database connectivity and migrations
- ✅ External service availability
- ✅ File permissions
- ✅ Security settings

### 2. Manual Verifications

- [ ] Recent backup exists (check `/var/backups/askproai/`)
- [ ] Team is notified of deployment window
- [ ] No critical alerts in monitoring
- [ ] Recent commits are tested in staging
- [ ] Database migrations reviewed
- [ ] Rollback plan communicated

### 3. Environment Variables

Ensure these are set in `.env.production`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.askproai.de

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=askproai
DB_USERNAME=askproai_user
DB_PASSWORD=<secure-password>

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=<redis-password>

# Cal.com Integration
DEFAULT_CALCOM_API_KEY=<api-key>
DEFAULT_CALCOM_TEAM_SLUG=<team-slug>

# Retell.ai Integration
DEFAULT_RETELL_API_KEY=<api-key>
DEFAULT_RETELL_AGENT_ID=<agent-id>

# Queue Configuration
QUEUE_CONNECTION=redis
HORIZON_PREFIX=horizon:askproai
```

## Deployment Process

### Step 1: Connect to Production Server

```bash
ssh deploy@production-server
cd /var/www/api-gateway
```

### Step 2: Run Deployment Script

```bash
./deploy/production-deploy.sh
```

The script automatically:
1. Checks disk space and services
2. Creates backup
3. Enables maintenance mode
4. Pulls latest code
5. Installs dependencies
6. Runs migrations
7. Optimizes application
8. Restarts services
9. Runs health checks
10. Disables maintenance mode
11. Monitors for 5 minutes

### Step 3: Monitor Deployment

Watch the deployment output. Key indicators:
- `[✓] Health check passed`
- `[✓] Cal.com integration healthy`
- `[✓] Queue workers running`
- `[✓] Cache warmed up`

### Manual Deployment (If Automated Fails)

```bash
# 1. Enable maintenance mode
php artisan down

# 2. Pull latest code
git pull origin main

# 3. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci --production

# 4. Run migrations
php artisan migrate --force

# 5. Clear and optimize caches
php artisan optimize:clear
php artisan optimize

# 6. Restart queue workers
php artisan horizon:terminate
sleep 5
php artisan horizon

# 7. Disable maintenance mode
php artisan up
```

## Rollback Procedures

### Automatic Rollback

The deployment script automatically rolls back if:
- Migration fails
- Health check fails after deployment
- Critical error during deployment

### Manual Rollback

```bash
# Quick rollback to latest backup
./deploy/rollback.sh

# Rollback to specific backup
./deploy/rollback.sh /var/backups/askproai/backup-20250618-120000.tar.gz
```

### Rollback Steps

1. **Immediate Actions**
   ```bash
   # Enable maintenance mode
   php artisan down
   
   # Stop services
   php artisan horizon:terminate
   ```

2. **Restore from Backup**
   ```bash
   # The rollback script handles this automatically
   ./deploy/rollback.sh
   ```

3. **Verify Rollback**
   ```bash
   # Check application version
   git log -1 --oneline
   
   # Run health check
   curl http://localhost/api/health
   ```

## Monitoring & Verification

### Health Check Endpoints

1. **Main Health Check**
   ```bash
   curl https://api.askproai.de/api/health
   ```
   
   Expected response:
   ```json
   {
     "status": "healthy",
     "checks": {
       "database": {"status": "healthy"},
       "cache": {"status": "healthy"},
       "queue": {"status": "healthy"}
     }
   }
   ```

2. **Cal.com Integration Health**
   ```bash
   curl https://api.askproai.de/api/health/calcom
   ```

3. **Metrics Endpoint**
   ```bash
   curl https://api.askproai.de/api/metrics
   ```

### Post-Deployment Verification

1. **Test Critical Flows**
   - Make test phone call
   - Verify appointment creation
   - Check webhook processing
   - Confirm email delivery

2. **Monitor Logs**
   ```bash
   # Application logs
   tail -f storage/logs/laravel.log
   
   # Queue logs
   php artisan horizon:status
   
   # Nginx logs
   tail -f /var/log/nginx/access.log
   ```

3. **Check Metrics**
   - Response times < 200ms
   - Error rate < 1%
   - Queue size < 100
   - Memory usage stable

## Backup & Recovery

### Automated Backups

Backups run automatically via cron:
- **Daily**: 2:00 AM
- **Weekly**: Sunday 3:00 AM
- **Monthly**: 1st day 4:00 AM

### Manual Backup

```bash
./deploy/backup-automation.sh daily
```

### Backup Contents
- Database dump (compressed)
- Application files (excluding vendor/node_modules)
- Environment configuration
- Backup manifest with checksums

### Recovery Process

1. **List Available Backups**
   ```bash
   ls -la /var/backups/askproai/
   ```

2. **Verify Backup Integrity**
   ```bash
   gunzip -t /var/backups/askproai/db-daily-*.sql.gz
   tar -tzf /var/backups/askproai/app-daily-*.tar.gz
   ```

3. **Restore Database**
   ```bash
   gunzip < backup.sql.gz | mysql -u root -p askproai
   ```

4. **Restore Application**
   ```bash
   cd /
   tar -xzf /var/backups/askproai/app-daily-*.tar.gz
   ```

## Troubleshooting

### Common Issues

1. **Migration Fails**
   ```bash
   # Check migration status
   php artisan migrate:status
   
   # Rollback last batch
   php artisan migrate:rollback
   ```

2. **Queue Workers Not Starting**
   ```bash
   # Check Horizon status
   php artisan horizon:status
   
   # Clear failed jobs
   php artisan queue:flush
   
   # Restart manually
   php artisan horizon
   ```

3. **Cache Issues**
   ```bash
   # Clear all caches
   php artisan optimize:clear
   
   # Rebuild caches
   php artisan optimize
   ```

4. **Permission Issues**
   ```bash
   # Fix permissions
   chown -R deploy:www-data /var/www/api-gateway
   find storage -type d -exec chmod 775 {} \;
   find storage -type f -exec chmod 664 {} \;
   ```

### Debug Mode

For emergency debugging:
```bash
# Temporarily enable debug mode
APP_DEBUG=true php artisan serve
```

**Warning**: Never leave debug mode enabled in production!

### Log Locations

- Application: `/var/www/api-gateway/storage/logs/`
- Nginx: `/var/log/nginx/`
- PHP-FPM: `/var/log/php8.2-fpm.log`
- MySQL: `/var/log/mysql/error.log`
- Redis: `/var/log/redis/redis-server.log`

## Emergency Contacts

### Escalation Path

1. **Level 1 - DevOps Team**
   - Primary: [DevOps Lead]
   - Secondary: [DevOps Engineer]
   - Response Time: 15 minutes

2. **Level 2 - Development Team**
   - Primary: [Tech Lead]
   - Secondary: [Senior Developer]
   - Response Time: 30 minutes

3. **Level 3 - Management**
   - CTO: [CTO Contact]
   - Response Time: 1 hour

### External Services

- **Cal.com Support**: support@cal.com
- **Retell.ai Support**: support@retellai.com
- **Hosting Provider**: [Provider emergency line]

## Best Practices

1. **Always deploy during low-traffic periods**
2. **Test deployment in staging first**
3. **Keep deployment windows under 30 minutes**
4. **Monitor for at least 1 hour post-deployment**
5. **Document any issues or changes**
6. **Update this guide with lessons learned**

## Deployment Schedule

Recommended deployment windows:
- **Regular Updates**: Tuesday/Thursday 2:00-4:00 AM CET
- **Emergency Fixes**: As needed with approval
- **Major Releases**: Saturday 2:00-6:00 AM CET

---

**Last Updated**: 2025-06-18  
**Maintained by**: DevOps Team  
**Review Cycle**: Monthly