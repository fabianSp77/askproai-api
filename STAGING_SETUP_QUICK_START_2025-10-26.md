# Staging Setup - Quick Start Guide
**Date**: 2025-10-26
**Target**: Customer Portal Feature
**Duration**: ~2-3 hours (infrastructure setup)

---

## Prerequisites

- SSH access to production server
- sudo privileges
- MySQL root or admin access
- Basic Linux command knowledge
- domain registration: staging.askproai.de (already exists)

---

## Step 1: Create Staging Database (15 min)

### 1.1 Connect to MySQL
```bash
mysql -u root -p
# Enter MySQL root password
```

### 1.2 Create Database and User
```sql
-- Create staging database
CREATE DATABASE askproai_staging
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Grant permissions to existing user
GRANT ALL PRIVILEGES ON askproai_staging.*
  TO 'askproai_user'@'localhost';

-- Verify
SHOW DATABASES LIKE 'askproai%';
USE askproai_staging;
SHOW TABLES;  -- Should be empty initially

FLUSH PRIVILEGES;
EXIT;
```

---

## Step 2: Copy Production Database to Staging (20 min)

### 2.1 Create Backup of Production
```bash
cd /var/www/api-gateway

# Backup production (for safety before sync)
mysqldump -u askproai_user -p askproai_db \
  > storage/backups/prod-before-staging-sync-$(date +%Y%m%d-%H%M%S).sql
```

### 2.2 Backup Staging (if it exists)
```bash
# Backup any existing staging data
mysqldump -u askproai_user -p askproai_staging \
  > storage/backups/staging-backup-$(date +%Y%m%d-%H%M%S).sql
```

### 2.3 Dump Production Data
```bash
mysqldump -u askproai_user -p askproai_db \
  --ignore-table=askproai_db.retell_call_events \
  --ignore-table=askproai_db.retell_transcripts \
  > /tmp/prod_dump.sql

# (Optional: Exclude large tables if disk space is limited)
# This avoids copying huge call history tables
```

### 2.4 Restore to Staging
```bash
mysql -u askproai_user -p askproai_staging < /tmp/prod_dump.sql

# Verify
mysql -u askproai_user -p askproai_staging -e "SHOW TABLES;" | wc -l
# Should show: tables count > 0
```

### 2.5 Sanitize Sensitive Data (Optional but Recommended)
```bash
mysql -u askproai_user -p askproai_staging << 'EOF'

-- Update user passwords to insecure test password
-- PASSWORD: 'test123' hashed with bcrypt
-- Hash generated: $2y$12$E8rnvTiP2VZ5dKIUgaF5OuYhSV4S6WqHqkK2w8K8E8K8E8K8E8K8E
UPDATE users
SET password = '$2y$12$E8rnvTiP2VZ5dKIUgaF5OuYhSV4S6WqHqkK2w8K8E8K8E8K8E8K8E'
WHERE 1=1;

-- Optional: Update test emails
-- UPDATE users
-- SET email = CONCAT(id, '+test@staging.askproai.de')
-- WHERE email NOT LIKE '%@askproai.de';

-- Verify: Check a user exists
SELECT id, name, email FROM users LIMIT 1;

EOF

echo "✅ Staging database ready for testing"
echo "Test login credentials:"
echo "  Email: (from database)"
echo "  Password: test123"
```

---

## Step 3: Setup Nginx Vhost (20 min)

### 3.1 Create Nginx Configuration File
```bash
sudo tee /etc/nginx/sites-available/staging.askproai.de > /dev/null << 'EOF'
# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name staging.askproai.de;
    return 301 https://$host$request_uri;
}

# HTTPS vhost for staging
server {
    listen 443 ssl http2;
    server_name staging.askproai.de;

    root /var/www/api-gateway/public;
    index index.php;

    # SSL Certificate (use Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/staging.askproai.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/staging.askproai.de/privkey.pem;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL_STAGING:10m;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Staging-Environment "true" always;

    # Upload limit
    client_max_body_size 700M;

    # Health check endpoint
    location = /health {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # API routes
    location ^~ /api/ {
        try_files $uri /index.php?$query_string;
    }

    # Portal routes
    location ^~ /portal/ {
        try_files $uri /index.php?$query_string;
    }

    # Admin routes
    location /admin {
        try_files $uri /index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param APP_ENV staging;
    }

    # Static files caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1h;
        add_header Cache-Control "public, immutable";
    }

    # Deny hidden files
    location ~ /\.ht {
        deny all;
    }
}
EOF

echo "✅ Nginx config created"
```

### 3.2 Enable the Vhost
```bash
sudo ln -s /etc/nginx/sites-available/staging.askproai.de \
           /etc/nginx/sites-enabled/staging.askproai.de

# Test nginx syntax
sudo nginx -t

# If OK: Reload nginx
sudo systemctl reload nginx

echo "✅ Nginx vhost enabled"
```

---

## Step 4: SSL Certificate (15 min)

