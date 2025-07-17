# Emergency Procedures - AskProAI

**Version**: 1.0  
**Last Updated**: 2025-01-10  
**Critical Document - Keep Updated and Accessible**

## Emergency Contact Information

### Escalation Matrix

| Priority | Role | Name | Phone | Alternative |
|----------|------|------|-------|-------------|
| 1 | DevOps On-Call | Via PagerDuty | - | Slack: #oncall |
| 2 | Tech Lead | [Name] | [Phone] | [Email] |
| 3 | CTO | [Name] | [Phone] | [Email] |
| 4 | CEO | [Name] | [Phone] | [Email] |

### External Support

| Service | Contact | Account # | Priority Line |
|---------|---------|-----------|---------------|
| Hosting Provider | [Provider] | [Account] | [24/7 Phone] |
| Cal.com | support@cal.com | [Account] | - |
| Retell.ai | support@retellai.com | [Account] | - |
| Cloudflare | Enterprise Support | [Account] | [Phone] |

---

## Critical Incident Response

### Severity Definitions

| Level | Impact | Response Time | Examples |
|-------|--------|---------------|----------|
| **SEV-1** | Complete outage | 15 min | Site down, data loss |
| **SEV-2** | Major degradation | 30 min | API errors >25%, slow response |
| **SEV-3** | Minor degradation | 2 hours | Feature broken, <10% errors |
| **SEV-4** | No user impact | 24 hours | Internal tools, monitoring |

### SEV-1: Complete System Outage

#### Immediate Actions (0-5 minutes)

```bash
# 1. Acknowledge incident
echo "SEV-1 acknowledged by $(whoami) at $(date)" | tee -a /var/log/incidents.log

# 2. Quick health check
curl -I https://api.askproai.de || echo "Site is DOWN"

# 3. Check basic services
systemctl status nginx php8.2-fpm mysql redis

# 4. Enable maintenance page (if possible)
php artisan down --message="We are experiencing technical difficulties. Please check back soon."

# 5. Alert team
./scripts/alert-sev1.sh "Complete outage detected"
```

#### Diagnosis (5-15 minutes)

```bash
# System resources
df -h          # Disk space
free -h        # Memory
htop           # CPU/Process

# Service specific
systemctl status --failed
journalctl -xe | tail -50

# Application logs
tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep -E "ERROR|CRITICAL"
tail -100 /var/log/nginx/error.log

# Database
mysql -e "SELECT 1;" || echo "Database is DOWN"
redis-cli ping || echo "Redis is DOWN"
```

#### Recovery Actions

**Option 1: Service Restart**
```bash
# Restart all services
systemctl restart nginx
systemctl restart php8.2-fpm
systemctl restart mysql
systemctl restart redis

# Clear caches
cd /var/www/api-gateway
php artisan cache:clear
php artisan config:clear

# Test
curl https://api.askproai.de/api/health
```

**Option 2: Emergency Rollback**
```bash
# If recent deployment caused issue
cd /var/www/api-gateway
./deploy/rollback.sh --emergency

# Or manual rollback
git log --oneline -10  # Find last known good commit
git reset --hard COMMIT_HASH
composer install --no-dev
php artisan migrate:rollback --step=1
```

**Option 3: Failover to Backup Server**
```bash
# Update DNS (via Cloudflare API)
./scripts/failover-dns.sh production backup

# Or manual via Cloudflare dashboard
# Point A record to backup server IP
```

---

## Specific Emergency Scenarios

### Database Failure

**Symptoms**: 
- "Connection refused" errors
- "Too many connections"
- Data corruption messages

**Actions**:
```bash
# 1. Check MySQL status
systemctl status mysql
journalctl -u mysql | tail -50

# 2. Check disk space (common cause)
df -h /var/lib/mysql

# 3. Emergency restart
systemctl stop mysql
# Check for hung processes
ps aux | grep mysql | grep -v grep
# Force kill if needed
killall -9 mysqld
# Start clean
systemctl start mysql

# 4. If corruption suspected
mysqlcheck --all-databases --auto-repair

# 5. If all fails, restore from backup
systemctl stop mysql
mv /var/lib/mysql /var/lib/mysql.corrupted
mysql_install_db --user=mysql
systemctl start mysql
mysql < /var/backups/askproai/db/latest.sql
```

### Redis Memory Overflow

**Symptoms**:
- "OOM command not allowed"
- Application slowing down

