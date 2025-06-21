<?php
// Simple test to verify login form is working
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Login Form</title>
</head>
<body>
    <h1>Test Login Form</h1>
    <form method="POST" action="/admin/login">
        <input type="hidden" name="_token" value="<?php echo $_GET['token'] ?? 'no-token'; ?>">
        <div>
            <label>Email:</label>
            <input type="email" name="email" value="fabian@askproai.de">
        </div>
        <div>
            <label>Password:</label>
            <input type="password" name="password" value="Qwe421as1!1">
        </div>
        <div>
            <button type="submit">Login</button>
        </div>
    </form>
    
    <h2>Debug Info:</h2>
    <ul>
        <li>Session ID: <?php echo session_id() ?: 'No session'; ?></li>
        <li>Cookies: <pre><?php print_r($_COOKIE); ?></pre></li>
    </ul>
</body>
</html>