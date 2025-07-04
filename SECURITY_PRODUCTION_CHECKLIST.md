# 🔒 Security Production Checklist

## ✅ Session Security (COMPLETED)

1. **Session Configuration** ✅
   - `SESSION_SECURE_COOKIE=true` (HTTPS-only)
   - `SESSION_SAME_SITE=strict` (CSRF protection)
   - `SESSION_HTTP_ONLY=true` (XSS protection)
   - `SESSION_ENCRYPT=true` (encrypted sessions)

2. **Security Headers Middleware** ✅
   - Created `app/Http/Middleware/SecurityHeaders.php`
   - Adds X-Frame-Options, CSP, HSTS, etc.

## 🔴 MUST DO BEFORE PRODUCTION

### 1. Enable Security Middleware in Kernel.php
```php
protected $middleware = [
    // ... existing middleware ...
    \App\Http\Middleware\ThreatDetectionMiddleware::class,
    \App\Http\Middleware\MonitoringMiddleware::class,
    \App\Http\Middleware\SecurityHeaders::class, // NEW
];
```

### 2. Set Production Environment Variables
```bash
# .env production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.askproai.de

# Session Security
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
SESSION_HTTP_ONLY=true
SESSION_ENCRYPT=true
SESSION_DRIVER=redis  # Don't use 'file' in production

# Security Features
ENABLE_THREAT_DETECTION=true
ENABLE_RATE_LIMITING=true
ENABLE_SQL_INJECTION_PROTECTION=true
```

### 3. Configure HTTPS Enforcement
```nginx
# nginx configuration
server {
    listen 80;
    server_name api.askproai.de;
    return 301 https://$server_name$request_uri;
}
```

### 4. Database Security
```bash
# Remove default users
mysql -u root -p
> DROP USER IF EXISTS 'root'@'%';
> CREATE USER 'askproai_prod'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
> GRANT SELECT, INSERT, UPDATE, DELETE ON askproai_db.* TO 'askproai_prod'@'localhost';
> FLUSH PRIVILEGES;
```

### 5. API Security
- Enable rate limiting on all endpoints
- Require API keys for all external access
- Use webhook signature verification

### 6. Monitoring & Alerting
```bash
# Enable all monitoring
php artisan monitoring:enable
php artisan security:enable-alerts
```

### 7. Backup Encryption
```bash
# Set in .env
BACKUP_ENCRYPTION_KEY=generate-strong-key-here
BACKUP_RETENTION_DAYS=30
```

## 📋 Pre-Deployment Checklist

- [ ] All SQL injections fixed (93 files remaining)
- [ ] Session security configured ✅
- [ ] Security headers enabled
- [ ] HTTPS enforced
- [ ] Debug mode disabled
- [ ] Error reporting configured
- [ ] Database credentials secured
- [ ] API keys encrypted
- [ ] Rate limiting enabled
- [ ] Monitoring active
- [ ] Backup automation tested
- [ ] Firewall rules configured
- [ ] SSH key-only access
- [ ] Fail2ban installed
- [ ] Security audit passed

## 🚨 Critical Commands

```bash
# Generate new APP_KEY
php artisan key:generate

# Clear all caches for production
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Enable monitoring
php artisan horizon
php artisan security:monitor

# Test security
php artisan security:audit
```

## 🔐 API Key Rotation

```bash
# Rotate all API keys
php artisan api:rotate-keys --force

# Verify encryption
php artisan api:verify-encryption
```

## 📊 Security Monitoring

1. **Check Security Logs Daily**
   ```sql
   SELECT * FROM security_logs 
   WHERE type IN ('sql_injection', 'xss_attempt', 'auth_failure')
   AND created_at > NOW() - INTERVAL 24 HOUR;
   ```

2. **Monitor Failed Logins**
   ```sql
   SELECT ip_address, COUNT(*) as attempts 
   FROM security_logs 
   WHERE type = 'auth_failure' 
   AND created_at > NOW() - INTERVAL 1 HOUR
   GROUP BY ip_address 
   HAVING attempts > 5;
   ```

3. **Check Rate Limiting**
   ```bash
   redis-cli
   > KEYS rate_limit:*
   ```

## ⚠️ Emergency Response

If security breach detected:

1. **Immediate Actions**
   ```bash
   # Block all traffic except SSH
   sudo ufw --force reset
   sudo ufw allow 22
   sudo ufw --force enable
   
   # Rotate all credentials
   php artisan security:emergency-rotation
   
   # Backup current state
   php artisan backup:run --type=security-incident
   ```

2. **Investigation**
   ```bash
   # Check access logs
   tail -n 1000 /var/log/nginx/access.log | grep -E "POST|PUT|DELETE"
   
   # Check application logs
   tail -n 1000 storage/logs/laravel.log | grep -i "exception\|error\|warning"
   ```

3. **Recovery**
   - Restore from last known good backup
   - Reset all user passwords
   - Notify affected users
   - File incident report

---

**Last Updated**: 2025-06-27
**Status**: Session Security COMPLETED ✅
**Next**: Fix SQL Injections (93 files)