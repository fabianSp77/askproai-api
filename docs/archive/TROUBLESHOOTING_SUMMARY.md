# Troubleshooting Summary - Admin Panel Issues

## Issues Encountered & Solutions Applied

### 1. ✅ **500 Errors on View Pages (FIXED)**
**Problem**: ViewRecord pages were trying to use custom Blade templates that had syntax errors
**Solution**: Removed custom `$view` property from all ViewRecord pages to use default Filament rendering

### 2. ✅ **405 Method Not Allowed (FIXED)**  
**Problem**: Create/Edit pages were using custom forms with incorrect HTTP method handling
**Solution**: Removed custom view references from CreateWorkingHour and EditWorkingHour pages

### 3. ✅ **404 Errors (FIXED)**
**Problem**: Resources were using German slugs ('filialen', 'benutzer') instead of English
**Solution**: Changed slugs to English ('branches', 'users')

### 4. ✅ **Missing Infolists (FIXED)**
**Problem**: ViewRecord pages had no infolist configuration after removing custom views
**Solution**: Added `infolist()` methods to all resources that needed them

### 5. ⚠️ **View Cache Permission Issues (PARTIALLY FIXED)**
**Problem**: Laravel view cache files have permission issues causing intermittent 500 errors
**Solutions Applied**:
- Cleared all view caches
- Fixed ownership (www-data:www-data)
- Set proper permissions (775)
- Restarted PHP-FPM service

## Current Status

✅ **Working internally** - All pages test successfully when accessed via CLI
⚠️ **Intermittent browser access issues** - May still show 500 errors due to view caching

## Correct URLs

### All pages are now accessible at:
- https://api.askproai.de/admin/branches/[id]
- https://api.askproai.de/admin/staff/[id]  
- https://api.askproai.de/admin/companies/[id]
- https://api.askproai.de/admin/users/[id]
- https://api.askproai.de/admin/working-hours/create

## Commands to Fix Remaining Issues

If you encounter 500 errors, run these commands:

```bash
# Clear all caches
php artisan optimize:clear

# Fix permissions
chown -R www-data:www-data /var/www/api-gateway/storage
chown -R www-data:www-data /var/www/api-gateway/bootstrap/cache
chmod -R 775 /var/www/api-gateway/storage
chmod -R 775 /var/www/api-gateway/bootstrap/cache

# Restart services
systemctl restart php8.3-fpm
systemctl reload nginx
```

## Files Modified

1. `/app/Filament/Admin/Resources/BranchResource.php` - Added infolist, changed slug
2. `/app/Filament/Admin/Resources/StaffResource.php` - Added infolist  
3. `/app/Filament/Admin/Resources/CompanyResource.php` - Added infolist
4. `/app/Filament/Admin/Resources/UserResource.php` - Added infolist, changed slug
5. All ViewRecord pages - Removed custom view references
6. All Create/Edit pages for WorkingHour - Removed custom view references

Last Updated: 2025-09-04 21:00