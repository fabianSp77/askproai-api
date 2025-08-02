<?php
// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;

// Force login the demo user
$user = PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();

if (\!$user) {
    // Create if not exists
    $user = PortalUser::create([
        'name' => 'Demo User',
        'email' => 'demo@askproai.de',
        'password' => bcrypt('demo123'),
        'company_id' => 1,
        'role' => 'admin',
        'is_active' => true,
        'email_verified_at' => now()
    ]);
}

// Force login
Auth::guard('portal')->login($user);
session(['portal_user_id' => $user->id]);
session(['portal_authenticated' => true]);
session()->regenerate();

// Set additional session keys that might be needed
$portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
session([$portalSessionKey => $user->id]);

// Redirect to business portal
header('Location: /business');
exit;
