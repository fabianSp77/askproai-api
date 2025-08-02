<?php
/**
 * Direct session login - bypasses API and sets session directly
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $user = User::where('email', 'demo@askproai.de')->first();
    
    if ($user) {
        Auth::login($user);
        Session::save();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user->email,
            'session_id' => Session::getId()
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Session Login Test</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-box {
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            border-color: #c3e6cb;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            border-color: #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        button:hover {
            background: #0056b3;
        }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Direct Session Login Test</h1>
        
        <div class="status-box info">
            <h3>Current Status</h3>
            <?php
            $sessionId = Session::getId();
            $isAuthenticated = Auth::check();
            
            echo "<p>Session ID: <code>$sessionId</code></p>";
            
            if ($isAuthenticated) {
                $user = Auth::user();
                echo "<p class='success'><strong>✅ Authenticated as:</strong> " . $user->email . "</p>";
                echo "<p>User ID: " . $user->id . "</p>";
                echo "<p>Company ID: " . $user->company_id . "</p>";
            } else {
                echo "<p class='error'><strong>❌ Not authenticated</strong></p>";
            }
            ?>
        </div>
        
        <div class="status-box">
            <h3>Session Configuration</h3>
            <ul>
                <li>Driver: <code><?php echo config('session.driver'); ?></code></li>
                <li>Cookie: <code><?php echo config('session.cookie'); ?></code></li>
                <li>Domain: <code><?php echo config('session.domain') ?: 'not set'; ?></code></li>
                <li>Secure: <code><?php echo config('session.secure') ? 'true' : 'false'; ?></code></li>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <?php if (!$isAuthenticated): ?>
                <button onclick="directLogin()">🚀 Direct Login as Demo User</button>
            <?php else: ?>
                <button onclick="testAccess()">Test Admin Access</button>
                <button onclick="logout()">Logout</button>
            <?php endif; ?>
            
            <button onclick="location.reload()">🔄 Refresh Page</button>
        </div>
        
        <div id="results"></div>
        
        <?php if ($isAuthenticated): ?>
        <div class="status-box success">
            <h3>✅ You are logged in!</h3>
            <p>Now try accessing these pages:</p>
            <ul>
                <li><a href="/admin" target="_blank">Admin Dashboard</a></li>
                <li><a href="/admin/calls" target="_blank">Calls Page</a></li>
                <li><a href="/admin/appointments" target="_blank">Appointments Page</a></li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    function addResult(message, isSuccess = true) {
        const results = document.getElementById('results');
        const div = document.createElement('div');
        div.className = 'status-box ' + (isSuccess ? 'success' : 'error');
        div.innerHTML = '<p>' + message + '</p>';
        results.appendChild(div);
    }
    
    function directLogin() {
        addResult('Attempting direct login...', true);
        
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=login',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addResult('✅ ' + data.message + ' - Reloading...', true);
                setTimeout(() => location.reload(), 1000);
            } else {
                addResult('❌ ' + data.message, false);
            }
        })
        .catch(error => {
            addResult('❌ Error: ' + error.message, false);
        });
    }
    
    function testAccess() {
        window.open('/admin', '_blank');
    }
    
    function logout() {
        addResult('Logging out...', true);
        fetch('/logout', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '<?php echo csrf_token(); ?>'
            },
            credentials: 'same-origin'
        })
        .then(() => {
            setTimeout(() => location.reload(), 500);
        });
    }
    </script>
</body>
</html>