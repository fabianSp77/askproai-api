<?php
// Quick fix for common test issues

// 1. Run migrations for test database
echo "Running test migrations...\n";
exec('php artisan migrate --env=testing --force');

// 2. Clear caches
echo "Clearing caches...\n";
exec('php artisan cache:clear --env=testing');
exec('php artisan config:clear --env=testing');

// 3. Regenerate autoload
echo "Regenerating autoload...\n";
exec('composer dump-autoload');

echo "✅ Quick fixes applied!\n";