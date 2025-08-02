<?php
/**
 * Portal Login Fix - Direct Solution
 * 
 * This script ensures proper portal authentication and redirects to dashboard
 */

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Initialize kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a business portal request to ensure correct session config
$request = Illuminate\Http\Request::create('/business/dashboard', 'GET');
$response = $kernel->handle($request);

// Get action
$action = $_GET['action'] ?? 'login';

if ($action === 'login') {
    // Get credentials
    $email = $_GET['email'] ?? 'demo@askproai.de';
    $password = $_GET['password'] ?? 'password';
    
    // Find user
    $user = \App\Models\PortalUser::withoutGlobalScopes()->where('email', $email)->first();
    
    if (!$user) {
        die(json_encode(['error' => 'User not found: ' . $email]));
    }
    
    // Verify password
    if (!\Illuminate\Support\Facades\Hash::check($password, $user->password)) {
        die(json_encode(['error' => 'Invalid password']));
    }
    
    // Perform login
    auth()->guard('portal')->login($user);
    
    // Set session data
    $sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
    session([$sessionKey => $user->id]);
    session(['portal_user_id' => $user->id]);
    session(['company_id' => $user->company_id]);
    
    // Force session save
    session()->save();
    
    // Set cookie manually to ensure it's sent
    $cookieName = config('session.cookie');
    $cookieValue = session()->getId();
    $cookiePath = config('session.path');
    $cookieDomain = config('session.domain');
    $cookieSecure = config('session.secure');
    $cookieHttpOnly = config('session.http_only');
    
    // Set the cookie
    setcookie(
        $cookieName,
        $cookieValue,
        time() + (config('session.lifetime') * 60),
        $cookiePath,
        $cookieDomain,
        $cookieSecure,
        $cookieHttpOnly
    );
    
    // Debug output if requested
    if (isset($_GET['debug'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
            ],
            'session' => [
                'id' => session()->getId(),
                'cookie_name' => $cookieName,
                'cookie_set' => $cookieName . '=' . $cookieValue,
                'auth_check' => auth()->guard('portal')->check(),
            ],
            'next_step' => 'Redirect to dashboard'
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Redirect to dashboard
    header('Location: /business/dashboard');
    exit;
    
} elseif ($action === 'check') {
    // Check current auth status
    $sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
    $user = auth()->guard('portal')->user();
    
    header('Content-Type: application/json');
    echo json_encode([
        'authenticated' => auth()->guard('portal')->check(),
        'user' => $user ? [
            'id' => $user->id,
            'email' => $user->email,
            'company_id' => $user->company_id,
        ] : null,
        'session' => [
            'id' => session()->getId(),
            'cookie' => $_COOKIE[config('session.cookie')] ?? null,
            'has_auth_key' => session()->has($sessionKey),
            'auth_user_id' => session($sessionKey),
        ],
        'cookies' => array_keys($_COOKIE),
    ], JSON_PRETTY_PRINT);
    exit;
}

// Terminate
$kernel->terminate($request, $response);