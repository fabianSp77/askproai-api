# Production Deployment Automation Suite

Complete automation for safe, validated production database migrations.

## 🚀 Quick Start

```bash
cd /var/www/api-gateway/scripts
./deploy-production.sh
```

That's it! The orchestrator handles everything automatically.

---

## 📚 Documentation

- **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - One-page command reference
- **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** - Complete usage documentation
- **[INTEGRATION_SUMMARY.md](INTEGRATION_SUMMARY.md)** - Architecture and integration details

---

## 🛠️ Available Scripts

| Script | Purpose | Usage |
|--------|---------|-------|
| **deploy-production.sh** ⭐ | Complete deployment orchestration | `./deploy-production.sh` |
| **deploy-pre-check.sh** | Pre-deployment validation | `./deploy-pre-check.sh` |
| **validate-migration.sh** | Migration table validation | `./validate-migration.sh <table>` |
| **smoke-test.sh** | Post-deployment functional tests | `./smoke-test.sh` |
| **monitor-deployment.sh** | 3-hour continuous monitoring | `./monitor-deployment.sh 180` |
| **emergency-rollback.sh** | Automated rollback procedure | `./emergency-rollback.sh --auto` |

---

## 🎯 Common Tasks

### Deploy to Production
```bash
./deploy-production.sh
```

### Emergency Rollback
```bash
./emergency-rollback.sh --auto
```

### Check Environment
```bash
./deploy-pre-check.sh
```

### Monitor Deployment
```bash
./monitor-deployment.sh 180
```

---

## 📊 What Gets Deployed

- **7 migrations** to production MySQL (askproai_db)
- **2 new tables**: policy_configurations, callback_requests
- **Validation** after each migration
- **Automated rollback** on failure
- **3-hour monitoring** window

---

## 🛡️ Safety Features

✅ Pre-deployment environment validation
✅ Automatic database backup (compressed)
✅ Maintenance mode during deployment
✅ Incremental migration validation
✅ Comprehensive smoke testing
✅ Auto-rollback on critical failures
✅ Continuous post-deployment monitoring
✅ Complete audit trail in logs

---

## 📍 File Locations

```
Scripts:    /var/www/api-gateway/scripts/
Logs:       /var/www/api-gateway/storage/logs/deployment/
Backups:    /var/www/api-gateway/storage/backups/
```

---

## 🆘 Emergency Commands

```bash
# Immediate rollback
./emergency-rollback.sh --auto

# Check site status
php artisan up

# View live errors
tail -f /var/www/api-gateway/storage/logs/laravel.log
```

---

## 📖 Learn More

Start with **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** for common operations.

Read **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** for complete documentation.

---

**Version**: 1.0.0 | **Last Updated**: 2025-10-02
