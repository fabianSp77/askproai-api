# 🚨 IMMEDIATE ACTION PLAN - Next 48 Hours

**Created**: 2025-06-27  
**Priority**: CRITICAL  
**Goal**: Fix showstopper security issues

---

## 🔥 TODAY (Next 8 Hours)

### 1. SQL Injection Fixes (2-3 hours)
```bash
# Find all vulnerable queries
grep -r "whereRaw\|DB::raw\|selectRaw\|orderByRaw" app/ --include="*.php" > sql_injection_locations.txt

# Critical files to fix first:
- app/Filament/Admin/Resources/CallResource.php
- app/Services/CustomerService.php  
- app/Services/CalcomService.php
- app/Http/Controllers/OptimizedRetellWebhookController.php
```

**Fix Pattern**:
```php
// VULNERABLE
->whereRaw("LOWER(name) LIKE ?", ['%' . strtolower($search) . '%'])

// SECURE
->where('name', 'LIKE', '%' . $search . '%')
```

### 2. Enable 2FA (1 hour)
```bash
# Install Laravel Fortify
composer require laravel/fortify

# Publish and configure
php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"

# Add to User model
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable {
    use TwoFactorAuthenticatable;
}

# Run migration
php artisan migrate
```

### 3. Database Indexes (30 min)
```bash
# Create critical indexes migration
php artisan make:migration add_critical_performance_indexes

# Add in migration:
$table->index(['company_id', 'created_at']);
$table->index(['phone', 'company_id']); 
$table->index(['branch_id', 'starts_at']);
$table->index(['provider', 'event_id']);

# Run migration
php artisan migrate --force
```

### 4. Fix Test Suite (2 hours)
```php
// Update CompatibleMigration base class
// Fix JSON columns for SQLite
// Update test database config
// Run: php artisan test
```

---

## 📅 TOMORROW (Day 2)

### Morning (4 hours)
1. **Connection Pooling**
   - Implement PDO persistent connections
   - Add connection limits
   - Monitor connection usage

2. **Rate Limiting**
   ```php
   // Add to routes
   Route::middleware('throttle:60,1')->group(function () {
       // API routes
   });
   
   // Login specific
   Route::post('/login')->middleware('throttle:5,1');
   ```

### Afternoon (4 hours)
1. **Webhook Timeout Protection**
   - Move to async processing
   - Add timeout handling
   - Implement retry logic

2. **Basic Monitoring**
   ```bash
   # Install monitoring
   composer require spatie/laravel-health
   
   # Add health checks
   php artisan health:check
   ```

---

## 🛠️ Quick Fix Scripts

### SQL Injection Auto-Fixer
```php
<?php
// save as fix-sql-injections.php
$files = glob('app/**/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Fix whereRaw
    $content = preg_replace(
        '/->whereRaw\(["\']LOWER\((\w+)\)\s+LIKE\s+\?["\'],\s*\[["\']%["\'].*?\.["\']%["\']\]\)/',
        '->where(\'$1\', \'LIKE\', \'%\' . $search . \'%\')',
        $content
    );
    
    // Fix orderByRaw with user input
    $content = preg_replace(
        '/->orderByRaw\(\$(\w+)\s*\.\s*["\'\s]+["\'\s]*\.\s*\$(\w+)\)/',
        '->orderBy(\$$1, \$$2)',
        $content
    );
    
    file_put_contents($file, $content);
}
```

### Emergency Security Lockdown
```bash
#!/bin/bash
# emergency-lockdown.sh

# Disable registration
php artisan down --allow=127.0.0.1

# Clear all sessions
php artisan session:clear

# Regenerate app key (warning: will log out all users)
php artisan key:generate

# Clear all caches
php artisan optimize:clear

# Enable maintenance mode with custom message
php artisan down --message="System maintenance in progress" --retry=3600
```

---

## 📊 Progress Tracking

### Day 1 Targets
- [ ] 0/103 SQL injections fixed → 103/103 ✅
- [ ] 0% 2FA enabled → 100% admin users ✅
- [ ] 0 critical indexes → 15 indexes added ✅
- [ ] 6% tests passing → 60% tests passing ✅

### Day 2 Targets  
- [ ] Connection pooling implemented
- [ ] Rate limiting on all endpoints
- [ ] Webhook processing async
- [ ] Basic monitoring active

---

## 🚦 Go/No-Go Decision Points

### After Day 1
✅ **Continue** if:
- All SQL injections fixed
- 2FA working for admins
- Tests > 50% passing

❌ **Stop & Reassess** if:
- SQL injections remain
- Can't enable 2FA
- Tests still failing

### After Day 2
✅ **Proceed to Phase 2** if:
- No critical security issues
- System stable under load
- Monitoring shows green

❌ **Extend Phase 1** if:
- Performance issues
- Security gaps found
- System unstable

---

## 📞 Escalation Path

1. **Technical Issues**: Team Lead → CTO
2. **Security Issues**: STOP → Security Team → CTO
3. **Data Issues**: DBA → Team Lead → CTO
4. **Business Impact**: Product Owner → CEO

---

## 💡 Pro Tips

1. **Commit Often**: After each SQL injection fix
2. **Test Immediately**: Run tests after each change
3. **Monitor Logs**: Watch for new errors
4. **Document Changes**: Update CHANGELOG.md
5. **Backup First**: Before any database changes

---

## 🎯 Success Metrics

**End of Day 1**:
- ✅ Zero SQL injection vulnerabilities
- ✅ 100% admins have 2FA option
- ✅ 50%+ test coverage
- ✅ Critical queries < 100ms

**End of Day 2**:
- ✅ All endpoints rate limited
- ✅ Webhook processing < 1s
- ✅ Zero timeout errors
- ✅ Monitoring dashboard live

---

**Remember**: Security first, features second! 🔒

**Next Update**: Tomorrow 10:00 CET