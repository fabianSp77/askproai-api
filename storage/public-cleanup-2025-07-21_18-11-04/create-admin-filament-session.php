<?php
// Create a proper Filament admin session with correct table and cookie
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Find or create demo user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();

if (!$user) {
    $company = \App\Models\Company::first();
    if (!$company) {
        die("No company found!");
    }
    
    $user = \App\Models\User::create([
        'name' => 'Demo User',
        'email' => 'demo@askproai.de',
        'password' => bcrypt('demo2024!'),
        'company_id' => $company->id,
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);
}

// Generate session ID
$sessionId = Str::random(40);

// Create session data that Filament expects
$sessionData = [
    '_token' => csrf_token(),
    'login.web' => $user->id,
    'password_hash_web' => $user->getAuthPassword(),
    '_previous' => ['url' => 'https://api.askproai.de/admin'],
];

// Check if admin_sessions table exists
$adminTableExists = Schema::hasTable('admin_sessions');
$sessionTable = $adminTableExists ? 'admin_sessions' : 'sessions';
$cookieName = $adminTableExists ? 'askproai_admin_session' : 'askproai_session';

// Insert into the appropriate sessions table
DB::table($sessionTable)->insert([
    'id' => $sessionId,
    'user_id' => $user->id,
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Mozilla/5.0 (Testing)',
    'payload' => base64_encode(serialize($sessionData)),
    'last_activity' => time(),
]);

// Verify insertion
$exists = DB::table($sessionTable)->where('id', $sessionId)->exists();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Filament Admin Session Created</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #4CAF50; margin-bottom: 20px; }
        .info-box { background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .warning-box { background: #fff8e1; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .curl-command { background: #263238; color: #aed581; padding: 15px; border-radius: 5px; font-family: monospace; overflow-x: auto; white-space: pre-wrap; word-break: break-all; margin: 10px 0; }
        .links { margin-top: 30px; text-align: center; }
        .link-button { display: inline-block; margin: 10px; padding: 15px 30px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .link-button:hover { background: #1976D2; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        .important { color: #d32f2f; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="success">✅ Filament Admin Session Created Successfully!</h1>
        
        <div class="info-box">
            <h2>Session Details:</h2>
            <p><strong>User:</strong> <?php echo $user->email; ?></p>
            <p><strong>User ID:</strong> <?php echo $user->id; ?></p>
            <p><strong>Company:</strong> <?php echo $user->company->name ?? 'N/A'; ?></p>
            <p><strong>Session ID:</strong> <code><?php echo $sessionId; ?></code></p>
            <p><strong>Session Table:</strong> <code><?php echo $sessionTable; ?></code> <?php echo $adminTableExists ? '(Admin-specific)' : '(Standard)'; ?></p>
            <p><strong>Cookie Name:</strong> <code class="important"><?php echo $cookieName; ?></code></p>
            <p><strong>Saved to DB:</strong> <?php echo $exists ? '✅ Yes' : '❌ No'; ?></p>
        </div>
        
        <div class="warning-box">
            <h2>🔧 Test with cURL:</h2>
            <p class="important">Important: Use the correct cookie name "<?php echo $cookieName; ?>"</p>
            
            <p><strong>Test Dashboard:</strong></p>
            <div class="curl-command">curl -s -L -H "Cookie: <?php echo $cookieName; ?>=<?php echo $sessionId; ?>" https://api.askproai.de/admin | grep -E "(Dashboard|Welcome|<title>)" | head -10</div>
            
            <p><strong>Test Calls Page:</strong></p>
            <div class="curl-command">curl -s -L -H "Cookie: <?php echo $cookieName; ?>=<?php echo $sessionId; ?>" https://api.askproai.de/admin/calls | grep -E "(Calls|Anrufe|<title>|table)" | head -10</div>
            
            <p><strong>Test Appointments:</strong></p>
            <div class="curl-command">curl -s -L -H "Cookie: <?php echo $cookieName; ?>=<?php echo $sessionId; ?>" https://api.askproai.de/admin/appointments | grep -E "(Appointments|Termine|<title>)" | head -10</div>
            
            <p><strong>Get Full Page (Save to File):</strong></p>
            <div class="curl-command">curl -s -L -H "Cookie: <?php echo $cookieName; ?>=<?php echo $sessionId; ?>" https://api.askproai.de/admin/calls -o /tmp/admin-calls.html && echo "Saved to /tmp/admin-calls.html"</div>
        </div>
        
        <div class="links">
            <h2>Or Open in Browser:</h2>
            <p>First set the cookie in your browser console:</p>
            <div class="curl-command">document.cookie = "<?php echo $cookieName; ?>=<?php echo $sessionId; ?>; path=/; domain=.askproai.de";</div>
            
            <p>Then visit these pages:</p>
            <a href="/admin" class="link-button" target="_blank">Admin Dashboard</a>
            <a href="/admin/calls" class="link-button" target="_blank">Calls</a>
            <a href="/admin/appointments" class="link-button" target="_blank">Appointments</a>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 5px;">
            <h3>Debug Info:</h3>
            <pre><?php
echo "Session Configuration:\n";
echo "- Default driver: " . config('session.driver') . "\n";
echo "- Admin driver: " . config('session_admin.driver') . "\n";
echo "- Default cookie: " . config('session.cookie') . "\n";
echo "- Admin cookie: " . config('session_admin.cookie') . "\n";
echo "- Default table: " . config('session.table') . "\n";
echo "- Admin table: " . config('session_admin.table') . "\n\n";

echo "Database Tables:\n";
echo "- sessions table exists: " . (Schema::hasTable('sessions') ? 'Yes' : 'No') . "\n";
echo "- admin_sessions table exists: " . (Schema::hasTable('admin_sessions') ? 'Yes' : 'No') . "\n\n";

echo "Session Counts:\n";
if (Schema::hasTable('sessions')) {
    echo "- Total sessions: " . DB::table('sessions')->count() . "\n";
    echo "- User sessions: " . DB::table('sessions')->where('user_id', $user->id)->count() . "\n";
}
if (Schema::hasTable('admin_sessions')) {
    echo "- Total admin sessions: " . DB::table('admin_sessions')->count() . "\n";
    echo "- User admin sessions: " . DB::table('admin_sessions')->where('user_id', $user->id)->count() . "\n";
}
            ?></pre>
        </div>
    </div>
</body>
</html>