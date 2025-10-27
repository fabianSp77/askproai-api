# Staging Deployment Strategy for Customer Portal Feature
**Status**: Analysis Complete | Ready for Implementation
**Date**: 2025-10-26
**Feature Branch**: `feature/customer-portal`
**Target**: Safe staging rollout before production deployment

---

## Executive Summary

The AskPro AI Gateway currently operates without a formal staging environment, deploying directly to production. This strategy establishes a comprehensive staging infrastructure for the customer portal feature with:

- **Infrastructure**: Separate staging subdomain (staging.askproai.de) with isolated database
- **Feature Flag System**: Already implemented (config/features.php) for safe rollout
- **CI/CD Pipeline**: Enhanced GitHub Actions workflow for staging validation
- **Database Strategy**: Sanitized production data copy + automated migrations
- **Testing**: Comprehensive multi-stage validation before production
- **Rollback**: Automated recovery capabilities for failed deployments

---

## Part 1: Current Deployment Analysis

### 1.1 Current Setup Overview

**Production Environment:**
- **Domain**: api.askproai.de
- **Server**: Single server (nginx + PHP-FPM + MySQL + Redis)
- **Laravel Version**: 11
- **Database**: MySQL (askproai_db)
- **Cache**: Redis (askpro_cache_ prefix)
- **Session Storage**: File-based with encrypted cookies

**Current Deployment Method:**
- Manual deployment via deployment scripts in `/scripts/deployment/`
- Production deployment script: `scripts/deploy-production.sh`
- Git branch strategy: Direct to main (no staging)
- Feature flags: Implemented but only for phonetic matching
- No automated staging environment

**Key Infrastructure Details:**
```
nginx configuration: /etc/nginx/sites-available/*
PHP-FPM: listening on unix socket
MySQL: Port 3306, database askproai_db
Redis: Port 6379, no password (internal only)
Let's Encrypt SSL: api.askproai.de
```

### 1.2 Existing CI/CD Pipeline

**GitHub Actions Workflow**: `.github/workflows/test-automation.yml`

Stages (runs on all push/PR to main/develop):
1. **Unit Tests** (PHPUnit with coverage)
   - MySQL 8.0 test database
   - Redis 7 test cache
   - Coverage reports to Codecov

2. **RCA Prevention Tests** (prevents known bugs)
   - Covers duplicate booking, race conditions, type mismatches

3. **Integration Tests** (Feature test suite)
   - Database interactions
   - API endpoints

4. **Performance Tests** (K6 load testing)
   - Booking flow baseline
   - Target: < 45 seconds

5. **E2E Tests** (Playwright)
   - Browser automation on Chromium
   - UI flow validation

6. **Security Tests** (PHPStan + composer audit)
   - Static analysis level 8
   - Dependency vulnerability scanning

**Status**: ✅ Robust test suite exists, ready to extend for staging

### 1.3 Feature Flag System

**Configuration File**: `/var/www/api-gateway/config/features.php`

Existing feature flags:
- `phonetic_matching_enabled` - Phone authentication enhancement
- `skip_alternatives_for_voice` - Retell AI optimization
- `customer_portal` - **NEW** (target for this feature)
- `customer_portal_calls` - Phase 1 (call history)
- `customer_portal_appointments` - Phase 1 (appointments)
- `customer_portal_crm` - Phase 2 (future)
- `customer_portal_services` - Phase 2 (future)
- `customer_portal_staff` - Phase 2 (future)
- `customer_portal_analytics` - Phase 3 (future)

**Middleware Protection**: `app/Http/Middleware/CheckFeatureFlag.php`
- Returns 404 when disabled (prevents enumeration)
- Safe for production (defaults to false)
- Route protection: `Route::middleware('feature:customer_portal')->group(...)`

**Current Default**: All customer_portal flags = false (safe default)

### 1.4 Environment Configuration

**Environment Files**:
```
.env                    → Production (MySQL, Redis, API keys)
.env.example            → Template
.env.testing            → Testing (separate askproai_testing database)
.env.staging (NEEDED)   → New staging environment
```

**Key Environment Variables Needed for Staging**:
```
APP_ENV=staging
APP_DEBUG=true (for debugging)
DB_HOST=127.0.0.1
DB_DATABASE=askproai_staging
DB_USERNAME=askproai_user
DB_PASSWORD=<same or different>

CACHE_STORE=redis (same as production)
QUEUE_CONNECTION=sync or database

Feature Flags:
FEATURE_CUSTOMER_PORTAL=false (initially)
```

### 1.5 Database Strategy

**Current Production Database**: askproai_db (MySQL 8.0)
- ~20+ tables (appointments, staff, services, etc.)
- Row-level security via companyscope
- Multi-tenant isolation

**Migrations**:
- Latest: `2025_10_26_115644_add_customer_portal_performance_indexes.php`
- Total: 40+ migrations in `database/migrations/`

