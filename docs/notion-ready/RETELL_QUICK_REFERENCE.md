# ⚡ Retell.ai Quick Reference

> 🎯 **Essential Commands & Information for Daily Operations**  
> Bookmark this page for instant access

---

## 🔑 Essential Commands

### Daily Operations
```bash
# Check system status
php artisan retell:status

# View today's metrics  
php artisan retell:metrics --today

# List active calls
php artisan retell:list-active-calls

# Quick health check
php retell-health-check.php
```

### Testing & Debugging
```bash
# Test agent
php artisan retell:test-agent {agent_id}

# Make test call
php artisan retell:test-call --to="+49123456789"

# Check logs
tail -f storage/logs/retell-*.log

# Debug specific call
php artisan retell:debug-call {call_id}
```

### Maintenance
```bash
# Clear caches
php artisan cache:forget "retell_*"

# Reload functions
php artisan retell:reload-functions  

# Import missed calls
php import-retell-calls-manual.php

# Backup agents
php artisan retell:backup-agents
```

---

## 📊 Quick Status Checks

### Dashboard URLs
- **Retell Control Center**: `/admin/retell-control-center`
- **Call Logs**: `/admin/calls`
- **System Logs**: `/admin/logs?filter=retell`
- **Horizon Queue**: `/horizon`

### API Health Endpoints
```bash
# Internal status
curl https://api.askproai.de/api/retell/status

# Webhook test
curl -X POST https://api.askproai.de/api/retell/webhook/test

# Metrics endpoint
curl https://api.askproai.de/api/metrics | grep retell
```

---

## 🛠️ Common Tasks

### Create New Agent
```bash
php artisan retell:create-agent \
  --name="Customer Service DE" \
  --language="de" \
  --voice="sarah" \
  --prompt="Du bist ein freundlicher Kundenservice-Mitarbeiter..."
```

### Update Agent Prompt
```php
// Via tinker
php artisan tinker
>>> $agent = \App\Models\RetellAgent::find('agent_123');
>>> $agent->prompt = "Neuer Prompt...";
>>> $agent->save();
```

### Check Call Details
```sql
-- Recent calls
SELECT * FROM calls 
WHERE created_at > NOW() - INTERVAL 1 HOUR 
ORDER BY created_at DESC;

-- Failed calls
SELECT * FROM calls 
WHERE status = 'failed' 
AND created_at > NOW() - INTERVAL 24 HOUR;
```

---

## 🔧 Configuration Reference

### Environment Variables
```env
# Core settings
RETELL_API_KEY=key_xxxxx
RETELL_WEBHOOK_SECRET=xxxxx
RETELL_BASE=https://api.retellai.com

# Feature flags
RETELL_DEBUG=false
RETELL_SIMPLE_MODE=false
RETELL_RECORD_CALLS=true
```

### Key File Locations
```
app/Services/RetellV2Service.php          # Main service
app/Services/Webhooks/RetellWebhookHandler.php  # Webhook handler
app/Services/Retell/CustomFunctions/      # Custom functions
config/retell.php                         # Configuration
```

---

## 📈 Performance Metrics

### Target Values
| Metric | Good | Warning | Critical |
|--------|------|---------|----------|
| API Response | <200ms | 200-500ms | >500ms |
| Webhook Process | <1s | 1-3s | >3s |
| Call Success | >95% | 90-95% | <90% |
| Queue Size | <100 | 100-500 | >500 |

### Quick Performance Check
```bash
# Queue metrics
php artisan horizon:metrics

# Database performance
mysql -e "SHOW STATUS LIKE 'Slow_queries';"

# API latency
tail -f storage/logs/retell-api.log | grep "duration"
```

---

## 🚨 Troubleshooting Matrix

| Symptom | Check | Fix |
|---------|-------|-----|
| No calls received | `php artisan horizon:status` | `php artisan horizon` |
| Webhook errors | Check signature in logs | Update webhook secret |
| Agent errors | Test with simple prompt | Reset agent config |
| Slow responses | Check queue size | Scale workers |
| Missing data | Verify custom functions | Clear function cache |

---

## 📞 Test Numbers

### Internal Test Lines
```
+49 XXX XXX 0001 - German test agent
+49 XXX XXX 0002 - English test agent  
+49 XXX XXX 0003 - Load test line
+49 XXX XXX 0004 - Debug mode agent
```

### Test Scenarios
1. **Simple greeting**: "Hallo, ich möchte einen Termin"
2. **Availability check**: "Was haben Sie morgen frei?"
3. **Booking request**: "Ich hätte gerne einen Termin am Freitag um 14 Uhr"
4. **Customer lookup**: "Ich bin bereits Kunde"

---

## 🔐 Security Checklist

### Daily Checks
- [ ] Verify webhook signatures working
- [ ] Check for unusual call patterns
- [ ] Review error logs for exploits
- [ ] Monitor API rate limits

### Weekly Tasks
- [ ] Rotate webhook secrets
- [ ] Review agent prompts for leaks
- [ ] Audit custom function access
- [ ] Check backup integrity

---

## 📋 Cheat Sheet

### One-Liners
```bash
# Count today's calls
mysql -e "SELECT COUNT(*) FROM calls WHERE DATE(created_at) = CURDATE();"

# Average call duration
mysql -e "SELECT AVG(duration_seconds) FROM calls WHERE DATE(created_at) = CURDATE();"

# Failed webhook count
grep -c "webhook.*failed" storage/logs/retell-webhooks.log

# Active agents
php artisan tinker --execute="App\Models\RetellAgent::active()->count()"

# Clear all Retell caches
php artisan cache:clear --tags=retell
```

### Quick SQL Queries
```sql
-- Busiest hours today
SELECT HOUR(created_at) as hour, COUNT(*) as calls
FROM calls 
WHERE DATE(created_at) = CURDATE()
GROUP BY hour 
ORDER BY calls DESC;

-- Customer repeat calls
SELECT customer_phone, COUNT(*) as call_count
FROM calls
WHERE created_at > NOW() - INTERVAL 7 DAY
GROUP BY customer_phone
HAVING call_count > 1
ORDER BY call_count DESC;
```

---

## 🎯 Pro Tips

1. **Monitor webhook queue size** - High numbers indicate processing issues
2. **Use test agents** for trying new prompts before production
3. **Enable debug mode** only when actively troubleshooting
4. **Keep agent prompts versioned** in git for rollback
5. **Set up alerts** for webhook failures > 5%
6. **Cache agent configs** but invalidate on updates
7. **Use custom functions** for complex logic, not prompts

---

## 🔗 Quick Links

### Internal Tools
- [Control Center](https://api.askproai.de/admin/retell-control-center)
- [Horizon Dashboard](https://api.askproai.de/horizon)
- [System Logs](https://api.askproai.de/admin/logs)

### External Resources  
- [Retell Dashboard](https://app.retellai.com)
- [Retell API Docs](https://docs.retellai.com)
- [Status Page](https://status.retellai.com)

### Support
- Slack: #retell-support
- Email: retell-team@askproai.de
- Emergency: +49 XXX XXX 1111

---

**Keyboard Shortcuts** (in Control Center):
- `Ctrl+T`: Test current agent
- `Ctrl+L`: View live calls
- `Ctrl+R`: Refresh metrics
- `Ctrl+S`: Save agent changes

---

**Last Updated**: January 10, 2025  
**Quick Access Code**: `RETELL-QR-2025`