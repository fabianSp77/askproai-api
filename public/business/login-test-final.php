<?php
// Create a simple login test that bypasses Laravel views
require_once __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create a proper Laravel request
    $loginRequest = Illuminate\Http\Request::create(
        '/business/login',
        'POST',
        [
            '_token' => $_POST['_token'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? ''
        ],
        $_COOKIE,
        [],
        $_SERVER
    );
    
    // Set the session
    $loginRequest->setLaravelSession(app('session.store'));
    
    // Get the login controller
    $controller = new \App\Http\Controllers\Portal\Auth\LoginController();
    
    // Process login
    $loginResponse = $controller->login($loginRequest);
    
    // Send the response
    $loginResponse->send();
    $kernel->terminate($loginRequest, $loginResponse);
    exit;
}

// Generate CSRF token
$token = csrf_token();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Business Portal Login - Direct Test</title>
    <meta charset="UTF-8">
</head>
<body>
    <h1>Business Portal Login - Direct Test</h1>
    
    <form method="POST" action="">
        <input type="hidden" name="_token" value="<?php echo $token; ?>">
        
        <div>
            <label>Email:</label>
            <input type="email" name="email" value="demo@askproai.de" required>
        </div>
        
        <div>
            <label>Password:</label>
            <input type="password" name="password" value="password" required>
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <hr>
    
    <h2>Debug Info:</h2>
    <pre>
Session ID: <?php echo session()->getId(); ?>

Auth Guard (portal): <?php echo auth()->guard('portal')->check() ? 'AUTHENTICATED' : 'NOT AUTHENTICATED'; ?>

<?php if (auth()->guard('portal')->check()): ?>
User: <?php echo auth()->guard('portal')->user()->email; ?>
<?php endif; ?>

CSRF Token: <?php echo substr($token, 0, 20); ?>...

Session Keys: <?php echo implode(', ', array_keys(session()->all())); ?>
    </pre>
</body>
</html>
<?php
$kernel->terminate($request, $response);