**Actions**:
```bash
# 1. Check memory usage
redis-cli info memory

# 2. Emergency flush (WARNING: Clears all cache)
redis-cli FLUSHALL

# 3. Adjust memory policy
redis-cli CONFIG SET maxmemory-policy allkeys-lru

# 4. Restart application
php artisan cache:clear
php artisan queue:restart
```

### Disk Space Emergency

**Symptoms**:
- "No space left on device"
- Services failing to start

**Actions**:
```bash
# 1. Quick cleanup (safe)
# Remove old logs
find /var/log -name "*.gz" -delete
find /var/log -name "*.1" -delete

# Clear Laravel logs
> /var/www/api-gateway/storage/logs/laravel.log

# Clear nginx logs
> /var/log/nginx/access.log
> /var/log/nginx/error.log

# 2. Remove old backups
find /var/backups -mtime +7 -delete

# 3. Clean package manager cache
apt-get clean
composer clear-cache
npm cache clean --force

# 4. Find large files
du -h / 2>/dev/null | grep -E "^[0-9.]+G" | sort -rh | head -20
```

### Security Breach

**Indicators**:
- Suspicious processes
- Unexpected network connections
- Modified files
- Unusual database queries

**Immediate Actions**:
```bash
# 1. ISOLATE SYSTEM
# Block all traffic except your IP
iptables -I INPUT 1 -s YOUR_IP -j ACCEPT
iptables -I INPUT 2 -j DROP

# 2. Preserve evidence
tar -czf /secure/evidence-$(date +%s).tar.gz \
  /var/log \
  /var/www/api-gateway/storage/logs \
  /home/*/.bash_history \
  /etc/passwd \
  /etc/shadow

# 3. Check for backdoors
find /var/www -name "*.php" -mtime -1 -ls
grep -r "eval\|base64_decode\|system\|exec" /var/www/api-gateway --include="*.php"

# 4. Rotate ALL credentials
cd /var/www/api-gateway
php artisan key:generate --force
# Update .env with new database passwords
# Rotate API keys for Cal.com, Retell.ai

# 5. Notify
./scripts/security-breach-notification.sh
```

### DDoS Attack

**Symptoms**:
- Very high traffic
- Slow response times
- Many connections from few IPs

**Actions**:
```bash
# 1. Enable Cloudflare DDoS protection
# Via dashboard: Security > DDoS > Enable "I'm Under Attack" mode

# 2. Local mitigation
# Limit connections per IP
iptables -A INPUT -p tcp --dport 80 -m connlimit --connlimit-above 50 -j DROP
iptables -A INPUT -p tcp --dport 443 -m connlimit --connlimit-above 50 -j DROP

# 3. Identify attacking IPs
netstat -ntu | awk '{print $5}' | cut -d: -f1 | sort | uniq -c | sort -nr | head -20

# 4. Block suspicious IPs
iptables -I INPUT -s ATTACKER_IP -j DROP

# 5. Enable rate limiting in nginx
# /etc/nginx/sites-available/askproai
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
limit_req zone=api burst=20 nodelay;
```

---

## Data Recovery Procedures

### Point-in-Time Recovery

```bash
# 1. Determine recovery point
echo "What time to restore to? (YYYY-MM-DD HH:MM:SS)"
read RECOVERY_TIME

# 2. Find appropriate backup
BACKUP_FILE=$(ls -1 /var/backups/askproai/db/*.sql.gz | tail -1)

# 3. Restore base backup
gunzip < $BACKUP_FILE | mysql askproai_recovery

# 4. Apply binary logs up to recovery point
mysqlbinlog \
  --stop-datetime="$RECOVERY_TIME" \
  /var/log/mysql/mysql-bin.* | mysql askproai_recovery

# 5. Verify recovery
mysql askproai_recovery -e "SELECT COUNT(*) FROM appointments WHERE created_at > '$RECOVERY_TIME';"

# 6. Switch to recovered database
mysql -e "DROP DATABASE askproai_old;"
mysql -e "RENAME DATABASE askproai TO askproai_old;"
mysql -e "RENAME DATABASE askproai_recovery TO askproai;"
```

### Emergency Data Export

```bash
#!/bin/bash
# Emergency data export when system is failing

# Critical business data only
TABLES="companies branches staff customers appointments calls"

for table in $TABLES; do
    echo "Exporting $table..."
    mysql askproai -e "SELECT * FROM $table" > /emergency/$table.csv
done

# Compress
tar -czf /emergency/emergency-export-$(date +%s).tar.gz /emergency/*.csv
```

---

## Communication Procedures

### Internal Communication

