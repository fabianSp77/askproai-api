# 🚀 IMMEDIATE DEPLOYMENT STEPS
**Zeit: 27.06.2025 | Für Operations Team**

## ✅ PRE-FLIGHT CHECKLIST

### 1. Code Status
```bash
# Aktueller Branch und Status prüfen
git status
git log -1 --oneline

# Erwartete Ausgabe:
# On branch main
# Your branch is up to date
```

### 2. Services starten (in dieser Reihenfolge)

#### a) PHP-FPM neu starten
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl status php8.3-fpm
```

#### b) Queue Worker starten
```bash
# In einem Screen oder Tmux Session:
screen -S horizon
php artisan horizon

# Oder als Service:
sudo systemctl start horizon
sudo systemctl status horizon
```

#### c) Log Rotation aktivieren
```bash
# Crontab editieren
sudo crontab -e

# Diese Zeile hinzufügen:
0 0 * * * /var/www/api-gateway/scripts/log-rotation.sh

# Script ausführbar machen
chmod +x /var/www/api-gateway/scripts/log-rotation.sh
```

### 3. Monitoring aktivieren

#### a) Initial Health Check
```bash
# API Health prüfen
curl -X GET https://api.askproai.de/api/health

# Cache Status
php artisan cache:manage status

# Database Connections
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' -e "SHOW STATUS LIKE 'Threads_connected';"
```

#### b) Live Monitoring starten
```bash
# In separatem Terminal
watch -n 5 'php artisan horizon:list'

# Oder Dashboard öffnen
# https://api.askproai.de/horizon
```

### 4. Erster Test-Anruf

#### a) Retell Agent Status prüfen
```bash
php artisan tinker
>>> $company = \App\Models\Company::first();
>>> $company->retell_agent_id; // Sollte nicht null sein
```

#### b) Test-Anruf durchführen
1. Anrufen: +49 [Configured Phone Number]
2. Erwartete Begrüßung hören
3. Testtermin vereinbaren
4. Im Admin-Panel prüfen: https://api.askproai.de/admin/appointments

### 5. Performance Validierung

#### a) Dashboard Load Test
```bash
# Zeit messen für Dashboard-Aufruf
time curl -s -o /dev/null -w "%{time_total}\n" https://api.askproai.de/admin

# Sollte < 1 Sekunde sein
```

#### b) Webhook Response Test
```bash
# Webhook Response Zeit testen
time curl -X POST https://api.askproai.de/api/retell/webhook \
  -H "Content-Type: application/json" \
  -d '{"event_type":"test"}'

# Sollte < 200ms sein (mit 401 Unauthorized)
```

## 🚨 NOTFALL-KONTAKTE

| Problem | Kontakt | Aktion |
|---------|---------|--------|
| Server Down | DevOps Team | +49xxx |
| Database Issue | DBA Sarah | +49xxx |
| Application Error | Backend Thomas | +49xxx |
| Retell.ai Problem | Fabian | +491604366218 |

## ⚠️ ROLLBACK (falls nötig)

```bash
# 1. Services stoppen
php artisan horizon:terminate
sudo systemctl stop php8.3-fpm

# 2. Zur letzten stabilen Version
git checkout e4c57f50

# 3. Deployment wiederholen
composer install --no-dev
php artisan optimize:clear
php artisan optimize

# 4. Services neu starten
sudo systemctl start php8.3-fpm
php artisan horizon
```

## ✅ GO-LIVE BESTÄTIGUNG

- [ ] Alle Services laufen
- [ ] Horizon aktiv
- [ ] Erster Test-Anruf erfolgreich
- [ ] Dashboard lädt < 1 Sekunde
- [ ] Keine Fehler in Logs

**Deployment abgeschlossen:** __________ Uhr
**Verantwortlich:** __________________