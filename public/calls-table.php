<?php

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

// Get calls with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

$totalCalls = \App\Models\Call::where('company_id', $companyId)->count();
$todayCalls = \App\Models\Call::where('company_id', $companyId)
    ->whereDate('created_at', today())
    ->count();
$completedCalls = \App\Models\Call::where('company_id', $companyId)
    ->where('status', 'completed')
    ->count();

$calls = \App\Models\Call::where('company_id', $companyId)
    ->with(['customer', 'appointment']) // Eager load relationships
    ->orderBy('created_at', 'desc')
    ->skip($offset)
    ->take($perPage)
    ->get();

$totalPages = ceil($totalCalls / $perPage);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anrufe - AskProAI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f3f4f6;
            color: #111827;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            flex: 1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card .label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .stat-card .value {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
        }
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:hover {
            background: #f9fafb;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-ended {
            background: #e0e7ff;
            color: #3730a3;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            background: white;
            border-top: 1px solid #e5e7eb;
        }
        .pagination a {
            padding: 8px 12px;
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            border-radius: 4px;
        }
        .pagination a:hover {
            background: #e5e7eb;
        }
        .pagination .current {
            background: #3b82f6;
            color: white;
        }
        .nav {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .nav a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .nav a:hover {
            text-decoration: underline;
        }
        .action-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .action-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="/admin">← Admin Dashboard</a>
            <a href="/admin/calls">Filament Calls (Original)</a>
            <a href="/direct-calls.php">Direct Calls (Simple)</a>
        </div>
        
        <div class="header">
            <h1>📞 Anrufe</h1>
            <p>Benutzer: <?= htmlspecialchars($user->email) ?> | Company: <?= $companyId ?></p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="label">Gesamt Anrufe</div>
                <div class="value"><?= $totalCalls ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Heute</div>
                <div class="value"><?= $todayCalls ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Abgeschlossen</div>
                <div class="value"><?= $completedCalls ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Seite</div>
                <div class="value"><?= $page ?> / <?= $totalPages ?></div>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Call ID</th>
                        <th>Datum & Zeit</th>
                        <th>Dauer</th>
                        <th>Status</th>
                        <th>Von</th>
                        <th>Nach</th>
                        <th>Kunde</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($calls as $call): ?>
                    <tr>
                        <td><strong>#<?= $call->id ?></strong></td>
                        <td><?= $call->call_id ? substr($call->call_id, 0, 8) . '...' : '—' ?></td>
                        <td><?= $call->created_at->format('d.m.Y H:i') ?></td>
                        <td>
                            <?php if($call->duration_sec > 0): ?>
                                <?= gmdate('i:s', $call->duration_sec) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $call->status ?>">
                                <?= $call->status === 'completed' ? 'Abgeschlossen' : ucfirst($call->status) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($call->from_phone ?? '—') ?></td>
                        <td><?= htmlspecialchars($call->to_phone ?? '—') ?></td>
                        <td>
                            <?php if($call->customer): ?>
                                <?= htmlspecialchars($call->customer->name ?? 'Customer #' . $call->customer_id) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/admin/calls/<?= $call->id ?>/edit" class="action-link">
                                Anzeigen →
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(count($calls) === 0): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: #6b7280;">
                            Keine Anrufe gefunden
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if($totalPages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="?page=1">«</a>
                    <a href="?page=<?= $page - 1 ?>">‹</a>
                <?php endif; ?>
                
                <?php for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'current' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>">›</a>
                    <a href="?page=<?= $totalPages ?>">»</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>