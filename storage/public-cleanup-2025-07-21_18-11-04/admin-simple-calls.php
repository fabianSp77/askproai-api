<?php
session_start();

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Handle the request
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Auto-login as admin
\Illuminate\Support\Facades\Auth::guard('web')->loginUsingId(6); // admin@askproai.de

// Get calls data
$user = \Illuminate\Support\Facades\Auth::user();
$calls = [];

if ($user && $user->company_id) {
    $calls = \App\Models\Call::where('company_id', $user->company_id)
        ->orderBy('start_timestamp', 'desc')
        ->limit(50)
        ->get();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Calls - Simple View</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        .status { padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .info { background: #d1ecf1; color: #0c5460; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        tr:hover { background: #f5f5f5; }
        .actions { display: flex; gap: 10px; margin-bottom: 20px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .no-data { text-align: center; padding: 40px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📞 Anrufe Übersicht</h1>
        
        <div class="status info">
            <strong>Angemeldet als:</strong> <?= htmlspecialchars($user->email ?? 'Nicht angemeldet') ?> | 
            <strong>Firma:</strong> <?= htmlspecialchars($user->company->name ?? 'Keine Firma') ?>
        </div>
        
        <div class="actions">
            <a href="/admin" class="btn">Admin Dashboard</a>
            <a href="/admin/calls" class="btn btn-secondary">Filament Calls View</a>
            <a href="/admin-enhanced.php" class="btn btn-secondary">Enhanced Admin</a>
        </div>
        
        <?php if (count($calls) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Datum/Zeit</th>
                        <th>Von</th>
                        <th>Kunde</th>
                        <th>Dauer</th>
                        <th>Status</th>
                        <th>Termin</th>
                        <th>Kosten</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calls as $call): ?>
                        <tr>
                            <td><?= htmlspecialchars($call->id) ?></td>
                            <td><?= $call->start_timestamp ? \Carbon\Carbon::parse($call->start_timestamp)->format('d.m.Y H:i') : '-' ?></td>
                            <td><?= htmlspecialchars($call->from_number ?? '-') ?></td>
                            <td><?= htmlspecialchars($call->customer->name ?? '-') ?></td>
                            <td><?= $call->duration_sec ? gmdate('i:s', $call->duration_sec) : '-' ?></td>
                            <td>
                                <?php
                                    $statusColors = [
                                        'completed' => '#28a745',
                                        'failed' => '#dc3545',
                                        'in_progress' => '#ffc107'
                                    ];
                                    $color = $statusColors[$call->call_status] ?? '#6c757d';
                                ?>
                                <span style="color: <?= $color ?>">
                                    <?= htmlspecialchars(ucfirst($call->call_status ?? 'unknown')) ?>
                                </span>
                            </td>
                            <td>
                                <?= $call->appointment_id ? '✅ Ja' : '❌ Nein' ?>
                            </td>
                            <td>
                                €<?= number_format($call->total_cost ?? 0, 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <p>Keine Anrufe gefunden.</p>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;">
            <p><strong>Debug Info:</strong></p>
            <ul>
                <li>User ID: <?= $user->id ?? 'null' ?></li>
                <li>Company ID: <?= $user->company_id ?? 'null' ?></li>
                <li>Total Calls in DB: <?= $user && $user->company_id ? \App\Models\Call::where('company_id', $user->company_id)->count() : 0 ?></li>
                <li>Session ID: <?= session_id() ?></li>
                <li>Auth Guard: web</li>
            </ul>
        </div>
    </div>
</body>
</html>

<?php
// Terminate the kernel
$kernel->terminate($request, $response);