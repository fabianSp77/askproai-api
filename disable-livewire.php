<?php
echo "=== DISABLING LIVEWIRE IN ADMIN PANEL ===\n\n";

// 1. Backup original files
echo "1. Creating backups...\n";
$files = [
    '/config/livewire.php',
    '/app/Providers/Filament/AdminPanelProvider.php'
];

foreach ($files as $file) {
    $path = __DIR__ . $file;
    if (file_exists($path)) {
        copy($path, $path . '.backup-' . date('Y-m-d-H-i-s'));
        echo "   ✓ Backed up $file\n";
    }
}

// 2. Disable Livewire in config
echo "\n2. Disabling Livewire...\n";
$livewireConfig = __DIR__ . '/config/livewire.php';
if (file_exists($livewireConfig)) {
    $content = file_get_contents($livewireConfig);
    // Set inject_assets to false
    $content = preg_replace("/'inject_assets' => true/", "'inject_assets' => false", $content);
    file_put_contents($livewireConfig, $content);
    echo "   ✓ Disabled Livewire asset injection\n";
}

// 3. Create a simple redirect after login
echo "\n3. Creating direct admin access...\n";
$simpleAdmin = '<?php
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
</html>';

file_put_contents(__DIR__ . '/public/admin-simple.php', $simpleAdmin);
echo "   ✓ Created simplified admin interface\n";

// 4. Create htaccess redirect
echo "\n4. Creating redirect rules...\n";
$htaccess = __DIR__ . '/public/.htaccess';
$redirect = "\n# Temporary redirect to bypass Livewire issues\nRewriteCond %{REQUEST_URI} ^/admin$ [NC]\nRewriteRule ^(.*)$ /admin-simple.php [L,R=302]\n";

$htaccessContent = file_get_contents($htaccess);
if (strpos($htaccessContent, 'admin-simple.php') === false) {
    // Add after RewriteEngine On
    $htaccessContent = str_replace(
        "RewriteEngine On",
        "RewriteEngine On" . $redirect,
        $htaccessContent
    );
    file_put_contents($htaccess, $htaccessContent);
    echo "   ✓ Added redirect rule\n";
}

echo "\n=== DONE ===\n";
echo "\nYou can now access:\n";
echo "- Simplified Admin: https://api.askproai.de/admin-simple.php\n";
echo "- Original Admin (may still have issues): https://api.askproai.de/admin-original\n\n";
echo "To revert: php enable-livewire.php\n";