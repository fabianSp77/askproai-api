<?php
// Create Permanent Admin Session

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Str;

echo "=== Creating Permanent Admin Session ===\n\n";

// Find admin user
$admin = User::where('email', 'admin@askproai.de')
    ->orWhere('email', 'fabian@askproai.de')
    ->first();

if (!$admin) {
    die("No admin user found!\n");
}

echo "Found admin user: {$admin->email} (ID: {$admin->id})\n\n";

// Create session in database
$sessionId = Str::random(40);
$payload = base64_encode(serialize([
    '_token' => Str::random(40),
    '_previous' => ['url' => 'https://api.askproai.de/admin'],
    '_flash' => ['old' => [], 'new' => []],
    'url' => [],
    'login_web_' . sha1('Illuminate\Auth\SessionGuard.web') => $admin->id,
    'password_hash_web' => $admin->password,
]));

// Delete old sessions for this user
DB::table('sessions')->where('user_id', $admin->id)->delete();

// Insert new session
DB::table('sessions')->insert([
    'id' => $sessionId,
    'user_id' => $admin->id,
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Mozilla/5.0',
    'payload' => $payload,
    'last_activity' => time(),
]);

echo "Session created in database with ID: $sessionId\n\n";
echo "=== IMPORTANT ===\n";
echo "1. Open your browser\n";
echo "2. Go to: https://api.askproai.de\n";
echo "3. Open Browser Developer Tools (F12)\n";
echo "4. Go to Application/Storage -> Cookies\n";
echo "5. Create a new cookie:\n";
echo "   - Name: askproai_session\n";
echo "   - Value: $sessionId\n";
echo "   - Domain: api.askproai.de\n";
echo "   - Path: /\n";
echo "   - Secure: Yes\n";
echo "   - HttpOnly: Yes\n";
echo "   - SameSite: Lax\n\n";
echo "6. Then go to: https://api.askproai.de/admin\n";
echo "\nYou should be logged in!\n";