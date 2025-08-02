<?php
// Create a working admin session with proper Redis storage
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Find or create demo user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();
if (!$user) {
    $company = \App\Models\Company::first();
    $user = \App\Models\User::create([
        'name' => 'Demo User',
        'email' => 'demo@askproai.de',
        'password' => bcrypt('demo2024!'),
        'company_id' => $company->id,
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);
}

// Create a new session
$sessionId = Str::random(40);

// Build session data
$sessionData = [
    '_token' => csrf_token(),
    'login.web' => $user->id,
    'password_hash_web' => $user->getAuthPassword(),
    '_previous' => ['url' => 'https://api.askproai.de/admin'],
];

// Store in Redis if available
if (config('session.driver') === 'redis') {
    // Use Redis directly
    $redis = app('redis');
    $key = config('cache.prefix') . ':' . config('session.cookie') . ':' . $sessionId;
    $redis->set($key, serialize($sessionData));
    $redis->expire($key, config('session.lifetime') * 60);
    
    $stored = 'Redis';
} else {
    // Fallback to database
    DB::table('sessions')->insert([
        'id' => $sessionId,
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => base64_encode(serialize($sessionData)),
        'last_activity' => time(),
    ]);
    
    $stored = 'Database';
}

// Generate curl commands
$cookieName = config('session.cookie');
$baseUrl = 'https://api.askproai.de';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Working Admin Session Created</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #4CAF50; }
        .success-box { background: #e8f5e9; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .info-box { background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .command-box { background: #263238; color: #aed581; padding: 15px; border-radius: 5px; font-family: monospace; overflow-x: auto; margin: 10px 0; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #1976D2; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        table th { background: #f5f5f5; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Working Admin Session Created!</h1>
        
        <div class="success-box">
            <h2>Session Details:</h2>
            <table>
                <tr><th>Property</th><th>Value</th></tr>
                <tr><td>User Email</td><td><?php echo $user->email; ?></td></tr>
                <tr><td>Company</td><td><?php echo $user->company->name ?? 'N/A'; ?></td></tr>
                <tr><td>Session ID</td><td><code><?php echo $sessionId; ?></code></td></tr>
                <tr><td>Storage</td><td><?php echo $stored; ?></td></tr>
                <tr><td>Cookie Name</td><td><code><?php echo $cookieName; ?></code></td></tr>
            </table>
        </div>
        
        <div class="info-box">
            <h2>🧪 Test Commands:</h2>
            
            <h3>1. Test Dashboard:</h3>
            <div class="command-box">curl -s -L -H "Cookie: <?php echo $cookieName; ?>=<?php echo $sessionId; ?>" <?php echo $baseUrl; ?>/admin | grep -o "&lt;title&gt;[^&lt;]*&lt;/title&gt;" | sed 's/&lt;[^&gt;]*&gt;//g'</div>
            
            <h3>2. Test Calls Page (with data):</h3>
            <div class="command-box">curl -s -L -H "Cookie: <?php echo $cookieName; ?>=<?php echo $sessionId; ?>" <?php echo $baseUrl; ?>/admin/calls 2>&1 | grep -E "(Anrufe|Calls|table|tbody)" | head -10</div>
            
            <h3>3. Save Full Page:</h3>
            <div class="command-box">curl -s -L -H "Cookie: <?php echo $cookieName; ?>=<?php echo $sessionId; ?>" <?php echo $baseUrl; ?>/admin/calls -o admin-calls.html && echo "Saved to admin-calls.html"</div>
            
            <h3>4. Check if Authenticated:</h3>
            <div class="command-box">curl -s -I -H "Cookie: <?php echo $cookieName; ?>=<?php echo $sessionId; ?>" <?php echo $baseUrl; ?>/admin | grep -E "(HTTP|Location)"</div>
        </div>
        
        <div class="grid">
            <div>
                <h3>📱 Browser Access:</h3>
                <p>Set cookie in browser console:</p>
                <div class="command-box">document.cookie = "<?php echo $cookieName; ?>=<?php echo $sessionId; ?>; path=/; domain=.askproai.de";</div>
                <p>Then visit:</p>
                <a href="/admin" class="btn" target="_blank">Admin Dashboard</a>
                <a href="/admin/calls" class="btn" target="_blank">Calls</a>
            </div>
            
            <div>
                <h3>📊 Quick Stats:</h3>
                <?php
                $callCount = \App\Models\Call::where('company_id', $user->company_id)->count();
                $appointmentCount = \App\Models\Appointment::where('company_id', $user->company_id)->count();
                $customerCount = \App\Models\Customer::where('company_id', $user->company_id)->count();
                ?>
                <table>
                    <tr><td>Total Calls</td><td><?php echo $callCount; ?></td></tr>
                    <tr><td>Total Appointments</td><td><?php echo $appointmentCount; ?></td></tr>
                    <tr><td>Total Customers</td><td><?php echo $customerCount; ?></td></tr>
                </table>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 5px;">
            <h3>🔍 Debug Info:</h3>
            <pre><?php
echo "PHP Session Handler: " . ini_get('session.save_handler') . "\n";
echo "Session Save Path: " . ini_get('session.save_path') . "\n";
echo "Session Cookie Params:\n";
$params = session_get_cookie_params();
foreach ($params as $key => $value) {
    echo "  - $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
}

if (config('session.driver') === 'redis') {
    echo "\nRedis Connection Test:\n";
    try {
        $redis = app('redis');
        $redis->ping();
        echo "  - Redis: ✅ Connected\n";
        
        // Check if session exists in Redis
        $key = config('cache.prefix') . ':' . config('session.cookie') . ':' . $sessionId;
        if ($redis->exists($key)) {
            echo "  - Session in Redis: ✅ Found\n";
            echo "  - TTL: " . $redis->ttl($key) . " seconds\n";
        } else {
            echo "  - Session in Redis: ❌ Not found\n";
        }
    } catch (\Exception $e) {
        echo "  - Redis: ❌ " . $e->getMessage() . "\n";
    }
}
            ?></pre>
        </div>
    </div>
</body>
</html>