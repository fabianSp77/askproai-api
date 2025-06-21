# 🚀 AskProAI Deployment Guide

## Overview

This guide covers the complete deployment process for AskProAI, including environment setup, deployment procedures, monitoring, and troubleshooting.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Environment Setup](#environment-setup)
3. [Deployment Process](#deployment-process)
4. [Post-Deployment](#post-deployment)
5. [Monitoring](#monitoring)
6. [Rollback Procedures](#rollback-procedures)
7. [Troubleshooting](#troubleshooting)
8. [Security Considerations](#security-considerations)

## Prerequisites

### System Requirements

- **OS**: Ubuntu 20.04 LTS or newer
- **PHP**: 8.2 with required extensions
- **MySQL**: 8.0 or MariaDB 10.5+
- **Redis**: 6.0+
- **Nginx**: 1.18+
- **Node.js**: 18.x LTS
- **Composer**: 2.x
- **Git**: 2.x

### Required PHP Extensions

```bash
php-bcmath php-ctype php-fileinfo php-json php-mbstring 
php-openssl php-pdo php-tokenizer php-xml php-curl 
php-gd php-imagick php-mysql php-redis php-zip
```

### Server Access

- SSH access with sudo privileges
- Deployment user: `deploy`
- Web server user: `www-data`

## Environment Setup

### 1. Create Environment File

```bash
cp .env.production.example .env.production
```

Edit `.env.production` with your specific values:

```env
# Critical settings that MUST be configured:
APP_KEY=base64:...  # Generate with: php artisan key:generate
DB_PASSWORD=...
REDIS_PASSWORD=...
DEFAULT_CALCOM_API_KEY=...
DEFAULT_RETELL_API_KEY=...
STRIPE_SECRET=...
```

### 2. SSL Certificate

Ensure SSL certificate is properly installed:

```bash
# Using Let's Encrypt
certbot --nginx -d api.askproai.de
```

### 3. Directory Permissions

```bash
# Set correct ownership
sudo chown -R deploy:www-data /var/www/api-gateway

# Set directory permissions
find /var/www/api-gateway -type d -exec chmod 755 {} \;
find /var/www/api-gateway -type f -exec chmod 644 {} \;

# Storage and cache need write permissions
chmod -R 775 storage bootstrap/cache
```

### 4. Supervisor Configuration

Create `/etc/supervisor/conf.d/askproai-horizon.conf`:

```ini
[program:askproai-horizon]
process_name=%(program_name)s
command=php /var/www/api-gateway/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/askproai/horizon.log
stopwaitsecs=3600
```

## Deployment Process

### 1. Pre-Deployment Checklist

- [ ] Backup current production database
- [ ] Verify all environment variables are set
- [ ] Check disk space (minimum 5GB free)
- [ ] Ensure all services are running
- [ ] Notify team about deployment

### 2. Run Deployment Script

```bash
cd /var/www/api-gateway/deploy
sudo ./deploy-production.sh
```

The script will:
1. Create full backup
2. Pull latest code
3. Install dependencies
4. Run migrations
5. Optimize application
6. Restart services
7. Run health checks

### 3. Manual Deployment Steps

If you need to deploy manually:

```bash
# 1. Enable maintenance mode
php artisan down --retry=60

# 2. Pull latest code
git pull origin main

# 3. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci --production
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# 6. Restart services
sudo systemctl reload php8.2-fpm
php artisan horizon:terminate
sudo supervisorctl restart askproai-horizon

# 7. Disable maintenance mode
php artisan up
```

## Post-Deployment

### 1. Verify Deployment

```bash
# Run health checks
php artisan askproai:health-check --detailed

# Check application version
php artisan about

# Monitor logs
tail -f storage/logs/laravel.log
```

### 2. Warm Up Caches

```bash
# Warm up Cal.com cache
php artisan calcom:cache-warmup

# Warm up route cache
php artisan route:cache

# Warm up config cache
php artisan config:cache
```

### 3. Test Critical Features

- [ ] Make a test API call to `/api/health`
- [ ] Verify Cal.com integration
- [ ] Test Retell webhook
- [ ] Send a test email
- [ ] Check queue processing

## Monitoring

### 1. Health Check Endpoints

```bash
# Main health check
curl https://api.askproai.de/api/health

# Detailed component checks
curl https://api.askproai.de/api/health/database
curl https://api.askproai.de/api/health/redis
curl https://api.askproai.de/api/health/calcom
curl https://api.askproai.de/api/health/queue
```

### 2. Performance Monitoring

```bash
# Run performance monitor
php artisan askproai:performance-monitor --live

# Check slow queries
php artisan askproai:performance-monitor --slow-queries

# View index statistics
php artisan askproai:performance-monitor --index-stats
```

### 3. Log Monitoring

Important log files:

- **Application**: `/storage/logs/laravel.log`
- **Queue**: `/storage/logs/horizon.log`
- **Nginx**: `/var/log/nginx/askproai.access.log`
- **PHP-FPM**: `/var/log/php8.2-fpm.log`

### 4. Metrics Endpoint

Prometheus metrics available at:
```
https://api.askproai.de/api/metrics
```

## Rollback Procedures

### Emergency Rollback

If deployment fails:

```bash
cd /var/www/api-gateway/deploy
sudo ./rollback-production.sh
```

### Manual Rollback Steps

1. **Enable maintenance mode**
   ```bash
   php artisan down
   ```

2. **Restore from backup**
   ```bash
   # List available backups
   ls -la /var/backups/askproai/
   
   # Restore database
   mysql -u askproai -p askproai_production < backup.sql
   
   # Restore files
   rsync -avz /var/backups/askproai/[timestamp]/app/ /var/www/api-gateway/
   ```

3. **Clear caches and restart**
   ```bash
   php artisan optimize:clear
   sudo systemctl restart php8.2-fpm nginx
   php artisan up
   ```

## Troubleshooting

### Common Issues

#### 1. Migration Failures

```bash
# Check migration status
php artisan migrate:status

# Rollback last batch
php artisan migrate:rollback

# Run specific migration
php artisan migrate --path=/database/migrations/2025_06_17_example.php
```

#### 2. Permission Issues

```bash
# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 3. Queue Processing Issues

```bash
# Check Horizon status
php artisan horizon:status

# Restart Horizon
php artisan horizon:terminate
sudo supervisorctl restart askproai-horizon

# Clear failed jobs
php artisan queue:flush
```

#### 4. Cache Issues

```bash
# Clear all caches
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear Redis
redis-cli FLUSHDB
```

#### 5. 500 Errors

1. Check error logs:
   ```bash
   tail -n 100 storage/logs/laravel.log
   ```

2. Check PHP-FPM logs:
   ```bash
   sudo tail -n 100 /var/log/php8.2-fpm.log
   ```

3. Enable debug mode temporarily:
   ```bash
   # In .env.production
   APP_DEBUG=true
   ```

### Performance Issues

1. **Check slow queries**:
   ```bash
   php artisan askproai:performance-monitor --slow-queries
   ```

2. **Verify indexes**:
   ```sql
   SHOW INDEX FROM appointments;
   ```

3. **Check Redis memory**:
   ```bash
   redis-cli INFO memory
   ```

## Security Considerations

### 1. Environment Security

- Never commit `.env` files
- Use strong passwords (minimum 16 characters)
- Rotate API keys regularly
- Enable 2FA for all admin accounts

### 2. Server Security

```bash
# Firewall rules
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# Fail2ban for SSH protection
sudo apt install fail2ban
sudo systemctl enable fail2ban
```

### 3. Application Security

```bash
# Run security audit
php artisan askproai:security-audit

# Check for vulnerable packages
composer audit
npm audit
```

### 4. Backup Security

- Encrypt all backups
- Store backups off-site
- Test restore procedures monthly
- Rotate backup encryption keys

## Deployment Schedule

### Recommended Deployment Windows

- **Production**: Tuesday-Thursday, 10:00-14:00 CET
- **Avoid**: Mondays, Fridays, weekends, holidays
- **Emergency**: Follow incident response procedure

### Pre-Deployment Communication

1. Send notification 24h before planned deployment
2. Update status page
3. Notify key stakeholders
4. Prepare rollback plan

## Continuous Deployment

### GitHub Actions Workflow

See `.github/workflows/deploy.yml` for automated deployment setup.

### Deployment Stages

1. **Development**: Auto-deploy on push to `develop`
2. **Staging**: Auto-deploy on push to `staging`
3. **Production**: Manual approval required

## Support

### Internal Resources

- **Documentation**: `/docs`
- **Runbooks**: `/deploy/runbooks`
- **Team Chat**: #askproai-ops

### External Support

- **Laravel**: https://laravel.com/docs
- **Cal.com API**: https://cal.com/docs/api
- **Retell API**: https://docs.retellai.com

---

Last Updated: 2025-06-17
Version: 1.0.0