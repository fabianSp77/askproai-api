# Installation Guide

## Overview

This guide provides step-by-step instructions for installing AskProAI on a fresh server. Follow these steps carefully to ensure a successful deployment.

## Prerequisites

Before starting, ensure you have:
- A server meeting the [system requirements](requirements.md)
- Root or sudo access to the server
- Domain names configured and pointing to your server
- API credentials for Retell.ai and Cal.com

## Step 1: Server Preparation

### Update System
```bash
# Update package lists
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y \
    curl \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release
```

### Create Application User
```bash
# Create a dedicated user for the application
sudo useradd -m -s /bin/bash askproai
sudo usermod -aG www-data askproai

# Create application directory
sudo mkdir -p /var/www/api-gateway
sudo chown askproai:www-data /var/www/api-gateway
```

## Step 2: Install PHP

### Add PHP Repository
```bash
# Add Ondrej PHP repository (Ubuntu)
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

### Install PHP and Extensions
```bash
# Install PHP 8.2 and required extensions
sudo apt install -y \
    php8.2-fpm \
    php8.2-cli \
    php8.2-common \
    php8.2-mysql \
    php8.2-zip \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-readline \
    php8.2-redis \
    php8.2-soap

# Configure PHP
sudo sed -i 's/memory_limit = 128M/memory_limit = 512M/g' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 50M/g' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/post_max_size = 8M/post_max_size = 50M/g' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/max_execution_time = 30/max_execution_time = 300/g' /etc/php/8.2/fpm/php.ini

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

## Step 3: Install MySQL

### Install MySQL Server
```bash
# Install MySQL 8.0
sudo apt install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation
```

### Create Database and User
```bash
# Login to MySQL as root
sudo mysql

# Create database and user
CREATE DATABASE askproai_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'askproai_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON askproai_db.* TO 'askproai_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Configure MySQL for Performance
```bash
# Edit MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Add these settings under [mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 500

# Restart MySQL
sudo systemctl restart mysql
```

## Step 4: Install Redis

### Install Redis Server
```bash
# Install Redis
sudo apt install -y redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf

# Set these values:
supervised systemd
maxmemory 2gb
maxmemory-policy allkeys-lru
appendonly yes
appendfsync everysec

# Enable and start Redis
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

## Step 5: Install Nginx

### Install and Configure Nginx
```bash
# Install Nginx
sudo apt install -y nginx

# Create site configuration
sudo nano /etc/nginx/sites-available/askproai

# Add the following configuration:
```

```nginx
server {
    listen 80;
    server_name api.askproai.de;
    root /var/www/api-gateway/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 32k;
        fastcgi_buffers 4 32k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/askproai /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default

# Test and reload Nginx
sudo nginx -t
sudo systemctl reload nginx
```

## Step 6: Install Composer

```bash
# Download and install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Verify installation
composer --version
```

## Step 7: Install Node.js

```bash
# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify installation
node --version
npm --version
```

## Step 8: Clone and Setup Application

### Clone Repository
```bash
# Switch to application user
sudo su - askproai

# Clone repository (replace with your repository URL)
cd /var/www
git clone https://github.com/your-org/api-gateway.git
cd api-gateway
```

### Install Dependencies
```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm install
npm run build
```

### Configure Environment
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit environment file
nano .env
```

Update the following values in `.env`:
```env
APP_NAME=AskProAI
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.askproai.de

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_db
DB_USERNAME=askproai_user
DB_PASSWORD=your_secure_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Add your service credentials
DEFAULT_RETELL_API_KEY=your_retell_key
DEFAULT_CALCOM_API_KEY=your_calcom_key
# ... other credentials
```

### Setup Application
```bash
# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
exit  # Return to root user
sudo chown -R askproai:www-data /var/www/api-gateway
sudo chmod -R 755 /var/www/api-gateway
sudo chmod -R 775 /var/www/api-gateway/storage
sudo chmod -R 775 /var/www/api-gateway/bootstrap/cache
```

## Step 9: Install SSL Certificate

### Using Let's Encrypt
```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d api.askproai.de -d app.askproai.de -d admin.askproai.de

# Auto-renewal
sudo systemctl enable certbot.timer
```

## Step 10: Setup Queue Workers

### Install Supervisor
```bash
# Install Supervisor
sudo apt install -y supervisor

