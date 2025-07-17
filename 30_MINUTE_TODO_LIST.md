# ✅ 30-Minuten TODO Liste
*Copy & Paste Commands - Keine Panik, fast alles läuft bereits!*

## 1. Backup-Cron (2 Min) ⏰
```bash
(crontab -l; echo "0 2 * * * cd /var/www/api-gateway && php artisan backup:run --only-db >> /var/log/backup.log 2>&1") | crontab -
```

## 2. Sentry aktivieren (5 Min) 🐛
```bash
# Gehe zu sentry.io → Create Project → Laravel → Kopiere DSN
# Dann in .env einfügen:
SENTRY_LARAVEL_DSN=https://xxxxx@sentry.io/xxxxx

php artisan config:cache
php artisan sentry:test
```

## 3. Uptime-Monitor (10 Min) 📡
```bash
# Gehe zu uptimerobot.com (kostenlos)
# Add Monitor: https://api.askproai.de/health.php
# Check every 1 minute, Email alerts
```

## 4. OpCache (3 Min) ⚡
```bash
sudo ./optimize-opcache.sh
sudo systemctl restart php8.3-fpm
```

## 5. Laravel Optimize (2 Min) 🚀
```bash
php artisan config:cache
php artisan view:cache
php artisan optimize
```

## 6. Quick Test (3 Min) ✓
```bash
# Check backup:
php artisan backup:run --only-db

# Check health:
curl https://api.askproai.de/health.php

# Check logs:
tail -20 storage/logs/laravel.log
```

---

**FERTIG! Das System ist jetzt produktionsbereit.**

Alles andere ist "nice to have" für später.