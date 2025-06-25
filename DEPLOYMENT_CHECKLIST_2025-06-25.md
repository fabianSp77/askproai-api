# 🚀 DEPLOYMENT CHECKLIST - RETELL ULTIMATE CONTROL CENTER
**Datum: 25.06.2025 | Zeit bis Production: ~30 Minuten**

## ✅ PRE-DEPLOYMENT (5 Min)

### 1. Backup erstellen
```bash
php artisan askproai:backup --type=full --encrypt --compress
```

### 2. Security Test durchführen
```bash
php test-security-fixes.php
# ✅ PASSED - Alle Tests erfolgreich
```

---

## 🔧 DEPLOYMENT STEPS (20 Min)

### 1. Database Migration (2 Min)
```bash
php artisan migrate --force
```
**Neue Tabellen:**
- `appointment_series` - Wiederkehrende Termine
- `customer_preferences` - Kundenpräferenzen
- `customer_interactions` - Interaktionshistorie
- `group_bookings` - Gruppenbuchungen

### 2. Cache leeren (1 Min)
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

### 3. Queue Restart (1 Min)
```bash
php artisan horizon:terminate
php artisan horizon
```

### 4. Retell Agent Update (15 Min) ⚠️ MANUELL!
1. **Login**: https://dashboard.retell.ai
2. **Agent bearbeiten**
3. **General Prompt** komplett ersetzen mit Inhalt aus:
   ```
   /var/www/api-gateway/RETELL_AGENT_UPDATE_INSTRUCTIONS.md
   ```
4. **Custom Functions** hinzufügen (7 Stück):
   - `check_intelligent_availability`
   - `create_multi_appointment`
   - `identify_customer`
   - `save_customer_preference`
   - `apply_vip_benefits`
   - `transfer_to_fabian`
   - `schedule_callback`

### 5. Verify Deployment (1 Min)
```bash
# Test Retell webhook
curl -X POST https://api.askproai.de/api/test/webhook

# Check health
curl https://api.askproai.de/api/health/comprehensive
```

---

## 📊 POST-DEPLOYMENT (5 Min)

### 1. Monitoring aktivieren
- Grafana Dashboard: http://localhost:3000
- Security Dashboard: https://api.askproai.de/admin/security-dashboard
- Retell Control Center: https://api.askproai.de/admin/retell-ultimate-control-center

### 2. Logs überwachen
```bash
tail -f storage/logs/laravel.log | grep -E "Security|Error|Warning|Retell"
```

### 3. Erste Tests
1. **Testanruf** durchführen
2. **VIP-Erkennung** testen (bekannte Nummer anrufen)
3. **Weiterleitung** zu Fabian testen
4. **Multi-Booking** mit 3 Terminen testen

---

## 🚨 ROLLBACK PLAN

Falls Probleme auftreten:
```bash
# 1. Alte Migration rückgängig machen
php artisan migrate:rollback --step=4

# 2. Cache leeren
php artisan optimize:clear

# 3. Backup wiederherstellen
php artisan askproai:restore --latest

# 4. Retell Agent auf alte Version zurücksetzen
```

---

## 📞 NOTFALL-KONTAKTE

- **Technischer Lead**: Fabian (+491604366218)
- **Retell Support**: support@retell.ai
- **Cal.com Support**: support@cal.com

---

## ✅ DEPLOYMENT COMPLETE CHECKLIST

- [ ] Backup erstellt
- [ ] Security Tests passed
- [ ] Database migriert
- [ ] Retell Agent updated
- [ ] Custom Functions added
- [ ] Monitoring aktiv
- [ ] Testanruf erfolgreich
- [ ] Team informiert

**GESCHÄTZTE ZEIT**: 30 Minuten bei normalem Verlauf