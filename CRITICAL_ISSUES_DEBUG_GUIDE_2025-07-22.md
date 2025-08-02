# 🚨 CRITICAL ISSUES - Debug Guide
**Erstellt für**: Sofortige Problembehebung nach Rückkehr
**Priorität**: HÖCHSTE

---

## 🔴 Problem 1: Business Portal Login - Server 500

### Schnelltest
```bash
# Test 1: Direct API Test
curl -X POST https://api.askproai.de/business/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"demo@askproai.de","password":"DemoPass2024!"}' \
  -i

# Test 2: Check Laravel Logs
tail -n 100 storage/logs/laravel.log | grep -A 10 -B 10 "500\|Error\|Exception"

# Test 3: PHP-FPM Logs
sudo tail -f /var/log/php8.3-fpm.log
```

### Mögliche Ursachen & Fixes

#### 1. JSON Input Reading Problem
**Check**: `app/Http/Controllers/Portal/Auth/LoginController.php`
```php
// Problem könnte hier sein:
$credentials = $request->validate([...]);

// Möglicher Fix:
$input = $request->all();
\Log::info('Login attempt', ['input' => $input]);
```

#### 2. Session Cookie Conflict
**Check**: Nach unseren Session-Änderungen
```bash
# Prüfe Session Config
php artisan tinker
>>> config('session.cookie')
>>> config('session_portal.cookie')
```

#### 3. Middleware Conflict
**Check**: `app/Http/Kernel.php`
```php
// Portal API group könnte problematisch sein
'portal.api' => [
    'throttle:api',
    \App\Http\Middleware\PortalCors::class,
    // Evtl. fehlt: 'bindings',
]
```

### QUICK FIX ATTEMPTS
```bash
# 1. Clear all caches
php artisan optimize:clear

# 2. Regenerate autoload
composer dump-autoload

# 3. Check file permissions
chown -R www-data:www-data storage/
chmod -R 775 storage/

# 4. Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

---

## 🔴 Problem 2: Admin Portal - Calls/Appointments Seiten laden ewig

### Performance Profiling
```bash
# 1. Enable Query Log
echo "DB_LOG_QUERIES=true" >> .env
php artisan config:cache

# 2. Use Telescope (if installed)
php artisan telescope:install
php artisan migrate

# 3. Manual Query Check
php artisan tinker
>>> \DB::enableQueryLog();
>>> \App\Models\Call::with(['company', 'branch', 'customer'])->paginate(20);
>>> dd(\DB::getQueryLog());
```

### Spezifische Dateien zum Prüfen

#### CallResource.php
```php
// app/Filament/Admin/Resources/CallResource.php
// Check table() method for:
- Missing ->with() eager loading
- Missing ->select() to limit columns  
- Missing ->limit() or paginate()
```

#### AppointmentResource.php
```php
// app/Filament/Admin/Resources/AppointmentResource.php
// Gleiches Problem vermutlich
```

### SOFORT-FIXES

#### 1. Add Eager Loading
```php
// In getTableQuery() or modifyQueryUsing()
return parent::getTableQuery()
    ->with(['company', 'branch', 'customer', 'service', 'staff'])
    ->latest()
    ->limit(100); // Temporary limit
```

#### 2. Add Missing Indexes
```sql
-- Check existing indexes
SHOW INDEXES FROM calls;
SHOW INDEXES FROM appointments;

-- Add if missing
ALTER TABLE calls ADD INDEX idx_company_created (company_id, created_at);
ALTER TABLE appointments ADD INDEX idx_company_date (company_id, scheduled_at);
```

#### 3. Disable Livewire Polling
```php
// In der Resource Blade View
// Suche nach: wire:poll
// Ändere zu: wire:poll.30s oder entferne
```

---

## 🛠️ EMERGENCY TOOLBOX

### Monitor Queries in Real-time
```php
// create: public/debug-queries.php
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

\DB::listen(function ($query) {
    echo "Query: " . $query->sql . "\n";
    echo "Time: " . $query->time . "ms\n";
    echo "Bindings: " . json_encode($query->bindings) . "\n\n";
});

// Then navigate to the slow page
```

### Quick Performance Check
```bash
# Create test script
cat > test-performance.php << 'EOF'
<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$start = microtime(true);

// Test Call query
$calls = \App\Models\Call::with(['company', 'branch', 'customer'])
    ->where('company_id', 1)
    ->latest()
    ->take(50)
    ->get();

$callTime = microtime(true) - $start;

// Test Appointment query  
$start = microtime(true);
$appointments = \App\Models\Appointment::with(['company', 'branch', 'customer', 'service', 'staff'])
    ->where('company_id', 1)
    ->latest() 
    ->take(50)
    ->get();

$appointmentTime = microtime(true) - $start;

echo "Calls Query: {$callTime}s for " . count($calls) . " records\n";
echo "Appointments Query: {$appointmentTime}s for " . count($appointments) . " records\n";
EOF

php test-performance.php
```

---

## 📊 HEALTH CHECK SCRIPT

```bash
# Create: health-check-critical.sh
#!/bin/bash

echo "=== CRITICAL SYSTEMS HEALTH CHECK ==="

echo -e "\n1. Business Portal Login Test"
response=$(curl -s -o /dev/null -w "%{http_code}" -X POST https://api.askproai.de/business/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@askproai.de","password":"DemoPass2024!"}')
echo "HTTP Status: $response"

echo -e "\n2. Database Connection"
php -r "
try {
    \$pdo = new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn');
    echo 'Database: OK\n';
    \$count = \$pdo->query('SELECT COUNT(*) FROM calls')->fetchColumn();
    echo 'Calls Count: ' . \$count . '\n';
} catch (Exception \$e) {
    echo 'Database: FAILED - ' . \$e->getMessage() . '\n';
}
"

echo -e "\n3. PHP-FPM Status"
sudo systemctl status php8.3-fpm | grep Active

echo -e "\n4. Recent Errors"
echo "Laravel Errors (last 5):"
grep -i "error\|exception" storage/logs/laravel.log | tail -5

echo -e "\n5. Disk Space"
df -h | grep -E "/$|/var"

echo -e "\n=== END HEALTH CHECK ==="
```

---

## 🎯 WENN ALLES ANDERE FEHLSCHLÄGT

### Nuclear Option 1: Rollback Session Changes
```bash
# Revert session-related commits
git log --oneline | grep -i session
# git revert <commit-hash>
```

### Nuclear Option 2: Bypass Authentication Temporarily
```php
// In routes/web.php - NUR FÜR DEBUGGING!
Route::get('/emergency-portal-access', function() {
    $user = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
    Auth::guard('portal')->login($user);
    return redirect('/business/dashboard');
});
```

### Nuclear Option 3: Enable Detailed Error Display
```bash
# In .env
APP_DEBUG=true
APP_ENV=local

# Then
php artisan config:cache
```

---

**WICHTIG**: Dokumentiere JEDE Änderung, die du machst, damit wir den Fix später richtig implementieren können!

*Viel Erfolg! Diese Issues MÜSSEN gelöst werden, bevor das System produktiv genutzt werden kann.* 🚨