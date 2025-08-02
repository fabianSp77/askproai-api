<?php
// Fix Business Portal Login - Create working demo account
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

echo "\n=== BUSINESS PORTAL LOGIN FIX ===\n\n";

// 1. Check existing portal users
echo "1. Checking existing portal users...\n";
$portalUsers = PortalUser::withoutGlobalScopes()->get();
echo "   Found " . $portalUsers->count() . " portal users\n";

foreach ($portalUsers as $user) {
    echo "   - {$user->email} (Company ID: {$user->company_id})\n";
}

// 2. Check if demo@askproai.de exists
echo "\n2. Checking demo@askproai.de account...\n";
$demoUser = PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();

if (\!$demoUser) {
    echo "   ❌ demo@askproai.de does not exist\n";
    echo "   Creating new demo user...\n";
    
    // Get or create company
    $company = Company::withoutGlobalScopes()->find(1);
    if (\!$company) {
        echo "   Creating company first...\n";
        $company = Company::create([
            'name' => 'Krückeberg Servicegruppe',
            'email' => 'info@krueckeberg.de',
            'phone' => '+49 30 12345678',
            'address' => 'Musterstraße 1',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country' => 'DE',
            'is_active' => true
        ]);
    }
    
    $demoUser = PortalUser::create([
        'name' => 'Demo User',
        'email' => 'demo@askproai.de',
        'password' => Hash::make('demo1234'),
        'company_id' => $company->id,
        'role' => 'admin',
        'is_active' => true,
        'email_verified_at' => now()
    ]);
    echo "   ✅ Created demo@askproai.de\n";
} else {
    echo "   ✅ demo@askproai.de exists\n";
    // Update password to ensure it's correct
    $demoUser->password = Hash::make('demo1234');
    $demoUser->is_active = true;
    $demoUser->email_verified_at = now();
    $demoUser->save();
    echo "   ✅ Password reset to: demo1234\n";
}

// 3. Create additional demo accounts
echo "\n3. Creating additional demo accounts...\n";

$demoAccounts = [
    [
        'email' => 'demo@example.com',
        'password' => 'password',
        'name' => 'Demo Example'
    ],
    [
        'email' => 'test@askproai.de',
        'password' => 'test1234',
        'name' => 'Test User'
    ]
];

foreach ($demoAccounts as $account) {
    $user = PortalUser::withoutGlobalScopes()->where('email', $account['email'])->first();
    
    if (\!$user) {
        PortalUser::create([
            'name' => $account['name'],
            'email' => $account['email'],
            'password' => Hash::make($account['password']),
            'company_id' => 1,
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        echo "   ✅ Created {$account['email']} / {$account['password']}\n";
    } else {
        $user->password = Hash::make($account['password']);
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();
        echo "   ✅ Updated {$account['email']} / {$account['password']}\n";
    }
}

echo "\n✅ Login fix complete\!\n";
echo "\n=== VERFÜGBARE LOGINS ===\n";
echo "1. demo@askproai.de / demo1234\n";
echo "2. demo@example.com / password\n";
echo "3. test@askproai.de / test1234\n";
EOF < /dev/null
