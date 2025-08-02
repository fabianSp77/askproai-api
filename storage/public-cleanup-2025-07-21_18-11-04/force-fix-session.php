<?php
// Force Fix Session - Direct Approach

// Set custom session save path
ini_set('session.save_path', __DIR__ . '/../storage/framework/sessions');
ini_set('session.gc_probability', 0); // Disable garbage collection

// Start PHP session first
session_name('askproai_session');
session_start();

// Now bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Override Laravel's session with our PHP session
$request->setLaravelSession(
    $session = $app->make('session')->driver()
);
$session->setId(session_id());
$session->start();

$response = $kernel->handle($request);

// Handle actions
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'login') {
        $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
        if ($user) {
            // PHP Session
            $_SESSION['user_id'] = $user->id;
            $_SESSION['logged_in'] = true;
            
            // Laravel Auth
            \Illuminate\Support\Facades\Auth::login($user);
            
            // Laravel Session
            $session->put('user_id', $user->id);
            $session->put(Auth::getName(), $user->id);
            $session->save();
            
            // Force write PHP session
            session_write_close();
            
            header('Location: /admin');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Fix Session</title>
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
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; text-align: center; }
        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            margin: 20px 0;
            background: #007bff;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
        }
        .btn:hover { background: #0056b3; }
        .status {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            font-family: monospace;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Force Fix Session</h1>
        
        <div class="status">
            <h3>Current Status:</h3>
            <?php
            echo "PHP Session ID: " . session_id() . "<br>";
            echo "PHP Session Active: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "<br>";
            echo "Laravel Session ID: " . $session->getId() . "<br>";
            echo "Laravel Session Started: " . ($session->isStarted() ? 'YES' : 'NO') . "<br>";
            echo "Auth Check: " . (Auth::check() ? 'YES - ' . Auth::user()->email : 'NO') . "<br>";
            ?>
        </div>
        
        <a href="?action=login" class="btn">Force Login as demo@askproai.de</a>
        
        <div class="status">
            <h3>Session Data:</h3>
            <pre><?php
            echo "PHP Session:\n";
            print_r($_SESSION);
            echo "\n\nLaravel Session:\n";
            print_r($session->all());
            ?></pre>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <p>After clicking "Force Login", you should be redirected to the admin panel and stay logged in.</p>
        </div>
    </div>
</body>
</html>

<?php
$kernel->terminate($request, $response);
?>