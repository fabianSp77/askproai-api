# Deployment Checklist & Runbook

## 🎯 Quick Access
- [Pre-Deployment](#pre-deployment-checklist)
- [Deployment Steps](#deployment-steps)
- [Post-Deployment](#post-deployment-verification)
- [Rollback Plan](#rollback-procedures)
- [Emergency Contacts](#emergency-contacts)

---

# ✅ Pre-Deployment Checklist

## 1. Code Readiness
- [ ] All PRs merged to target branch
- [ ] CI/CD pipeline passing on target branch
- [ ] No pending security alerts
- [ ] Documentation updated
- [ ] CHANGELOG.md updated

## 2. Testing Complete
- [ ] Unit tests passing (coverage >80%)
- [ ] Integration tests passing
- [ ] E2E tests passing
- [ ] Manual QA sign-off
- [ ] Performance benchmarks met

## 3. Infrastructure Check
- [ ] Server resources adequate (CPU, Memory, Disk)
- [ ] Database backup completed
- [ ] Redis memory usage <80%
- [ ] Queue backlog cleared
- [ ] No active incidents

## 4. Communication
- [ ] Deployment window scheduled
- [ ] Team notified via Slack
- [ ] Customer communication prepared (if needed)
- [ ] On-call engineer assigned
- [ ] Rollback plan reviewed

## 5. External Dependencies
- [ ] Cal.com API operational
- [ ] Retell.ai API operational
- [ ] Email service operational
- [ ] Payment gateway operational
- [ ] Monitoring systems ready

---

# 🚀 Deployment Steps

## Standard Deployment Process

### 1. Start Deployment
```bash
# Log deployment start
echo "Deployment started by $(whoami) at $(date)" >> /var/log/deployments.log

# Create deployment marker
touch /tmp/deployment_in_progress
```

### 2. Pre-Deployment Backup
```bash
# Backup current state
./scripts/backup-before-deploy.sh

# Verify backup
ls -la /var/backups/askproai/pre-deploy/
```

### 3. Execute Deployment

#### Option A: GitHub Actions (Recommended)
```bash
gh workflow run deploy.yml \
  -f environment=production \
  -f ref=main \
  -f reason="Release v1.2.3 - Feature X"
```

#### Option B: Manual Deployment
```bash
# SSH to server
ssh deploy@production

# Run deployment script
cd /var/www/api-gateway
./deploy/deploy.sh production
```

### 4. Monitor Progress
```bash
# Watch deployment logs
tail -f /var/log/askproai/deployment.log

# Monitor application logs
tail -f storage/logs/laravel.log

# Check queue status
php artisan horizon:status
```

---

# ✅ Post-Deployment Verification

## 1. Health Checks
```bash
# Run all health checks
./scripts/post-deploy-health-check.sh

# Manual checks
curl https://api.askproai.de/api/health
curl https://api.askproai.de/api/health/database
curl https://api.askproai.de/api/health/redis
curl https://api.askproai.de/api/health/queue
```

## 2. Functional Tests
- [ ] Test login functionality
- [ ] Create test appointment
- [ ] Process test webhook
- [ ] Send test email
- [ ] Check queue processing

## 3. Performance Verification
```bash
# Response time check
ab -n 100 -c 10 https://api.askproai.de/api/health

# Database query performance
mysql -e "SHOW PROCESSLIST;"

# Redis latency
redis-cli --latency

# Memory usage
free -h
```

## 4. Error Monitoring
```bash
# Check for errors
grep "ERROR" storage/logs/laravel.log | tail -20

# Check failed jobs
php artisan queue:failed

# Review error rates in monitoring
# Navigate to Grafana dashboard
```

## 5. External Integration Tests
- [ ] Cal.com webhook test
- [ ] Retell.ai call test
- [ ] Email delivery test
- [ ] SMS notification test (if enabled)

---

# 🔄 Rollback Procedures

## Automatic Rollback Triggers
- Health check failures (3 consecutive)
- Error rate >10% for 5 minutes
- Response time >2s for 5 minutes
- Critical service unavailable

## Manual Rollback Decision Criteria
- [ ] Major functionality broken
- [ ] Data corruption detected
- [ ] Security vulnerability exposed
- [ ] Performance severely degraded
- [ ] Integration failures

## Rollback Steps

### 1. Quick Rollback (< 5 minutes)
```bash
# Enable maintenance mode
php artisan down --message="Brief maintenance, back soon"

# Execute rollback
./deploy/rollback.sh

# Verify rollback
curl https://api.askproai.de/api/health

# Disable maintenance mode
php artisan up
```

### 2. Full Rollback with Database
```bash
# Stop all services
php artisan down
php artisan queue:pause

# Rollback application
cd /var/www/api-gateway
git reset --hard PREVIOUS_COMMIT

# Rollback database
php artisan migrate:rollback --step=X

# Restore from backup if needed
mysql askproai < /var/backups/askproai/backup.sql

# Clear all caches
php artisan optimize:clear
redis-cli FLUSHALL

# Restart services
systemctl restart php8.2-fpm nginx
php artisan up
php artisan queue:restart
```

---

# 📋 Environment-Specific Checklists

## Production Deployment
- [ ] Deployment window confirmed (not Friday!)
- [ ] Customer notification sent (if downtime)
- [ ] Backup verified offsite
- [ ] Monitoring alerts configured
- [ ] On-call schedule confirmed
- [ ] Rollback tested on staging

## Staging Deployment
- [ ] Latest main branch merged
- [ ] Test data refreshed
- [ ] SSL certificates valid
- [ ] External APIs in test mode
- [ ] Performance profiling enabled

## Hotfix Deployment
- [ ] Issue severity documented
- [ ] Minimal changeset verified
- [ ] Fast-track approval obtained
- [ ] Rollback plan simplified
- [ ] Post-fix review scheduled

---

# 🔍 Deployment Validation Scripts

## Health Check Script
```bash
#!/bin/bash
# post-deploy-health.sh

echo "Running post-deployment health checks..."

# Define endpoints
ENDPOINTS=(
  "/api/health"
  "/api/health/database"
  "/api/health/redis"
  "/api/health/queue"
  "/api/health/calcom"
  "/api/health/retell"
)

# Check each endpoint
for endpoint in "${ENDPOINTS[@]}"; do
  RESPONSE=$(curl -s -w "\n%{http_code}" https://api.askproai.de$endpoint)
  HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
  BODY=$(echo "$RESPONSE" | head -n-1)
  
  if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ $endpoint - OK"
  else
    echo "❌ $endpoint - FAILED (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
    exit 1
  fi
done

echo "All health checks passed!"
```

## Performance Check Script
```bash
#!/bin/bash
# check-performance.sh

echo "Checking application performance..."

# API response time
RESPONSE_TIME=$(curl -s -o /dev/null -w "%{time_total}" https://api.askproai.de/api/health)
echo "API Response Time: ${RESPONSE_TIME}s"

if (( $(echo "$RESPONSE_TIME > 1" | bc -l) )); then
  echo "⚠️  Warning: Response time exceeds 1 second"
fi

# Database performance
mysql -e "SELECT COUNT(*) as slow_queries FROM mysql.slow_log WHERE start_time > NOW() - INTERVAL 10 MINUTE;"

# Queue status
QUEUE_SIZE=$(php artisan queue:size)
echo "Queue Size: $QUEUE_SIZE"

if [ "$QUEUE_SIZE" -gt 1000 ]; then
  echo "⚠️  Warning: Large queue backlog"
fi
```

---

# 📞 Emergency Contacts

## Escalation Matrix

### Level 1 - DevOps Team (0-15 min)
- **Primary**: On-call engineer via PagerDuty
- **Backup**: DevOps Slack channel `#oncall`
- **Response Time**: 15 minutes

### Level 2 - Tech Lead (15-30 min)
- **Name**: [Tech Lead Name]
- **Phone**: [Phone Number]
- **Email**: [Email]
- **Slack**: @techlead

### Level 3 - CTO (30-60 min)
- **Name**: [CTO Name]
- **Phone**: [Phone Number]
- **Email**: [Email]

### External Support
- **Hosting**: [Provider] - [24/7 Support Number]
- **Cal.com**: support@cal.com
- **Retell.ai**: support@retellai.com
- **Cloudflare**: [Enterprise Support]

---

# 📊 Deployment Metrics to Track

## During Deployment
- Deployment duration
- Build time
- Migration execution time
- Cache warming time
- Service restart time

## Post-Deployment (First Hour)
- Error rate
- Response time (p50, p95, p99)
- Throughput (requests/second)
- Queue processing rate
- Memory usage

## Success Criteria
- ✅ Error rate < 0.1%
- ✅ Response time p95 < 500ms
- ✅ All health checks passing
- ✅ No increase in failed jobs
- ✅ Memory usage stable

---

# 🔧 Troubleshooting Quick Reference

## Common Issues & Solutions

### 1. Migration Timeout
```bash
# Check running migrations
mysql -e "SHOW PROCESSLIST;" | grep migration

# Kill if needed
mysql -e "KILL QUERY process_id;"

# Run with extended timeout
php artisan migrate --force --timeout=600
```

### 2. Service Won't Start
```bash
# Check logs
journalctl -u php8.2-fpm -n 50
tail -100 /var/log/nginx/error.log

# Verify permissions
ls -la storage/
chown -R www-data:www-data storage bootstrap/cache

# Test configuration
nginx -t
php-fpm8.2 -t
```

### 3. High Error Rate
```bash
# Identify errors
tail -f storage/logs/laravel.log | grep ERROR

# Check recent changes
git log --oneline -10

# Quick revert if needed
php artisan down
git reset --hard HEAD~1
php artisan up
```

---

# 📝 Post-Deployment Checklist

## Immediate (First 30 minutes)
- [ ] All health checks passing
- [ ] Error rate normal
- [ ] Performance metrics stable
- [ ] Queue processing normally
- [ ] No customer complaints

## Short-term (First 24 hours)
- [ ] Monitor error logs
- [ ] Check integration webhooks
- [ ] Verify scheduled jobs running
- [ ] Review performance graphs
- [ ] Collect team feedback

## Follow-up (Within 1 week)
- [ ] Post-mortem meeting (if issues)
- [ ] Update documentation
- [ ] Close deployment ticket
- [ ] Plan improvements
- [ ] Archive deployment logs

---

**Template Version**: 1.0
**Last Updated**: 2025-01-10
**Next Review**: Monthly