<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Login as admin
$admin = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($admin) {
    \Illuminate\Support\Facades\Auth::login($admin);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Access Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f3f4f6; }
        .info { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        a { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        a:hover { background: #2563eb; }
    </style>
</head>
<body>
    <h1>Dashboard Access Test</h1>
    
    <div class="info">
        <h2>Available Admin Pages</h2>
        <?php
        $pages = [
            'Dashboard' => \App\Filament\Admin\Pages\Dashboard::class,
            'OperationsDashboard' => \App\Filament\Admin\Pages\OperationsDashboard::class,
        ];
        
        foreach ($pages as $name => $class) {
            echo "<h3>$name</h3>";
            if (class_exists($class)) {
                echo '<p class="success">✓ Class exists</p>';
                
                // Check methods
                $page = new $class();
                echo '<p>Slug: ' . $class::getSlug() . '</p>';
                echo '<p>URL: /admin/' . $class::getSlug() . '</p>';
                echo '<p>Can Access: ' . ($class::canAccess() ? 'Yes' : 'No') . '</p>';
                
                if (method_exists($page, 'getWidgets')) {
                    $widgets = $page->getWidgets();
                    echo '<p>Widgets: ' . count($widgets) . '</p>';
                }
            } else {
                echo '<p class="error">✗ Class does not exist</p>';
            }
        }
        ?>
    </div>
    
    <div class="info">
        <h2>Test Dashboard URLs</h2>
        <a href="/admin">Admin Home</a>
        <a href="/admin/dashboard">Operations Dashboard</a>
        <?php if ($admin): ?>
            <p class="success">✓ Logged in as: <?php echo $admin->email; ?></p>
        <?php else: ?>
            <p class="error">✗ Not logged in</p>
        <?php endif; ?>
    </div>
    
    <div class="info">
        <h2>Widget Render Test Results</h2>
        <p>Now that the FilterableWidget base class exists and the getViewData() methods are fixed, the widgets should render properly.</p>
        <a href="/test-dashboard-widgets.php">Run Full Widget Test</a>
    </div>
    
    <div class="info">
        <h2>What Was Fixed</h2>
        <ul>
            <li>✓ Created <code>FilterableWidget</code> base class that all dashboard widgets extend</li>
            <li>✓ Fixed <code>getViewData()</code> method to properly merge parent data</li>
            <li>✓ Created <code>Dashboard.php</code> class that extends <code>OperationsDashboard</code></li>
            <li>✓ Added dashboard-specific CSS to ensure widgets display properly</li>
            <li>✓ Fixed syntax errors in widget files</li>
        </ul>
    </div>
</body>
</html>