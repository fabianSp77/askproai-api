<?php
// Ultra-simple direct login without any framework
session_start();

// Hardcoded DB credentials for emergency
$host = '127.0.0.1';
$db = 'askproai_db';
$user = 'askproai_user';
$pass = 'lkZ57Dju9EDjrMxn';

$error = '';
$debug = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Test connection first
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $debug[] = "✓ Database connection successful";
        
        // Find user
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            $debug[] = "✓ User found: " . $userData['email'];
            
            if (password_verify($password, $userData['password'])) {
                $debug[] = "✓ Password correct";
                
                // Set session
                $_SESSION['admin_user_id'] = $userData['id'];
                $_SESSION['admin_user_email'] = $userData['email'];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['csrf_bypass'] = true;
                
                // Set cookies
                setcookie('admin_auth', base64_encode($userData['id'] . ':' . time()), time() + 86400, '/', '', true, true);
                
                // Success - redirect
                header('Location: /admin');
                exit;
            } else {
                $error = 'Falsches Passwort';
                $debug[] = "✗ Password verification failed";
            }
        } else {
            $error = 'Benutzer nicht gefunden';
            $debug[] = "✗ No user found with email: $email";
        }
    } catch (PDOException $e) {
        $error = 'Datenbankfehler: ' . $e->getMessage();
        $debug[] = "✗ PDO Error: " . $e->getMessage();
    } catch (Exception $e) {
        $error = 'Fehler: ' . $e->getMessage();
        $debug[] = "✗ General Error: " . $e->getMessage();
    }
}

// Test DB connection on page load
try {
    $testPdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $testStmt = $testPdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $testStmt->fetch(PDO::FETCH_ASSOC);
    $dbStatus = "✓ DB OK - {$userCount['count']} users found";
} catch (Exception $e) {
    $dbStatus = "✗ DB Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Admin Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .container { background: #2a2a2a; padding: 40px; border-radius: 10px; box-shadow: 0 0 30px rgba(0,0,0,0.5); width: 100%; max-width: 450px; }
        h1 { margin: 0 0 30px 0; text-align: center; color: #4ade80; }
        input { width: 100%; padding: 15px; margin: 10px 0; background: #1a1a1a; border: 2px solid #404040; border-radius: 5px; color: #fff; font-size: 16px; box-sizing: border-box; }
        input:focus { outline: none; border-color: #4ade80; }
        button { width: 100%; padding: 15px; background: #4ade80; color: #000; border: none; border-radius: 5px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        button:hover { background: #22c55e; }
        .error { background: #dc2626; color: #fff; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .debug { background: #1a1a1a; padding: 15px; border-radius: 5px; margin-top: 20px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto; }
        .status { text-align: center; margin-bottom: 20px; padding: 10px; background: #1a1a1a; border-radius: 5px; font-family: monospace; }
        .status.ok { color: #4ade80; }
        .status.error { color: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔓 Direct Admin Login</h1>
        
        <div class="status <?php echo strpos($dbStatus, '✓') !== false ? 'ok' : 'error'; ?>">
            <?php echo htmlspecialchars($dbStatus); ?>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="email" name="email" placeholder="E-Mail" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            <input type="password" name="password" placeholder="Passwort" required>
            <button type="submit">EINLOGGEN</button>
        </form>
        
        <?php if (!empty($debug)): ?>
            <div class="debug">
                <strong>Debug Info:</strong><br>
                <?php foreach ($debug as $msg): ?>
                    <?php echo htmlspecialchars($msg); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>