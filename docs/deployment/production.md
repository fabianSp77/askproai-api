# 🚀 Production Deployment Guide

**AskProAI Production Deployment**  
**Version:** 1.2.0  
**Target:** Ubuntu 22.04 LTS / CentOS 8+ / Debian 11+

## 📋 Pre-Deployment Checklist

### System Requirements
- [ ] **Server:** 4GB RAM, 2 vCPUs, 50GB SSD minimum
- [ ] **OS:** Ubuntu 22.04 LTS (recommended)
- [ ] **Domain:** SSL Certificate ready
- [ ] **Database:** MySQL 8.0+ / MariaDB 10.4+
- [ ] **Redis:** Version 6.0+
- [ ] **Node.js:** Version 18+
- [ ] **PHP:** Version 8.3+

### Security Requirements
- [ ] **Firewall:** Configured (ports 80, 443, 22)
- [ ] **SSH Keys:** Password auth disabled
- [ ] **Fail2Ban:** Installed and configured
- [ ] **SSL Certificate:** Valid wildcard cert
- [ ] **Backup System:** Tested and verified

---

## 🛠 Server Preparation

### 1. System Update & Basic Security

```bash
# Update system
apt update && apt upgrade -y

# Install essential packages
apt install -y curl wget git unzip software-properties-common \
  ufw fail2ban htop tree supervisor

# Configure UFW firewall
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 'Nginx Full'
ufw --force enable

# Configure Fail2Ban
cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
systemctl enable fail2ban
systemctl start fail2ban
```

### 2. Install PHP 8.3

```bash
# Add PHP repository
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP and extensions
apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis \
  php8.3-curl php8.3-zip php8.3-gd php8.3-mbstring php8.3-xml \
  php8.3-bcmath php8.3-intl php8.3-sqlite3

# Configure PHP-FPM
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.3/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.3/fpm/php.ini
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 20M/' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 25M/' /etc/php/8.3/fpm/php.ini

# Restart PHP-FPM
systemctl restart php8.3-fpm
systemctl enable php8.3-fpm
```

### 3. Install MySQL/MariaDB

```bash
# Install MariaDB
apt install -y mariadb-server mariadb-client

# Secure installation
mysql_secure_installation

# Create database and user
mysql -u root -p << EOF
CREATE DATABASE askproai_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'askproai_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON askproai_db.* TO 'askproai_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF

# Configure MySQL for performance
cat >> /etc/mysql/mariadb.conf.d/50-server.cnf << EOF

# AskProAI Optimizations
max_connections = 200
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
slow_query_log = 1
long_query_time = 2
EOF

systemctl restart mariadb
```

### 4. Install Redis

```bash
# Install Redis
apt install -y redis-server

# Configure Redis
sed -i 's/# maxmemory <bytes>/maxmemory 512mb/' /etc/redis/redis.conf
sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf

systemctl restart redis-server
systemctl enable redis-server
```

### 5. Install Nginx

```bash
# Install Nginx
apt install -y nginx

# Remove default config
rm /etc/nginx/sites-enabled/default

# Create AskProAI config
cat > /etc/nginx/sites-available/askproai << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name api.askproai.de;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.askproai.de;

    root /var/www/askproai/public;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.askproai.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.askproai.de/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;
    ssl_dhparam /etc/nginx/dhparam.pem;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ ^/(storage|bootstrap/cache) {
        deny all;
    }

    # Horizon Dashboard (restrict access)
    location /horizon {
        allow 10.0.0.0/8;
        allow 172.16.0.0/12;
        allow 192.168.0.0/16;
        deny all;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

# Enable site
ln -s /etc/nginx/sites-available/askproai /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### 6. Install Node.js & NPM

```bash
# Install Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Verify installation
node --version
npm --version
```

### 7. Install Composer

```bash
# Download Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Verify installation
composer --version
```

---

## 📦 Application Deployment

### 1. Create Deployment User

```bash
# Create application user
useradd -m -s /bin/bash askproai
usermod -aG www-data askproai

# Create directories
mkdir -p /var/www/askproai
chown askproai:www-data /var/www/askproai
```

### 2. Deploy Application Code

```bash
# Switch to deployment user
sudo -u askproai bash

# Clone repository
cd /var/www
git clone https://github.com/your-org/askproai.git
cd askproai

# Set proper permissions
sudo chown -R askproai:www-data /var/www/askproai
sudo chmod -R 755 /var/www/askproai
sudo chmod -R 775 storage bootstrap/cache
```

### 3. Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev --no-interaction

# Install Node.js dependencies
npm ci

# Build assets
npm run build
```

### 4. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate --force

# Configure environment (edit with your values)
cat > .env << EOF
APP_NAME=AskProAI
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://api.askproai.de

LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAYS=7

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_db
DB_USERNAME=askproai_user
DB_PASSWORD=YOUR_DB_PASSWORD

BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cal.com Integration
CALCOM_API_KEY=your_calcom_api_key
CALCOM_BASE_URL=https://api.cal.com/v1
CALCOM_WEBHOOK_SECRET=your_calcom_webhook_secret

