<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Get the actual session key name
$guard = Auth::guard('web');
$reflection = new ReflectionClass($guard);
$method = $reflection->getMethod('getName');
$method->setAccessible(true);
$sessionKey = $method->invoke($guard);

// Also calculate it manually
$name = 'web'; // default guard name
$class = 'Illuminate\Auth\SessionGuard';
$manualKey = 'login_' . $name . '_' . sha1($class);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Auth Key</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #000; color: #0f0; }
        .info { background: #111; padding: 20px; margin: 10px 0; border: 1px solid #0f0; }
        .error { color: #f00; }
        .success { color: #0f0; }
    </style>
</head>
<body>
    <h1>Auth Session Key Debug</h1>
    
    <div class="info">
        <h3>Calculated Session Keys:</h3>
        <p>From Guard->getName(): <strong><?= htmlspecialchars($sessionKey) ?></strong></p>
        <p>Manual Calculation: <strong><?= htmlspecialchars($manualKey) ?></strong></p>
        <p>SHA1 of Class: <strong><?= sha1($class) ?></strong></p>
    </div>
    
    <div class="info">
        <h3>Current Session Data:</h3>
        <pre><?php
        $session = app('session.store');
        $data = $session->all();
        foreach ($data as $key => $value) {
            echo htmlspecialchars($key) . " => ";
            if (is_array($value)) {
                echo json_encode($value);
            } else {
                echo htmlspecialchars(substr((string)$value, 0, 60));
            }
            echo "\n";
        }
        ?></pre>
    </div>
    
    <div class="info">
        <h3>Auth Check with Correct Key:</h3>
        <?php
        $userId = $session->get($sessionKey);
        echo "<p>User ID from session key '$sessionKey': " . ($userId ?: 'NULL') . "</p>";
        
        // Try to login with that ID
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                echo "<p class='success'>User found: " . htmlspecialchars($user->email) . "</p>";
                
                // Set the user on the guard
                $guard->setUser($user);
                
                echo "<p>Auth::check() after setUser: " . (Auth::check() ? 'TRUE' : 'FALSE') . "</p>";
            } else {
                echo "<p class='error'>User with ID $userId not found in database!</p>";
            }
        } else {
            echo "<p class='error'>No user ID in session!</p>";
        }
        ?>
    </div>
    
    <div class="info">
        <h3>Fix Actions:</h3>
        <a href="?fix=true" style="color: #0f0;">Click here to fix the session key</a>
    </div>
    
    <?php
    if (isset($_GET['fix'])) {
        // Get demo user
        $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
        if ($user) {
            // Set with correct key
            $session->put($sessionKey, $user->id);
            $session->put('password_hash_' . $name, $user->password);
            $session->save();
            
            echo '<div class="info success">';
            echo '<h3>✓ Session Fixed!</h3>';
            echo '<p>Set session key: ' . htmlspecialchars($sessionKey) . ' = ' . $user->id . '</p>';
            echo '<p><a href="/admin" style="color: #0f0;">Go to Admin</a></p>';
            echo '</div>';
        }
    }
    ?>
</body>
</html>