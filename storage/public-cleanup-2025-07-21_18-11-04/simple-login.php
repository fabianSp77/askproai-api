<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

try {
    // Step 1: Load Composer
    if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
        throw new Exception('Vendor autoload not found');
    }
    require __DIR__.'/../vendor/autoload.php';
    
    // Step 2: Load Laravel
    if (!file_exists(__DIR__.'/../bootstrap/app.php')) {
        throw new Exception('Bootstrap app not found');
    }
    $app = require __DIR__.'/../bootstrap/app.php';
    
    // Step 3: Boot the application
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Step 4: Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON input');
    }
    
    if (empty($data['email']) || empty($data['password'])) {
        http_response_code(422);
        echo json_encode(['message' => 'Email und Passwort sind erforderlich']);
        exit;
    }
    
    // Step 5: Find user
    $user = \App\Models\User::where('email', $data['email'])->first();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['message' => 'Benutzer nicht gefunden']);
        exit;
    }
    
    // Step 6: Check password
    if (!\Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
        http_response_code(401);
        echo json_encode(['message' => 'Falsches Passwort']);
        exit;
    }
    
    // Step 7: Create token
    $token = $user->createToken('command-intelligence')->plainTextToken;
    
    // Success!
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
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Error',
        'error' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Fatal Error',
        'error' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
}