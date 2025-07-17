<?php
// Termine Liste - Direktzugang

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Appointment;

// Admin User prüfen
$admin = User::where('email', 'admin@askproai.de')
    ->orWhere('email', 'fabian@askproai.de')
    ->first();

if (!$admin) {
    die('Kein Admin-Benutzer gefunden!');
}

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Query bauen
$query = Appointment::with(['customer', 'staff', 'service', 'branch']);

if ($search) {
    $query->where(function($q) use ($search) {
        $q->whereHas('customer', function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        })
        ->orWhereHas('staff', function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    });
}

if ($status) {
    $query->where('status', $status);
}

if ($dateFrom) {
    $query->whereDate('date', '>=', $dateFrom);
}

if ($dateTo) {
    $query->whereDate('date', '<=', $dateTo);
}

$totalAppointments = $query->count();
$appointments = $query->orderBy('date', 'desc')
    ->limit($perPage)
    ->offset($offset)
    ->get();

$totalPages = ceil($totalAppointments / $perPage);

// Statistiken
$stats = [
    'total' => $totalAppointments,
    'today' => Appointment::whereDate('date', today())->count(),
    'upcoming' => Appointment::where('date', '>', now())->where('status', 'confirmed')->count(),
    'completed' => Appointment::where('status', 'completed')->count(),
];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Termine - Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
        }
        .header {
            background: white;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .nav-links {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .nav-link {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
        }
        .nav-link:hover {
            background: #2563eb;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        label {
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }
        input, select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        .btn {
            padding: 8px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn:hover {
            background: #2563eb;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
        }
        .stat-label {
            color: #6b7280;
            margin-top: 5px;
        }
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
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
            border-bottom: 1px solid #e5e7eb;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:hover {
            background: #f9fafb;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-completed {
            background: #e0e7ff;
            color: #3730a3;
        }
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .page-link {
            padding: 8px 12px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
        }
        .page-link:hover {
            background: #f3f4f6;
        }
        .page-link.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        .back-link {
            color: #3b82f6;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <a href="/admin-direct.php" class="back-link">← Zurück zum Dashboard</a>
            <h1>Termine</h1>
        </div>
    </div>

    <div class="container">
        <div class="nav-links">
            <a href="/admin-direct.php" class="nav-link">Dashboard</a>
            <a href="/admin-export-data.php" class="nav-link">Daten exportieren</a>
            <a href="/admin-staff-list.php" class="nav-link">Mitarbeiter</a>
            <a href="/admin-calls.php" class="nav-link">Anrufe</a>
            <a href="/admin-appointments.php" class="nav-link">Termine</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">Termine gesamt</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['today']); ?></div>
                <div class="stat-label">Termine heute</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['upcoming']); ?></div>
                <div class="stat-label">Bevorstehend</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['completed']); ?></div>
                <div class="stat-label">Abgeschlossen</div>
            </div>
        </div>

        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Suche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Kunde oder Mitarbeiter...">
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Alle</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Bestätigt</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Ausstehend</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Abgeschlossen</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Storniert</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Von</label>
                        <input type="date" name="date_from" value="<?php echo $dateFrom; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Bis</label>
                        <input type="date" name="date_to" value="<?php echo $dateTo; ?>">
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn">Filtern</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Datum</th>
                        <th>Uhrzeit</th>
                        <th>Kunde</th>
                        <th>Mitarbeiter</th>
                        <th>Service</th>
                        <th>Filiale</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo $appointment->id; ?></td>
                        <td><?php echo $appointment->date ? $appointment->date->format('d.m.Y') : '-'; ?></td>
                        <td><?php echo $appointment->start_time ?? '-'; ?></td>
                        <td><?php echo $appointment->customer ? htmlspecialchars($appointment->customer->name) : '-'; ?></td>
                        <td><?php echo $appointment->staff ? htmlspecialchars($appointment->staff->name) : '-'; ?></td>
                        <td><?php echo $appointment->service ? htmlspecialchars($appointment->service->name) : '-'; ?></td>
                        <td><?php echo $appointment->branch ? htmlspecialchars($appointment->branch->name) : '-'; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $appointment->status; ?>">
                                <?php 
                                $statusMap = [
                                    'confirmed' => 'Bestätigt',
                                    'pending' => 'Ausstehend',
                                    'completed' => 'Abgeschlossen',
                                    'cancelled' => 'Storniert'
                                ];
                                echo $statusMap[$appointment->status] ?? $appointment->status;
                                ?>
                            </span>
                        </td>
                        <td>
                            <a href="/admin-appointment-detail.php?id=<?php echo $appointment->id; ?>" class="btn" style="font-size: 12px;">Details</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" class="page-link">←</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i <= 3 || $i > $totalPages - 3 || abs($i - $page) <= 1): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" 
                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php elseif ($i === 4 || $i === $totalPages - 3): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" class="page-link">→</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>