```bash
#!/bin/bash
# notify-incident.sh

SEVERITY=$1
MESSAGE=$2

# Slack
curl -X POST $SLACK_WEBHOOK_URL \
  -H 'Content-type: application/json' \
  --data "{
    \"text\": \"🚨 $SEVERITY INCIDENT\",
    \"attachments\": [{
      \"color\": \"danger\",
      \"text\": \"$MESSAGE\",
      \"fields\": [
        {\"title\": \"Time\", \"value\": \"$(date)\", \"short\": true},
        {\"title\": \"Reported by\", \"value\": \"$(whoami)\", \"short\": true}
      ]
    }]
  }"

# Email
echo "$MESSAGE" | mail -s "[$SEVERITY] AskProAI Incident" devops@askproai.de

# PagerDuty
./scripts/pagerduty-trigger.sh "$SEVERITY" "$MESSAGE"
```

### Customer Communication

**Templates**:

1. **Initial Response** (within 15 minutes):
```
We are currently experiencing technical difficulties with our service. 
Our team is actively working on resolving the issue. 
We'll provide updates every 30 minutes.
```

2. **Progress Update** (every 30 minutes):
```
Update: We've identified the issue and are implementing a fix.
Current impact: [describe impact]
Expected resolution: [time estimate]
```

3. **Resolution**:
```
The issue has been resolved and all services are operational.
Impact duration: [start] to [end]
We apologize for any inconvenience caused.
```

---

## Post-Incident Procedures

### Incident Timeline Template

```markdown
# Incident Report - [DATE]

## Summary
- **Duration**: [START] to [END]
- **Severity**: SEV-[1-4]
- **Impact**: [Describe user impact]
- **Root Cause**: [Brief description]

## Timeline
- **14:00** - First alert received
- **14:05** - Engineer acknowledged
- **14:10** - Initial diagnosis
- **14:20** - Fix implemented
- **14:30** - Service restored
- **14:45** - Monitoring normal

## Root Cause Analysis
[Detailed explanation]

## Action Items
1. [ ] Implement monitoring for X
2. [ ] Add redundancy for Y
3. [ ] Update runbook for Z

## Lessons Learned
- What went well
- What could be improved
- Process changes needed
```

### Recovery Verification

```bash
#!/bin/bash
# post-incident-verify.sh

echo "Running post-incident verification..."

# 1. Service health
ENDPOINTS=(
  "/api/health"
  "/api/health/database"
  "/api/health/redis"
  "/api/health/queue"
)

for endpoint in "${ENDPOINTS[@]}"; do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" "https://api.askproai.de$endpoint")
  echo "$endpoint: $STATUS"
done

# 2. Performance baseline
ab -n 100 -c 10 https://api.askproai.de/api/health | grep -E "Requests per second|Time per request"

# 3. Error rate
ERROR_COUNT=$(grep "ERROR" /var/www/api-gateway/storage/logs/laravel.log | grep -c "$(date +%Y-%m-%d)")
echo "Errors today: $ERROR_COUNT"

# 4. Queue health
php artisan queue:monitor
```

---

## Quick Reference Card

### Critical Commands

```bash
# STOP EVERYTHING
php artisan down
systemctl stop nginx php8.2-fpm
php artisan queue:pause

# RESTART EVERYTHING
systemctl restart nginx php8.2-fpm mysql redis
php artisan up
php artisan queue:restart

# ROLLBACK
cd /var/www/api-gateway
./deploy/rollback.sh

# CLEAR ALL CACHES
php artisan optimize:clear
redis-cli FLUSHALL

# VIEW ALL LOGS
tail -f /var/log/**/*.log | grep -E "ERROR|error|Error"
```

### Health Check URLs

```
https://api.askproai.de/api/health
https://api.askproai.de/api/health/database
https://api.askproai.de/api/health/redis
https://api.askproai.de/api/health/queue
https://api.askproai.de/api/metrics
```

### Emergency Scripts Location

```
/var/www/api-gateway/deploy/rollback.sh
/var/www/api-gateway/deploy/emergency-recovery.sh
/var/www/api-gateway/scripts/alert-sev1.sh
/var/www/api-gateway/scripts/failover-dns.sh
```

---

**Remember**: 
1. **Stay Calm** - Follow the procedures
2. **Communicate** - Keep stakeholders informed
3. **Document** - Record all actions taken
4. **Learn** - Conduct thorough post-mortems

**Document Version**: 1.0  
**Last Updated**: 2025-01-10  
**Review Frequency**: Monthly  
**Emergency Line**: [24/7 Phone Number]