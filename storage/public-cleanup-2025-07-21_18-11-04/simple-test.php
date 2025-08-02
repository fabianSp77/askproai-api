<?php
// Ensure clean output
ob_clean();
header('Content-Type: application/json');

try {
    // Bootstrap Laravel
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::capture();
    $response = $kernel->handle($request);
    
    // Force session config
    config(['session.secure' => false]);
    
    // Get session and auth
    $session = app('session');
    $auth = app('auth');
    
    $user = $auth->guard('portal')->user();
    
    $data = [
        'success' => true,
        'authenticated' => (bool) $user,
        'user' => $user ? [
            'id' => $user->id,
            'email' => $user->email
        ] : null,
        'session' => [
            'id' => $session->getId(),
            'portal_user_id' => $session->get('portal_user_id'),
            'has_data' => !empty($session->all())
        ]
    ];
    
    echo json_encode($data);
    exit;
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}