# 🚨 EMERGENCY RESPONSE PLAYBOOK

> **⏱️ Zeit ist kritisch!** Dieses Playbook führt dich durch die wichtigsten Schritte bei Production-Problemen.

## 🔴 SEVERITY LEVELS

| Level | Response Time | Beispiele |
|-------|--------------|-----------|
| **S1 - Critical** | < 15 Min | System komplett down, Datenverlust |
| **S2 - High** | < 1 Stunde | Keine neuen Bookings möglich |
| **S3 - Medium** | < 4 Stunden | Performance degradiert |
| **S4 - Low** | < 24 Stunden | Cosmetic issues |

---

## 🚨 S1: SYSTEM KOMPLETT DOWN

### ⚡ SOFORT-MASSNAHMEN (< 5 Minuten)

```bash
# 1. Status Check
curl -I https://api.askproai.de/health
ssh hosting215275@hosting215275.ae83d.netcup.net "systemctl status nginx php8.3-fpm mysql"

# 2. Emergency Page aktivieren
ssh hosting215275@hosting215275.ae83d.netcup.net "ln -sf /var/www/maintenance.html /var/www/api-gateway/public/index.php"

# 3. Team alarmieren
./scripts/alert-team.sh --severity=S1 --message="Production Down"
```

### 🔍 DIAGNOSE (5-10 Minuten)

```bash
# Server Ressourcen
ssh hosting215275@hosting215275.ae83d.netcup.net
htop  # CPU/Memory
df -h # Disk Space
netstat -tuln | grep -E ':(80|443|3306|6379)' # Ports

# Service Status
systemctl status nginx
systemctl status php8.3-fpm
systemctl status mysql
systemctl status redis

# Logs checken
tail -n 100 /var/log/nginx/error.log
tail -n 100 /var/www/api-gateway/storage/logs/laravel.log
journalctl -u php8.3-fpm -n 50
```

### 🔧 QUICK FIXES

#### Problem: "502 Bad Gateway"
```bash
# PHP-FPM neustart
sudo systemctl restart php8.3-fpm

# Wenn das nicht hilft - Config prüfen
php-fpm8.3 -t
nginx -t
```

#### Problem: "Database Connection Failed"
```bash
# MySQL neustart
sudo systemctl restart mysql

# Connection testen
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' -e "SELECT 1"

# Wenn voll: Logs rotieren
cd /var/lib/mysql
sudo rm -f mysql-bin.*
sudo systemctl restart mysql
```

#### Problem: "Disk Full"
```bash
# Große Files finden
du -sh /var/* | sort -hr | head -20

# Logs leeren
truncate -s 0 /var/www/api-gateway/storage/logs/*.log
rm -rf /var/www/api-gateway/storage/framework/cache/*
rm -rf /var/www/api-gateway/storage/framework/sessions/*

# Docker cleanup (wenn genutzt)
docker system prune -af
```

### 🔄 ROLLBACK (wenn nötig)

```bash
# Letztes funktionierendes Deployment
cd /var/www/api-gateway
git log --oneline -10  # Finde letzten stabilen Commit

# Rollback
git checkout [STABLE_COMMIT_HASH]
composer install --no-dev
php artisan config:cache
php artisan route:cache
sudo systemctl restart php8.3-fpm
```

---

## 🟡 S2: KRITISCHE FEATURES AUSGEFALLEN

### Retell.ai Integration Down

```bash
# 1. Status prüfen
php artisan retell:health-check

# 2. Fallback aktivieren
php artisan config:set retell.fallback_mode true
php artisan cache:clear

# 3. Manual webhook replay
php artisan retell:replay-failed --hours=1

# 4. Retell Dashboard checken
open https://app.retellai.com/dashboard
```

### Cal.com Sync Failed

```bash
# 1. Circuit Breaker Status
php artisan circuit-breaker:status calcom

# 2. Reset wenn nötig
php artisan circuit-breaker:reset calcom

# 3. Manual sync
php artisan calcom:sync --force

# 4. API Key prüfen
php artisan tinker
>>> $company = Company::first();
>>> CalcomService::validateApiKey($company->calcom_api_key);
```

### Queue/Horizon Down

```bash
# 1. Redis prüfen
redis-cli ping
redis-cli info memory

# 2. Horizon neustart
php artisan horizon:terminate
sleep 5
php artisan horizon

# 3. Failed Jobs prüfen
php artisan queue:failed
php artisan queue:retry all

# 4. Queue leeren (VORSICHT!)
php artisan queue:flush  # Nur wenn absolut nötig!
```