# RetellAI Integration
RETELL_API_KEY=your_retell_api_key
RETELL_WEBHOOK_SECRET=your_retell_webhook_secret

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_smtp_user
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@askproai.de
MAIL_FROM_NAME="${APP_NAME}"

# AWS S3 (for backups)
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=askproai-backups

# Stripe Integration
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret
EOF

# Set secure permissions
chmod 600 .env
```

### 5. Database Setup

```bash
# Run migrations
php artisan migrate --force

# Seed admin user and demo data
php artisan db:seed --class=AdminUserSeeder

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
php artisan storage:link
```

---

## ⚙️ Process Management

### 1. Configure Supervisor for Horizon

```bash
# Create Horizon supervisor config
cat > /etc/supervisor/conf.d/askproai-horizon.conf << EOF
[program:askproai-horizon]
process_name=%(program_name)s
command=php /var/www/askproai/artisan horizon
directory=/var/www/askproai
autostart=true
autorestart=true
user=askproai
redirect_stderr=true
stdout_logfile=/var/www/askproai/storage/logs/horizon.log
stopwaitsecs=3600
EOF

# Update supervisor
supervisorctl reread
supervisorctl update
supervisorctl start askproai-horizon
```

### 2. Configure Cron Jobs

```bash
# Add cron jobs for askproai user
crontab -u askproai -e

# Add this line:
* * * * * cd /var/www/askproai && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Log Rotation

```bash
# Configure log rotation
cat > /etc/logrotate.d/askproai << EOF
/var/www/askproai/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 askproai www-data
    postrotate
        supervisorctl restart askproai-horizon > /dev/null
    endscript
}
EOF
```

---

## 🔒 SSL Certificate Setup

### Install Certbot

```bash
# Install Certbot
apt install -y certbot python3-certbot-nginx

# Generate DH parameters
openssl dhparam -out /etc/nginx/dhparam.pem 2048

# Obtain SSL certificate
certbot --nginx -d api.askproai.de

# Test auto-renewal
certbot renew --dry-run

# Add auto-renewal to crontab
echo "0 12 * * * /usr/bin/certbot renew --quiet" | crontab -
```

---

## 📊 Monitoring Setup

### 1. Health Check Script

```bash
# Create health check script
cat > /usr/local/bin/askproai-health.sh << 'EOF'
#!/bin/bash

ERRORS=0
LOGFILE="/var/log/askproai-health.log"

log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOGFILE
}

# Check HTTP response
if ! curl -sf https://api.askproai.de/api/health >/dev/null; then
    log_message "ERROR: HTTP health check failed"
    ERRORS=$((ERRORS + 1))
fi

# Check database
cd /var/www/askproai
if ! php artisan db:monitor --databases=mysql >/dev/null 2>&1; then
    log_message "ERROR: Database health check failed"
    ERRORS=$((ERRORS + 1))
fi

# Check Horizon
if ! php artisan horizon:status | grep -q "running"; then
    log_message "ERROR: Horizon not running"
    supervisorctl restart askproai-horizon
    ERRORS=$((ERRORS + 1))
fi

# Check Redis
if ! redis-cli ping >/dev/null 2>&1; then
    log_message "ERROR: Redis not responding"
    ERRORS=$((ERRORS + 1))
fi

if [ $ERRORS -eq 0 ]; then
    log_message "INFO: All systems operational"
    exit 0
else
    log_message "WARNING: $ERRORS issues detected"
    exit 1
fi
EOF

chmod +x /usr/local/bin/askproai-health.sh

# Add to crontab (every 5 minutes)
echo "*/5 * * * * /usr/local/bin/askproai-health.sh" >> /etc/crontab
```

### 2. System Monitoring

```bash
# Install additional monitoring tools
apt install -y iotop iftop nethogs

# Create system stats script
cat > /usr/local/bin/askproai-stats.sh << 'EOF'
#!/bin/bash

echo "=== AskProAI System Status $(date) ==="
echo

echo "=== PHP-FPM Status ==="
systemctl is-active php8.3-fpm

echo "=== Nginx Status ==="
systemctl is-active nginx

echo "=== Database Status ==="
systemctl is-active mariadb

echo "=== Redis Status ==="
systemctl is-active redis-server

echo "=== Horizon Status ==="
cd /var/www/askproai && php artisan horizon:status

echo "=== Queue Statistics ==="
cd /var/www/askproai && php artisan queue:monitor redis:default --max=100

echo "=== Disk Usage ==="
df -h | grep -E '^/dev|Filesystem'

echo "=== Memory Usage ==="
free -h

echo "=== Top Processes ==="
ps aux --sort=-%cpu | head -10
EOF

chmod +x /usr/local/bin/askproai-stats.sh
```

---

## 🔄 Backup Strategy

### 1. Database Backup Script

