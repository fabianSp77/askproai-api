# NAVIGATION BADGE RESTORATION CHECKLIST
**Generated:** 2025-10-03 19:50
**Purpose:** Track restoration progress for disabled navigation badges

---

## RESTORATION PATTERN (Copy-Paste Template)

```php
use App\Filament\Concerns\HasCachedNavigationBadge;

class YourResource extends Resource
{
    use HasCachedNavigationBadge;

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            // PASTE ORIGINAL LOGIC FROM BACKUP FILE HERE
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getCachedBadgeColor(function() {
            // PASTE ORIGINAL COLOR LOGIC FROM BACKUP FILE HERE
        });
    }

    // For badges with tooltips:
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Your tooltip text';
    }
}
```

---

## PHASE 1: CRITICAL RESOURCES (10 Resources)

### 🔴 ActivityLogResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/ActivityLogResource.php`
- [ ] Add `use HasCachedNavigationBadge;` trait
- [ ] Restore badge logic from backup lines 48-62
- [ ] Restore badge color logic from backup lines 64-70
- [ ] Restore tooltip from backup lines 72-75
- [ ] Test: Verify critical logs badge shows "🔴 N" or "⚠️ N"
- [ ] Test: Verify color is 'danger' or 'warning'

**Original Logic:**
```php
// Badge: Count critical + error logs from today that are unread
// Shows: "🔴 N" for critical, "⚠️ N" for errors
// Color: 'danger' if critical exists, 'warning' if errors
// Tooltip: 'Ungelesene kritische Ereignisse'
```

### 🔴 BalanceTopupResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/BalanceTopupResource.php`
- [ ] Add `use HasCachedNavigationBadge;` trait
- [ ] Restore badge logic from backup lines 50-52
- [ ] Restore badge color: 'warning'
- [ ] Restore tooltip: 'Ausstehende Aufladungen'
- [ ] Test: Verify pending topups count shows

**Original Logic:**
```php
// Badge: Count pending balance topups
// Color: 'warning'
// Tooltip: 'Ausstehende Aufladungen'
```

### 🔴 CallbackRequestResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/CallbackRequestResource.php`
- [ ] Add `use HasCachedNavigationBadge;` trait
- [ ] Restore badge logic from backup lines 41-45
- [ ] Restore badge color logic from backup lines 49-56
- [ ] Test: Verify pending callbacks count shows
- [ ] Test: Verify color changes (danger >10, warning >5, info otherwise)

**Original Logic:**
```php
// Badge: Count pending callback requests (with 5-min cache)
// Color: 'danger' if >10, 'warning' if >5, 'info' otherwise
```

### 🔴 CustomerNoteResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/CustomerNoteResource.php`
- [ ] Add `use HasCachedNavigationBadge;` trait
- [ ] Restore badge logic from backup lines 357-360
- [ ] Restore badge color logic from backup lines 364-367
- [ ] Test: Verify important notes count shows
- [ ] Test: Verify color is 'danger' when important notes exist

**Original Logic:**
```php
// Badge: Count important customer notes (with 5-min cache)
// Color: 'danger' if important notes exist
```

### 🔴 IntegrationResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/IntegrationResource.php`
- [ ] Add `use HasCachedNavigationBadge;` trait
- [ ] Restore badge logic from backup lines 36-40
- [ ] Restore badge color logic from backup lines 44-52
- [ ] Test: Verify badge shows "N / M" format (active / total)
- [ ] Test: Verify color: 'danger' if errors, 'success' if all healthy, 'warning' otherwise

**Original Logic:**
```php
// Badge: "{active} / {total}" integrations
// Color: 'danger' if errors exist, 'success' if all healthy, 'warning' otherwise
```

