# 📋 AskProAI Production Deployment Checklist

## 🚨 Pre-Deployment Verification

### Security Checks
- [ ] Verify `.env.production` has all required variables
- [ ] Ensure `APP_DEBUG=false` and `APP_ENV=production`
- [ ] Confirm all API keys are production keys (not test/sandbox)
- [ ] Check `CALCOM_WEBHOOK_SECRET` is set correctly
- [ ] Verify `RETELL_WEBHOOK_SECRET` matches Retell.ai dashboard
- [ ] Ensure database credentials are production-ready
- [ ] Confirm Redis password is set

### Code Verification
- [ ] Run all tests: `php artisan test`
- [ ] Run E2E tests: `php artisan test --testsuite=E2E`
- [ ] Check for debug code: `grep -r "dd(\|dump(\|var_dump" app/`
- [ ] Verify no test files in root: `ls test_*.php`
- [ ] Ensure no TODO comments in critical paths

## 🔧 Server Preparation

### System Requirements
- [ ] PHP 8.2+ with required extensions
- [ ] MySQL 8.0+ or MariaDB 10.5+
- [ ] Redis 6.0+
- [ ] Node.js 18+ and NPM
- [ ] Supervisor for queue workers
- [ ] Nginx or Apache configured

### SSL/TLS
- [ ] SSL certificate installed and valid
- [ ] Force HTTPS redirect configured
- [ ] HSTS headers enabled

## 📦 Deployment Steps

### 1. Backup Current State
```bash
# Backup database
mysqldump -u root -p askproai > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz storage/ .env
```

### 2. Deploy Code
```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install --production
npm run production

# Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 3. Database Migration
```bash
# Run migrations
php artisan migrate --force

# Run the performance indexes if not already done
php artisan migrate --path=/database/migrations/2025_06_17_add_performance_critical_indexes.php --force

# Clear and rebuild caches
php artisan optimize:clear
```

### 4. Cache Optimization
```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Warm up Cal.com cache
php artisan calcom:cache-warmup

# Create scheduled tasks cache
php artisan schedule:cache
```

### 5. Queue Configuration
```bash
# Start Horizon (using Supervisor)
php artisan horizon:terminate
php artisan horizon

# Verify queues are processing
php artisan horizon:status
```

### 6. Cron Jobs
Add to crontab:
```cron
* * * * * cd /var/www/api-gateway && php artisan schedule:run >> /dev/null 2>&1
*/5 * * * * cd /var/www/api-gateway && php artisan appointments:cleanup-locks >> /dev/null 2>&1
0 * * * * cd /var/www/api-gateway && php artisan calcom:cache-warmup >> /dev/null 2>&1
0 2 * * * cd /var/www/api-gateway && php artisan askproai:backup --type=full >> /dev/null 2>&1
```

## ✅ Post-Deployment Verification

### Health Checks
- [ ] Main health endpoint: `curl https://api.askproai.de/health`
- [ ] Cal.com integration: `curl https://api.askproai.de/health/calcom`
- [ ] Database connection: Check `/health` response includes DB status
- [ ] Redis connection: Verify cache is working
- [ ] Queue processing: Check Horizon dashboard

### Functional Tests
- [ ] Test phone call → appointment booking flow
- [ ] Verify Cal.com sync is working
- [ ] Check email notifications are sent
- [ ] Test webhook endpoints with curl
- [ ] Verify multi-tenancy isolation

### Performance Verification
```bash
# Check query performance
php artisan askproai:performance-monitor

# Monitor for slow queries
php artisan askproai:performance-monitor --slow-queries --threshold=100

# Verify index usage
php artisan askproai:performance-monitor --index-stats
```

### Security Verification
```bash
# Check tenant isolation
php artisan tenant:check-security

# Test tenant isolation
php artisan tenant:test-isolation

# Verify no sensitive data in logs
tail -n 1000 storage/logs/laravel.log | grep -i "api_key\|token\|secret"
```

## 🔍 Monitoring Setup

### Critical Metrics to Monitor
- [ ] API response times < 100ms (p95)
- [ ] Cal.com API success rate > 98%
- [ ] Queue processing time < 30s
- [ ] Database query time < 50ms
- [ ] Memory usage < 80%
- [ ] CPU usage < 70%

### Alert Configuration
- [ ] Setup alerts for API errors > 1%
- [ ] Alert on queue backlog > 1000 jobs
- [ ] Monitor Cal.com circuit breaker status
- [ ] Alert on failed tenant isolation checks
- [ ] Database connection failures

### Log Aggregation
- [ ] Configure log shipping to central logging
- [ ] Setup correlation ID tracking
- [ ] Monitor for security events
- [ ] Track business metrics (bookings/hour)

## 🚨 Rollback Plan

If issues occur:

1. **Immediate Rollback**
```bash
# Revert code
git checkout [previous-version-tag]

# Restore database
mysql -u root -p askproai < backup_[timestamp].sql

# Clear caches
php artisan optimize:clear

# Restart services
php artisan horizon:terminate
systemctl restart php8.2-fpm
systemctl restart nginx
```

2. **Notify Stakeholders**
- [ ] Inform development team
- [ ] Update status page
- [ ] Notify affected customers if needed

3. **Post-Mortem**
- [ ] Document what went wrong
- [ ] Update deployment procedures
- [ ] Add missing tests

## 📞 Emergency Contacts

- **DevOps Lead**: [Contact Info]
- **Backend Lead**: [Contact Info]
- **Database Admin**: [Contact Info]
- **Cal.com Support**: support@cal.com
- **Retell.ai Support**: support@retell.ai

## 🎉 Success Criteria

Deployment is successful when:
- ✅ All health checks pass
- ✅ No errors in logs for 30 minutes
- ✅ Performance metrics meet targets
- ✅ Test booking completes successfully
- ✅ Monitoring shows stable operation

---
*Last Updated: 2025-06-17*
*Version: 1.0*