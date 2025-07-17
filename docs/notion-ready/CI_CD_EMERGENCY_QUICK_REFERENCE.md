# 🚨 CI/CD Emergency Quick Reference Card

## 🔴 CRITICAL - Service Down

### Immediate Actions (0-5 min)
```bash
# 1. Enable maintenance mode
php artisan down --message="Technical difficulties. Back soon."

# 2. Check services
systemctl status nginx php8.2-fpm mysql redis

# 3. Quick restart
systemctl restart nginx php8.2-fpm

# 4. Check health
curl https://api.askproai.de/api/health
```

### If Not Resolved → Rollback
```bash
# Emergency rollback
cd /var/www/api-gateway
./deploy/rollback.sh --emergency
```

---

## 🟡 High Error Rate / Performance Issues

### Quick Diagnostics
```bash
# Check errors
tail -100 storage/logs/laravel.log | grep ERROR

# Check queue
php artisan horizon:status
php artisan queue:failed

# Check database
mysql -e "SHOW PROCESSLIST;"
mysql -e "SHOW ENGINE INNODB STATUS\G"

# Check Redis
redis-cli info stats
redis-cli --latency
```

### Quick Fixes
```bash
# Clear caches
php artisan optimize:clear

# Restart queue workers
php artisan queue:restart

# Flush Redis (CAREFUL!)
redis-cli FLUSHALL
```

---

## 🔄 Emergency Deployment

### Via GitHub Actions
```bash
gh workflow run deploy.yml \
  -f environment=production \
  -f ref=hotfix/critical-fix \
  -f skip_tests=true \
  -f reason="Emergency: [describe issue]"
```

### Manual Emergency Deploy
```bash
cd /var/www/api-gateway
git fetch origin
git checkout hotfix/critical-fix
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

---

## 🔙 Rollback Procedures

### Quick Rollback (Last Deploy)
```bash
./deploy/rollback.sh
```

### Rollback to Specific Version
```bash
# Find commit
git log --oneline -10

# Rollback
git reset --hard COMMIT_SHA
composer install --no-dev
php artisan migrate:rollback --step=1
```

### Full System Restore
```bash
# From backup
./deploy/restore-from-backup.sh /var/backups/askproai/backup-20250110.tar.gz
```

---

## 📞 Emergency Contacts

| Priority | Contact | Response Time |
|----------|---------|---------------|
| 🔴 **L1** | PagerDuty On-Call | 15 min |
| 🟡 **L2** | Tech Lead: [Name] [Phone] | 30 min |
| 🟠 **L3** | CTO: [Name] [Phone] | 1 hour |

**External Support**:
- Hosting: [Provider 24/7]: [Phone]
- Cloudflare: [Enterprise Support]

---

## 🔍 System Status Commands

### Health Checks
```bash
# All health endpoints
for endpoint in health database redis queue calcom retell; do
  curl -s https://api.askproai.de/api/health/$endpoint
done
```

### Resource Check
```bash
# Quick system check
df -h          # Disk space
free -h        # Memory
htop           # CPU/Processes
iotop          # Disk I/O
iftop          # Network
```

---

## 🛠️ Common Fixes

### Database Issues
```bash
# Too many connections
mysql -e "SET GLOBAL max_connections = 500;"

# Kill slow queries
mysql -e "SHOW PROCESSLIST;" | grep -i "Query" | awk '{print $1}' | xargs -I {} mysql -e "KILL {};"

# Repair tables
mysqlcheck --all-databases --auto-repair
```

### Queue Issues
```bash
# Clear failed jobs
php artisan queue:flush

# Restart Horizon
php artisan horizon:terminate
nohup php artisan horizon &
```

### Disk Space Emergency
```bash
# Quick cleanup
find /var/log -name "*.gz" -delete
> /var/www/api-gateway/storage/logs/laravel.log
> /var/log/nginx/access.log
apt-get clean
```

---

## 📊 Monitoring URLs

| Service | URL |
|---------|-----|
| **Health Check** | https://api.askproai.de/api/health |
| **Metrics** | https://api.askproai.de/api/metrics |
| **Grafana** | https://grafana.askproai.de |
| **Horizon** | https://api.askproai.de/horizon |

---

## 🚦 Status Indicators

### ✅ System Healthy
- All health checks: 200 OK
- Error rate: < 0.1%
- Response time: < 500ms
- Queue size: < 100

### ⚠️ Warning State
- Health checks: Some failing
- Error rate: 0.1% - 5%
- Response time: 500ms - 1s
- Queue size: 100 - 1000

### 🔴 Critical State
- Health checks: Multiple failing
- Error rate: > 5%
- Response time: > 1s
- Queue size: > 1000

---

## 📝 Emergency Checklist

When system is down:
1. [ ] Acknowledge incident
2. [ ] Enable maintenance mode
3. [ ] Check basic services
4. [ ] Try quick restart
5. [ ] If not fixed → Rollback
6. [ ] Notify team
7. [ ] Document actions

---

## 🔧 Recovery Verification

After fixing issue:
```bash
# 1. Health check
./scripts/post-deploy-health-check.sh

# 2. Performance check
ab -n 100 -c 10 https://api.askproai.de/api/health

# 3. Error check
grep -c "ERROR" storage/logs/laravel.log

# 4. Queue check
php artisan queue:monitor
```

---

**⚡ Remember**: 
- **Stay Calm** - Follow procedures
- **Communicate** - Update team/status page
- **Document** - Log all actions
- **Rollback** - When in doubt, rollback

**Print and keep handy during on-call shifts!**