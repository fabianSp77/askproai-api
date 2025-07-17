<?php
// Direct Login - Bypasses Laravel completely

$host = '127.0.0.1';
$db = 'askproai_db';
$user = 'askproai_user';
$pass = 'lkZ57Dju9EDjrMxn';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Get user
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Create a simple cookie
            setcookie('admin_direct_login', $user['id'] . ':' . time(), time() + 3600, '/');
            
            // Create Laravel session manually
            $sessionId = bin2hex(random_bytes(20));
            setcookie('askproai_session', $sessionId, time() + 7200, '/', '', true, true);
            
            // Redirect to special route
            header('Location: /admin-direct-auth?uid=' . $user['id'] . '&token=' . $sessionId);
            exit;
        } else {
            $error = 'Invalid credentials';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Login</title>
    <meta name="csrf-token" content="bypass">
    <style>
        body { font-family: Arial; max-width: 400px; margin: 50px auto; }
        input, button { display: block; width: 100%; margin: 10px 0; padding: 10px; }
        .error { color: red; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Direct Login (Bypasses Laravel)</h2>
    
    <div class="info">
        This form bypasses Laravel/Livewire completely and creates a session manually.
    </div>
    
    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    
    <form method="POST">
        <input type="email" name="email" placeholder="Email" value="admin@askproai.de" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Direct Login</button>
    </form>
    
    <hr>
    <h3>Known Admin Users:</h3>
    <ul>
        <li>admin@askproai.de</li>
        <li>fabian@askproai.de</li>
        <li>superadmin@askproai.de</li>
    </ul>
</body>
</html>