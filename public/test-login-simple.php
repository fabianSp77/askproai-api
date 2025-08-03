<?php
/**
 * Simple Login Test Script
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
use Illuminate\Support\Facades\Auth;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h2>Login Attempt:</h2>";
    echo "Email: " . htmlspecialchars($email) . "<br>";
    
    // Find user
    $user = PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
        ->where('email', $email)
        ->first();
    
    if ($user) {
        echo "✅ User found (ID: {$user->id})<br>";
        echo "Active: " . ($user->is_active ? 'Yes' : 'No') . "<br>";
        echo "Company ID: {$user->company_id}<br>";
        
        // Check password
        if (Hash::check($password, $user->password)) {
            echo "✅ Password correct<br>";
            
            // Try to login
            Auth::guard('portal')->login($user);
            
            // Check if login worked
            if (Auth::guard('portal')->check()) {
                echo "✅ Login successful!<br>";
                echo "Auth user ID: " . Auth::guard('portal')->id() . "<br>";
                echo '<a href="/business/dashboard">Go to Dashboard</a>';
            } else {
                echo "❌ Login failed - Auth check failed<br>";
            }
        } else {
            echo "❌ Password incorrect<br>";
        }
    } else {
        echo "❌ User not found<br>";
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        input, button { display: block; width: 100%; margin: 10px 0; padding: 10px; }
        button { background: #3b82f6; color: white; border: none; cursor: pointer; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Simple Login Test</h1>
    
    <div class="info">
        <strong>Test Credentials:</strong><br>
        Email: demo@askproai.de<br>
        Password: password
    </div>
    
    <form method="POST">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <input type="email" name="email" placeholder="Email" value="demo@askproai.de" required>
        <input type="password" name="password" placeholder="Password" value="password" required>
        <button type="submit">Login</button>
    </form>
</body>
</html>