# Create Horizon supervisor configuration
sudo nano /etc/supervisor/conf.d/horizon.conf
```

Add the following configuration:
```ini
[program:horizon]
process_name=%(program_name)s
command=php /var/www/api-gateway/artisan horizon
autostart=true
autorestart=true
user=askproai
redirect_stderr=true
stdout_logfile=/var/www/api-gateway/storage/logs/horizon.log
stopwaitsecs=3600
```

```bash
# Start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start horizon
```

## Step 11: Setup Cron Jobs

```bash
# Edit crontab for askproai user
sudo crontab -u askproai -e

# Add Laravel scheduler
* * * * * cd /var/www/api-gateway && php artisan schedule:run >> /dev/null 2>&1
```

## Step 12: Configure Firewall

```bash
# Setup UFW firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
```

## Step 13: Performance Optimization

### Enable OPcache
```bash
# Edit PHP configuration
sudo nano /etc/php/8.2/fpm/conf.d/10-opcache.ini

# Add these settings:
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Configure Swap (if needed)
```bash
# Create swap file
sudo fallocate -l 4G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Make permanent
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

## Step 14: Initial Data Setup

```bash
# Switch to application user
sudo su - askproai
cd /var/www/api-gateway

# Create first admin user
php artisan askproai:create-admin

# Import initial data (if available)
php artisan db:seed --class=ProductionSeeder
```

## Step 15: Verify Installation

### Check Services
```bash
# Check all services are running
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status redis
sudo supervisorctl status

# Check Laravel installation
php artisan about
```

### Test Application
```bash
# Test homepage
curl -I https://api.askproai.de

# Test API health endpoint
curl https://api.askproai.de/api/health

# Check logs for errors
tail -f /var/www/api-gateway/storage/logs/laravel.log
```

## Post-Installation Steps

### 1. Configure Webhooks
- Log into Retell.ai dashboard and set webhook URL to `https://api.askproai.de/api/retell/webhook`
- Log into Cal.com and configure webhook URL to `https://api.askproai.de/api/webhooks/calcom`

### 2. Setup Monitoring
```bash
# Install monitoring agent (example: New Relic)
curl -Ls https://download.newrelic.com/install/newrelic-cli/scripts/install.sh | bash
sudo NEW_RELIC_API_KEY=YOUR_KEY NEW_RELIC_ACCOUNT_ID=YOUR_ACCOUNT_ID /usr/local/bin/newrelic install
```

### 3. Configure Backups
```bash
# Setup automated backups
sudo nano /usr/local/bin/backup-askproai.sh

# Add backup script content
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/askproai"
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u askproai_user -p'your_secure_password' askproai_db | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C /var/www/api-gateway storage/app/public

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete

# Make executable
sudo chmod +x /usr/local/bin/backup-askproai.sh

# Add to cron
sudo crontab -e
0 3 * * * /usr/local/bin/backup-askproai.sh
```

### 4. Security Hardening
```bash
# Install Fail2ban
sudo apt install -y fail2ban

# Configure Fail2ban for Laravel
sudo nano /etc/fail2ban/jail.local

[laravel-auth]
enabled = true
port = http,https
filter = laravel-auth
logpath = /var/www/api-gateway/storage/logs/laravel.log
maxretry = 5
bantime = 3600
```

## Troubleshooting

### Common Issues

#### Permission Errors
```bash
# Fix permissions
sudo chown -R askproai:www-data /var/www/api-gateway
sudo chmod -R 755 /var/www/api-gateway
sudo chmod -R 775 /var/www/api-gateway/storage
sudo chmod -R 775 /var/www/api-gateway/bootstrap/cache
```

#### 500 Server Error
```bash
# Check logs
tail -n 50 /var/www/api-gateway/storage/logs/laravel.log
tail -n 50 /var/log/nginx/error.log

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

#### Database Connection Error
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check credentials in .env file
```

#### Queue Not Processing
```bash
# Check Horizon status
php artisan horizon:status

# Restart supervisor
sudo supervisorctl restart horizon
```

## Next Steps

1. Review the [Production Deployment Guide](production.md)
2. Configure [Monitoring](../operations/monitoring.md)
3. Setup [Backup Strategy](backup.md)
4. Review [Security Best Practices](../configuration/security.md)

## Related Documentation
- [System Requirements](requirements.md)
- [Production Deployment](production.md)
- [Configuration Guide](../configuration/)
- [Troubleshooting Guide](../operations/troubleshooting.md)