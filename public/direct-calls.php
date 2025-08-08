<?php

// Direct access to calls data without Filament

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Check authentication
if (!auth()->check()) {
    header('Location: /admin/login');
    exit;
}

$user = auth()->user();
$companyId = $user->company_id;

// Get calls
$calls = \App\Models\Call::where('company_id', $companyId)
    ->orderBy('created_at', 'desc')
    ->take(50)
    ->get();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Calls - Direct Access</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .status-completed { color: green; }
        .status-failed { color: red; }
        .status-ended { color: blue; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 15px; color: #3b82f6; text-decoration: none; }
        .nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/admin">← Admin Dashboard</a>
        <a href="/admin/calls">Filament Calls Page</a>
        <a href="/admin/login">Logout</a>
    </div>
    
    <h1>Calls for Company <?= $companyId ?></h1>
    <p>User: <?= htmlspecialchars($user->email) ?></p>
    <p>Total calls shown: <?= count($calls) ?></p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Call ID</th>
                <th>Date</th>
                <th>Duration</th>
                <th>Status</th>
                <th>From</th>
                <th>To</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($calls as $call): ?>
            <tr>
                <td><?= $call->id ?></td>
                <td><?= substr($call->call_id ?? 'N/A', 0, 8) ?>...</td>
                <td><?= $call->created_at->format('Y-m-d H:i') ?></td>
                <td><?= $call->duration_sec ?> sec</td>
                <td class="status-<?= $call->status ?>"><?= $call->status ?></td>
                <td><?= htmlspecialchars($call->from_phone ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($call->to_phone ?? 'N/A') ?></td>
                <td>
                    <a href="/admin/calls/<?= $call->id ?>/edit">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <hr>
    <p style="margin-top: 20px; color: #666;">
        This is a direct access page. If you can see this but not the Filament page, 
        there's an issue with Filament authentication or middleware.
    </p>
</body>
</html>