<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Start session
session_start();

// Get all auth info
$portalUser = Auth::guard('portal')->user();
$webUser = Auth::guard('web')->user();
$sessionId = session()->getId();

// Get portal session key
$portalSessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);

header('Content-Type: application/json');
echo json_encode([
    'portal_auth' => [
        'authenticated' => Auth::guard('portal')->check(),
        'user' => $portalUser ? [
            'id' => $portalUser->id,
            'email' => $portalUser->email,
            'company_id' => $portalUser->company_id
        ] : null
    ],
    'web_auth' => [
        'authenticated' => Auth::guard('web')->check(),
        'user' => $webUser ? [
            'id' => $webUser->id,
            'email' => $webUser->email
        ] : null
    ],
    'session' => [
        'id' => $sessionId,
        'has_portal_key' => session()->has($portalSessionKey),
        'portal_user_id' => session($portalSessionKey),
        'all_keys' => array_keys(session()->all())
    ],
    'cookies' => array_keys($_COOKIE),
    'request_headers' => getallheaders()
], JSON_PRETTY_PRINT);