**Key Tables for Customer Portal**:
- `retell_call_sessions` - Call history (read-only in phase 1)
- `appointments` - Appointments (read-only in phase 1)
- `companies` - Tenant isolation
- `users` - Customer portal users
- `retell_call_events` - Call transcript data

---

## Part 2: Staging Environment Setup Strategy

### 2.1 Recommended Architecture

```
PRODUCTION (current)          STAGING (new)
───────────────────          ─────────────
api.askproai.de              staging.askproai.de
Same server                  Same server (separate vhost)
Port 443 (SSL)               Port 443 (SSL)
askproai_db                  askproai_staging
Redis: prefix=askpro_cache_  Redis: prefix=askpro_staging_
```

**Rationale for Same Server**:
✅ Cost-effective (no separate infrastructure)
✅ Identical to production environment
✅ Easy data sync for testing
⚠️ Requires careful vhost isolation
⚠️ Requires separate database with identical schema

### 2.2 Subdomain Strategy: staging.askproai.de

**Option 1: Separate Vhost (RECOMMENDED)**
```
nginx config: /etc/nginx/sites-available/staging.askproai.de
Root: /var/www/api-gateway-staging (symlink or separate clone)
OR: /var/www/api-gateway with APP_NAME override
```

**Option 2: Path-based Routing (Simpler)**
```
nginx: Proxy /staging/* → different Laravel instance
More complex routing, less recommended
```

**Decision**: Use **Option 1** (separate vhost) - cleaner isolation

### 2.3 Database Setup

#### Step 1: Create Staging Database
```sql
-- Create database
CREATE DATABASE askproai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (or reuse with different database)
CREATE USER 'askproai_staging'@'localhost' IDENTIFIED BY '<secure_password>';
GRANT ALL PRIVILEGES ON askproai_staging.* TO 'askproai_staging'@'localhost';

-- Or simpler: same user, different database
GRANT ALL PRIVILEGES ON askproai_staging.* TO 'askproai_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Step 2: Database Population Strategy

**Option A: Full Production Data Copy (Recommended for First Sync)**
```bash
# Backup production
mysqldump -u askproai_user -p askproai_db > /tmp/prod_backup.sql

# Restore to staging
mysql -u askproai_user -p askproai_staging < /tmp/prod_backup.sql

# Sanitize sensitive data (optional but recommended)
mysql -u askproai_user -p askproai_staging << EOF
  UPDATE users SET email = CONCAT(id, '+test@staging.askproai.de');
  UPDATE users SET password = bcrypt('test123');
  -- Keep companies, services, appointments, staff intact
EOF
```

**Option B: Seeded Test Data (For Quick Iteration)**
```bash
# Fresh migration
php artisan migrate --env=staging

# Seed test data
php artisan db:seed --env=staging --class=StagingDataSeeder
```

**Decision**: Use **Option A first** (prod data copy) for realistic testing, then **Option B** for quick reset cycles.

### 2.4 Git Workflow Strategy

#### Current Problem
```
feature/customer-portal → main (production)
                           ✗ No staging validation
                           ✗ Direct to production risk
```

#### Recommended Workflow
```
1. Development
   feature/customer-portal
   └─ Local testing + unit tests

2. Staging Validation
   feature/customer-portal → staging (via CI/CD)
   └─ Deploy to staging.askproai.de
   └─ Full integration testing
   └─ Feature flag OFF by default
   └─ Stakeholder approval

3. Production Merge
   feature/customer-portal → main (via PR review)
   └─ Merge triggers production deployment
   └─ Feature flag OFF by default
   └─ Gradual rollout via flag

Hotfix Flow:
   fix/hotfix-issue → main (urgent fixes)
   └─ Can also be deployed to staging first
   └─ Fast-track if critical
