#!/usr/bin/env php
<?php

echo "🔧 AskProAI Auth Issues Fix Script\n";
echo "==================================\n\n";

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$kernel->terminate($request, $response);

// Step 1: Check current configuration
echo "📋 Current Session Configuration:\n";
echo "  Driver: " . config('session.driver') . "\n";
echo "  Domain: " . config('session.domain') . "\n";
echo "  Cookie: " . config('session.cookie') . "\n";
echo "  Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";
echo "  Lifetime: " . config('session.lifetime') . " minutes\n\n";

// Step 2: Check database sessions
echo "📊 Session Database Status:\n";
try {
    $totalSessions = DB::table('sessions')->count();
    $activeSessions = DB::table('sessions')
        ->where('last_activity', '>', time() - (config('session.lifetime') * 60))
        ->count();
    $authenticatedSessions = DB::table('sessions')
        ->whereNotNull('user_id')
        ->where('last_activity', '>', time() - (config('session.lifetime') * 60))
        ->count();
    
    echo "  Total sessions: $totalSessions\n";
    echo "  Active sessions: $activeSessions\n";
    echo "  Authenticated sessions: $authenticatedSessions\n\n";
    
    // Show recent sessions
    echo "📝 Recent Sessions (last 5):\n";
    $recentSessions = DB::table('sessions')
        ->orderBy('last_activity', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($recentSessions as $session) {
        $age = time() - $session->last_activity;
        $ageStr = $age < 60 ? "{$age}s" : round($age/60) . "m";
        echo "  - {$session->id} (User: " . ($session->user_id ?: 'Guest') . ", Age: $ageStr)\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "  ❌ Error accessing sessions table: " . $e->getMessage() . "\n\n";
}

// Step 3: Test authentication
echo "🔐 Testing Authentication:\n";

// Test admin user
$adminUser = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($adminUser) {
    echo "  ✅ Admin user found (ID: {$adminUser->id})\n";
} else {
    echo "  ❌ Admin user not found\n";
}

// Test portal user
$portalUser = \App\Models\PortalUser::where('email', 'demo@example.com')->first();
if ($portalUser) {
    echo "  ✅ Demo portal user found (ID: {$portalUser->id})\n";
} else {
    echo "  ❌ Demo portal user not found\n";
}

echo "\n";

// Step 4: Fix recommendations
echo "🔧 Applying Fixes:\n";

// Clear all caches
echo "  1. Clearing all caches...\n";
\Illuminate\Support\Facades\Artisan::call('config:clear');
\Illuminate\Support\Facades\Artisan::call('cache:clear');
\Illuminate\Support\Facades\Artisan::call('view:clear');
\Illuminate\Support\Facades\Artisan::call('route:clear');
echo "     ✅ Caches cleared\n";

// Clean old sessions
echo "  2. Cleaning expired sessions...\n";
$deleted = DB::table('sessions')
    ->where('last_activity', '<', time() - (config('session.lifetime') * 60))
    ->delete();
echo "     ✅ Deleted $deleted expired sessions\n";

// Check .env file
echo "  3. Checking .env configuration...\n";
$envPath = base_path('.env');
$envContent = file_get_contents($envPath);

$issues = [];
if (strpos($envContent, 'SESSION_DOMAIN=api.askproai.de') !== false) {
    $issues[] = "SESSION_DOMAIN should be '.askproai.de' or removed";
}
if (strpos($envContent, 'SESSION_DRIVER=file') !== false) {
    $issues[] = "SESSION_DRIVER should be 'database' not 'file'";
}

if (empty($issues)) {
    echo "     ✅ .env configuration looks correct\n";
} else {
    echo "     ⚠️  Found issues:\n";
    foreach ($issues as $issue) {
        echo "        - $issue\n";
    }
}

echo "\n";

// Step 5: Test session creation
echo "🧪 Testing Session Creation:\n";
try {
    // Start a new session
    session()->regenerate();
    $sessionId = session()->getId();
    session()->put('test_key', 'test_value');
    session()->save();
    
    echo "  ✅ Created new session: $sessionId\n";
    
    // Verify in database
    $dbSession = DB::table('sessions')->where('id', $sessionId)->first();
    if ($dbSession) {
        echo "  ✅ Session found in database\n";
    } else {
        echo "  ❌ Session NOT found in database!\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error creating session: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 6: Recommendations
echo "📌 Recommendations:\n";
echo "  1. Update .env file:\n";
echo "     SESSION_DOMAIN=.askproai.de  (or remove this line)\n";
echo "     SESSION_DRIVER=database\n";
echo "     SESSION_SECURE_COOKIE=true\n";
echo "\n";
echo "  2. Run these commands:\n";
echo "     php artisan config:cache\n";
echo "     sudo systemctl restart php8.3-fpm\n";
echo "     sudo systemctl restart nginx\n";
echo "\n";
echo "  3. Test login at:\n";
echo "     https://api.askproai.de/portal-auth-debug.html\n";
echo "\n";

// Create quick fix script
$quickFix = <<<'BASH'
#!/bin/bash
echo "Applying quick fix..."

# Update SESSION_DOMAIN
sed -i 's/SESSION_DOMAIN=api.askproai.de/SESSION_DOMAIN=.askproai.de/' .env

# Clear and rebuild caches
php artisan config:clear
php artisan cache:clear
php artisan config:cache

# Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx

echo "Quick fix applied! Test login now."
BASH;

file_put_contents('quick-fix-auth.sh', $quickFix);
chmod('quick-fix-auth.sh', 0755);

echo "💡 Quick fix script created: ./quick-fix-auth.sh\n";
echo "   Run it with: ./quick-fix-auth.sh\n";

echo "\n✅ Analysis complete!\n";