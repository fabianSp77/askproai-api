<?php

header('Content-Type: application/json');

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if this is the login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['email']) && isset($data['password'])) {
    // Bootstrap Laravel
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('/test', 'GET');
    $response = $kernel->handle($request);
    $kernel->bootstrap();
    
    // Find user without any scopes
    $user = \App\Models\PortalUser::withoutGlobalScopes()
        ->where('email', $data['email'])
        ->first();
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    // Check password
    if (!\Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid password'
        ]);
        exit;
    }
    
    // Check if active
    if (!$user->is_active) {
        echo json_encode([
            'success' => false,
            'message' => 'Account is inactive'
        ]);
        exit;
    }
    
    // Force login
    \Illuminate\Support\Facades\Auth::guard('portal')->login($user);
    session(['portal_user_id' => $user->id]);
    session(['portal_company_id' => $user->company_id]);
    session()->save();
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'company_id' => $user->company_id
        ],
        'redirect' => '/business/dashboard',
        'session_id' => session()->getId()
    ]);
    
    $kernel->terminate($request, $response);
    exit;
}

// Not a login request
echo json_encode(['error' => 'Invalid request']);