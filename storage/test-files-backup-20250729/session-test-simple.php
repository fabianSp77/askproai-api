<?php
// Simple session test without Laravel
session_start();

// Track page views
if (!isset($_SESSION['views'])) {
    $_SESSION['views'] = 0;
    $_SESSION['created'] = date('Y-m-d H:i:s');
}
$_SESSION['views']++;
$_SESSION['last_access'] = date('Y-m-d H:i:s');

// Check cookies
$cookies = $_COOKIE;
$sessionCookie = $_COOKIE[session_name()] ?? null;

// Get session configuration
$sessionConfig = [
    'save_path' => session_save_path(),
    'name' => session_name(),
    'id' => session_id(),
    'gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'cookie_path' => ini_get('session.cookie_path'),
    'cookie_domain' => ini_get('session.cookie_domain'),
    'cookie_secure' => ini_get('session.cookie_secure'),
    'cookie_httponly' => ini_get('session.cookie_httponly'),
    'cookie_samesite' => ini_get('session.cookie_samesite'),
];

// Check Laravel session file
$laravelSessionPath = __DIR__ . '/../storage/framework/sessions';
$laravelSessions = [];
if (is_dir($laravelSessionPath)) {
    $files = scandir($laravelSessionPath);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $laravelSessions[] = [
                'file' => $file,
                'size' => filesize($laravelSessionPath . '/' . $file),
                'modified' => date('Y-m-d H:i:s', filemtime($laravelSessionPath . '/' . $file))
            ];
        }
    }
}

// Response headers
$headers = headers_list();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Session Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .info { background: #d1ecf1; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .cookie { background: #e9ecef; padding: 5px; margin: 5px 0; border-radius: 3px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Simple Session Test</h1>
    
    <div class="status <?= $_SESSION['views'] > 1 ? 'success' : 'warning' ?>">
        <h2>Session Status: <?= $_SESSION['views'] > 1 ? 'Working ✓' : 'First Visit' ?></h2>
        <p>Page Views: <strong><?= $_SESSION['views'] ?></strong></p>
        <p>Created: <?= $_SESSION['created'] ?></p>
        <p>Last Access: <?= $_SESSION['last_access'] ?></p>
    </div>

    <h2>PHP Session Configuration</h2>
    <table>
        <?php foreach ($sessionConfig as $key => $value): ?>
        <tr>
            <th><?= htmlspecialchars($key) ?></th>
            <td><?= htmlspecialchars($value ?: '(empty)') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Cookies (<?= count($cookies) ?>)</h2>
    <?php if ($sessionCookie): ?>
        <div class="status success">
            <strong>Session Cookie Found:</strong> <?= session_name() ?> = <?= htmlspecialchars($sessionCookie) ?>
        </div>
    <?php else: ?>
        <div class="status error">
            <strong>No Session Cookie Found!</strong>
        </div>
    <?php endif; ?>
    
    <?php foreach ($cookies as $name => $value): ?>
        <div class="cookie">
            <strong><?= htmlspecialchars($name) ?>:</strong> 
            <?= htmlspecialchars(substr($value, 0, 100)) ?><?= strlen($value) > 100 ? '...' : '' ?>
        </div>
    <?php endforeach; ?>

    <h2>Response Headers</h2>
    <pre><?= htmlspecialchars(implode("\n", $headers)) ?></pre>

    <h2>Laravel Session Files (<?= count($laravelSessions) ?>)</h2>
    <?php if (!empty($laravelSessions)): ?>
    <table>
        <tr>
            <th>File</th>
            <th>Size</th>
            <th>Modified</th>
        </tr>
        <?php foreach ($laravelSessions as $session): ?>
        <tr>
            <td><?= htmlspecialchars($session['file']) ?></td>
            <td><?= $session['size'] ?> bytes</td>
            <td><?= $session['modified'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p>No Laravel session files found.</p>
    <?php endif; ?>

    <h2>$_SESSION Data</h2>
    <pre><?= htmlspecialchars(print_r($_SESSION, true)) ?></pre>

    <div style="margin-top: 20px;">
        <button onclick="location.reload()" style="padding: 10px 20px; font-size: 16px;">Refresh Page</button>
        <button onclick="window.location='?clear=1'" style="padding: 10px 20px; font-size: 16px;">Clear Session</button>
    </div>

    <?php if (isset($_GET['clear'])): ?>
        <?php session_destroy(); ?>
        <script>window.location = '<?= $_SERVER['PHP_SELF'] ?>';</script>
    <?php endif; ?>
</body>
</html>