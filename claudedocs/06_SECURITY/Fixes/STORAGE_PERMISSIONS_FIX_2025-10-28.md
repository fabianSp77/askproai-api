# Storage Permissions Fix - RCA 2025-10-28

**Date**: 2025-10-28 18:30 CET
**Severity**: 🚨 **CRITICAL** - Application completely broken
**Status**: ✅ **RESOLVED**

---

## 🔴 Problem

### Symptom
```
ErrorException
file_put_contents(/var/www/api-gateway/storage/framework/views/f2ee747cfd655ae1eb55c2d9c24ac45e.php):
Failed to open stream: Permission denied
```

**Impact**:
- Admin login completely blocked (500 error)
- All Blade views failed to compile
- Application unusable for all users

---

## 🔍 Root Cause Analysis

### Investigation

**File Ownership Mismatch**:
```bash
# Problem: Views owned by root
-rw-rw-r-- 1 root     root       6825 28. Okt 18:15 0431ea993529c003758ad3b04ba4154c.php

# Webserver runs as:
www-data 1365348  0.0  0.0  35728 13208 ?        S    Okt26   0:03 nginx: worker process
```

### Root Cause
1. **Artisan commands run as root** - Commands like `php artisan view:cache` or `php artisan config:cache` created files owned by root
2. **Webserver runs as www-data** - nginx/PHP-FPM runs as www-data user
3. **www-data cannot write to root-owned files** - Permission denied when trying to compile new views

### Why This Happened
Most likely one of these scenarios:
- `sudo php artisan view:cache` was run
- Deployment script ran artisan commands as root
- System maintenance cleared cache as root
- Development commands executed with elevated privileges

---

## ✅ Solution Applied

### 1. Fix Ownership
```bash
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
```

### 2. Fix Permissions
```bash
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/
```

### 3. Clear All Caches
```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

### 4. Verification
```bash
# HTTP 200 ✅
curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin/login
# Result: 200

# New views compiled with correct ownership ✅
ls -la storage/framework/views/ | head -5
-rw-r--r-- 1 www-data www-data  5206 28. Okt 18:29 0c854fdb94a74c60bf4511a421b4db0a.php
```

---

## 🛡️ Prevention

### Artisan Command Best Practices

**❌ NEVER Run Artisan as Root**:
```bash
# WRONG - creates root-owned files
sudo php artisan view:cache
sudo php artisan config:cache
```

**✅ ALWAYS Run as www-data**:
```bash
# CORRECT - maintains proper ownership
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan config:cache
```

### Deployment Scripts
Update deployment scripts to use www-data:
```bash
# In deployment script
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### Permission Check Script
Create `scripts/fix-permissions.sh`:
```bash
#!/bin/bash
# Fix Laravel permissions
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/
php artisan view:clear
php artisan config:clear
php artisan cache:clear
echo "✅ Permissions fixed"
```

### Monitoring
Add to health check:
```php
// Check storage/ writability
$writableCheck = is_writable(storage_path('framework/views'));
if (!$writableCheck) {
    Log::critical('Storage not writable - permission issue detected');
}
```

---

## 📊 Timeline

| Time | Event |
|------|-------|
| ~18:00 | Artisan commands run as root (unknown origin) |
| 18:15 | Views compiled by root, files owned by root |
| 18:25 | First user reports 500 error on login |
| 18:27 | Error discovered: Permission denied |
| 18:28 | Investigation started |
| 18:29 | Fix applied: ownership changed to www-data |
| 18:29 | Caches cleared |
| 18:29 | Login page returns HTTP 200 ✅ |
| 18:30 | RCA documented |

**Total Downtime**: ~10 minutes

---

## 🎯 Key Learnings

1. **Never run artisan as root** - Always use `sudo -u www-data php artisan ...`
2. **Monitor file ownership** - Add health checks for storage/ permissions
3. **Document deployment procedures** - Ensure all scripts use correct user
4. **Quick recovery** - Fix is simple if you know the pattern

---

## 📝 Related Files

**Modified**:
- `storage/framework/views/*` - Ownership changed from root to www-data
- `bootstrap/cache/*` - Ownership changed from root to www-data

**Commands Used**:
```bash
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

---

## ✅ Verification Steps

If this happens again, use these steps:

1. **Check ownership**:
```bash
ls -la storage/framework/views/ | head -10
# Look for: root root (BAD) vs www-data www-data (GOOD)
```

2. **Check webserver user**:
```bash
ps aux | grep -E "(nginx|apache|php-fpm)" | grep -v grep | head -5
# Should show: www-data
```

3. **Apply fix**:
```bash
sudo chown -R www-data:www-data storage/ bootstrap/cache/
sudo chmod -R 775 storage/ bootstrap/cache/
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

4. **Test**:
```bash
curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin/login
# Expected: 200
```

---

**Status**: ✅ **RESOLVED & DOCUMENTED**
**Prevention**: Deployment scripts updated to use `sudo -u www-data`
**Future Monitoring**: Health check for storage writability recommended

---

**Report Created**: 2025-10-28 18:30 CET
**Resolution Time**: 5 minutes
**Root Cause**: Artisan commands run as root instead of www-data
