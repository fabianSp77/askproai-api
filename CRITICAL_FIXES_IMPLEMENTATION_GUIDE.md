# Critical Fixes Implementation Guide

## Quick Start (5 minutes)

### Step 1: Run Test Suite
```bash
cd /var/www/api-gateway
php test-critical-fixes.php
```

Expected output:
- All tests should show ✓ (checkmarks)
- Note any failures for troubleshooting

### Step 2: Apply Database Changes
```bash
# Create permissions
php artisan db:seed --class=RetellControlCenterPermissionSeeder

# Verify permissions created
php artisan tinker
>>> \Spatie\Permission\Models\Permission::where('name', 'manage_retell_control_center')->exists()
>>> // Should return true
```

### Step 3: Apply Code Patches

#### Option A: Manual Application (Recommended)
1. Open `/var/www/api-gateway/app/Filament/Admin/Pages/RetellUltimateControlCenter.php`
2. Add imports after line 9:
   ```php
   use App\Services\Security\ApiKeyService;
   use App\Services\ErrorHandlingService;
   ```

3. Add authorization methods after line 25:
   ```php
   public static function canAccess(): bool
   {
       $user = auth()->user();
       
       // Only super admins or users with specific permission
       return $user && (
           $user->hasRole('super_admin') || 
           $user->can('manage_retell_control_center')
       );
   }

   protected function authorize(): void
   {
       if (!static::canAccess()) {
           abort(403, 'Unauthorized access to Retell Control Center');
       }
   }
   ```

4. Update mount() method (add at beginning):
   ```php
   public function mount(): void
   {
       // Add authorization check
       $this->authorize();
       
       // ... existing code ...
   }
   ```

#### Option B: Apply Patch File
```bash
cd /var/www/api-gateway
patch -p1 < patches/retell-ultimate-control-center-security.patch
```

### Step 4: Update Company Model
Add these methods to `/var/www/api-gateway/app/Models/Company.php` after line 100:

```php
/**
 * Get decrypted Retell API key
 */
public function getDecryptedRetellApiKey(): ?string
{
    return ApiKeyService::decrypt($this->retell_api_key);
}

/**
 * Set and encrypt Retell API key
 */
public function setRetellApiKeyAttribute($value): void
{
    $this->attributes['retell_api_key'] = ApiKeyService::encrypt($value);
}

/**
 * Get decrypted Cal.com API key
 */
public function getDecryptedCalcomApiKey(): ?string
{
    return ApiKeyService::decrypt($this->calcom_api_key);
}

/**
 * Set and encrypt Cal.com API key
 */
public function setCalcomApiKeyAttribute($value): void
{
    $this->attributes['calcom_api_key'] = ApiKeyService::encrypt($value);
}
```

### Step 5: Add Health Check Route
Add to `/var/www/api-gateway/routes/api.php`:

```php
use App\Http\Controllers\Api\HealthCheckController;

Route::get('/health/circuit-breaker', [HealthCheckController::class, 'circuitBreaker'])
    ->name('health.circuit-breaker');
```

### Step 6: Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Verification Steps

### 1. Test Authorization
```bash
# As regular user (should fail)
curl -X GET https://api.askproai.de/admin/retell-ultimate-control-center \
  -H "Cookie: your-session-cookie" \
  -v

# Should return 403 Forbidden
```

### 2. Test API Key Encryption
```bash
php artisan tinker
>>> $company = Company::first();
>>> $company->retell_api_key = 'key_test123';
>>> $company->save();
>>> 
>>> // Verify encrypted in database
>>> $raw = DB::table('companies')->where('id', $company->id)->value('retell_api_key');
>>> strlen($raw) > 50; // Should be true (encrypted)
>>> 
>>> // Verify decryption works
>>> $company->fresh()->getDecryptedRetellApiKey(); // Should return 'key_test123'
```

### 3. Test Circuit Breaker
```bash
# Check health endpoint
curl https://api.askproai.de/api/health/circuit-breaker

# Expected response:
{
    "healthy": true,
    "services": {
        "calcom": {
            "state": "closed",
            "failures": 0,
            "last_failure": null
        },
        "retell": {
            "state": "closed",
            "failures": 0,
            "last_failure": null
        },
        "stripe": {
            "state": "closed",
            "failures": 0,
            "last_failure": null
        }
    },
    "timestamp": "2025-06-25T12:00:00+00:00"
}
```

### 4. Test Error Handling
```bash
php artisan tinker
>>> use App\Services\ErrorHandlingService;
>>> $error = new Exception('Test error');
>>> $result = ErrorHandlingService::handle($error);
>>> $result['error_id']; // Should return UUID
>>> 
>>> // Check if stored in database
>>> DB::table('critical_errors')->where('error_id', $result['error_id'])->exists();
```

## Monitoring Setup

### 1. Add Cron Job for Circuit Breaker Monitoring
```bash
# Edit crontab
crontab -e

# Add this line
*/5 * * * * curl -s https://api.askproai.de/api/health/circuit-breaker | grep -q '"healthy":true' || echo "Circuit breaker issue detected at $(date)" >> /var/log/askproai-alerts.log
```

### 2. Set Up Log Monitoring
```bash
# Watch for authorization failures
tail -f storage/logs/laravel.log | grep -E "(403|Unauthorized|manage_retell_control_center)"

# Watch for API key issues
tail -f storage/logs/laravel.log | grep -E "(decrypt|encrypt|API key)"

# Watch for circuit breaker events
tail -f storage/logs/laravel.log | grep -E "(Circuit breaker|fallback|service unavailable)"
```

## Rollback Plan

If issues occur:

### 1. Rollback Code Changes
```bash
cd /var/www/api-gateway
git status  # Check changed files
git diff    # Review changes

# If needed, revert specific files
git checkout -- app/Filament/Admin/Pages/RetellUltimateControlCenter.php
git checkout -- app/Models/Company.php

# Clear caches
php artisan optimize:clear
```

### 2. Rollback Permissions
```sql
-- Connect to database
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db

-- Remove permissions
DELETE FROM permissions WHERE name = 'manage_retell_control_center';
DELETE FROM role_has_permissions WHERE permission_id IN (
    SELECT id FROM permissions WHERE name = 'manage_retell_control_center'
);
```

### 3. Emergency Access
If locked out of Retell Control Center:
```bash
php artisan tinker
>>> $user = User::find(1); // Your admin user ID
>>> $user->assignRole('super_admin');
>>> $user->givePermissionTo('manage_retell_control_center');
```

## Success Checklist

- [ ] All tests pass in `test-critical-fixes.php`
- [ ] Permissions seeder executed successfully
- [ ] RetellUltimateControlCenter requires authorization
- [ ] API keys are encrypted in database
- [ ] Circuit breaker health endpoint responds
- [ ] Error handling creates error IDs
- [ ] No plain text API keys in logs
- [ ] Monitoring alerts configured

## Next Steps

1. Monitor error logs for the next 24 hours
2. Check circuit breaker status every hour
3. Review any error_ids reported by users
4. Plan gradual rollout of SafeMigration to other migrations
5. Schedule security audit for remaining endpoints

## Support

If issues arise:
1. Check `/storage/logs/laravel.log` for detailed errors
2. Use error_id to trace specific issues
3. Monitor `/api/health/circuit-breaker` for service status
4. Review `CRITICAL_FIXES_ACTION_PLAN.md` for detailed explanations