### 4.1 Create/Obtain SSL Certificate (Let's Encrypt)
```bash
# Check if certificate already exists
ls -la /etc/letsencrypt/live/staging.askproai.de/ 2>/dev/null

# If not, create one
sudo certbot certonly --nginx \
  --non-interactive \
  --agree-tos \
  -m admin@askproai.de \
  -d staging.askproai.de

# Verify certificate
sudo certbot certificates | grep staging

echo "✅ SSL certificate ready"
```

### 4.2 Verify Certificate in Nginx
```bash
# Test HTTPS connection
curl -I https://staging.askproai.de

# Should show: HTTP/2 200 (if Laravel app is ready)
# or HTTP/2 500 (if app not fully configured yet - OK for now)
```

---

## Step 5: Setup .env.staging (10 min)

### 5.1 Copy File
```bash
cd /var/www/api-gateway

# The file has already been created during the analysis phase
# Verify it exists
ls -la .env.staging

# If missing, copy from template
# cp .env.staging.backup .env.staging
```

### 5.2 Verify Configuration
```bash
# Check database configuration
grep "DB_DATABASE" .env.staging
# Should show: DB_DATABASE=askproai_staging

# Check feature flags
grep "FEATURE_CUSTOMER_PORTAL" .env.staging
# Should show: FEATURE_CUSTOMER_PORTAL=true

# Check cache prefix
grep "CACHE_PREFIX" .env.staging
# Should show: CACHE_PREFIX=askpro_staging_
```

---

## Step 6: Initialize Laravel Application (20 min)

### 6.1 Install Dependencies
```bash
cd /var/www/api-gateway

# Install Composer dependencies (if not already done)
composer install --no-interaction --optimize-autoloader

echo "✅ Dependencies installed"
```

### 6.2 Setup Environment
```bash
# Copy staging env to active .env for artisan commands
cp .env.staging .env

# Generate application key (if needed)
php artisan key:generate --force

# Cache configuration
php artisan config:cache

echo "✅ Laravel configured for staging"
```

### 6.3 Run Migrations
```bash
# Run all pending migrations on staging database
php artisan migrate --force --env=staging

# Verify tables
mysql -u askproai_user -p askproai_staging -e "SHOW TABLES;" | head -20

echo "✅ Database migrations complete"
```

### 6.4 Clear All Caches
```bash
# Clear application caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verify Redis connection
redis-cli -h 127.0.0.1 PING
# Should respond: PONG

echo "✅ Caches cleared"
```

---

## Step 7: Verify Staging Deployment (15 min)

### 7.1 Health Check
```bash
# Test health endpoint
curl -I https://staging.askproai.de/health

# Expected: HTTP/2 200 OK
# Body should show: {"status":"ok", ...}
```

### 7.2 Test Portal Access (Feature Disabled)
```bash
# Switch back to production .env (feature disabled)
cp .env .env.production-backup
cp .env.example .env

# OR manually set FEATURE_CUSTOMER_PORTAL=false in .env

php artisan config:cache

# Portal should return 404
curl -I https://staging.askproai.de/portal

# Expected: HTTP/2 404 Not Found

echo "✅ Portal correctly returns 404 when feature disabled"
```

### 7.3 Test Portal Access (Feature Enabled)
```bash
# Use .env.staging (feature enabled)
cp .env.staging .env

php artisan config:cache

# Portal should load (with login required)
curl -I https://staging.askproai.de/portal

# Expected: HTTP/2 302 (redirect to login)
# or HTTP/2 200 (if already logged in)

echo "✅ Portal accessible when feature enabled"
```

### 7.4 Test Admin Panel
```bash
# Admin panel should work regardless of feature flag
curl -I https://staging.askproai.de/admin

# Expected: HTTP/2 302 (redirect to login)
# or HTTP/2 200 (if already logged in)

echo "✅ Admin panel accessible"
```

---

## Step 8: Database Sync Script (Optional but Recommended)

### 8.1 Create Automated Sync Script
```bash
cat > /var/www/api-gateway/scripts/sync-staging-database.sh << 'SCRIPT'
#!/bin/bash
set -euo pipefail

echo "Syncing staging database from production..."

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

# 1. Backup staging (safety first)
echo "💾 Backing up existing staging database..."
mysqldump -u askproai_user -p askproai_staging > \
  /var/www/api-gateway/storage/backups/staging-backup-$(date +%Y%m%d-%H%M%S).sql
echo "✅ Staging backup created"

# 2. Dump production
echo "📥 Exporting production database..."
mysqldump -u askproai_user -p askproai_db \
  --ignore-table=askproai_db.retell_call_events \
  --ignore-table=askproai_db.retell_transcripts \
  > /tmp/prod_dump.sql
echo "✅ Production exported"

# 3. Restore to staging
echo "📤 Importing to staging database..."
mysql -u askproai_user -p askproai_staging < /tmp/prod_dump.sql
echo "✅ Staging database updated"

# 4. Sanitize
echo "🔒 Sanitizing sensitive data..."
mysql -u askproai_user -p askproai_staging << EOF
UPDATE users
SET password = '\$2y\$12\$E8rnvTiP2VZ5dKIUgaF5OuYhSV4S6WqHqkK2w8K8E8K8E8K8E8K8E'
WHERE 1=1;
EOF
echo "✅ Staging database sanitized"

echo ""
echo -e "${GREEN}✅ Staging database sync complete!${NC}"
echo "Login: Use any email with password 'test123'"

# Cleanup
rm /tmp/prod_dump.sql

SCRIPT

chmod +x /var/www/api-gateway/scripts/sync-staging-database.sh

echo "✅ Sync script created"
```

