# Essential Commands Quick Reference

## Daily Commands
```bash
# Database
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db

# Cache Management
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Build & Deploy
npm run build
php artisan filament:clear-cached-components

# Queue & Monitoring
php artisan horizon
php artisan horizon:status
tail -f storage/logs/laravel.log
```

## Testing
```bash
php artisan test
php artisan test --filter=FeatureName
php artisan test --parallel
```

## MCP Discovery
```bash
php artisan mcp:discover "task description"
php artisan mcp:health
```
