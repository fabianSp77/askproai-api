# Server Migration Plan: VPS 1000 → VPS 2000 ARM G11

## Neue Server Details:
- **Hostname**: v2202507255565358960.bestsrv.de
- **IP**: 152.53.116.127
- **IPv6**: 2a0a:4cc0:c0:374f::/64
- **Root Password**: fk38QOQdfidIyW4

## Migration Timeline: ~4-6 Stunden

### Phase 1: Vorbereitung (Alter Server)
```bash
# 1. Backup erstellen
cd /var/www
tar -czf /root/askproai-backup-$(date +%Y%m%d).tar.gz api-gateway/

# 2. Datenbank exportieren
mysqldump -u root -p'V9LGz2tdR5gpDQz' askproai_db > /root/askproai-db-$(date +%Y%m%d).sql

# 3. Redis Daten sichern
redis-cli BGSAVE
cp /var/lib/redis/dump.rdb /root/redis-backup.rdb

# 4. Nginx Config sichern
tar -czf /root/nginx-config.tar.gz /etc/nginx/

# 5. Systemd Services sichern
tar -czf /root/systemd-services.tar.gz /etc/systemd/system/askproai*
```

### Phase 2: Neuen Server vorbereiten
```bash
# SSH zum neuen Server
ssh root@152.53.116.127

# 1. System Update
apt update && apt upgrade -y

# 2. Basis-Software installieren
apt install -y nginx mysql-server redis-server php8.3-fpm php8.3-cli php8.3-common \
php8.3-mysql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml \
php8.3-bcmath php8.3-redis php8.3-intl composer nodejs npm git supervisor

# 3. Docker installieren (für Monitoring)
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
apt install -y docker-compose

# 4. User erstellen
useradd -m -s /bin/bash www-data
usermod -aG docker www-data
```

### Phase 3: Daten Transfer
```bash
# Auf altem Server
rsync -avz --progress /root/*.tar.gz root@152.53.116.127:/root/
rsync -avz --progress /root/*.sql root@152.53.116.127:/root/
rsync -avz --progress /root/redis-backup.rdb root@152.53.116.127:/root/

# Auf neuem Server
cd /var/www
tar -xzf /root/askproai-backup-*.tar.gz
chown -R www-data:www-data api-gateway/

# MySQL einrichten
mysql -u root -p
CREATE DATABASE askproai_db;
CREATE USER 'askproai_user'@'localhost' IDENTIFIED BY 'lkZ57Dju9EDjrMxn';
GRANT ALL PRIVILEGES ON askproai_db.* TO 'askproai_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Datenbank importieren
mysql -u root -p askproai_db < /root/askproai-db-*.sql

# Redis Daten wiederherstellen
cp /root/redis-backup.rdb /var/lib/redis/dump.rdb
chown redis:redis /var/lib/redis/dump.rdb
systemctl restart redis-server
```

### Phase 4: Konfiguration
```bash
# Nginx Konfiguration
tar -xzf /root/nginx-config.tar.gz -C /
systemctl restart nginx

# Systemd Services
tar -xzf /root/systemd-services.tar.gz -C /
systemctl daemon-reload
systemctl enable askproai-horizon askproai-scheduler

# Laravel Setup
cd /var/www/api-gateway
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan horizon:install
php artisan storage:link

# Permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache

# Environment File anpassen
# IP in .env aktualisieren
sed -i 's/65.109.168.45/152.53.116.127/g' .env
```

### Phase 5: Monitoring Setup
```bash
cd /var/www/api-gateway
docker-compose -f docker-compose.observability.yml up -d
```

### Phase 6: DNS Update & Test
1. DNS A-Record von 65.109.168.45 auf 152.53.116.127 ändern
2. Warten bis DNS propagiert ist (ca. 5-30 Minuten)
3. Tests durchführen:
   - https://api.askproai.de sollte funktionieren
   - Admin Panel testen
   - API Endpoints testen
   - Webhooks testen

### Phase 7: Cleanup & Optimierung
```bash
# Auf neuem Server - Performance Tuning für ARM
echo "vm.swappiness=10" >> /etc/sysctl.conf
echo "net.core.somaxconn=65535" >> /etc/sysctl.conf
sysctl -p

# PHP-FPM für mehr RAM optimieren
sed -i 's/pm.max_children = 5/pm.max_children = 50/g' /etc/php/8.3/fpm/pool.d/www.conf
sed -i 's/pm.start_servers = 2/pm.start_servers = 10/g' /etc/php/8.3/fpm/pool.d/www.conf
systemctl restart php8.3-fpm

# MySQL für mehr RAM
echo "[mysqld]" >> /etc/mysql/my.cnf
echo "innodb_buffer_pool_size = 2G" >> /etc/mysql/my.cnf
echo "max_connections = 200" >> /etc/mysql/my.cnf
systemctl restart mysql
```

### Rollback Plan
Falls etwas schief geht:
1. DNS zurück auf alte IP ändern
2. Alter Server läuft weiter
3. Fehler analysieren und beheben
4. Migration erneut versuchen

## Automatisiertes Migrations-Script
Speichern Sie dieses Script als `/root/migrate-to-new-server.sh` auf dem alten Server.