### 8.2 Run the Sync Script
```bash
bash /var/www/api-gateway/scripts/sync-staging-database.sh

# This will prompt for MySQL password twice (dump and restore)
```

---

## Step 9: Test Login (10 min)

### 9.1 Get Test User Email
```bash
mysql -u askproai_user -p askproai_staging << 'EOF'
SELECT id, name, email FROM users LIMIT 3;
EOF

# Note down an email address from the list
```

### 9.2 Login to Staging Portal
```
1. Open browser: https://staging.askproai.de/admin
2. Enter email from step 9.1
3. Enter password: test123
4. You should be logged in
```

### 9.3 Test Portal Page
```
1. Open: https://staging.askproai.de/portal
2. Should see customer portal dashboard
3. Check "Calls" section - should show calls from database
4. Check "Appointments" section - should show appointments
```

---

## Step 10: Monitoring & Logs (5 min)

### 10.1 Watch Logs
```bash
# Real-time log monitoring
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Filter for errors only
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i error

# Filter for portal-related messages
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i portal
```

### 10.2 Check Redis
```bash
# Monitor Redis cache
redis-cli monitor

# Check staging cache keys
redis-cli KEYS "askpro_staging_*" | head -20
```

---

## Troubleshooting

### Issue: "Connection refused" to MySQL
```bash
# Check MySQL is running
sudo systemctl status mysql

# Check database exists
mysql -u askproai_user -p -e "SHOW DATABASES LIKE 'askproai%';"
```

### Issue: "Permission denied" in nginx
```bash
# Check nginx error log
sudo tail -f /var/log/nginx/error.log

# Fix file permissions
sudo chown -R www-data:www-data /var/www/api-gateway/storage
sudo chmod -R 755 /var/www/api-gateway/storage
```

### Issue: "PHP-FPM connection refused"
```bash
# Check PHP-FPM is running
sudo systemctl status php8.2-fpm

# Check socket exists
ls -la /run/php/php8.2-fpm.sock
```

### Issue: "SSL certificate not found"
```bash
# Verify certificate exists
ls -la /etc/letsencrypt/live/staging.askproai.de/

# If missing, create self-signed (temporary)
sudo openssl req -x509 -nodes -days 365 \
  -newkey rsa:2048 \
  -keyout /etc/letsencrypt/live/staging.askproai.de/privkey.pem \
  -out /etc/letsencrypt/live/staging.askproai.de/fullchain.pem \
  -subj "/CN=staging.askproai.de"

sudo systemctl reload nginx
```

---

## Quick Verification Checklist

- [ ] Staging database exists: `mysql -e "SHOW DATABASES LIKE 'askproai_staging';"`
- [ ] Database has tables: `mysql -e "USE askproai_staging; SHOW TABLES;" | wc -l`
- [ ] Nginx vhost enabled: `sudo nginx -t` returns ok
- [ ] SSL certificate valid: `curl -I https://staging.askproai.de`
- [ ] Laravel migrations ran: `mysql -e "USE askproai_staging; SELECT COUNT(*) FROM migrations;"`
- [ ] Health endpoint works: `curl https://staging.askproai.de/health`
- [ ] Portal feature disabled shows 404: `curl -I https://staging.askproai.de/portal` (returns 404)
- [ ] Portal feature enabled works: Update .env.staging, check portal loads
- [ ] Can login with test credentials: test123 password for any user
- [ ] Logs accessible: `tail -f /var/www/api-gateway/storage/logs/laravel.log`

---

## Next Steps

1. **Deploy feature/customer-portal branch to staging**
   ```bash
   cd /var/www/api-gateway
   git fetch origin feature/customer-portal
   git checkout feature/customer-portal
   composer install
   php artisan config:cache
   ```

2. **Run testing checklist from main strategy document**
   - Test all phases (1-7)
   - Document any issues
   - Fix on feature branch

3. **Setup GitHub Actions for automated deployment**
   - Create `.github/workflows/staging-deployment.yml`
   - Test automatic deployment on push to feature/*

4. **After testing complete: Merge to main for production deployment**

---

**Estimated Time**: 2-3 hours total
**Difficulty**: Medium
**Support**: Check STAGING_DEPLOYMENT_STRATEGY_2025-10-26.md for detailed info

✅ **Setup Complete!** Staging environment ready for customer portal testing.
