<?php
/**
 * Clear admin session conflicts
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Clear all auth sessions
session()->forget(['is_admin_viewing', 'admin_impersonation', 'portal_user_id']);
session()->forget('login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal'));
session()->forget('login_web_' . sha1('Illuminate\Auth\SessionGuard.web'));

// Clear portal auth
Auth::guard('portal')->logout();

// Clear web auth 
Auth::guard('web')->logout();

// Clear all session data
session()->flush();

echo "✅ Alle Sessions wurden gelöscht!\n";
echo "Sie können sich jetzt wieder normal einloggen:\n";
echo "- Admin Portal: https://api.askproai.de/admin/login\n";
echo "- Business Portal: https://api.askproai.de/business/login\n";