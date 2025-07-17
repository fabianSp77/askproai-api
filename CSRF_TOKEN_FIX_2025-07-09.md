# CSRF Token Fix - 419 Session Expired

## Problem
- 419 Session Expired error when trying to login to admin panel
- CSRF token mismatch between session and request

## Root Cause
- Session domain was set to `.askproai.de` (wildcard domain)
- But the application runs on `api.askproai.de`
- This mismatch caused sessions not to be properly stored/retrieved

## Solution
1. Changed SESSION_DOMAIN in .env from `.askproai.de` to `api.askproai.de`
2. Cleared all existing sessions
3. Cleared and rebuilt all caches

## Commands Executed
```bash
# 1. Update .env
SESSION_DOMAIN=api.askproai.de  # Changed from .askproai.de

# 2. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

# 3. Rebuild caches
php artisan config:cache
php artisan view:cache

# 4. Clear all sessions
mysql -u askproai_user -p'***' askproai_db -e "TRUNCATE TABLE sessions;"
```

## Result
- Login should now work properly
- Sessions will be correctly stored for api.askproai.de
- CSRF tokens will match between session and requests

## If Issues Persist
1. Clear browser cookies for askproai.de domain
2. Use incognito/private browsing mode
3. Make sure accessing via HTTPS (not HTTP)
4. Check browser console for any JavaScript errors