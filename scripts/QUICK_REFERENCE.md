# Deployment Scripts - Quick Reference Card

## 🚀 One-Command Deployment

```bash
cd /var/www/api-gateway/scripts && ./deploy-production.sh
```

**What it does**: Complete automated deployment with validation, monitoring, and rollback

---

## 📝 Individual Scripts

### Pre-Check
```bash
./deploy-pre-check.sh
```
✅ Pass → Safe to deploy | ❌ Fail → Fix issues first

### Validate Migration
```bash
./validate-migration.sh policy_configurations
./validate-migration.sh callback_requests
```

### Smoke Test
```bash
./smoke-test.sh
```
🟢 GREEN → All good | 🟡 YELLOW → Warnings | 🔴 RED → Critical failure

### Monitor
```bash
./monitor-deployment.sh 180    # 3 hours
./monitor-deployment.sh 60     # 1 hour
```
Press Ctrl+C to stop

### Emergency Rollback
```bash
./emergency-rollback.sh                           # Interactive
./emergency-rollback.sh --auto                    # No confirmation
./emergency-rollback.sh --backup-file=/path.sql   # Specific backup
```

---

## 🔥 Emergency Quick Actions

### Immediate Rollback
```bash
cd /var/www/api-gateway/scripts
./emergency-rollback.sh --auto
```

### Check Site Status
```bash
# Is site in maintenance mode?
ls -la /var/www/api-gateway/storage/framework/down

# Bring site up
cd /var/www/api-gateway && php artisan up
```

### View Live Errors
```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i error
```

### Check Migration Tables
```bash
mysql -u root askproai_db -e "
SELECT COUNT(*) as policy_configs FROM policy_configurations;
SELECT COUNT(*) as callbacks FROM callback_requests;
"
```

---

## 📊 Exit Codes

| Script | 0 | 1 | 2 | 3 |
|--------|---|---|---|---|
| **deploy-pre-check.sh** | ✅ Pass | ❌ Fail | - | - |
| **validate-migration.sh** | ✅ Pass | ❌ Fail | - | - |
| **smoke-test.sh** | 🟢 GREEN | 🟡 YELLOW | 🔴 RED | - |
| **monitor-deployment.sh** | ✅ Complete | ❌ Critical | - | - |
| **emergency-rollback.sh** | ✅ Success | ❌ Failed | - | - |
| **deploy-production.sh** | ✅ Success | Pre-check fail | Deploy fail | Error rollback |

---

## 📁 Log Locations

```bash
# All deployment logs
ls -lht /var/www/api-gateway/storage/logs/deployment/ | head -10

# Latest deployment log
tail -f /var/www/api-gateway/storage/logs/deployment/deploy-*.log

# Application errors
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Backups
ls -lht /var/www/api-gateway/storage/backups/ | head -5
```

---

## ⚡ Common Commands

### Check Deployment Status
```bash
cd /var/www/api-gateway

# Migration status
php artisan migrate:status

# Database connection
php artisan db:show

# Cache status
php artisan cache:clear
redis-cli INFO | grep used_memory_human
```

### Manual Migration Operations
```bash
cd /var/www/api-gateway

# Run migrations
php artisan migrate --force --step=7

# Rollback migrations
php artisan migrate:rollback --step=7

# Reset all migrations (⚠️  DANGEROUS)
php artisan migrate:fresh --force
```

### Service Management
```bash
# Restart services
systemctl restart php8.3-fpm
systemctl restart nginx
systemctl restart redis

# Check status
systemctl status php8.3-fpm
systemctl status mysql
```

---

## 🎯 Deployment Checklist

### Pre-Deployment
- [ ] Run `./deploy-pre-check.sh`
- [ ] Check disk space: `df -h`
- [ ] Verify backups directory: `ls -lh storage/backups/`
- [ ] Review migration files
- [ ] Notify team

### During Deployment
- [ ] Monitor logs: `tail -f storage/logs/deployment/deploy-*.log`
- [ ] Watch for errors: `tail -f storage/logs/laravel.log`
- [ ] Be ready to rollback

### Post-Deployment
- [ ] Verify smoke tests passed (🟢 GREEN)
- [ ] Monitor for 3 hours
- [ ] Check error rates
- [ ] Test key functionality
- [ ] Keep backup for 7 days

---

## 🆘 Troubleshooting Quick Fixes

### Site Down After Deployment
```bash
cd /var/www/api-gateway
php artisan up
systemctl restart php8.3-fpm nginx
```

### Database Connection Issues
```bash
# Test connection
mysql -u root askproai_db -e "SELECT 1;"

# Check credentials
cat /var/www/api-gateway/.env | grep DB_
```

### Migration Stuck
```bash
# Check locks
mysql -u root -e "SHOW OPEN TABLES WHERE In_use > 0;"

# Kill processes
mysql -u root -e "SHOW PROCESSLIST;"
mysql -u root -e "KILL <process_id>;"
```

### Cache Issues
```bash
cd /var/www/api-gateway
php artisan cache:clear
php artisan config:clear
redis-cli FLUSHALL
systemctl restart php8.3-fpm
```

---

## 📞 Escalation Path

1. **Check logs** → Review deployment and application logs
2. **Run diagnostics** → Pre-check and smoke tests
3. **Attempt rollback** → Use emergency-rollback.sh
4. **Contact DevOps** → If rollback fails
5. **Database admin** → If data integrity at risk

---

## 💡 Pro Tips

- **Always test in staging first** - Catch issues before production
- **Low-traffic deployments** - Schedule during off-peak hours
- **Keep backups 7 days** - Insurance against delayed issues
- **Monitor full 3 hours** - Issues may not appear immediately
- **Document everything** - Log unexpected behavior

---

**Quick Help**: `cat /var/www/api-gateway/scripts/DEPLOYMENT_GUIDE.md`

**Version**: 1.0.0 | **Last Updated**: 2025-10-02
