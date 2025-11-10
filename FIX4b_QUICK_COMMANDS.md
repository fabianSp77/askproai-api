# DocsAuthController Fix - Quick Command Reference

**Issue**: Controller class not found (500 error)
**Root Cause**: Bootstrap cache permissions + stale OPcache

---

## Immediate Fix Commands (Staging)

```bash
# SSH to staging
ssh deploy@152.53.116.127

# Navigate to current release
cd /var/www/api-gateway-staging/current

# Fix bootstrap cache permissions
chmod -R 775 bootstrap/cache
chgrp -R www-data bootstrap/cache

# Clear all caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear

# Rebuild optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Exit SSH
exit
```

---

## Verification Commands

```bash
# SSH to staging
ssh deploy@152.53.116.127

cd /var/www/api-gateway-staging/current

# 1. Check class is loadable
php artisan tinker --execute="echo class_exists('App\\Http\\Controllers\\DocsAuthController') ? 'YES' : 'NO';"

# 2. Check routes registered
php artisan route:list | grep docs/backup-system

# 3. Test GET endpoint
curl -s https://staging.askproai.de/docs/backup-system/login -w "\nHTTP %{http_code}\n" | tail -1

# 4. Test POST endpoint (CSRF will fail but route should exist)
curl -s -X POST https://staging.askproai.de/docs/backup-system/login \
  -d "username=test&password=test" \
  -w "\nHTTP %{http_code}\n" | tail -1

# Expected: 419 (CSRF token missing) or 302 (redirect)
# NOT: 500 (controller not found)

exit
```

---

## Production Deployment Commands

**DO NOT run until verified on staging!**

```bash
# SSH to production
ssh deploy@PRODUCTION_IP

cd /var/www/api-gateway-production/current

# Fix permissions
chmod -R 775 bootstrap/cache
chgrp -R www-data bootstrap/cache

# Clear caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reload PHP-FPM (requires sudo)
# sudo systemctl reload php8.3-fpm

exit
```

---

## Add to Deployment Workflow

**File**: `.github/workflows/deploy-staging.yml`

**Add after symlink switch, before health check**:

```yaml
- name: Fix Bootstrap Cache Permissions
  run: |
    ssh deploy@152.53.116.127 << 'ENDSSH'
      cd /var/www/api-gateway-staging/current
      chmod -R 775 bootstrap/cache
      chgrp -R www-data bootstrap/cache
      chmod -R 775 storage
      chgrp -R www-data storage
    ENDSSH

- name: Clear and Rebuild Laravel Caches
  run: |
    ssh deploy@152.53.116.127 << 'ENDSSH'
      cd /var/www/api-gateway-staging/current
      php artisan route:clear
      php artisan config:clear
      php artisan cache:clear
      php artisan config:cache
      php artisan route:cache
      php artisan view:cache
    ENDSSH
```

---

## Expected Results

### ✅ Success Indicators

```bash
# Class loading
$ php artisan tinker --execute="class_exists('App\\Http\\Controllers\\DocsAuthController')"
Controller exists: YES

# Routes
$ php artisan route:list | grep docs/backup-system
GET|HEAD  docs/backup-system/login
POST      docs/backup-system/login
POST      docs/backup-system/logout

# HTTP
$ curl https://staging.askproai.de/docs/backup-system/login -w "%{http_code}"
200

# Bootstrap cache
$ ls -ld bootstrap/cache
drwxrwxr-x 2 deploy www-data 4096 bootstrap/cache
```

### ❌ Failure Indicators

```bash
# Wrong permissions
$ ls -ld bootstrap/cache
drwxrwxr-x 2 deploy deploy 4096 bootstrap/cache  # ❌ should be www-data

# Class not found
$ php artisan tinker --execute="class_exists(...)"
Controller exists: NO  # ❌ should be YES

# HTTP 500
$ curl https://staging.askproai.de/docs/backup-system/login -w "%{http_code}"
500  # ❌ should be 200
```

---

## Emergency Rollback

If issue persists:

```bash
# SSH to staging
ssh deploy@152.53.116.127

# Check current release
ls -la /var/www/api-gateway-staging/current

# List available releases
ls -lt /var/www/api-gateway-staging/releases/

# Rollback to previous release
cd /var/www/api-gateway-staging
ln -sfn releases/PREVIOUS_RELEASE_DIR current

# Clear caches on previous release
cd current
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Verify
curl -s https://staging.askproai.de/health -w "\nHTTP %{http_code}\n"
```

---

## Status Check Script

Save as `check_docsauth.sh`:

```bash
#!/bin/bash
set -e

SERVER="${1:-152.53.116.127}"
ENV="${2:-staging}"

echo "🔍 Checking DocsAuthController on $ENV ($SERVER)"

ssh deploy@$SERVER << 'ENDSSH'
  cd /var/www/api-gateway-staging/current

  echo ""
  echo "1️⃣ Class Loading:"
  php artisan tinker --execute="echo class_exists('App\\Http\\Controllers\\DocsAuthController') ? '✅ YES' : '❌ NO';" 2>&1 | tail -1

  echo ""
  echo "2️⃣ Routes Registered:"
  php artisan route:list 2>&1 | grep -E 'docs/backup-system/(login|logout)' | wc -l | xargs -I {} echo "{} routes (expected: 3)"

  echo ""
  echo "3️⃣ Bootstrap Cache Permissions:"
  ls -ld bootstrap/cache | awk '{print $1, $3":"$4}'

  echo ""
  echo "4️⃣ HTTP Endpoint:"
  curl -s https://staging.askproai.de/docs/backup-system/login -w "\nHTTP %{http_code}\n" -o /dev/null | tail -1
ENDSSH

echo ""
echo "✅ Check complete"
```

Usage:
```bash
chmod +x check_docsauth.sh
./check_docsauth.sh 152.53.116.127 staging
```

---

**Quick Reference Card** - Keep this handy for future deploys!