```bash
# Create backup directory
mkdir -p /var/backups/askproai/{db,files,config}

# Database backup script
cat > /usr/local/bin/askproai-db-backup.sh << 'EOF'
#!/bin/bash

BACKUP_DIR="/var/backups/askproai/db"
DATE=$(date +%Y%m%d_%H%M%S)
FILENAME="askproai_db_${DATE}.sql"

# Create backup
mysqldump --single-transaction --routines --triggers \
  -u askproai_user -p'YOUR_DB_PASSWORD' askproai_db > "${BACKUP_DIR}/${FILENAME}"

# Compress backup
gzip "${BACKUP_DIR}/${FILENAME}"

# Upload to S3 (if configured)
if [ ! -z "$AWS_ACCESS_KEY_ID" ]; then
    aws s3 cp "${BACKUP_DIR}/${FILENAME}.gz" "s3://askproai-backups/db/${FILENAME}.gz"
fi

# Keep only last 7 days locally
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "Database backup completed: ${FILENAME}.gz"
EOF

chmod +x /usr/local/bin/askproai-db-backup.sh
```

### 2. Full Backup Script

```bash
# Full system backup
cat > /usr/local/bin/askproai-full-backup.sh << 'EOF'
#!/bin/bash

BACKUP_DIR="/var/backups/askproai"
DATE=$(date +%Y%m%d_%H%M%S)
LOG_FILE="/var/log/askproai-backup.log"

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a $LOG_FILE
}

log "Starting full backup..."

# Database backup
/usr/local/bin/askproai-db-backup.sh

# Files backup
log "Backing up application files..."
tar -czf "${BACKUP_DIR}/files/askproai_files_${DATE}.tar.gz" \
    --exclude='/var/www/askproai/storage/logs' \
    --exclude='/var/www/askproai/node_modules' \
    /var/www/askproai

# Config backup
log "Backing up configuration..."
tar -czf "${BACKUP_DIR}/config/askproai_config_${DATE}.tar.gz" \
    /etc/nginx/sites-available/askproai \
    /etc/supervisor/conf.d/askproai-*.conf \
    /var/www/askproai/.env

# Cleanup old backups
find $BACKUP_DIR -name "*.tar.gz" -mtime +14 -delete

log "Full backup completed successfully"
EOF

chmod +x /usr/local/bin/askproai-full-backup.sh

# Schedule daily backups at 3 AM
echo "0 3 * * * /usr/local/bin/askproai-full-backup.sh" >> /etc/crontab
```

---

## 🚀 Go Live Checklist

### Final Steps

- [ ] **DNS:** Point domain to server
- [ ] **SSL:** Certificate installed and working
- [ ] **Firewall:** Configured and active
- [ ] **Services:** All services running and enabled
- [ ] **Backups:** First backup completed successfully
- [ ] **Monitoring:** Health checks passing
- [ ] **Logs:** Log rotation configured
- [ ] **Performance:** Site loads in < 2 seconds
- [ ] **Security:** Security headers verified
- [ ] **API:** All endpoints responding correctly

### Post-Deployment Verification

```bash
# Run final verification
cd /var/www/askproai

# Check services
systemctl status nginx php8.3-fpm mariadb redis-server
supervisorctl status askproai-horizon

# Test application
php artisan about
php artisan config:show app
php artisan route:list

# Test database
php artisan db:monitor

# Test external connections
curl -I https://api.askproai.de
curl -X POST https://api.askproai.de/api/retell/webhook \
  -H "Content-Type: application/json" \
  -d '{"event":"test"}'

# Check logs
tail -f storage/logs/laravel.log
```

### Performance Tuning

```bash
# Optimize PHP OPCache
echo "opcache.enable=1" >> /etc/php/8.3/fpm/conf.d/10-opcache.ini
echo "opcache.memory_consumption=128" >> /etc/php/8.3/fpm/conf.d/10-opcache.ini
echo "opcache.max_accelerated_files=4000" >> /etc/php/8.3/fpm/conf.d/10-opcache.ini

# Restart services
systemctl restart php8.3-fpm nginx

# Warm up caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🆘 Troubleshooting

### Common Issues

#### 1. 502 Bad Gateway
```bash
# Check PHP-FPM status
systemctl status php8.3-fpm

# Check PHP-FPM logs
tail -f /var/log/php8.3-fpm.log

# Check socket permissions
ls -la /var/run/php/php8.3-fpm.sock
```

#### 2. Queue Jobs Not Processing
```bash
# Check Horizon status
supervisorctl status askproai-horizon

# Check Redis connection
redis-cli ping

# Restart Horizon
supervisorctl restart askproai-horizon
```

#### 3. Database Connection Issues
```bash
# Check database status
systemctl status mariadb

# Test connection
mysql -u askproai_user -p askproai_db

# Check Laravel configuration
php artisan config:show database
```

#### 4. SSL Certificate Issues
```bash
# Test SSL
openssl s_client -connect api.askproai.de:443

# Renew certificate
certbot renew --force-renewal

# Check certificate expiry
certbot certificates
```

---

**Deployment completed successfully! 🎉**

Your AskProAI production environment is now ready.

*Last Updated: August 14, 2025*  
*Deployment Guide v1.2.0*