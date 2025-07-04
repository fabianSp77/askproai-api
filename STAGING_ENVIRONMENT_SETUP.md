# 🔧 STAGING ENVIRONMENT SETUP
**Stand: 25.06.2025 22:40 Uhr**

## ✅ PRODUCTION PREPARATION COMPLETE

### 1. **Cache Optimierung** ✅
```bash
php artisan optimize:clear  # ✅ Alle Caches geleert
php artisan config:cache    # ✅ Configuration cached
php artisan route:cache     # ✅ Routes cached
```

### 2. **Migrations Ready** ✅
```bash
# Pending Migrations bereinigt:
- Unsichere Migration entfernt: 2025_06_25_200001_add_multi_booking_support.php
- Sichere Migration bereit: 2025_06_25_200001_add_multi_booking_support_safe.php

# Bereit für:
php artisan migrate --force
```

### 3. **Security Status** ✅
```
Security Score: 81.25%
Critical Risks: 0
High Risks: 0
Low Risks: 3 (acceptable)
```

---

## 🚀 STAGING DEPLOYMENT SCRIPT

```bash
#!/bin/bash
# staging-deploy.sh

echo "🚀 Starting Staging Deployment..."

# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Run migrations
php artisan migrate --force

# 4. Clear and cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart services
php artisan horizon:terminate
supervisorctl restart all

# 6. Health check
curl -s https://staging.askproai.de/api/health | jq .

echo "✅ Staging Deployment Complete!"
```

---

## 🧪 STAGING TEST CHECKLIST

### 1. **Security Tests**
- [ ] Login mit verschiedenen Rollen testen
- [ ] Retell Control Center Zugriff nur für berechtigte User
- [ ] API Keys sind verschlüsselt in DB
- [ ] Keine Fehlermeldungen mit Stack Traces

### 2. **Functionality Tests**
- [ ] Phone Call simulieren
- [ ] Appointment Booking testen
- [ ] Circuit Breaker Test (Retell offline simulieren)
- [ ] Multi-Booking Feature testen

### 3. **Performance Tests**
- [ ] Response Time < 200ms für API Calls
- [ ] Database Queries < 50ms
- [ ] Memory Usage stabil
- [ ] Queue Processing funktioniert

### 4. **Integration Tests**
- [ ] Cal.com Integration
- [ ] Retell.ai Webhooks
- [ ] Email Versand
- [ ] SMS Notifications (falls aktiv)

---

## 📊 MONITORING SETUP

### Grafana Dashboard URLs:
- **System Overview**: https://grafana.askproai.de/d/system
- **Security Events**: https://grafana.askproai.de/d/security
- **API Performance**: https://grafana.askproai.de/d/api
- **Queue Status**: https://grafana.askproai.de/d/queues

### Alert Thresholds:
```yaml
alerts:
  - name: High Error Rate
    condition: error_rate > 5%
    action: notify_slack
    
  - name: Circuit Breaker Open
    condition: circuit_state == "open"
    action: notify_oncall
    
  - name: Queue Backlog
    condition: queue_size > 1000
    action: scale_workers
```

---

## 🔄 ROLLBACK PLAN

Falls Probleme auftreten:
```bash
# 1. Zur vorherigen Version zurück
git checkout v1.9.0

# 2. Rollback migrations
php artisan migrate:rollback --step=7

# 3. Clear caches
php artisan optimize:clear

# 4. Restart services
supervisorctl restart all

# 5. Verify
curl https://staging.askproai.de/api/health
```

---

## ✅ READY FOR STAGING

**Nächste Schritte:**
1. Deploy auf Staging (staging.askproai.de)
2. Vollständige Test Suite durchführen
3. Load Testing mit 1000 concurrent users
4. 24h Monitoring vor Production

**Geschätzte Zeit:** 
- Staging Deploy: 10 Minuten
- Tests: 2 Stunden
- Monitoring: 24 Stunden

**Go-Live bereit:** 26.06.2025 nach erfolgreichem Staging Test