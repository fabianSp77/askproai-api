<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Authenticate
$user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
auth()->login($user);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Final Test - Calls Page Fix</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .actions { margin: 20px 0; }
        .actions a { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; margin-right: 10px; }
        .actions a:hover { background: #2563eb; }
    </style>
</head>
<body>
    <h1>Final Test - Calls Page Fix</h1>
    
    <h2>1. Database Check</h2>
    <?php
    $calls = \App\Models\Call::where('company_id', $user->company_id ?? 1)->limit(5)->get();
    if (count($calls) > 0) {
        echo "<p class='success'>✓ Found " . count($calls) . " calls in database</p>";
    } else {
        echo "<p class='error'>✗ No calls found in database</p>";
    }
    ?>
    
    <h2>2. CallResource Check</h2>
    <?php
    use App\Filament\Admin\Resources\CallResource;
    
    if (class_exists(CallResource::class)) {
        echo "<p class='success'>✓ CallResource class exists</p>";
        
        $file = '/var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php';
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "<p>File size: $size bytes (simplified: " . ($size < 5000 ? 'YES' : 'NO') . ")</p>";
        echo "<p>Last modified: $modified</p>";
    } else {
        echo "<p class='error'>✗ CallResource class not found</p>";
    }
    ?>
    
    <h2>3. ListCalls Page Check</h2>
    <?php
    use App\Filament\Admin\Resources\CallResource\Pages\ListCalls;
    
    if (class_exists(ListCalls::class)) {
        echo "<p class='success'>✓ ListCalls class exists</p>";
        
        $file = '/var/www/api-gateway/app/Filament/Admin/Resources/CallResource/Pages/ListCalls.php';
        $size = filesize($file);
        echo "<p>File size: $size bytes (simplified: " . ($size < 1000 ? 'YES' : 'NO') . ")</p>";
    } else {
        echo "<p class='error'>✗ ListCalls class not found</p>";
    }
    ?>
    
    <h2>4. Sample Data</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Status</th>
                <th>Duration</th>
                <th>From</th>
                <th>To</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($calls as $call): ?>
            <tr>
                <td><?= $call->id ?></td>
                <td><?= $call->created_at->format('Y-m-d H:i') ?></td>
                <td><?= $call->status ?></td>
                <td><?= $call->duration_sec ?>s</td>
                <td><?= $call->from_phone ?: '—' ?></td>
                <td><?= $call->to_phone ?: '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h2>5. Actions</h2>
    <div class="actions">
        <a href="/admin/calls?_t=<?= time() ?>" target="_blank">Open Filament Calls Page</a>
        <a href="/calls-table.php" target="_blank">Open Alternative Table</a>
        <a href="/test-standalone-table.php" target="_blank">Open Standalone Test</a>
    </div>
    
    <h2>6. Clear Browser Cache</h2>
    <p>If you still see empty cells:</p>
    <ol>
        <li>Press <strong>Ctrl+F5</strong> on the Calls page</li>
        <li>Or open in Incognito/Private window</li>
        <li>Or clear browser cache completely (Ctrl+Shift+Del)</li>
    </ol>
    
    <script>
        // Force clear any service workers
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                    console.log('Unregistered service worker');
                }
            });
        }
    </script>
</body>
</html>