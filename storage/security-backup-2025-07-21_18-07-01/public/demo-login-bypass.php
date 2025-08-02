<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;

// Login demo user directly
$user = PortalUser::withoutGlobalScopes()->where('email', 'demo2025@askproai.de')->first();
if ($user) {
    Auth::guard('portal')->login($user, true);
    session(['skip_2fa' => true]);
    header('Location: /business');
    exit;
}