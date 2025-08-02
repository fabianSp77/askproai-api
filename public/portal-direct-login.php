<?php
// Direct login helper for Business Portal
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Start the session framework
$request->setLaravelSession($app['session.store']);

// Check for action
$action = $_GET['action'] ?? 'check';

if ($action === 'login') {
    // Force login as demo user
    $email = $_GET['email'] ?? 'demo@askproai.de';
    $user = \App\Models\PortalUser::withoutGlobalScopes()->where('email', $email)->first();
    
    if ($user) {
        // Login the user
        auth()->guard('portal')->login($user);
        
        // Set all necessary session data
        $sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
        session([$sessionKey => $user->id]);
        session(['portal_user_id' => $user->id]);
        session(['company_id' => $user->company_id]);
        
        // Force save the session
        session()->save();
        
        // Set response header to ensure cookie is sent
        header('Location: /business/dashboard');
        exit;
    } else {
        echo "User not found: $email";
    }
} elseif ($action === 'check') {
    // Check current authentication status
    $sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
    $user = auth()->guard('portal')->user();
    
    // Try to restore from session if not authenticated
    if (!$user && session()->has($sessionKey)) {
        $userId = session($sessionKey);
        $user = \App\Models\PortalUser::find($userId);
        if ($user) {
            auth()->guard('portal')->loginUsingId($userId, false);
        }
    }
    
    $data = [
        'authenticated' => auth()->guard('portal')->check(),
        'user' => $user ? [
            'id' => $user->id,
            'email' => $user->email,
            'company_id' => $user->company_id,
        ] : null,
        'session' => [
            'id' => session()->getId(),
            'name' => session()->getName(),
            'cookie' => request()->cookie('askproai_portal_session'),
            'has_auth_key' => session()->has($sessionKey),
            'auth_user_id' => session($sessionKey),
            'portal_user_id' => session('portal_user_id'),
            'company_id' => session('company_id'),
        ],
        'file' => [
            'path' => storage_path('framework/sessions/portal/' . session()->getId()),
            'exists' => file_exists(storage_path('framework/sessions/portal/' . session()->getId())),
        ]
    ];
    
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
} elseif ($action === 'dashboard') {
    // Direct dashboard access with session check
    if (auth()->guard('portal')->check()) {
        // Redirect to dashboard
        header('Location: /business/dashboard');
        exit;
    } else {
        // Try to restore from session
        $sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
        if (session()->has($sessionKey)) {
            $userId = session($sessionKey);
            $user = \App\Models\PortalUser::find($userId);
            if ($user) {
                auth()->guard('portal')->loginUsingId($userId, false);
                header('Location: /business/dashboard');
                exit;
            }
        }
        
        // Not authenticated, redirect to login
        header('Location: /business/login');
        exit;
    }
}

$kernel->terminate($request, $response);