```

#### Implementation

**New GitHub Actions Workflow: staging-deployment.yml**
```yaml
name: Deploy to Staging
on:
  push:
    branches: [feature/*, chore/*, fix/*]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    # ... run all tests ...

  deploy-staging:
    needs: test
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && startsWith(github.ref, 'refs/heads/feature/')
    steps:
      - uses: actions/checkout@v3
      - name: Deploy to staging.askproai.de
        run: |
          # SSH to server
          # Deploy to staging root
          # Run migrations
          # Cache clear
          # Health check
```

**Main Deployment Workflow: production-deployment.yml**
```yaml
name: Deploy to Production
on:
  push:
    branches: [main]

jobs:
  test:
    # ... run all tests ...

  deploy-production:
    needs: test
    runs-on: ubuntu-latest
    environment: production  # Manual approval required
    steps:
      - uses: actions/checkout@v3
      - name: Deploy to production
        # ... production deployment steps ...
```

### 2.5 Environment Configuration Files

#### .env.staging (NEW)
```php
APP_NAME="AskPro AI Gateway (Staging)"
APP_ENV=staging
APP_DEBUG=true
APP_TIMEZONE=Europe/Berlin
APP_URL=https://staging.askproai.de
APP_LOCALE=de
APP_FALLBACK_LOCALE=de

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_staging
DB_USERNAME=askproai_user
DB_PASSWORD=askproai_secure_pass_2024

CACHE_STORE=redis
CACHE_PREFIX=askpro_staging_
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Feature Flags - STAGING TESTING
FEATURE_CUSTOMER_PORTAL=true          # ENABLED for testing
FEATURE_CUSTOMER_PORTAL_CALLS=true
FEATURE_CUSTOMER_PORTAL_APPOINTMENTS=true
FEATURE_CUSTOMER_PORTAL_CRM=false     # Phase 2
FEATURE_CUSTOMER_PORTAL_SERVICES=false
FEATURE_CUSTOMER_PORTAL_STAFF=false

# All other flags same as production
FEATURE_PHONETIC_MATCHING_ENABLED=false
```

#### .env (PRODUCTION - unchanged)
```php
FEATURE_CUSTOMER_PORTAL=false  # Disabled for safe rollout
FEATURE_CUSTOMER_PORTAL_CALLS=true
FEATURE_CUSTOMER_PORTAL_APPOINTMENTS=true
```

---

## Part 3: Staging Infrastructure Setup

### 3.1 Nginx Vhost Configuration

**File**: `/etc/nginx/sites-available/staging.askproai.de`

```nginx
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

    # SSL Certificate (same as production or separate)
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
    add_header X-Staging-Environment "true" always;  # Identify staging

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

    # PHP handling (same as production)
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param APP_ENV staging;  # Override for staging
    }

    # Static files (cache)
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1h;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to hidden files
    location ~ /\.ht {
        deny all;
    }
}
```

**Enable the vhost**:
```bash
sudo ln -s /etc/nginx/sites-available/staging.askproai.de \
           /etc/nginx/sites-enabled/staging.askproai.de
sudo nginx -t
sudo systemctl reload nginx
```

### 3.2 SSL Certificate Setup

**Option 1: Self-Signed (Dev/Testing)**
```bash
sudo mkdir -p /etc/letsencrypt/live/staging.askproai.de
sudo openssl req -x509 -nodes -days 365 \
  -newkey rsa:2048 \
  -keyout /etc/letsencrypt/live/staging.askproai.de/privkey.pem \
  -out /etc/letsencrypt/live/staging.askproai.de/fullchain.pem \
  -subj "/CN=staging.askproai.de"
```

**Option 2: Let's Encrypt (Recommended)**
```bash
sudo apt-get install certbot python3-certbot-nginx
sudo certbot certonly --nginx -d staging.askproai.de
```

### 3.3 PHP-FPM Configuration

Create separate PHP-FPM pool for staging (optional but cleaner):

**File**: `/etc/php/8.2/fpm/pool.d/staging.conf`
```ini
[staging]
user = www-data
group = www-data
listen = /run/php/php8.2-fpm-staging.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35

# Logging
slowlog = /var/log/php-fpm-staging.log
request_slowlog_timeout = 10s
```

Or use shared pool (simpler): Update nginx config to use standard socket.

### 3.4 Redis Namespace Isolation

Redis already supports key prefixes (CACHE_PREFIX):

**Production**: `askpro_cache_`
**Staging**: `askpro_staging_`

This ensures cache isolation without separate Redis instances.

---

## Part 4: Testing & Validation Strategy

### 4.1 Staging Validation Checklist

#### Phase 1: Infrastructure Validation (Day 1)
- [ ] Staging domain resolves (staging.askproai.de)
- [ ] SSL certificate valid
- [ ] nginx vhost loads
- [ ] PHP-FPM processes requests
- [ ] Database connection successful
- [ ] Redis cache working
- [ ] Baseline health check: `curl https://staging.askproai.de/health`

#### Phase 2: Database Validation (Day 1)
- [ ] askproai_staging database created
- [ ] Migrations run successfully
- [ ] Tables created: users, companies, appointments, etc.
- [ ] Row-level security (companyscope) enforced
- [ ] Test data loaded (or production data synced)
- [ ] Multi-tenant isolation verified

#### Phase 3: Application Startup (Day 1)
- [ ] Laravel boot check: `php artisan config:cache`
- [ ] Routes registered: `php artisan route:list | grep portal`
- [ ] Filament panel accessible: `/admin`
- [ ] Customer portal feature flag disabled: `/portal` → 404
- [ ] Enable feature flag in .env.staging
- [ ] Customer portal accessible: `/portal`

#### Phase 4: Customer Portal Features (Days 2-3)
- [ ] **Call History Page**
  - [ ] Loads call list with pagination
  - [ ] Displays transcripts
  - [ ] Search/filter working
  - [ ] Multi-tenant isolation (users see only own calls)

- [ ] **Appointments Page**
  - [ ] Shows upcoming appointments
  - [ ] Calendar view renders
  - [ ] List view displays
  - [ ] Filters by date/status
  - [ ] Multi-tenant isolation verified

- [ ] **Dashboard**
  - [ ] Stats load correctly
  - [ ] Charts render (if any)
  - [ ] Recent activity shows

#### Phase 5: Security & Isolation (Day 3)
- [ ] User cannot access other tenant's data
- [ ] SQL injection prevention verified
- [ ] CSRF protection enabled
- [ ] XSS protection headers present
- [ ] Authentication required for all /portal/* routes
- [ ] Feature flag properly blocks access when disabled

#### Phase 6: Performance Baseline (Day 3)
- [ ] Homepage load time < 2s
- [ ] Admin dashboard load < 3s
- [ ] Portal dashboard load < 3s
- [ ] API endpoints respond < 500ms
- [ ] No N+1 query issues
- [ ] Cache hit ratio > 60%

#### Phase 7: Database Safety (Day 4)
- [ ] Backup/restore procedure tested
- [ ] Migration rollback tested
- [ ] Data sanitization process documented
- [ ] No production data exposed in logs

### 4.2 Automated Staging Deployment Test

**GitHub Actions: staging-validation.yml**

```yaml
name: Staging Deployment Validation

on:
  push:
    branches: [feature/*]
  workflow_dispatch:  # Manual trigger

env:
  STAGING_HOST: staging.askproai.de
  STAGING_USER: deploy
  STAGING_KEY: ${{ secrets.STAGING_DEPLOY_KEY }}

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Deploy to staging
        run: |
          mkdir -p ~/.ssh
          echo "${{ secrets.STAGING_DEPLOY_KEY }}" > ~/.ssh/deploy_key
          chmod 600 ~/.ssh/deploy_key
          ssh-keyscan -H staging.askproai.de >> ~/.ssh/known_hosts

          ssh deploy@staging.askproai.de << 'EOF'
            cd /var/www/api-gateway
            git fetch origin feature/customer-portal
            git checkout feature/customer-portal
            composer install --no-interaction
            php artisan config:cache
            php artisan migrate --force
            php artisan cache:clear
          EOF

      - name: Health check
        run: |
          curl -f https://staging.askproai.de/health || exit 1

      - name: Run staging tests
        run: |
          ssh deploy@staging.askproai.de << 'EOF'
            cd /var/www/api-gateway
            php artisan test --env=staging --testsuite=Feature
          EOF

      - name: Portal accessibility check
        run: |
          curl -f https://staging.askproai.de/portal || exit 1
```

### 4.3 Manual Testing Procedure

**For QA Team**:

```
1. Access staging portal
   URL: https://staging.askproai.de
   Admin user: test@staging.askproai.de
   Password: (from test data)

2. Test Call History Feature
   - Navigate to /portal/calls
   - Verify list displays calls
   - Click on call to view transcript
   - Test date filter
   - Test search

3. Test Appointments Feature
   - Navigate to /portal/appointments
   - Verify appointments displayed
   - Switch to calendar view
   - Check multi-day appointments
   - Verify correct dates

4. Feature Flag Behavior
   - Admin: Disable FEATURE_CUSTOMER_PORTAL in /admin
   - Test: Access /portal → should get 404
   - Admin: Re-enable feature flag
   - Test: Access /portal → should work

5. Multi-Tenant Isolation
   - Login as User A (Company X)
   - Verify User A sees only Company X data
   - Logout, login as User B (Company Y)
   - Verify User B sees only Company Y data
   - Cross-company data should never appear
```

---

## Part 5: Git Workflow & Branch Strategy

### 5.1 Recommended Branch Structure

```
main (production)
├── feature/customer-portal (staging/development)
│   ├── feature/portal-calls (sub-branch)
│   ├── feature/portal-appointments (sub-branch)
│   └── feature/portal-dashboard (sub-branch)
│
├── feature/unified-booking-flow
├── feature/appointment-booking-v2
│
└── fix/hotfix-issue (for urgent production fixes)
```

### 5.2 Merging Strategy

#### Scenario 1: Feature Complete → Merge to Main
```bash
# 1. Ensure all tests pass on feature/customer-portal
# 2. Create Pull Request: feature/customer-portal → main
# 3. Staging validation passes automatically
# 4. Code review + approval from 2 reviewers
# 5. Merge with squash commit (cleaner history)
# 6. GitHub Actions automatically deploys to production
# 7. Feature flag CUSTOMER_PORTAL remains false (safe default)
```

#### Scenario 2: Hotfix During Staging Testing
```bash
# 1. Create: fix/portal-issue from feature/customer-portal
# 2. Fix bug, commit, push
# 3. Automated tests run
# 4. Manual staging test (quick)
# 5. Create PR: fix/portal-issue → feature/customer-portal
# 6. Merge back to feature branch
```

#### Scenario 3: Main Branch Hotfix (Production Bug)
```bash
# 1. Create: fix/production-bug from main
# 2. Fix, commit, push
# 3. All tests pass (quick cycle)
# 4. PR to main with manual approval
# 5. Auto-deploy to production (feature flags protect new code)
# 6. Can also merge back to feature/customer-portal
```

### 5.3 Git Commands for Team

```bash
# Create feature branch
git checkout -b feature/customer-portal
git push -u origin feature/customer-portal

# Push changes (auto-triggers staging deployment)
git commit -m "feat: add customer portal"
git push origin feature/customer-portal

# View staging deployment status
# → GitHub Actions shows deployment status
# → Staging URL: https://staging.askproai.de/portal

# When ready for production
git push origin feature/customer-portal
# → Create PR in GitHub UI
# → Tests run automatically
# → Await review approval
# → Merge to main
# → Auto-deploy to production (feature flag OFF)
```

---

## Part 6: Gradual Rollout Strategy (Feature Flag)

### 6.1 Phased Rollout Plan

**Phase 0: Development (Weeks 1-3)**
- Code on `feature/customer-portal` branch
- Feature flag: `FEATURE_CUSTOMER_PORTAL=false` in staging
- Testing: Full staging validation
- Status: ✓ In progress

**Phase 1: Production Deploy (Week 4)**
```bash
# .env (production)
FEATURE_CUSTOMER_PORTAL=false  # Disabled
# Users: /portal → 404 (feature not visible)
# Safety: Code deployed but not active
```

**Phase 2: Pilot Testing (Week 5)**
```bash
# .env or admin config
FEATURE_CUSTOMER_PORTAL=true   # Enabled

# OR: Test company rollout
FEATURE_CUSTOMER_PORTAL=true
FEATURE_CUSTOMER_PORTAL_TEST_COMPANIES=15  # Only company ID 15
```

**Phase 3: Gradual Rollout (Week 6+)**
- Week 6: Enable for 2-3 pilot companies
- Week 7: Enable for 10% of customers
- Week 8: Enable for 50% (if no issues)
- Week 9: Full rollout (100%)

### 6.2 Rollback Procedure

If issues found in staging:
```bash
# Quick fix on feature branch
git commit --amend
git push -f origin feature/customer-portal
# → Tests re-run automatically
# → Staging re-deploys

# If production issue occurs:
1. Disable feature flag immediately
   FEATURE_CUSTOMER_PORTAL=false

2. Investigate (access staging to reproduce)

3. Fix on feature branch

4. Re-test on staging

5. Re-enable with caution
```

---

## Part 7: Deployment Commands & Scripts

### 7.1 Manual Staging Deployment

**Script**: `/var/www/api-gateway/scripts/deploy-staging.sh` (NEW)

```bash
#!/bin/bash
set -euo pipefail

STAGING_ROOT="/var/www/api-gateway"
STAGING_BRANCH="${1:-feature/customer-portal}"
LOG_FILE="/var/www/api-gateway/storage/logs/deployment/staging-$(date +%Y%m%d-%H%M%S).log"

echo "Deploying $STAGING_BRANCH to staging.askproai.de..."

# 1. Fetch latest
cd "$STAGING_ROOT"
git fetch origin

# 2. Checkout branch
git checkout "$STAGING_BRANCH"

# 3. Install dependencies
composer install --no-interaction --optimize-autoloader

# 4. Copy env file
cp .env.staging .env

# 5. Generate key (if needed)
php artisan key:generate

# 6. Run migrations
php artisan migrate --force

# 7. Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Health check
if curl -f https://staging.askproai.de/health > /dev/null 2>&1; then
    echo "✅ Staging deployment successful"
    echo "📍 Portal accessible at: https://staging.askproai.de/portal"
    exit 0
else
    echo "❌ Health check failed"
    exit 1
fi
```

### 7.2 Database Sync Script

**Script**: `/var/www/api-gateway/scripts/sync-staging-database.sh` (NEW)

```bash
#!/bin/bash
set -euo pipefail

echo "Syncing staging database from production..."

# 1. Backup staging (safety)
mysqldump -u askproai_user -p askproai_staging > \
  /var/www/api-gateway/storage/backups/staging-backup-$(date +%Y%m%d-%H%M%S).sql

# 2. Backup production
mysqldump -u askproai_user -p askproai_db > /tmp/prod_dump.sql

# 3. Restore to staging
mysql -u askproai_user -p askproai_staging < /tmp/prod_dump.sql

# 4. Sanitize sensitive data (optional)
mysql -u askproai_user -p askproai_staging << EOF
-- Update user passwords to test123
UPDATE users SET password = '\$2y\$12\$E8rnvTiP2VZ5dKIUgaF5OuYhSV4S6WqHqkK2w8K8E8K8E8K8E8K8E';

-- Update emails to test addresses (optional)
-- UPDATE users SET email = CONCAT(id, '+test@staging.askproai.de')
-- WHERE email NOT LIKE '%@askproai.de';

-- Keep companies, services, appointments intact
EOF

echo "✅ Staging database synced successfully"
echo "Users can login with: test123 password"
```

### 7.3 Feature Flag Toggle Script

**Script**: `/var/www/api-gateway/scripts/toggle-feature-flag.php` (NEW)

```php
#!/usr/bin/env php
<?php

$env = $argv[1] ?? 'staging';  // staging or production
$feature = $argv[2] ?? null;
$value = $argv[3] ?? null;

if (!$feature || !$value) {
    echo "Usage: php toggle-feature-flag.php <env> <feature> <true|false>\n";
    echo "Example: php toggle-feature-flag.php staging customer_portal true\n";
    exit(1);
}

$envFile = match($env) {
    'staging' => '.env.staging',
    'production' => '.env',
    default => die("Invalid environment: $env\n"),
};

$envContent = file_get_contents($envFile);

// Feature flag patterns
$featureEnvVars = [
    'customer_portal' => 'FEATURE_CUSTOMER_PORTAL',
    'portal_calls' => 'FEATURE_CUSTOMER_PORTAL_CALLS',
    'portal_appointments' => 'FEATURE_CUSTOMER_PORTAL_APPOINTMENTS',
];

$envVar = $featureEnvVars[$feature] ?? null;
if (!$envVar) {
    echo "Unknown feature: $feature\n";
    exit(1);
}

$boolValue = strtolower($value) === 'true' ? 'true' : 'false';

// Update .env file
$pattern = "/^$envVar=.*/m";
$replacement = "$envVar=$boolValue";
$newContent = preg_replace($pattern, $replacement, $envContent);

if ($newContent === $envContent) {
    // Variable not found, append it
    $newContent .= "\n$envVar=$boolValue\n";
}

file_put_contents($envFile, $newContent);
echo "✅ Updated $envVar=$boolValue in $envFile\n";

if ($env === 'production') {
    echo "⚠️  Remember to restart PHP-FPM: sudo systemctl restart php8.2-fpm\n";
}
```

---

## Part 8: Production Deployment Plan

### 8.1 Pre-Deployment Checklist

- [ ] Feature development complete on `feature/customer-portal`
- [ ] All GitHub Actions tests pass (unit, integration, E2E, security)
- [ ] Staging validation complete (all checklists passed)
- [ ] Product team approval granted
- [ ] Security review completed
- [ ] Database migration safety verified
- [ ] Rollback plan documented
- [ ] Team trained on feature flag behavior

### 8.2 Production Deployment Steps

**1. Create Pull Request**
```bash
git push origin feature/customer-portal
# Create PR: feature/customer-portal → main on GitHub
```

**2. Code Review & Tests**
- GitHub Actions runs all test suites automatically
- Team reviews code
- At least 2 approvals required
- No merge conflicts

**3. Merge to Main**
```
Click "Merge pull request" on GitHub
Select: "Squash and merge" (cleaner history)
```

**4. GitHub Actions: Auto-Deploy**
- Triggers production deployment workflow
- Runs smoke tests on production
- Slack notification on completion

**5. Verify Deployment**
```bash
# SSH to production
ssh deploy@api.askproai.de

# Check git branch
git log --oneline -1
# Should show: "feat: customer portal implementation"

# Verify feature flag is disabled
grep FEATURE_CUSTOMER_PORTAL /var/www/api-gateway/.env
# Should show: FEATURE_CUSTOMER_PORTAL=false

# Health check
curl https://api.askproai.de/health

# Portal should return 404 (feature disabled)
curl https://api.askproai.de/portal
# Expected: 404 Not Found
```

**6. Monitor Production**
- Watch logs: `tail -f /var/www/api-gateway/storage/logs/laravel.log`
- Check error rates for 1 hour
- Monitor performance metrics
- No customer impact expected (feature disabled)

### 8.3 Feature Activation (Week 5+)

Once production is stable:

```bash
# 1. Enable feature for pilot companies
ssh deploy@api.askproai.de
nano /var/www/api-gateway/.env

# Change:
FEATURE_CUSTOMER_PORTAL=false → FEATURE_CUSTOMER_PORTAL=true

# Optionally limit to test companies:
FEATURE_CUSTOMER_PORTAL_TEST_COMPANIES=15  # Only company ID 15

# 2. Clear caches
php artisan config:cache
sudo systemctl restart php8.2-fpm

# 3. Verify portal is accessible
curl https://api.askproai.de/portal
# Should render portal page (if logged in)

# 4. Monitor for issues
tail -f storage/logs/laravel.log
```

### 8.4 Rollback Procedure (If Issues)

**Immediate Mitigation** (minutes):
```bash
# Disable feature flag
nano .env
# FEATURE_CUSTOMER_PORTAL=false
php artisan config:cache
sudo systemctl restart php8.2-fpm

# Verify
curl https://api.askproai.de/portal
# Should return 404
```

**If Code Issues** (rollback commit):
```bash
git log --oneline  # Find deployment commit
git revert <commit-hash>
git push origin main
# GitHub Actions auto-deploys reverted version
```

**Full Database Rollback** (if migrations failed):
```bash
# Only if migrations caused critical issues
php artisan migrate:rollback --force
# Verify application works
curl https://api.askproai.de/health
```

---

## Part 9: Health Checks & Monitoring

### 9.1 Health Check Endpoint

**Endpoint**: `GET /health`

Already implemented in nginx config and Laravel routes.

**Response**:
```json
{
  "status": "ok",
  "environment": "production|staging",
  "database": "connected",
  "cache": "connected",
  "version": "1.0.0"
}
```

**Monitoring**:
```bash
# Monitor health
watch -n 5 'curl -s https://staging.askproai.de/health | jq .'

# Staging health
curl https://staging.askproai.de/health

# Production health
curl https://api.askproai.de/health
```

### 9.2 Log Monitoring

**Logs Location**: `/var/www/api-gateway/storage/logs/`

**Real-time Tailing**:
```bash
# Production
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Filter errors only
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "ERROR|Exception"

# Watch specific feature
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i portal
```

**Log Rotation**:
- Daily rotation enabled (LOG_STACK=daily)
- 7-day retention (LOG_DAILY_DAYS=7)
- Automatic cleanup

### 9.3 Performance Metrics

**Database Queries**:
```bash
# Enable query logging (temporary)
nano .env
# DB_LOG=true (custom log to queries.log)

# Monitor slow queries
tail -f storage/logs/queries.log | grep 'took.*ms'
```

**Cache Hit Rate**:
```bash
# Redis stats
redis-cli INFO stats | grep -E "keyspace_hits|keyspace_misses"

# Calculate hit ratio
# hit_ratio = hits / (hits + misses)
```

**Key Endpoints to Monitor**:
- `GET /portal` - Portal homepage
- `GET /portal/calls` - Call history list
- `GET /portal/appointments` - Appointments list
- `GET /admin` - Admin panel
- `GET /health` - Health check

---

## Part 10: Documentation & Team Communication

### 10.1 Documentation Files to Create

1. **STAGING_DEPLOYMENT_GUIDE.md**
   - Step-by-step for DevOps team
   - Infrastructure setup
   - Database sync procedures

2. **CUSTOMER_PORTAL_TESTING_GUIDE.md**
   - QA testing checklists
   - Test scenarios
   - Known limitations

3. **FEATURE_FLAG_MANAGEMENT.md**
   - How to enable/disable features
   - Company-level rollout procedure
   - Monitoring during rollout

4. **TROUBLESHOOTING_GUIDE.md**
   - Common issues during deployment
   - Solution procedures
   - Rollback procedures

### 10.2 Team Communication Plan

**Before Deployment**:
- Email: Team gets staging URL and credentials
- Slack: "#deployments" channel notifications
- Standup: Deploy timeline discussed

**During Staging Testing**:
- Daily standup: Test results
- Slack: Issue reporting and fixes
- GitHub Issues: Bug tracking

**Before Production Release**:
- Meeting: Sign-off from all teams
- Runbook: Sent to on-call engineer
- Slack: Deployment window announced

**After Production Release**:
- Monitoring: 24h log watch
- Daily updates: First week
- Retrospective: Lessons learned

---

## Part 11: Risk Assessment & Mitigation

### 11.1 Risk Matrix

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|-----------|
| Database migration fails | Low | High | Test migrations on staging first, rollback plan |
| Feature flag not working | Low | High | Test with flag ON/OFF before production |
| Multi-tenant isolation broken | Low | Critical | Security tests in CI/CD, audit in staging |
| Production data exposed in staging | Medium | High | Sanitization script, separate credentials |
| Cache collision | Low | Medium | Separate Redis prefix (askpro_staging_) |
| Performance degradation | Medium | Medium | Baseline performance tests, monitoring |
| User authentication broken | Low | High | E2E tests for login flow |
| SSL certificate issues | Low | Medium | Certificate monitoring, pre-renewal |

### 11.2 Mitigation Strategies

**Prevention**:
- ✅ Comprehensive test suite (unit, integration, E2E, security)
- ✅ Staging validation checklist (17 phases)
- ✅ Code review + approval process
- ✅ Feature flags for safe rollout

**Detection**:
- ✅ Health check endpoint
- ✅ Log aggregation and monitoring
- ✅ Performance baseline testing
- ✅ Daily standup communication

**Recovery**:
- ✅ Rollback procedure (disable feature flag)
- ✅ Database backup before migration
- ✅ Git commit revert capability
- ✅ 24-hour post-deployment monitoring

---

## Summary: Implementation Timeline

### Week 4 (Oct 26-30): Infrastructure Setup
- [ ] Create staging.askproai.de vhost (nginx)
- [ ] Create askproai_staging database
- [ ] Configure .env.staging
- [ ] Setup SSL certificate
- [ ] Test basic PHP/MySQL connectivity

### Week 5 (Nov 2-6): Deployment Automation
- [ ] Create GitHub Actions staging workflow
- [ ] Create deployment scripts (deploy-staging.sh, etc.)
- [ ] Test automated deployment from feature branch
- [ ] Setup health check monitoring
- [ ] Document procedures

### Week 6 (Nov 9-13): Feature Validation
- [ ] Deploy feature/customer-portal to staging
- [ ] Run complete staging validation checklist
- [ ] QA team tests all scenarios
- [ ] Fix any issues found
- [ ] Security review

### Week 7 (Nov 16-20): Production Deployment
- [ ] Create pull request: feature/customer-portal → main
- [ ] Code review + approvals
- [ ] Merge to main
- [ ] GitHub Actions deploys to production
- [ ] Verify deployment (feature flag OFF)
- [ ] 24-hour monitoring

### Week 8+ (Nov 23+): Gradual Rollout
- [ ] Enable for 2-3 pilot companies
- [ ] Monitor for 1 week
- [ ] Enable for 10% of customers
- [ ] Gradual increase based on stability

---

## Deliverables Checklist

- [x] **Current Deployment Analysis** (Part 1)
  - [x] Infrastructure details
  - [x] CI/CD pipeline
  - [x] Feature flag system
  - [x] Environment configuration
  - [x] Database strategy

- [x] **Staging Environment Setup** (Parts 2-3)
  - [x] Architecture recommendations
  - [x] Subdomain strategy
  - [x] Database setup procedures
  - [x] Git workflow
  - [x] Environment configuration files
  - [x] Nginx vhost configuration
  - [x] SSL setup
  - [x] PHP-FPM configuration
  - [x] Redis isolation

- [x] **Testing & Validation** (Part 4)
  - [x] Staging validation checklist (7 phases, 40+ items)
  - [x] Automated testing workflow
  - [x] Manual testing procedure

- [x] **Git Workflow** (Part 5)
  - [x] Branch structure
  - [x] Merging strategy
  - [x] Git commands for team

- [x] **Gradual Rollout** (Part 6)
  - [x] Phased rollout plan
  - [x] Rollback procedure

- [x] **Deployment Scripts** (Part 7)
  - [x] Manual staging deployment script
  - [x] Database sync script
  - [x] Feature flag toggle script

- [x] **Production Deployment** (Part 8)
  - [x] Pre-deployment checklist
  - [x] Step-by-step deployment
  - [x] Feature activation plan
  - [x] Rollback procedure

- [x] **Health Checks & Monitoring** (Part 9)
  - [x] Health endpoint
  - [x] Log monitoring
  - [x] Performance metrics

- [x] **Documentation Plan** (Part 10)
  - [x] Documentation files to create
  - [x] Team communication plan

- [x] **Risk Assessment** (Part 11)
  - [x] Risk matrix
  - [x] Mitigation strategies

- [x] **Implementation Timeline** (Summary)
  - [x] 4-week rollout plan
  - [x] Weekly milestones

---

## Files to Create/Modify

### Configuration Files
1. **Create** `.env.staging` - New staging environment
2. **Create** `config/features.php` - Already exists, no changes needed
3. **Create** `.gitlab-ci.yml` or enhance GitHub Actions (optional)

### Infrastructure Files
4. **Create** `/etc/nginx/sites-available/staging.askproai.de`
5. **Create** `/etc/php/8.2/fpm/pool.d/staging.conf` (optional)

### Deployment Scripts
6. **Create** `/scripts/deploy-staging.sh`
7. **Create** `/scripts/sync-staging-database.sh`
8. **Create** `/scripts/toggle-feature-flag.php`

### GitHub Actions Workflows
9. **Create** `.github/workflows/staging-deployment.yml`
10. **Create** `.github/workflows/production-deployment.yml`

### Documentation
11. **Create** `STAGING_DEPLOYMENT_GUIDE.md`
12. **Create** `CUSTOMER_PORTAL_TESTING_GUIDE.md`
13. **Create** `FEATURE_FLAG_MANAGEMENT.md`
14. **Create** `TROUBLESHOOTING_GUIDE.md`

---

## Quick Reference: Key Commands

```bash
# View staging deployment logs
tail -f /var/www/api-gateway/storage/logs/deployment/staging-*.log

# Sync staging database from production
bash /var/www/api-gateway/scripts/sync-staging-database.sh

# Deploy feature branch to staging
bash /var/www/api-gateway/scripts/deploy-staging.sh feature/customer-portal

# Enable customer portal feature in staging
php /var/www/api-gateway/scripts/toggle-feature-flag.php staging customer_portal true

# Enable for production (carefully!)
php /var/www/api-gateway/scripts/toggle-feature-flag.php production customer_portal true

# Check health
curl https://staging.askproai.de/health
curl https://api.askproai.de/health

# Monitor logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i portal
```

---

**Document Created**: 2025-10-26
**Version**: 1.0
**Status**: Ready for Implementation
**Next Step**: Review with team → Approve → Begin Week 4 infrastructure setup
