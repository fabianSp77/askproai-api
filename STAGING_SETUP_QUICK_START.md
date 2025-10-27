# 🚀 STAGING SETUP - QUICK START GUIDE

**Duration**: 2-3 hours
**Difficulty**: Intermediate
**Purpose**: Set up staging environment for Customer Portal testing

---

## 📋 PREREQUISITES

- [x] Root or sudo access on server
- [x] MySQL/MariaDB installed
- [x] Nginx installed
- [x] PHP 8.3 + FPM installed
- [x] Git access to repository

---

## ⚡ QUICK SETUP (10 STEPS)

### Step 1: Create Staging Database (5 min)

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database
CREATE DATABASE askproai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user
CREATE USER 'askproai_staging_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';

# Grant permissions
GRANT ALL PRIVILEGES ON askproai_staging.* TO 'askproai_staging_user'@'localhost';
FLUSH PRIVILEGES;

# Verify
SHOW DATABASES LIKE 'askproai_staging';
exit;
```

✅ **Checkpoint**: Database `askproai_staging` exists

---

### Step 2: Configure `.env.staging` (10 min)

```bash
cd /var/www/api-gateway

# Edit .env.staging (already created)
nano .env.staging
```

**Replace these placeholders**:
- `YOUR_STAGING_KEY_HERE` → Generate: `php artisan key:generate --show`
- `YOUR_STAGING_DB_PASSWORD_HERE` → Password from Step 1
- `YOUR_STAGING_CALCOM_API_KEY` → Cal.com test API key
- `YOUR_STAGING_RETELL_API_KEY` → Retell test API key

**Save and exit** (Ctrl+X, Y, Enter)

✅ **Checkpoint**: All placeholders replaced

---

### Step 3: Setup Nginx vHost (10 min)

```bash
# Symlink config to sites-enabled
sudo ln -s /var/www/api-gateway/config/nginx/staging.askproai.de.conf \
            /etc/nginx/sites-enabled/staging.askproai.de

# Test nginx config
sudo nginx -t

# Should output: "syntax is ok" and "test is successful"
```

✅ **Checkpoint**: Nginx config valid

---

### Step 4: Get SSL Certificate (15 min)

```bash
# Install certbot if not installed
sudo apt install certbot python3-certbot-nginx -y

# Get certificate for staging domain
sudo certbot --nginx -d staging.askproai.de

# Follow prompts:
# - Enter email address
# - Agree to Terms of Service
# - Choose: Redirect HTTP to HTTPS (option 2)
```

✅ **Checkpoint**: SSL certificate installed

---

### Step 5: Reload Nginx (1 min)

```bash
# Reload Nginx to apply changes
sudo systemctl reload nginx

# Check status
sudo systemctl status nginx

# Should show: "active (running)"
```

✅ **Checkpoint**: Nginx reloaded successfully

---

### Step 6: Sync Production Database (20 min)

⚠️ **WARNING**: This will copy production data (sanitized) to staging

```bash
cd /var/www/api-gateway

# Run sync script
./scripts/sync-staging-database.sh

# Enter MySQL passwords when prompted
# Confirm with 'yes' when asked

# Wait for completion (~10-15 minutes for large DB)
```

**What this does**:
- ✅ Backs up current staging database
- ✅ Dumps production database
- ✅ Sanitizes emails (→ `test_*@staging.local`)
- ✅ Sanitizes phone numbers (→ `+49123456789`)
- ✅ Sanitizes API keys (→ `key_STAGING_SANITIZED`)
- ✅ Hashes passwords (reset required)
- ✅ Imports to staging

✅ **Checkpoint**: Database synced and sanitized

---

### Step 7: Reset Test User Passwords (5 min)

```bash
cd /var/www/api-gateway

php artisan tinker --env=staging
```

```php
// Reset ALL staging user passwords to: TestPass123!
use App\Models\User;

User::where('email', 'LIKE', '%@staging.local')
    ->update(['password' => bcrypt('TestPass123!')]);

// Create admin test user
$admin = User::create([
    'name' => 'Staging Admin',
    'email' => 'admin@staging.local',
    'password' => bcrypt('AdminPass123!'),
    'company_id' => null
]);
$admin->assignRole('super_admin');

// Create customer test user
$company = \App\Models\Company::first();
$customer = User::create([
    'name' => 'Test Customer',
    'email' => 'customer@staging.local',
    'password' => bcrypt('TestPass123!'),
    'company_id' => $company->id
]);
$customer->assignRole('company_owner');

exit
```

✅ **Checkpoint**: Test users created

---

### Step 8: Deploy Customer Portal Branch (10 min)

```bash
cd /var/www/api-gateway

# Deploy feature/customer-portal to staging
./scripts/deploy-staging.sh feature/customer-portal

