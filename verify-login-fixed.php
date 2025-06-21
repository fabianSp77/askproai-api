<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "Login Verification After Fix\n";
echo "============================\n\n";

// 1. Check user and password
$user = User::where('email', 'fabian@askproai.de')->first();
$passwordOk = $user && Hash::check('Qwe421as1!1', $user->password);
echo "1. User exists: " . ($user ? "✅ YES" : "❌ NO") . "\n";
echo "2. Password correct: " . ($passwordOk ? "✅ YES" : "❌ NO") . "\n";

// 2. Check session configuration
echo "3. Session secure cookies: " . (config('session.secure') ? "❌ ENABLED (bad for HTTP)" : "✅ DISABLED (good for HTTP)") . "\n";
echo "4. Session driver: " . config('session.driver') . "\n";

// 3. Test authentication
$attempt = Auth::attempt(['email' => 'fabian@askproai.de', 'password' => 'Qwe421as1!1']);
echo "5. Authentication test: " . ($attempt ? "✅ SUCCESS" : "❌ FAILED") . "\n";

if ($attempt) {
    Auth::logout();
}

// 4. Check Filament
$canAccessPanel = $user && method_exists($user, 'canAccessPanel') && $user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin'));
echo "6. Can access Filament: " . ($canAccessPanel ? "✅ YES" : "❌ NO") . "\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "RESULT: ";

if ($passwordOk && !config('session.secure') && $attempt && $canAccessPanel) {
    echo "✅ ALL CHECKS PASSED!\n\n";
    echo "You should now be able to login at:\n";
    echo "- URL: /admin/login\n";
    echo "- Email: fabian@askproai.de\n";
    echo "- Password: Qwe421as1!1\n";
} else {
    echo "❌ SOME CHECKS FAILED\n\n";
    echo "Please review the failures above.\n";
}

echo "\nNOTE: For production, always use HTTPS and enable secure cookies!\n";