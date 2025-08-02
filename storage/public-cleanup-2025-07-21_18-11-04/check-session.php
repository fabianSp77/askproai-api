<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Bootstrap Laravel with HTTP context
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

header('Content-Type: application/json');

// Get all session data
$sessionData = session()->all();

// Check authentication
$portalUser = Auth::guard('portal')->user();
$webUser = Auth::guard('web')->user();

$data = [
    'session_id' => session()->getId(),
    'session_exists' => session()->isStarted(),
    'csrf_token' => csrf_token(),
    'portal_auth' => [
        'check' => Auth::guard('portal')->check(),
        'user' => $portalUser ? [
            'id' => $portalUser->id,
            'email' => $portalUser->email,
            'name' => $portalUser->name
        ] : null
    ],
    'web_auth' => [
        'check' => Auth::guard('web')->check(),
        'user' => $webUser ? [
            'id' => $webUser->id,
            'email' => $webUser->email,
            'name' => $webUser->name
        ] : null
    ],
    'session_data' => [
        'has_portal_login' => isset($sessionData['portal_login']),
        'keys' => array_keys($sessionData)
    ],
    'cookies' => [
        'session_cookie' => $_COOKIE[config('session.cookie')] ?? 'not set',
        'all_cookies' => array_keys($_COOKIE)
    ],
    'request_info' => [
        'url' => $request->fullUrl(),
        'method' => $request->method(),
        'headers' => [
            'X-Requested-With' => $request->header('X-Requested-With'),
            'Accept' => $request->header('Accept'),
            'Cookie' => substr($request->header('Cookie'), 0, 50) . '...'
        ]
    ]
];

echo json_encode($data, JSON_PRETTY_PRINT);