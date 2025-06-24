# 🚨 Emergency Portal Fix - If Still Broken

If the Company Integration Portal is STILL broken after all fixes, here are emergency steps:

## 1. Nuclear Option - Clear Everything

```bash
# Stop all services
php artisan down

# Clear EVERYTHING
rm -rf storage/framework/views/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf bootstrap/cache/*
rm -rf public/build/*

# Rebuild
npm run build
php artisan optimize:clear
php artisan up
```

## 2. Check for Browser Issues

### Chrome DevTools:
1. Open Chrome DevTools (F12)
2. Go to Network tab
3. Check "Disable cache"
4. Hold reload button and select "Empty Cache and Hard Reload"

### Console Errors:
```javascript
// In console, check these:
window.Livewire  // Should exist
window.Alpine    // Should exist
document.querySelector('.fi-company-integration-portal')  // Should find element
```

## 3. Temporary Workaround

If you need it working NOW, create a simple version:

```bash
php artisan make:filament-page SimpleCompanyPortal
```

Then use basic HTML table instead of grid.

## 4. Server-Side Issues

### Check PHP errors:
```bash
tail -f storage/logs/laravel.log
```

### Check permissions:
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache
```

### Restart services:
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

## 5. Database Check

```sql
-- Check if companies exist
SELECT COUNT(*) FROM companies;

-- Check user permissions
SELECT * FROM model_has_roles WHERE model_id = YOUR_USER_ID;
```

## 6. Last Resort - Rollback

If absolutely nothing works:

```bash
# Revert the view
cd /var/www/api-gateway
git checkout resources/views/filament/admin/pages/company-integration-portal.blade.php

# Update PHP to use old view
# Edit app/Filament/Admin/Pages/CompanyIntegrationPortal.php
# Change: protected static string $view = 'filament.admin.pages.company-integration-portal';
```

## 7. Get Help

Take screenshots of:
1. The broken page
2. Browser console (F12)
3. Network tab showing any 404s
4. The output of: `php artisan about`

## Common Culprits

1. **CDN/Proxy**: CloudFlare caching old assets
2. **Browser Extensions**: Ad blockers, script blockers
3. **Corporate Firewall**: Blocking WebSocket/Livewire
4. **PHP Version**: Needs PHP 8.1+
5. **Missing Extensions**: php-mbstring, php-xml

## Quick Test URL

Try this simplified test:
```
/admin/companies
```

If this works but portal doesn't, it's view-specific.
If this also broken, it's system-wide.