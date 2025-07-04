<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    define('LARAVEL_START', microtime(true));

    // Register Composer autoloader
    require __DIR__.'/../vendor/autoload.php';

    // Bootstrap Laravel application
    $app = require_once __DIR__.'/../bootstrap/app.php';

    // Create a kernel instance but don't handle request through middleware
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Create request manually
    $request = Illuminate\Http\Request::capture();

// Only handle POST requests
if ($request->getMethod() !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

// Get JSON data
$data = $request->json()->all();

// Validate input
if (empty($data['email']) || empty($data['password'])) {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Email und Passwort sind erforderlich']);
    exit;
}

// Manually resolve dependencies and handle login
$app->make('db');

try {
    $user = \App\Models\User::where('email', $data['email'])->first();
    
    if (!$user || !\Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Ungültige Anmeldedaten']);
        exit;
    }
    
    // Create Sanctum token
    $token = $user->createToken('command-intelligence')->plainTextToken;
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Erfolgreich angemeldet',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
        ],
        'token' => $token,
        'token_type' => 'Bearer'
    ]);
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Server error',
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
    exit;
}
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Fatal error',
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
    exit;
}