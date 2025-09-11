# Laravel View Cache Ownership Crisis - Fixed September 2025

## Executive Summary
The AskProAI admin portal experienced catastrophic failures due to Laravel view cache files being created with incorrect ownership (root instead of www-data). This caused `filemtime() stat failed` errors that rendered the admin portal unusable.

## Root Cause Analysis

### The Vicious Cycle
1. **Root-owned processes** (cron jobs, artisan commands) created cache files owned by root
2. **PHP-FPM running as www-data** couldn't stat these files
3. **ViewServiceProvider's "helper" logic** attempted to fix by calling Artisan commands
4. **These commands ran as root** when triggered by cron, creating more root-owned files
5. **Cycle repeated** every 5-10 minutes, accumulating 672+ root-owned files

### Contributing Factors
- Multiple Laravel processes running as root since August 2025
- Cron jobs executing PHP artisan commands directly as root
- Auto-fix scripts that didn't preserve ownership
- ViewServiceProvider's aggressive cache recompilation logic

## The Permanent Fix

### 1. Ownership Correction
```bash
chown -R www-data:www-data /var/www/api-gateway/storage/
chown -R www-data:www-data /var/www/api-gateway/bootstrap/cache/
```

### 2. Process Management
- Terminated all root-owned PHP/artisan processes
- Configured supervisor to run horizon and scheduler as www-data
- Moved all cron jobs from root to www-data user

### 3. Safe Scripts
Created `/var/www/api-gateway/scripts/safe-cache-manager.sh` that:
- Always runs PHP commands as www-data
- Verifies ownership after operations
- Provides monitoring capabilities

### 4. Disabled Harmful Code
Commented out ViewServiceProvider's auto-fix logic that was creating root-owned files

### 5. Cron Job Configuration
www-data crontab now contains:
```cron
*/5 * * * * cd /var/www/api-gateway && /usr/bin/php artisan view:clear > /dev/null 2>&1
*/10 * * * * cd /var/www/api-gateway && /usr/bin/php artisan cache:clear > /dev/null 2>&1
0 */6 * * * cd /var/www/api-gateway && /usr/bin/php artisan optimize:clear > /dev/null 2>&1
```

## Prevention Measures

### Never Run As Root
- NEVER run `php artisan` commands as root
- NEVER set up cron jobs for Laravel as root
- ALWAYS use `sudo -u www-data` when running artisan manually

### Monitoring Commands
```bash
# Check for root-owned files
find /var/www/api-gateway/storage -user root | wc -l

# Monitor cache health
/var/www/api-gateway/scripts/safe-cache-manager.sh monitor

# Safe cache reset
/var/www/api-gateway/scripts/safe-cache-manager.sh reset
```

### Supervisor Services
All Laravel background processes now run via supervisor as www-data:
- horizon (queue processing)
- scheduler (cron replacement)
- laravel-cache-monitor
- view-cache-monitor

## Warning Signs
If you see these symptoms, ownership issues have returned:
- `filemtime(): stat failed` errors
- Empty admin dashboard
- HTTP 500 errors on admin pages
- Files in storage/framework/views owned by root

## Emergency Recovery
If issues recur:
```bash
# Run the safe cache manager
/var/www/api-gateway/scripts/safe-cache-manager.sh reset

# Check for root processes
ps aux | grep "php artisan" | grep root

# Verify supervisor services
supervisorctl status
```

## Key Takeaways
1. **Ownership matters**: All Laravel files must be owned by the web server user
2. **Process context is critical**: Background jobs inherit the user context they're started with
3. **Monitoring is essential**: Regular ownership checks prevent accumulation of issues
4. **Automation must be safe**: Scripts that "help" can make things worse if not properly designed

## GitHub Issue Reference
This fix resolves GitHub Issue #652: Admin Portal Navigation Disaster

---
Document created: September 6, 2025
Author: Claude with SuperClaude Framework
Status: RESOLVED