### 🔴 InvoiceResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/InvoiceResource.php`
- [ ] Add `use HasCachedNavigationBadge;` trait
- [ ] Restore badge logic from backup lines 51-53
- [ ] Restore badge color logic from backup lines 57-60
- [ ] Test: Verify unpaid invoices count shows
- [ ] Test: Verify color: 'danger' >10, 'warning' >0, 'success' otherwise

**Original Logic:**
```php
// Badge: Count unpaid invoices (with 5-min cache)
// Color: 'danger' if >10, 'warning' if >0, 'success' otherwise
```

### 🔴 NotificationQueueResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/NotificationQueueResource.php`
- [ ] Add `use HasCachedNavigationBadge;` trait
- [ ] Restore badge logic from backup line 363
- [ ] Restore badge color logic from backup line 367
- [ ] Test: Verify pending notifications count shows
- [ ] Test: Verify color: 'danger' if failed notifications exist, 'warning' otherwise

**Original Logic:**
```php
// Badge: Count pending notifications
// Color: 'danger' if failed notifications exist, 'warning' otherwise
```

### 🔴 RetellAgentResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/RetellAgentResource.php`
- [ ] Add `use HasCachedNavigationBadge;` trait
- [ ] Restore badge logic from backup lines 45-48
- [ ] Restore badge color logic from backup lines 52-58
- [ ] Test: Verify badge shows "N / M" format (active / total)
- [ ] Test: Verify color based on percentage: ≥80% success, ≥50% warning, <50% danger

**Original Logic:**
```php
// Badge: "{active} / {total}" agents
// Color: Based on percentage: ≥80% 'success', ≥50% 'warning', <50% 'danger'
```

### 🔴 SystemSettingsResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/SystemSettingsResource.php`
- [ ] Add `use HasCachedNavigationBadge;` trait
- [ ] Restore badge logic from backup lines 43-65
- [ ] Restore badge color logic from backup lines 69-74
- [ ] Restore tooltip: 'Kritische Einstellungen prüfen'
- [ ] Test: Verify critical settings issues count shows
- [ ] Test: Verify color: 'danger' if maintenance mode, 'warning' otherwise

**Original Logic:**
```php
// Badge: Count issues in critical settings (maintenance_mode, backup_enabled, enable_2fa, debug_mode, api_rate_limiting)
// Color: 'danger' if maintenance_mode active, 'warning' otherwise
// Tooltip: 'Kritische Einstellungen prüfen'
```

### 🔴 TenantResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/TenantResource.php`
- [ ] Add `use HasCachedNavigationBadge;` trait
- [ ] Restore badge logic from backup lines 47-50
- [ ] Restore badge color logic from backup lines 54-60
- [ ] Test: Verify badge shows "N / M" format (active / total)
- [ ] Test: Verify color based on percentage: ≥90% success, ≥70% warning, <70% danger

**Original Logic:**
```php
// Badge: "{active} / {total}" tenants
// Color: Based on percentage: ≥90% 'success', ≥70% 'warning', <70% 'danger'
```

---

## PHASE 2: HIGH PRIORITY (8 Resources)

### 🟡 BalanceBonusTierResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/BalanceBonusTierResource.php`
- [ ] Add trait and restore from backup lines 394-396, 400-402

**Original Logic:**
```php
// Badge: Count active bonus tiers (with 5-min cache)
// Color: 'success' if >0, 'gray' otherwise
```

### 🟡 CurrencyExchangeRateResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/CurrencyExchangeRateResource.php`
- [ ] Add trait and restore from backup lines 304-305

**Original Logic:**
```php
// Badge: Count active exchange rates
```

### 🟡 PhoneNumberResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/PhoneNumberResource.php`
- [ ] Add trait and restore from backup (check backup file for exact lines)

### 🟡 PlatformCostResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/PlatformCostResource.php`
- [ ] Add trait and restore from backup (check backup file for exact lines)

### 🟡 PricingPlanResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/PricingPlanResource.php`
- [ ] Add trait and restore from backup lines 38-40, 44-47

