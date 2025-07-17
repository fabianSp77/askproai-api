<?php
// Test Business Portal Login with CSRF handling

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Start session
session_start();

// Handle GET request - show form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Generate CSRF token
    if (!isset($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['_token'];
    
    ?>
<!DOCTYPE html>
<html>
<head>
    <title>Business Portal Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 400px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="email"], input[type="password"] { 
            width: 100%; padding: 8px; border: 1px solid #ddd; 
        }
        button { 
            background: #3B82F6; color: white; padding: 10px 20px; 
            border: none; cursor: pointer; width: 100%; 
        }
        button:hover { background: #2563EB; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
        .info { background: #f0f0f0; padding: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Business Portal Login Test</h1>
        
        <div class="info">
            <strong>Test Credentials:</strong><br>
            Email: demo@example.com<br>
            Password: password123
        </div>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="/test-business-portal-login.php">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="demo@example.com" required>
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" value="password123" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <hr style="margin-top: 30px;">
        <p><small>CSRF Token: <?= substr($csrfToken, 0, 10) ?>...</small></p>
    </div>
</body>
</html>
    <?php
    exit;
}

// Handle POST request - process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create request
    $request = Illuminate\Http\Request::create('/business/login', 'POST', $_POST);
    $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
    
    // Set session
    $request->setLaravelSession(app('session'));
    app()->instance('request', $request);
    
    // Bootstrap
    $response = $kernel->handle($request);
    
    // Get login controller
    $controller = new \App\Http\Controllers\Portal\Auth\LoginController();
    
    try {
        // Validate CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== $_SESSION['_token']) {
            throw new Exception('CSRF token mismatch');
        }
        
        // Attempt login
        $loginResponse = $controller->login($request);
        
        // Check response
        if ($loginResponse instanceof \Illuminate\Http\RedirectResponse) {
            $targetUrl = $loginResponse->getTargetUrl();
            
            if (str_contains($targetUrl, 'dashboard')) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful!',
                    'redirect' => $targetUrl,
                    'user' => [
                        'email' => $_POST['email'],
                        'auth_check' => \Illuminate\Support\Facades\Auth::guard('portal')->check()
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Login failed - unexpected redirect',
                    'redirect' => $targetUrl
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Login failed',
                'errors' => method_exists($loginResponse, 'errors') ? $loginResponse->errors() : []
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    // Terminate
    $kernel->terminate($request, $response);
}