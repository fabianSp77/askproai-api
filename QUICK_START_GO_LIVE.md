# 🚀 QUICK START - SOFORT LIVE GEHEN!

**⏱️ Zeit bis Go-Live: 30 Minuten**  
**💰 Umsatzverlust pro Tag Verzögerung: 100%**

## 🔴 KRITISCH: Diese 3 Schritte SOFORT ausführen!

### SCHRITT 1: Stripe-Keys besorgen (5 Min)
```bash
# 1. Öffnen Sie: https://dashboard.stripe.com/apikeys
# 2. Kopieren Sie:
#    - Publishable key (beginnt mit pk_live_)
#    - Secret key (beginnt mit sk_live_)
```

### SCHRITT 2: Konfiguration aktivieren (5 Min)
```bash
# Terminal öffnen und ausführen:
cd /var/www/api-gateway

# Billing-Konfiguration bearbeiten
nano .env.billing.production

# Diese 2 Zeilen anpassen:
STRIPE_KEY="pk_live_IHR_KEY_HIER"
STRIPE_SECRET="sk_live_IHR_KEY_HIER"

# Speichern: Strg+O, Enter, Strg+X
```

### SCHRITT 3: Deployment starten (10 Min)
```bash
# Deployment-Script ausführen
./scripts/deploy-billing-phase1.sh

# Den Anweisungen folgen!
```

## ✅ FERTIG! System ist live!

### Monitoring starten:
```bash
# Live-Dashboard öffnen
./scripts/billing-dashboard.sh
```

---

## 📋 Webhook-Setup (10 Min)

Nach dem Deployment:

1. **Stripe Dashboard öffnen**: https://dashboard.stripe.com/webhooks
2. **"Add endpoint" klicken**
3. **Endpoint URL**: `https://api.askproai.de/billing/webhook`
4. **Events auswählen**:
   - ✓ checkout.session.completed
   - ✓ payment_intent.succeeded
   - ✓ payment_intent.payment_failed
   - ✓ charge.refunded
5. **"Add endpoint" klicken**
6. **Signing secret kopieren** (whsec_...)
7. **In .env einfügen**:
```bash
nano .env
# Diese Zeile anpassen:
STRIPE_WEBHOOK_SECRET="whsec_IHR_SECRET_HIER"

# Cache neu laden
php artisan config:cache
```

## 🎯 Test-Zahlung durchführen

```bash
# Test-URL im Browser öffnen:
https://api.askproai.de/billing/topup?amount=1000

# Test-Kreditkarte verwenden:
Nummer: 4242 4242 4242 4242
Ablauf: 12/34
CVC: 123
```

## 📊 Erfolg verifizieren

```bash
# Health-Check ausführen
php artisan billing:health-check

# Dashboard prüfen
./scripts/billing-dashboard.sh

# Sollte zeigen:
✅ Stripe: Konfiguriert
✅ Database: Online
✅ Bilanzen: OK
```

## 🆘 Bei Problemen

### Problem: "Stripe keys not valid"
```bash
# Keys nochmal prüfen
grep STRIPE .env

# Müssen mit pk_live_ und sk_live_ beginnen!
# NICHT pk_test_ oder sk_test_!
```

### Problem: "Webhook not working"
```bash
# Webhook-Secret prüfen
grep STRIPE_WEBHOOK_SECRET .env

# Muss mit whsec_ beginnen!
```

### Problem: Rollback nötig
```bash
# Letztes Backup finden
ls -la /var/www/backups/billing-deployment/

# Rollback ausführen
/var/www/backups/billing-deployment/backup_TIMESTAMP/rollback.sh
```

## 📞 Support

**Email**: admin@askproai.de  
**Urgent**: Stripe Support Chat (24/7)

---

## 🎉 Nach erfolgreichem Go-Live

### Tag 1: Erste Reseller
1. Admin-Panel öffnen: https://api.askproai.de/admin
2. Tenant → Create → Type: "reseller"
3. Provision: 25% standard

### Woche 1: Monitoring
```bash
# Täglicher Report
php artisan billing:health-check --email

# Live-Metriken
./scripts/billing-dashboard.sh
```

### Monat 1: Optimierung
- Phase 2 starten (Reseller-Tools)
- Performance-Baseline messen
- Erste Auszahlungen

---

**WICHTIG**: Jeder Tag ohne aktive Stripe-Integration = 100% Umsatzverlust!

**JETZT STARTEN!** 🚀