# Wait for completion (~5 minutes)
```

**What this does**:
- ✅ Checks out `feature/customer-portal` branch
- ✅ Pulls latest changes
- ✅ Runs `composer install`
- ✅ Runs database migrations (adds performance indexes)
- ✅ Clears all caches
- ✅ Restarts PHP-FPM

✅ **Checkpoint**: Branch deployed to staging

---

### Step 9: Verify Setup (10 min)

**Test Admin Panel**:
```bash
# Open in browser
https://staging.askproai.de/admin

# Login credentials:
# Email: admin@staging.local
# Password: AdminPass123!
```
Expected: Admin dashboard loads ✅

**Test Customer Portal** (Feature Flag ON):
```bash
# Open in browser
https://staging.askproai.de/portal

# Login credentials:
# Email: customer@staging.local
# Password: TestPass123!
```
Expected: Customer dashboard loads ✅

**Test Feature Flag OFF**:
```bash
# Disable feature flag
cd /var/www/api-gateway
echo "FEATURE_CUSTOMER_PORTAL=false" >> .env.staging
php artisan config:clear --env=staging

# Try to access portal
https://staging.askproai.de/portal
```
Expected: 404 Not Found ✅

**Re-enable for testing**:
```bash
# Enable again
sed -i 's/FEATURE_CUSTOMER_PORTAL=false/FEATURE_CUSTOMER_PORTAL=true/' .env.staging
php artisan config:clear --env=staging
```

✅ **Checkpoint**: All tests pass

---

### Step 10: Monitor Logs (Optional)

```bash
# Watch Laravel logs
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Watch Nginx access logs
sudo tail -f /var/log/nginx/staging.askproai.de-access.log

# Watch Nginx error logs
sudo tail -f /var/log/nginx/staging.askproai.de-error.log
```

✅ **Checkpoint**: Logs monitoring active

---

## 🎉 SETUP COMPLETE!

Your staging environment is now ready!

**Access URLs**:
- Admin Panel: https://staging.askproai.de/admin
- Customer Portal: https://staging.askproai.de/portal

**Test Credentials**:
```
Admin:
  Email: admin@staging.local
  Password: AdminPass123!

Customer:
  Email: customer@staging.local
  Password: TestPass123!
```

---

## 🔄 DAILY OPERATIONS

### Deploy New Branch to Staging
```bash
./scripts/deploy-staging.sh feature/my-new-feature
```

### Sync Database from Production (Weekly)
```bash
./scripts/sync-staging-database.sh
```

### Check Deployment Status
```bash
cd /var/www/api-gateway
git status
git log --oneline -5
```

---

## 🐛 TROUBLESHOOTING

### Issue: 502 Bad Gateway

**Cause**: PHP-FPM not running

**Fix**:
```bash
sudo systemctl status php8.3-fpm
sudo systemctl start php8.3-fpm
```

---

### Issue: Database Connection Failed

**Cause**: Wrong credentials in `.env.staging`

**Fix**:
```bash
# Test connection
mysql -u askproai_staging_user -p askproai_staging

# If fails, check .env.staging:
nano /var/www/api-gateway/.env.staging
# Verify DB_USERNAME, DB_PASSWORD, DB_DATABASE
```

---

### Issue: 404 on Portal

**Cause**: Feature flag disabled

**Fix**:
```bash
cd /var/www/api-gateway
grep FEATURE_CUSTOMER_PORTAL .env.staging
# Should be: FEATURE_CUSTOMER_PORTAL=true

# If false, enable:
sed -i 's/FEATURE_CUSTOMER_PORTAL=false/FEATURE_CUSTOMER_PORTAL=true/' .env.staging
php artisan config:clear --env=staging
```

---

### Issue: SSL Certificate Error

**Cause**: Certificate not installed or expired

**Fix**:
```bash
# Renew certificate
sudo certbot renew

# Check expiry
sudo certbot certificates
```

---

### Issue: Migration Failed

**Cause**: Database schema mismatch

**Fix**:
```bash
cd /var/www/api-gateway

# Check migration status
php artisan migrate:status --env=staging

# Rollback and re-run
php artisan migrate:rollback --env=staging
php artisan migrate --env=staging --force
```

---

## 📚 NEXT STEPS

Now that staging is set up, proceed to:

1. **Security Testing**: `STAGING_TEST_CHECKLIST.md` → Security section
2. **Performance Testing**: Run `./scripts/performance_test_indexes.php`
3. **Load Testing**: Install k6 and run load tests
4. **Manual QA**: Test all 40+ checklist items

---

## 🆘 NEED HELP?

**Logs to check**:
- Laravel: `/var/www/api-gateway/storage/logs/laravel.log`
- Nginx Access: `/var/log/nginx/staging.askproai.de-access.log`
- Nginx Error: `/var/log/nginx/staging.askproai.de-error.log`
- PHP-FPM: `/var/log/php8.3-fpm.log`

**Useful commands**:
```bash
# Check services
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm

# Check disk space
df -h

# Check memory
free -h
```

---

**Setup Date**: 2025-10-26
**Last Updated**: 2025-10-26
**Version**: 1.0
