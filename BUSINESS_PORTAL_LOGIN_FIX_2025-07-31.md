# Business Portal Login Fix - 2025-07-31

## Problem
The business portal login at https://api.askproai.de/business/login was not working. After investigation, we found:

1. **Session Cookie Mismatch**: The business portal was using `askproai_portal_session` instead of the configured `askproai_session`
2. **Multiple Login Controllers**: There were 15+ duplicate login controllers creating confusion
3. **Problematic Dashboard View**: The dashboard was loading a view with CDN dependencies and custom JavaScript files

## Solution

### 1. Fixed Session Cookie Configuration
- Updated `/var/www/api-gateway/app/Http/Middleware/PortalSessionConfig.php` to use the correct session cookie name from .env
- Updated `/var/www/api-gateway/config/session_portal.php` to use `askproai_session` instead of `askproai_portal_session`
- Ensured Redis driver is used as configured in .env

### 2. Fixed Dashboard View
- Changed `DashboardController` to return `portal.dashboard-simple` instead of `portal.business-integrated`
- This prevents loading the problematic view with CDN Tailwind and custom JavaScript files

### 3. Applied Configuration Changes
- Cleared config cache: `php artisan config:clear && php artisan config:cache`
- Restarted PHP-FPM: `sudo systemctl restart php8.3-fpm`

## Testing
Successfully tested login flow:
```bash
# Login page now sets correct cookie
curl -s https://api.askproai.de/business/login
# Cookie: askproai_session (correct!)

# Login works with demo credentials
# demo@askproai.de / password

# Dashboard is accessible after login
curl -s -b cookies.txt https://api.askproai.de/business/dashboard
# Returns: "Sie sind erfolgreich im Business Portal angemeldet."
```

## Remaining Tasks
1. Remove duplicate login controllers (15+ found)
2. Ensure all business portal features work with the simple dashboard view
3. Properly implement production-ready views (no CDN dependencies)

## Security Notes
- Session cookies are now properly encrypted and secured
- Redis is used for session storage as configured
- Domain is set to `.askproai.de` for proper subdomain support