<?php
/**
 * Emergency Admin Access Script
 * This bypasses Laravel/Filament and creates a direct login
 */

// Start session
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin-emergency-access.php');
    exit;
}

// Database connection
$db_host = '127.0.0.1';
$db_name = 'askproai_db';
$db_user = 'askproai_user';
$db_pass = 'lkZ57Dju9EDjrMxn';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Find user
    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_user_name'] = $user['name'];
        $_SESSION['admin_user_email'] = $user['email'];
        
        // Set Laravel session
        setcookie('askproai_admin_session', 'emergency_' . uniqid(), time() + 7200, '/', '', true, true);
        
        header('Location: /admin-emergency-dashboard.php');
        exit;
    } else {
        $error = "Invalid credentials";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Admin Access</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            margin: 0 0 30px;
            font-size: 24px;
            color: #1f2937;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #374151;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #d97706;
        }
        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info {
            background: #e0f2fe;
            border: 1px solid #7dd3fc;
            color: #0369a1;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Emergency Admin Access</h1>
        
        <div class="info">
            ⚠️ Emergency access mode - Use only if normal admin panel is unavailable
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus 
                       value="admin@askproai.de" placeholder="admin@askproai.de">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>