<?php
// Direct Business Portal Access
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;

// Force demo user login
$user = PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();
if ($user) {
    Auth::guard('portal')->login($user);
    session(['portal_authenticated' => true]);
    session(['portal_user_id' => $user->id]);
    session()->regenerate();
}

// Redirect to proper business URL
header('Location: /business');
exit;