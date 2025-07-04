# 🚀 PRODUCTION DEPLOYMENT - FINAL CHECKLIST
**Datum: 26.06.2025 | Zeit: 09:00 Uhr | Status: READY**

## ✅ PRE-DEPLOYMENT VERIFICATION

### Security Status (UPDATED)
```bash
php artisan askproai:security-audit
# ✅ Security Score: 81.25%
# ✅ Critical Risks: 0 (FIXED)
# ✅ High Risks: 0 (FIXED)
# ✅ SQL Injection: FIXED
# ✅ Multi-tenancy Bypass: FIXED
# ✅ Authentication Bypass: FIXED
```

### Critical Security Fixes Applied
- Fixed SQL injection vulnerabilities in DatabaseMCPServer
- Implemented proper tenant isolation in CompanyScope
- Removed all test/debug endpoints
- Added authentication to MCP routes
- See CRITICAL_SECURITY_FIXES_COMPLETE.md for details

### System Health
```bash
curl https://api.askproai.de/api/health
# ✅ All services operational
```

### Backup Status
```bash
ls -la storage/backups/database/
# ✅ Latest backup: askproai_full_2025-06-25_02-00-02.sql.gz
```

---

## 📋 DEPLOYMENT STEPS (09:00 - 09:30)

### STEP 1: Final Backup (09:00)
```bash
php artisan askproai:backup --type=full --encrypt --compress
echo "✅ Backup created: $(date)"
```

### STEP 2: Enable Maintenance Mode (09:05)
```bash
php artisan down --message="System upgrade in progress" --retry=60
```

### STEP 3: Pull Latest Code (09:06)
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm install --production
npm run build
```

### STEP 4: Run Migrations (09:08)
```bash
php artisan migrate --force
# Expected: 7 migrations to run
```

### STEP 5: Clear & Optimize (09:10)
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
```

### STEP 6: Restart Services (09:12)
```bash
php artisan horizon:terminate
supervisorctl restart all
systemctl restart php8.2-fpm
```

### STEP 7: Health Check (09:14)
```bash
php artisan up
curl https://api.askproai.de/api/health
curl https://api.askproai.de/api/health/comprehensive
```

---

## 🔧 RETELL AGENT UPDATE (09:15 - 09:25)

### 1. Login to Retell Dashboard
- URL: https://dashboard.retell.ai
- Agent: agent_9a8202a740cd3120d96fcfda1e

### 2. Update Agent Prompt
Replace entire prompt with content from:
`/var/www/api-gateway/RETELL_AGENT_UPDATE_INSTRUCTIONS.md`

### 3. Add Custom Functions (7 total)
1. `check_intelligent_availability`
2. `create_multi_appointment` 
3. `identify_customer`
4. `save_customer_preference`
5. `apply_vip_benefits`
6. `transfer_to_fabian`
7. `schedule_callback`

### 4. Test Configuration
- Make test call to +49 30 837 93 369
- Verify dynamic variables work
- Test appointment booking

---

## 🧪 POST-DEPLOYMENT VERIFICATION (09:25 - 09:30)

### 1. Functional Tests
```bash
# Test Retell webhook
curl -X POST https://api.askproai.de/api/test/webhook \
  -H "Content-Type: application/json" \
  -d '{"event_type":"test","call_id":"deploy_test_001"}'

# Test authentication
curl https://api.askproai.de/admin/retell-ultimate-control-center
# Should redirect to login

# Test Circuit Breaker
curl https://api.askproai.de/api/health/circuit-breaker
```

### 2. Performance Check
```bash
./load-test-script.php https://api.askproai.de 10 30
# Expected: All tests PASSED
```

### 3. Security Verification
```bash
# Check API key encryption
php artisan tinker
>>> Company::first()->retell_api_key; // Should be decrypted
>>> Company::first()->getMaskedRetellApiKey(); // Should show key_****

# Check permissions
>>> User::find(1)->can('manage_retell_control_center'); // true/false
```

### 4. Monitoring Setup
- Open Grafana: https://grafana.askproai.de
- Check all dashboards loading
- Verify alerts configured

---

## 📞 FIRST PRODUCTION CALL (09:30)

### Test Scenario:
1. Call +49 30 837 93 369
2. Say: "Hallo, ich möchte einen Termin für morgen nachmittag buchen"
3. Verify:
   - Correct phone number captured
   - Tomorrow's date calculated correctly
   - Appointment saved in system

### Success Criteria:
- ✅ No hardcoded phone number
- ✅ Dynamic date calculation
- ✅ Appointment in database
- ✅ Confirmation email sent

---

## 🚨 ROLLBACK PLAN (if needed)

```bash
# 1. Enable maintenance
php artisan down

# 2. Rollback code
git checkout v1.9.0
composer install

# 3. Rollback migrations
php artisan migrate:rollback --step=7

# 4. Clear caches
php artisan optimize:clear

# 5. Restart services
supervisorctl restart all

# 6. Disable maintenance
php artisan up
```

---

## 📊 SUCCESS METRICS

### After 1 Hour:
- [ ] No critical errors in logs
- [ ] Response time < 200ms (P95)
- [ ] Success rate > 99%
- [ ] No security incidents

### After 24 Hours:
- [ ] 100+ successful calls processed
- [ ] 50+ appointments booked
- [ ] Circuit breaker remained closed
- [ ] No memory leaks

---

## 📞 SUPPORT CONTACTS

| Role | Contact | When to Call |
|------|---------|--------------|
| **Technical Lead** | Fabian (+491604366218) | Any critical issues |
| **Retell Support** | support@retell.ai | Agent configuration |
| **DevOps On-Call** | ops@askproai.de | Infrastructure issues |
| **Security Team** | security@askproai.de | Security incidents |

---

## ✅ FINAL CHECKLIST

Pre-Deployment:
- [x] All critical fixes implemented
- [x] Security audit passed (81.25%)
- [x] Load test script ready
- [x] Rollback plan documented
- [x] Team notified

Deployment:
- [ ] Backup created
- [ ] Migrations run
- [ ] Services restarted
- [ ] Health checks passed
- [ ] Retell agent updated

Post-Deployment:
- [ ] First call successful
- [ ] Monitoring active
- [ ] No errors in logs
- [ ] Team celebration! 🎉

---

**GO LIVE TIME: 26.06.2025 09:30 Uhr** 

Nach 1 Woche harter Arbeit und kritischen Security Fixes ist das System bereit für Production! 

**Let's make it happen!** 🚀