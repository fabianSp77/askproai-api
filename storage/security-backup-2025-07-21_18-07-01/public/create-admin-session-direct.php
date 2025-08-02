<?php
// Direct session creation for admin access
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

// Create session data
$sessionData = [
    '_token' => csrf_token(),
    'login.web' => $user->id,
    'password_hash_web' => $user->getAuthPassword(),
    '_previous' => ['url' => 'https://api.askproai.de/admin'],
];

// Insert directly into sessions table
DB::table('sessions')->insert([
    'id' => $sessionId,
    'user_id' => $user->id,
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Mozilla/5.0 (Testing)',
    'payload' => base64_encode(serialize($sessionData)),
    'last_activity' => time(),
]);

// Verify insertion
$exists = DB::table('sessions')->where('id', $sessionId)->exists();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Admin Session</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #4CAF50; margin-bottom: 20px; }
        .session-box { background: #e8f5e9; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .curl-command { background: #263238; color: #aed581; padding: 15px; border-radius: 5px; font-family: monospace; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
        .links { margin-top: 30px; }
        .link-button { display: inline-block; margin: 10px; padding: 15px 30px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .link-button:hover { background: #1976D2; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="success">✅ Direct Admin Session Created!</h1>
        
        <div class="session-box">
            <h2>Session Details:</h2>
            <p><strong>User:</strong> <?php echo $user->email; ?></p>
            <p><strong>User ID:</strong> <?php echo $user->id; ?></p>
            <p><strong>Company:</strong> <?php echo $user->company->name ?? 'N/A'; ?></p>
            <p><strong>Session ID:</strong> <code><?php echo $sessionId; ?></code></p>
            <p><strong>Saved to DB:</strong> <?php echo $exists ? '✅ Yes' : '❌ No'; ?></p>
        </div>
        
        <div class="session-box" style="background: #fff8e1;">
            <h2>🔧 Test with cURL:</h2>
            <p>Use these commands to test the admin pages:</p>
            
            <p><strong>Test Dashboard:</strong></p>
            <div class="curl-command">curl -s -L -H "Cookie: askproai_session=<?php echo $sessionId; ?>" https://api.askproai.de/admin | grep -E "(Dashboard|<title>)" | head -5</div>
            
            <p><strong>Test Calls Page:</strong></p>
            <div class="curl-command">curl -s -L -H "Cookie: askproai_session=<?php echo $sessionId; ?>" https://api.askproai.de/admin/calls | grep -E "(Calls|<title>|table)" | head -5</div>
            
            <p><strong>Test Appointments:</strong></p>
            <div class="curl-command">curl -s -L -H "Cookie: askproai_session=<?php echo $sessionId; ?>" https://api.askproai.de/admin/appointments | grep -E "(Appointments|<title>)" | head -5</div>
        </div>
        
        <div class="links">
            <h2>Or Open in Browser:</h2>
            <p>First set the cookie in your browser console:</p>
            <div class="curl-command">document.cookie = "askproai_session=<?php echo $sessionId; ?>; path=/; domain=.askproai.de";</div>
            
            <p>Then visit these pages:</p>
            <a href="/admin" class="link-button" target="_blank">Admin Dashboard</a>
            <a href="/admin/calls" class="link-button" target="_blank">Calls</a>
            <a href="/admin/appointments" class="link-button" target="_blank">Appointments</a>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 5px;">
            <h3>Debug Info:</h3>
            <pre><?php
echo "Session driver: " . config('session.driver') . "\n";
echo "Sessions table count: " . DB::table('sessions')->count() . "\n";
echo "User sessions: " . DB::table('sessions')->where('user_id', $user->id)->count() . "\n";
            ?></pre>
        </div>
    </div>
</body>
</html>