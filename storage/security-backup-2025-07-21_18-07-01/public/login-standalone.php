<?php
// Completely standalone login - no Laravel dependencies
error_reporting(0); // Suppress all errors for clean output
ini_set('display_errors', 0);

session_start();

// Database connection
$host = '127.0.0.1';
$db = 'askproai_db';
$user = 'askproai_user';
$pass = 'lkZ57Dju9EDjrMxn';

$message = '';
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        
        // Find user
        $stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData && password_verify($password, $userData['password'])) {
            // Set session
            $_SESSION['admin_user_id'] = $userData['id'];
            $_SESSION['admin_email'] = $userData['email'];
            $_SESSION['admin_auth'] = true;
            $_SESSION['login_time'] = time();
            
            // Set cookie for 30 days
            setcookie('admin_auth_standalone', base64_encode($userData['id'] . '|' . time()), time() + (30 * 24 * 60 * 60), '/', '', true, true);
            
            $message = 'Login erfolgreich! Weiterleitung...';
            header('Refresh: 2; url=/admin');
        } else {
            $error = true;
            $message = 'Ungültige Anmeldedaten';
        }
    } catch (Exception $e) {
        $error = true;
        $message = 'Verbindungsfehler';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standalone Admin Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            color: #1a202c;
            margin-bottom: 0.5rem;
            font-size: 1.875rem;
            text-align: center;
        }
        .subtitle {
            color: #718096;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.875rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            color: #4a5568;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        button:hover {
            background: #5a67d8;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .message {
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
        }
        .message.success {
            background: #c6f6d5;
            color: #276749;
            border: 1px solid #9ae6b4;
        }
        .message.error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        .alternatives {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        .alternatives a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.875rem;
            margin: 0 0.5rem;
        }
        .alternatives a:hover {
            text-decoration: underline;
        }
        .info {
            background: #ebf8ff;
            color: #2c5282;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Login</h1>
        <p class="subtitle">Standalone Login System</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $error ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" required autofocus 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Anmelden</button>
        </form>
        
        <div class="info">
            Dieser Login umgeht alle Laravel-Systeme und arbeitet direkt mit der Datenbank.
            Nach erfolgreichem Login werden Sie zum Admin-Panel weitergeleitet.
        </div>
        
        <div class="alternatives">
            <a href="/admin">Standard Login</a> |
            <a href="/admin-direct-login.php">Direct Login</a> |
            <a href="/admin-emergency-login.php">Emergency Login</a>
        </div>
    </div>
</body>
</html>