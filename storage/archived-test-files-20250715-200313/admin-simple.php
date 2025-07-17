<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: /login-standalone.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Simplified</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f3f4f6; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #1f2937; }
        .menu { display: flex; gap: 20px; margin: 20px 0; }
        .menu a { padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; }
        .menu a:hover { background: #2563eb; }
        .info { background: #eff6ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Panel - Vereinfachter Modus</h1>
        <div class="info">
            ⚠️ Das normale Admin Panel hat technische Probleme. Dies ist eine vereinfachte Version.
        </div>
        
        <div class="menu">
            <a href="/admin/companies">Firmen verwalten</a>
            <a href="/admin/calls">Anrufe</a>
            <a href="/admin/appointments">Termine</a>
            <a href="/admin/users">Benutzer</a>
            <a href="/admin/settings">Einstellungen</a>
        </div>
        
        <h2>Schnellzugriff</h2>
        <ul>
            <li><a href="/horizon">Laravel Horizon (Queue Management)</a></li>
            <li><a href="/admin/business-portal-admin">Business Portal Admin</a></li>
            <li><a href="/logout">Abmelden</a></li>
        </ul>
    </div>
</body>
</html>