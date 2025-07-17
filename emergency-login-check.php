<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "\n🚨 EMERGENCY LOGIN CHECK\n";
echo "========================\n\n";

$email = 'fabianspitzer@icloud.com';
$password = 'demo123';

// 1. Check user exists
echo "1️⃣ USER CHECK:\n";
$user = PortalUser::where('email', $email)->first();

if (!$user) {
    echo "❌ USER NOT FOUND!\n";
    
    // Check if user was deleted
    $deletedUser = DB::table('portal_users')
        ->where('email', $email)
        ->whereNotNull('deleted_at')
        ->first();
        
    if ($deletedUser) {
        echo "⚠️  User was DELETED!\n";
    }
    
    // List all portal users
    echo "\nAll portal users:\n";
    $allUsers = PortalUser::all();
    foreach ($allUsers as $u) {
        echo "  - {$u->email} (ID: {$u->id}, Active: " . ($u->is_active ? 'YES' : 'NO') . ")\n";
    }
    
    exit(1);
}

echo "✅ User found: {$user->email}\n";
echo "   - ID: {$user->id}\n";
echo "   - Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
echo "   - Created: {$user->created_at}\n";
echo "   - Updated: {$user->updated_at}\n";

// 2. Check password
echo "\n2️⃣ PASSWORD CHECK:\n";
echo "   - Current hash: " . substr($user->password, 0, 30) . "...\n";
$isValid = Hash::check($password, $user->password);
echo "   - Password '$password' valid: " . ($isValid ? 'YES' : 'NO') . "\n";

if (!$isValid) {
    echo "\n🔧 FIXING: Setting password to 'demo123'...\n";
    $user->password = Hash::make('demo123');
    $user->save();
    echo "✅ Password updated!\n";
    
    // Verify
    $isValid = Hash::check('demo123', $user->password);
    echo "   - Verification: " . ($isValid ? 'SUCCESS' : 'FAILED') . "\n";
}

// 3. Check company
echo "\n3️⃣ COMPANY CHECK:\n";
$company = Company::find($user->company_id);
if (!$company) {
    echo "❌ Company not found!\n";
} else {
    echo "✅ Company: {$company->name}\n";
    echo "   - ID: {$company->id}\n";
    echo "   - Active: " . ($company->is_active ? 'YES' : 'NO') . "\n";
    
    if (!$company->is_active) {
        echo "\n🔧 FIXING: Activating company...\n";
        $company->is_active = true;
        $company->save();
        echo "✅ Company activated!\n";
    }
}

// 4. Check branch
echo "\n4️⃣ BRANCH CHECK:\n";
$branches = DB::table('branches')->where('company_id', $user->company_id)->get();
echo "   - Branch count: " . $branches->count() . "\n";
if ($branches->isNotEmpty()) {
    foreach ($branches as $branch) {
        echo "   - {$branch->name} (Active: " . ($branch->is_active ? 'YES' : 'NO') . ")\n";
    }
}

// 5. Test authentication
echo "\n5️⃣ AUTHENTICATION TEST:\n";
$attempt = Auth::guard('portal')->attempt([
    'email' => $email,
    'password' => 'demo123'
]);

echo "   - Auth attempt: " . ($attempt ? 'SUCCESS' : 'FAILED') . "\n";

if (!$attempt) {
    // Check what's wrong
    echo "\n❌ Authentication failed! Debugging...\n";
    
    // Check if user is being found by auth
    $foundUser = PortalUser::where('email', $email)->first();
    echo "   - User found by query: " . ($foundUser ? 'YES' : 'NO') . "\n";
    
    if ($foundUser) {
        echo "   - User active: " . ($foundUser->is_active ? 'YES' : 'NO') . "\n";
        echo "   - Password check: " . (Hash::check('demo123', $foundUser->password) ? 'VALID' : 'INVALID') . "\n";
    }
    
    // Try manual login
    if ($foundUser && $foundUser->is_active && Hash::check('demo123', $foundUser->password)) {
        Auth::guard('portal')->login($foundUser);
        echo "   - Manual login: " . (Auth::guard('portal')->check() ? 'SUCCESS' : 'FAILED') . "\n";
    }
}

// 6. Check LoginController logic
echo "\n6️⃣ LOGIN CONTROLLER CHECK:\n";
$loginControllerPath = app_path('Http/Controllers/Portal/Auth/LoginController.php');
if (file_exists($loginControllerPath)) {
    $content = file_get_contents($loginControllerPath);
    
    // Check if it's checking company status
    if (strpos($content, 'company->is_active') !== false) {
        echo "⚠️  LoginController checks company active status\n";
    }
    
    // Check if it has additional validation
    if (strpos($content, 'is_active') !== false) {
        echo "⚠️  LoginController checks user active status\n";
    }
}

echo "\n✅ Check complete!\n";