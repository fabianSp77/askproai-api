<?php
/**
 * Create Portal Session Script
 * Creates a valid portal session that persists properly
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "=== CREATING PERSISTENT PORTAL SESSION ===\n\n";

// Create test users with different roles
$users = [
    [
        'email' => 'test@askproai.de',
        'password' => 'Test123!',
        'name' => 'Test Admin',
        'role' => 'admin'
    ],
    [
        'email' => 'demo@askproai.de',
        'password' => 'Demo123!',
        'name' => 'Demo User',
        'role' => 'admin'
    ],
    [
        'email' => 'portal@askproai.de',
        'password' => 'Portal123!',
        'name' => 'Portal Test',
        'role' => 'staff'
    ]
];

foreach ($users as $userData) {
    $user = PortalUser::where('email', $userData['email'])->first();
    
    if (!$user) {
        $user = PortalUser::create([
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'name' => $userData['name'],
            'company_id' => 1,
            'is_active' => true,
            'role' => $userData['role'],
            'permissions' => json_encode([
                'calls.view_all' => true,
                'billing.view' => true,
                'billing.manage' => true,
                'appointments.view_all' => true,
                'customers.view_all' => true
            ])
        ]);
        echo "✅ Created user: {$userData['email']}\n";
    } else {
        // Update password
        $user->password = Hash::make($userData['password']);
        $user->is_active = true;
        $user->save();
        echo "✅ Updated user: {$userData['email']}\n";
    }
}

echo "\n=== LOGIN CREDENTIALS ===\n";
foreach ($users as $userData) {
    echo "Email: {$userData['email']}\n";
    echo "Password: {$userData['password']}\n\n";
}

// Create direct access link
$token = bin2hex(random_bytes(32));
\Illuminate\Support\Facades\Cache::put('portal_direct_access_' . $token, $users[0]['email'], 3600);

echo "=== DIRECT ACCESS LINKS ===\n\n";
echo "🚀 Direct Access (Recommended):\n";
echo "https://api.askproai.de/portal-direct-access.php\n\n";

echo "📝 Normal Login:\n";
echo "https://api.askproai.de/business/login\n\n";

echo "=== TEST INSTRUCTIONS ===\n\n";
echo "1. Öffnen Sie den Direct Access Link\n";
echo "2. Sie werden automatisch zur Anrufliste weitergeleitet\n";
echo "3. Testen Sie die neuen Features:\n";
echo "   - Audio Player (Play Button bei jedem Anruf)\n";
echo "   - Transkript Toggle (Dokument Icon)\n";
echo "   - Übersetzung (Globus Icon)\n";
echo "   - Call Details (Klick auf Anruf)\n";
echo "   - Stripe Integration (Billing Seite)\n\n";

// Test authentication
echo "=== TESTING AUTHENTICATION ===\n";
$credentials = ['email' => 'test@askproai.de', 'password' => 'Test123!'];
if (Auth::guard('portal')->attempt($credentials)) {
    echo "✅ Authentication test successful!\n";
    $user = Auth::guard('portal')->user();
    echo "Logged in as: {$user->name} ({$user->email})\n";
    Auth::guard('portal')->logout();
} else {
    echo "❌ Authentication test failed!\n";
}

echo "\nDone!\n";