**Original Logic:**
```php
// Badge: Count active pricing plans (with 5-min cache)
// Color: 'success' if >5, 'primary' if >0, 'danger' if 0
```

### 🟡 StaffResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/StaffResource.php`
- [ ] Add trait and restore from backup lines 36-37, 41-42

**Original Logic:**
```php
// Badge: Count active staff
// Color: 'success' if >50, 'warning' if >20, 'info' otherwise
```

### 🟡 TransactionResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/TransactionResource.php`
- [ ] Add trait and restore from backup lines 403-405, 409

**Original Logic:**
```php
// Badge: Count today's transactions (with 5-min cache)
// Color: 'info'
```

### 🟡 UserResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/UserResource.php`
- [ ] Add trait and restore from backup lines 37-40, 44-50

**Original Logic:**
```php
// Badge: "{active} / {total}" users
// Color: 'danger' if >5 inactive, 'warning' if >0 inactive, 'success' otherwise
```

---

## PHASE 3: MEDIUM/LOW PRIORITY (6 Resources)

### 🟢 BranchResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/BranchResource.php`
- [ ] Add trait and restore from backup lines 39, 43-44

### 🟢 CompanyResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/CompanyResource.php`
- [ ] Add trait and restore from backup lines 41, 45-46

### 🟢 PermissionResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/PermissionResource.php`
- [ ] Add trait and restore from backup lines 487, 491

### 🟢 RoleResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/RoleResource.php`
- [ ] Add trait and restore from backup lines 44, 48

### 🟢 WorkingHourResource
- [ ] File: `/var/www/api-gateway/app/Filament/Resources/WorkingHourResource.php`
- [ ] Add trait and restore from backup lines 41, 45-46

---

## TESTING CHECKLIST (After Each Restoration)

### Memory Monitoring
- [ ] Check server memory usage before restoration
- [ ] Check server memory usage after restoration
- [ ] Monitor for 30 minutes under normal load
- [ ] Verify memory doesn't grow unbounded

### Functional Testing
- [ ] Badge displays correct count
- [ ] Badge color matches logic
- [ ] Badge updates within 5 minutes of data change
- [ ] No visual glitches in navigation menu
- [ ] No console errors in browser

### Cache Verification
- [ ] Check Redis/cache for new badge keys
- [ ] Verify cache TTL is 300 seconds
- [ ] Verify cache keys are unique per resource
- [ ] Test cache invalidation (manual or automatic)

### Performance Testing
- [ ] Page load time before restoration: _____ms
- [ ] Page load time after restoration: _____ms
- [ ] Database query count before: _____
- [ ] Database query count after: _____

---

## PROGRESS TRACKER

**Phase 1 (Critical):** 0/10 completed
**Phase 2 (High):** 0/8 completed
**Phase 3 (Medium/Low):** 0/6 completed

**Already Restored (Before this analysis):**
- ✅ AppointmentResource
- ✅ CallResource
- ✅ CustomerResource

**Total Progress:** 3/26 resources restored (11.5%)

---

## ROLLBACK PROCEDURE (If Memory Issues Return)

If memory issues occur after restoration:

1. **Immediate:** Comment out the `use HasCachedNavigationBadge;` line in affected resource
2. **Immediate:** Return `return null;` in badge methods
3. **Immediate:** Clear application cache: `php artisan cache:clear`
4. **Analysis:** Check error logs for specific resource causing issues
5. **Fix:** Adjust cache duration or query logic for that specific resource
6. **Re-test:** Restore with modified settings

---

## NOTES

- All backup files are preserved at `*.pre-caching-backup`
- HasCachedNavigationBadge trait location: `/var/www/api-gateway/app/Filament/Concerns/HasCachedNavigationBadge.php`
- Cache duration is 300 seconds (5 minutes) - adjustable in trait if needed
- Some badges have tooltips - don't forget to restore those too

---

**Last Updated:** 2025-10-03 19:50
