<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

// Create a request for business portal
$request = Illuminate\Http\Request::create('/business/debug', 'GET');

// Copy cookies from current request
foreach ($_COOKIE as $name => $value) {
    $request->cookies->set($name, $value);
}

// Handle request through kernel to initialize session properly
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);

// Now check session state
$sessionData = [];
$auth = [];

try {
    // Session info
    $sessionData = [
        'id' => session()->getId(),
        'name' => session()->getName(),
        'all_data' => session()->all(),
        'portal_user_id' => session('portal_user_id'),
        'login_portal_key' => 'login_portal_' . sha1(\App\Models\PortalUser::class),
        'login_portal_value' => session('login_portal_' . sha1(\App\Models\PortalUser::class)),
    ];
    
    // Auth info
    $auth = [
        'portal_check' => auth()->guard('portal')->check(),
        'portal_user' => auth()->guard('portal')->user() ? [
            'id' => auth()->guard('portal')->user()->id,
            'email' => auth()->guard('portal')->user()->email,
            'name' => auth()->guard('portal')->user()->name,
        ] : null,
        'web_check' => auth()->guard('web')->check(),
    ];
    
    // Check session files
    $portalSessionId = $_COOKIE['askproai_portal_session'] ?? null;
    $sessionFiles = [];
    
    if ($portalSessionId) {
        $sessionPath = storage_path('framework/sessions/portal/' . $portalSessionId);
        if (file_exists($sessionPath)) {
            $sessionFiles['portal'] = [
                'exists' => true,
                'path' => $sessionPath,
                'size' => filesize($sessionPath),
                'modified' => date('Y-m-d H:i:s', filemtime($sessionPath)),
                'content' => unserialize(file_get_contents($sessionPath)),
            ];
        } else {
            $sessionFiles['portal'] = [
                'exists' => false,
                'path' => $sessionPath,
            ];
        }
    }
    
    // Check default session
    $defaultSessionId = $_COOKIE['askproai_session'] ?? null;
    if ($defaultSessionId) {
        $defaultPath = storage_path('framework/sessions/' . $defaultSessionId);
        if (file_exists($defaultPath)) {
            $sessionFiles['default'] = [
                'exists' => true,
                'path' => $defaultPath,
                'size' => filesize($defaultPath),
                'modified' => date('Y-m-d H:i:s', filemtime($defaultPath)),
            ];
        }
    }
    
} catch (\Exception $e) {
    $sessionData['error'] = $e->getMessage();
}

$output = [
    'cookies' => [
        'portal_session' => $_COOKIE['askproai_portal_session'] ?? 'NOT SET',
        'default_session' => $_COOKIE['askproai_session'] ?? 'NOT SET',
        'xsrf_token' => $_COOKIE['XSRF-TOKEN'] ?? 'NOT SET',
    ],
    'config' => [
        'session_cookie' => config('session.cookie'),
        'session_path' => config('session.path'),
        'session_domain' => config('session.domain'),
        'session_driver' => config('session.driver'),
        'session_files' => config('session.files'),
    ],
    'session' => $sessionData,
    'auth' => $auth,
    'session_files' => $sessionFiles,
    'debug' => [
        'middleware_groups' => app('router')->getMiddlewareGroups()['business-portal'] ?? [],
    ],
];

header('Content-Type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);