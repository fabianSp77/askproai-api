<?php
// Direct login bypass - no Laravel, no sessions, just direct access
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    
    // Hardcoded check for testing
    if ($email === "admin@askproai.de" && $password === "Admin123!") {
        // Set a simple cookie
        setcookie("bypass_auth", "admin_authenticated", time() + 3600, "/", "", true, true);
        header("Location: /admin");
        exit;
    } else {
        $message = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Login</title>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, sans-serif;
            background: #1a1a1a;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: #2d2d2d;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #10b981;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
        }
        input {
            width: 100%;
            padding: 12px;
            background: #1a1a1a;
            border: 1px solid #374151;
            border-radius: 6px;
            color: #fff;
            font-size: 16px;
        }
        input:focus {
            outline: none;
            border-color: #10b981;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #10b981;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #059669;
        }
        .error {
            background: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .info {
            background: #1e40af;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .info code {
            background: rgba(0,0,0,0.3);
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 System Access</h1>
        
        <?php if ($message): ?>
            <div class="error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="info">
            <strong>Test Credentials:</strong><br>
            Email: <code>admin@askproai.de</code><br>
            Password: <code>Admin123!</code>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="admin@askproai.de" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" value="Admin123!" required>
            </div>
            <button type="submit">Login</button>
        </form>
        
        <div style="margin-top: 30px; text-align: center; color: #6b7280;">
            <p>Alternative: <a href="/standalone-dashboard.html" style="color: #10b981;">Feature Dashboard</a></p>
        </div>
    </div>
</body>
</html>