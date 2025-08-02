<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

// Check auth
if (!auth()->check()) {
    die('Please login first');
}

// Test queries directly
try {
    $stats = [
        'users' => \App\Models\User::count(),
        'companies' => \App\Models\Company::count(),
        'branches' => \App\Models\Branch::count(),
        'appointments_today' => \App\Models\Appointment::whereDate('created_at', today())->count(),
        'calls_today' => \App\Models\Call::whereDate('created_at', today())->count(),
    ];
    
    // Test the problematic query
    $pendingQuery = \App\Models\Appointment::where('status', 'scheduled');
    
    // Check if starts_at column exists
    $columns = \DB::select("SHOW COLUMNS FROM appointments WHERE Field = 'starts_at'");
    if (!empty($columns)) {
        $stats['pending'] = $pendingQuery->where('starts_at', '>=', today()->startOfDay())->count();
    } else {
        $stats['pending'] = 'Column starts_at not found';
    }
    
} catch (Exception $e) {
    $stats = ['error' => $e->getMessage()];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Standalone Dashboard Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stat { text-align: center; }
        .stat-value { font-size: 2em; font-weight: bold; color: #333; }
        .stat-label { color: #666; margin-top: 5px; }
        .error { color: red; padding: 20px; background: #fee; border: 1px solid #fcc; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Standalone Dashboard Test</h1>
        
        <?php if (isset($stats['error'])): ?>
            <div class="error">
                Error: <?= htmlspecialchars($stats['error']) ?>
            </div>
        <?php else: ?>
            <div class="card">
                <h2>Statistics</h2>
                <div class="stats">
                    <?php foreach ($stats as $key => $value): ?>
                        <div class="stat">
                            <div class="stat-value"><?= htmlspecialchars($value) ?></div>
                            <div class="stat-label"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Test Links</h3>
            <ul>
                <li><a href="/admin/optimized-dashboard">Optimized Dashboard (might error)</a></li>
                <li><a href="/admin/simplest-dashboard">Simplest Dashboard</a></li>
                <li><a href="/admin/test-minimal-dashboard">Test Minimal Dashboard</a></li>
                <li><a href="/admin">Main Admin Panel</a></li>
            </ul>
        </div>
    </div>
</body>
</html>