---

## 🟠 S3: PERFORMANCE PROBLEME

### Diagnose Tools

```bash
# 1. Slow Query Log aktivieren
mysql -u root -p'V9LGz2tdR5gpDQz' -e "SET GLOBAL slow_query_log = 'ON'"
mysql -u root -p'V9LGz2tdR5gpDQz' -e "SET GLOBAL long_query_time = 1"

# 2. Laravel Debugbar
# In .env: DEBUGBAR_ENABLED=true

# 3. APM Metrics
php artisan performance:analyze
curl http://localhost:9090/metrics  # Prometheus

# 4. Top Queries finden
mysqldumpslow -s t /var/log/mysql/slow-query.log | head -20
```

### Quick Optimizations

```bash
# 1. Cache rebuild
php artisan optimize:clear
php artisan optimize

# 2. Database optimize
mysqlcheck -u root -p'V9LGz2tdR5gpDQz' --optimize askproai_db

# 3. Redis memory
redis-cli FLUSHDB  # Vorsicht: Löscht Session-Daten!
redis-cli CONFIG SET maxmemory 2gb
redis-cli CONFIG SET maxmemory-policy allkeys-lru

# 4. PHP-FPM tuning
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
# pm.max_children = 50
# pm.start_servers = 10
# pm.min_spare_servers = 5
# pm.max_spare_servers = 20
sudo systemctl restart php8.3-fpm
```

---

## 📞 NOTFALL-KONTAKTE

### Intern
- **CTO**: Klaus Meyer | +49 171 234 5678 | klaus@askproai.de
- **Lead Dev**: Sarah Schmidt | +49 172 345 6789 | sarah@askproai.de
- **DevOps**: Tom Weber | +49 173 456 7890 | tom@askproai.de

### Extern  
- **Netcup Support**: +49 931 320950 | support@netcup.de
- **Retell.ai Support**: support@retellai.com
- **Cal.com Support**: help@cal.com

### Eskalation
1. Versuche Intern-Kontakte (15 Min)
2. Netcup Support bei Server-Issues
3. CEO informieren bei > 30 Min Downtime

---

## 📋 POST-INCIDENT CHECKLIST

Nach Behebung des Problems:

```bash
# 1. Monitoring prüfen
php artisan health:check --detailed
curl https://api.askproai.de/health

# 2. Failed Jobs nacharbeiten
php artisan queue:retry all

# 3. Customers benachrichtigen
php artisan notify:incident-resolved

# 4. Logs sichern
tar -czf incident-$(date +%Y%m%d-%H%M%S).tar.gz \
  /var/www/api-gateway/storage/logs/ \
  /var/log/nginx/ \
  /var/log/mysql/

# 5. Post-Mortem erstellen
cp templates/POST_MORTEM_TEMPLATE.md incidents/$(date +%Y%m%d)-incident.md
```

### Post-Mortem Template
```markdown
## Incident Post-Mortem

**Date**: [DATE]
**Duration**: [START] - [END]  
**Severity**: S[1-4]
**Impact**: [Affected users/services]

### Timeline
- HH:MM - Event
- HH:MM - Event

### Root Cause
[What caused the issue]

### Resolution
[How it was fixed]

### Action Items
- [ ] Prevention measure 1
- [ ] Prevention measure 2

### Lessons Learned
[What we learned]
```

---

## 🛠️ PREVENTIVE MEASURES

### Daily Checks (Automated)
```bash
# Cronjob läuft täglich um 6:00
0 6 * * * /var/www/api-gateway/scripts/daily-health-check.sh
```

### Weekly Review
- [ ] Check Disk Space Trends
- [ ] Review Error Logs
- [ ] Update Dependencies
- [ ] Backup Verification

### Monthly Tasks
- [ ] Security Updates
- [ ] Performance Baseline
- [ ] Disaster Recovery Test
- [ ] Team Training

---

## 🎯 QUICK REFERENCE CARD

```bash
# --- MOST USED COMMANDS ---
# Emergency Status
php artisan emergency:status

# Quick Fix Attempt  
php artisan emergency:autofix

# Service Restarts
sudo systemctl restart nginx php8.3-fpm mysql redis

# Cache Clear
php artisan optimize:clear

# Check Everything
php artisan health:check --all

# Rollback Last Deploy
./scripts/rollback-latest.sh

# Page Team
./scripts/alert-team.sh --severity=S1
```

---

<div align="center">
⚡ <b>Remember: Stay calm, follow the playbook, communicate clearly.</b> ⚡
</div>