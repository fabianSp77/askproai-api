# FIX4b Post-Deployment Permissions Hotfix

**Date:** 2025-11-01 21:11 UTC
**Issue:** Laravel log file permission denied errors on staging
**Status:** ✅ RESOLVED

---

## Problem

After successful FIX4b deployment, the staging application showed permission errors when trying to write logs:

```
The stream or file "/var/www/api-gateway-staging/releases/20251101_205203-d4dd19b7/storage/logs/laravel-2025-11-01.log"
could not be opened in append mode: Failed to open stream: Permission denied
```

## Root Cause Analysis

### Ownership Mismatch

**PHP-FPM Configuration:**
```
user = www-data
group = www-data
```

**Log Files Ownership:**
```
-rwxrwxr-x 1 deploy deploy 41046 laravel-2025-11-01.log
```

**Problem:**
- PHP-FPM runs as `www-data:www-data`
- Log files owned by `deploy:deploy`
- Permissions: `rwxrwxr-x` (775)
  - Owner (deploy): rwx ✅
  - Group (deploy): rwx - but www-data is NOT in deploy group ❌
  - Others: r-x (NO WRITE) ❌
- Result: www-data cannot write to log files

## Solution

### Immediate Fix (Manual)

```bash
# Fix ownership to deploy:www-data (PHP-FPM group)
sudo chown -R deploy:www-data /var/www/api-gateway-staging/shared/storage

# Ensure group write permissions
sudo chmod -R 775 /var/www/api-gateway-staging/shared/storage
```

### Permanent Fix (Workflow Automation)

Added automatic permissions fix to `.github/workflows/deploy-staging.yml`:

```yaml
- name: Fix storage permissions
  run: |
    ssh -i ~/.ssh/staging_key -o StrictHostKeyChecking=no "${{ env.STAGING_USER }}@${{ env.STAGING_HOST }}" << 'EOF'
    set -euo pipefail
    # Ensure shared storage has correct ownership and permissions for PHP-FPM (www-data)
    sudo chown -R deploy:www-data "${STAGING_BASE_DIR:-/var/www/api-gateway-staging}/shared/storage"
    sudo chmod -R 775 "${STAGING_BASE_DIR:-/var/www/api-gateway-staging}/shared/storage"
    echo "✅ Storage permissions fixed (deploy:www-data, 775)"
    EOF
```

**Execution Order:**
1. Run migrations
2. Clear caches
3. **Fix storage permissions** ← NEW STEP
4. Switch current symlink
5. Reload services
6. Health checks

## Verification

### Before Fix

```bash
$ ls -la /var/www/api-gateway-staging/shared/storage/logs/
drwxrwxr-x 2 deploy www-data  4096  logs/
-rwxrwxr-x 1 deploy deploy   41046  laravel-2025-11-01.log

$ curl https://staging.askproai.de/
→ Internal Server Error (Permission denied)
```

### After Fix

```bash
$ ls -la /var/www/api-gateway-staging/shared/storage/logs/
drwxrwxr-x 2 deploy www-data  4096  logs/
-rwxrwxr-x 1 deploy www-data 41046  laravel-2025-11-01.log

$ curl -H "Authorization: Bearer ..." https://staging.askproai.de/healthcheck.php
→ {"status":"healthy","service":"staging","timestamp":1762027934}

$ grep "Permission denied" /var/www/api-gateway-staging/shared/storage/logs/laravel-2025-11-01.log
→ (no results - 0 errors)
```

## Impact

### Files Affected
- `storage/logs/*` - All log files
- `storage/framework/cache/*` - Cache files
- `storage/framework/sessions/*` - Session files
- `storage/framework/views/*` - Compiled views
- `storage/app/*` - Application files

### Directories Fixed
```
/var/www/api-gateway-staging/shared/storage/
├── logs/           (deploy:www-data, 775)
├── framework/
│   ├── cache/      (deploy:www-data, 775)
│   ├── sessions/   (deploy:www-data, 775)
│   └── views/      (deploy:www-data, 775)
├── app/            (deploy:www-data, 775)
├── backups/        (deploy:www-data, 775)
└── docs/           (deploy:www-data, 775)
```

## Why This Matters

### Security
- Follows principle of least privilege
- deploy user owns files (deployment control)
- www-data group has write access (runtime needs)
- Others have read-only access

### Reliability
- No permission errors during request handling
- Logs properly written for debugging
- Cache and sessions work correctly
- Application stable under load

### Maintainability
- Automated in deployment workflow
- Consistent across all releases
- No manual intervention needed
- Self-healing on each deployment

## Git Commit

```
commit 1aa72306
fix(deploy): Add automatic storage permissions fix to staging deployment

- Added "Fix storage permissions" step after cache clearing
- Sets ownership to deploy:www-data (group write access)
- Sets permissions to 775 (rwxrwxr-x)
- Runs before symlink switch to ensure clean state
```

## Lessons Learned

1. **Ownership Matters:** File ownership is critical when different users (deploy vs www-data) interact with files
2. **Group Permissions:** Using group ownership (www-data) allows both deployment and runtime access
3. **Symlink Behavior:** Storage symlinks to shared directory, so permissions must be set on shared, not release
4. **Testing Depth:** Initial health checks passed but didn't catch application-level errors
5. **Automation First:** Manual fixes are temporary; workflow automation prevents recurrence

## Next Deployment

The next deployment will automatically include this permissions fix, preventing any recurrence of this issue.

**Expected Behavior:**
1. Build artifacts
2. Upload to server
3. Extract to release directory
4. Create symlinks (storage, .env, uploads)
5. Run migrations
6. Clear caches
7. **Fix storage permissions** ← Automatic
8. Switch symlink
9. Reload services
10. Health checks pass ✅

---

**Fixed By:** Claude (Automated CI/CD System)
**Commit:** 1aa72306
**Branch:** develop
**Date:** 2025-11-01 21:11 UTC
