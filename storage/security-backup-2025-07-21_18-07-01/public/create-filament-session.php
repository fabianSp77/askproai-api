<?php
// Create a proper Filament admin session
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Find demo user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();

if (!$user) {
    // Create demo user
    $company = \App\Models\Company::first();
    if (!$company) {
        die("No company found in database!");
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

// Start session
session()->start();

// Login the user using web guard (Filament uses web guard)
Auth::guard('web')->login($user);

// Set Filament-specific session data
session([
    'login.web' => $user->id,
    '_token' => csrf_token(),
    'password_hash_web' => $user->password,
]);

// Save session
session()->save();

$sessionId = session()->getId();

// Verify session was saved to database
$sessionExists = DB::table('sessions')->where('id', $sessionId)->exists();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Filament Session Created</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #4CAF50; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff8e1; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .links { margin-top: 30px; text-align: center; }
        .links a { display: inline-block; margin: 10px; padding: 15px 30px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .links a:hover { background: #1976D2; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .curl-example { background: #263238; color: #aed581; padding: 15px; border-radius: 5px; font-family: monospace; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="success">✅ Filament Admin Session Created!</h1>
        
        <div class="info">
            <h3>Session Details:</h3>
            <p><strong>User:</strong> <?php echo $user->email; ?></p>
            <p><strong>User ID:</strong> <?php echo $user->id; ?></p>
            <p><strong>Company:</strong> <?php echo $user->company->name ?? 'N/A'; ?></p>
            <p><strong>Session ID:</strong> <code><?php echo $sessionId; ?></code></p>
            <p><strong>Session in DB:</strong> <?php echo $sessionExists ? '✅ Yes' : '❌ No'; ?></p>
        </div>
        
        <div class="warning">
            <h3>⚠️ Important:</h3>
            <p>This session is tied to your current browser. To test with curl, use the session ID above:</p>
            <div class="curl-example">
curl -H "Cookie: askproai_session=<?php echo $sessionId; ?>" https://api.askproai.de/admin
            </div>
        </div>
        
        <div class="links">
            <h2>Test Admin Pages:</h2>
            <a href="/admin" target="_blank">Dashboard</a>
            <a href="/admin/calls" target="_blank">Calls</a>
            <a href="/admin/appointments" target="_blank">Appointments</a>
            <a href="/admin/customers" target="_blank">Customers</a>
        </div>
        
        <div style="margin-top: 30px;">
            <h3>Debug Information:</h3>
            <pre><?php
echo "Auth Guards:\n";
echo "- web: " . (Auth::guard('web')->check() ? '✅ Authenticated' : '❌ Not authenticated') . "\n";
echo "- portal: " . (Auth::guard('portal')->check() ? '✅ Authenticated' : '❌ Not authenticated') . "\n\n";

echo "Session Configuration:\n";
echo "- Driver: " . config('session.driver') . "\n";
echo "- Cookie Name: " . config('session.cookie') . "\n";
echo "- Domain: " . config('session.domain') . "\n";
echo "- Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";
echo "- HTTP Only: " . (config('session.http_only') ? 'Yes' : 'No') . "\n\n";

echo "Session Data:\n";
$sessionData = session()->all();
foreach ($sessionData as $key => $value) {
    if (is_string($value) || is_numeric($value)) {
        echo "- $key: $value\n";
    } else {
        echo "- $key: " . gettype($value) . "\n";
    }
}
            ?></pre>
        </div>
    </div>
</body>
</html>