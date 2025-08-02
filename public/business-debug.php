<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

// Create request for business calls
$request = Illuminate\Http\Request::create('/business/calls', 'GET');

// Copy all cookies
foreach ($_COOKIE as $name => $value) {
    $request->cookies->set($name, $value);
}

// Handle request through kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
} catch (\Exception $e) {
    $statusCode = 500;
    $content = $e->getMessage() . "\n" . $e->getTraceAsString();
}

// Get auth and session info
$portalAuth = auth()->guard('portal')->check();
$portalUser = auth()->guard('portal')->user();
$sessionData = session()->all();

$output = [
    'request' => [
        'url' => '/business/calls',
        'cookies' => array_keys($_COOKIE),
        'portal_session_cookie' => $_COOKIE['askproai_portal_session'] ?? 'NOT SET',
    ],
    'response' => [
        'status_code' => $statusCode,
        'error' => $statusCode === 500 ? substr($content, 0, 500) : null,
    ],
    'auth' => [
        'portal_authenticated' => $portalAuth,
        'portal_user' => $portalUser ? [
            'id' => $portalUser->id,
            'email' => $portalUser->email,
            'company_id' => $portalUser->company_id,
        ] : null,
    ],
    'session' => [
        'id' => session()->getId(),
        'all_keys' => array_keys($sessionData),
        'login_portal_key' => 'login_portal_' . sha1(\App\Models\PortalUser::class),
        'has_login_key' => isset($sessionData['login_portal_' . sha1(\App\Models\PortalUser::class)]),
        'portal_user_id' => $sessionData['portal_user_id'] ?? null,
    ],
    'config' => [
        'session_cookie' => config('session.cookie'),
        'session_driver' => config('session.driver'),
        'session_files' => config('session.files'),
    ],
    'company_context' => [
        'app_instance' => app()->has('current_company_id') ? app('current_company_id') : null,
        'session_company' => session('admin_impersonation.company_id'),
    ],
];

header('Content-Type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);