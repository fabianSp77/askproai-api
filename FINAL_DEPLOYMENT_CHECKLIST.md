# 🚀 AskProAI Final Deployment Checklist

## Pre-Deployment Verification

### 1. Code Quality ✓
- [ ] All tests passing (`php artisan test`)
- [ ] No `dd()`, `dump()`, or `console.log()` statements
- [ ] No hardcoded credentials or API keys
- [ ] All TODOs addressed or documented
- [ ] Code review completed

### 2. Security Audit ✓
- [ ] Multi-tenancy isolation verified
- [ ] API authentication on all endpoints
- [ ] Webhook signatures verified
- [ ] SQL injection prevention confirmed
- [ ] XSS protection enabled
- [ ] CORS properly configured

### 3. Performance Verification ✓
- [ ] Database indexes applied
- [ ] Query performance < 50ms
- [ ] API response time < 200ms
- [ ] Connection pooling configured
- [ ] Redis cluster ready

### 4. Configuration ✓
- [ ] `.env.production` file prepared
- [ ] All API keys set (Cal.com, Retell, Stripe)
- [ ] Database credentials configured
- [ ] Redis connection verified
- [ ] Email settings tested

## Deployment Steps

### 1. Backup Current System
```bash
# Database backup
mysqldump -h localhost -u root askproai > backup_$(date +%Y%m%d_%H%M%S).sql

# Application backup
tar -czf app_backup_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/api-gateway

# Upload to S3
aws s3 cp backup_*.sql s3://askproai-backups/
aws s3 cp app_backup_*.tar.gz s3://askproai-backups/
```

### 2. Prepare Application
```bash
# Switch to deployment branch
git checkout main
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install --production
npm run build

# Clear all caches
php artisan optimize:clear
```

### 3. Database Migration
```bash
# Put application in maintenance mode
php artisan down --message="Upgrading system" --retry=60

# Run migrations
php artisan migrate --force

# Seed production data if needed
php artisan db:seed --class=ProductionSeeder --force
```

### 4. Update Configuration
```bash
# Copy production environment
cp .env.production .env

# Generate new app key if needed
php artisan key:generate

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Start Services
```bash
# Start queue workers
php artisan horizon

# Start scheduler
php artisan schedule:work

# Warm up caches
php artisan calcom:cache-warmup
php artisan cache:warm
```

### 6. Health Checks
```bash
# Remove maintenance mode
php artisan up

# Verify health endpoints
curl https://api.askproai.de/health
curl https://api.askproai.de/health/calcom

# Check metrics endpoint
curl https://api.askproai.de/metrics
```

## Post-Deployment Verification

### 1. Functional Tests
- [ ] Create test booking via phone
- [ ] Verify webhook processing
- [ ] Check email delivery
- [ ] Test dashboard access
- [ ] Verify multi-tenancy

### 2. Performance Tests
```bash
# Basic load test
ab -n 100 -c 10 https://api.askproai.de/api/health

# Check response times
curl -w "@curl-format.txt" -o /dev/null -s https://api.askproai.de/api/health
```

### 3. Monitoring Setup
- [ ] Prometheus scraping metrics
- [ ] Grafana dashboards configured
- [ ] Alerts configured
- [ ] Log aggregation working
- [ ] Error tracking active

### 4. Security Verification
```bash
# SSL certificate check
openssl s_client -connect api.askproai.de:443 -servername api.askproai.de

# Security headers check
curl -I https://api.askproai.de

# Rate limiting test
for i in {1..100}; do curl https://api.askproai.de/api/health; done
```

## Rollback Plan

### If Issues Occur:
1. **Immediate Rollback** (< 5 minutes)
```bash
# Enable maintenance mode
php artisan down

# Restore previous code
git checkout previous-release-tag

# Restore database
mysql -u root askproai < backup_latest.sql

# Clear caches
php artisan optimize:clear

# Restart services
php artisan up
```

2. **Feature Flags** (disable specific features)
```bash
# Disable problematic features
php artisan config:set feature.phone_validation false
php artisan config:set feature.webhook_queue false
php artisan config:set calcom.force_v2 false
```

3. **Emergency Contacts**
- Tech Lead: +49 xxx xxxx
- DevOps: +49 xxx xxxx
- Cal.com Support: support@cal.com
- Retell Support: support@retell.ai

## Monitoring Dashboard URLs

- **Application**: https://app.askproai.de
- **Admin Panel**: https://app.askproai.de/admin
- **Horizon**: https://app.askproai.de/horizon
- **Telescope**: https://app.askproai.de/telescope
- **Grafana**: https://monitoring.askproai.de
- **Prometheus**: https://prometheus.askproai.de

## Success Criteria

### Technical Metrics
- ✅ Error rate < 0.1%
- ✅ Response time p95 < 200ms
- ✅ Uptime > 99.9%
- ✅ Queue processing < 5s
- ✅ Zero data leaks

### Business Metrics
- ✅ Booking success rate > 95%
- ✅ Customer satisfaction maintained
- ✅ No increase in support tickets
- ✅ All webhooks processed
- ✅ Email delivery rate > 99%

## Final Sign-offs

- [ ] **Technical Lead**: ___________________ Date: ___________
- [ ] **QA Lead**: _______________________ Date: ___________
- [ ] **Security**: ______________________ Date: ___________
- [ ] **Product Owner**: _________________ Date: ___________
- [ ] **DevOps**: _______________________ Date: ___________

## Post-Deployment Tasks

### Within 24 Hours:
- [ ] Monitor error logs
- [ ] Check performance metrics
- [ ] Verify all cron jobs running
- [ ] Review customer feedback
- [ ] Update documentation

### Within 1 Week:
- [ ] Performance analysis report
- [ ] Security audit
- [ ] Capacity planning review
- [ ] Team retrospective
- [ ] Update runbooks

---

## Deployment Log

| Date | Time | Version | Deployed By | Notes |
|------|------|---------|-------------|-------|
| | | | | |

---
*Document Version: 1.0*
*Last Updated: 2025-06-17*
*Next Review: Post-deployment*