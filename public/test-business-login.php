<?php
/**
 * Test Business Portal Login
 * Direct login test bypassing potential middleware issues
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

// Get credentials from request
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $email && $password) {
    // Try to authenticate
    $user = PortalUser::where('email', $email)->first();
    
    if ($user && Hash::check($password, $user->password)) {
        // Success - start session
        session_start();
        $_SESSION['portal_user_id'] = $user->id;
        $_SESSION['portal_user_email'] = $user->email;
        
        echo "Login successful! User: " . $user->email . "<br>";
        echo "Session ID: " . session_id() . "<br>";
        echo '<a href="/business/dashboard">Go to Dashboard</a>';
    } else {
        echo "Login failed! Invalid credentials.<br>";
        if (!$user) {
            echo "User not found with email: " . htmlspecialchars($email) . "<br>";
        } else {
            echo "Password check failed.<br>";
        }
    }
    
    // Log attempt
    Log::info('Test login attempt', [
        'email' => $email,
        'user_found' => $user ? 'yes' : 'no',
        'password_correct' => $user && Hash::check($password, $user->password) ? 'yes' : 'no'
    ]);
    
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Business Login</title>
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        input { width: 100%; padding: 10px; margin: 10px 0; }
        button { width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; cursor: pointer; }
        button:hover { background: #2563eb; }
        .info { background: #f3f4f6; padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Test Business Portal Login</h1>
    
    <div class="info">
        <strong>Test Credentials:</strong><br>
        Email: demo@askproai.de<br>
        Password: password
    </div>
    
    <form method="POST">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        
        <label>Email:</label>
        <input type="email" name="email" required value="demo@askproai.de">
        
        <label>Password:</label>
        <input type="password" name="password" required value="password">
        
        <button type="submit">Login</button>
    </form>
    
    <div class="info">
        <strong>Debug Info:</strong><br>
        Session Name: <?php echo session_name(); ?><br>
        Session ID: <?php echo session_id() ?: 'Not started'; ?><br>
        PHP Session Path: <?php echo ini_get('session.save_path'); ?><br>
    </div>
</body>
</html>