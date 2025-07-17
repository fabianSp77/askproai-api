<?php
session_start();

// Check if logged in
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: /admin-emergency-access.php');
    exit;
}

// Database connection
$db_host = '127.0.0.1';
$db_name = 'askproai_db';
$db_user = 'askproai_user';
$db_pass = 'lkZ57Dju9EDjrMxn';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed");
}

// Get statistics
$stats = [];

// Total calls
$stmt = $pdo->query("SELECT COUNT(*) as total FROM calls");
$stats['total_calls'] = $stmt->fetch()['total'];

// After hours calls
$stmt = $pdo->query("SELECT COUNT(*) as total FROM calls WHERE HOUR(created_at) < 8 OR HOUR(created_at) >= 18");
$stats['after_hours'] = $stmt->fetch()['total'];
$stats['after_hours_percent'] = $stats['total_calls'] > 0 ? round(($stats['after_hours'] / $stats['total_calls']) * 100, 1) : 0;

// Total companies
$stmt = $pdo->query("SELECT COUNT(*) as total FROM companies");
$stats['total_companies'] = $stmt->fetch()['total'];

// Total balance
$stmt = $pdo->query("SELECT SUM(balance) as total FROM prepaid_balances");
$stats['total_balance'] = round($stmt->fetch()['total'] ?? 0, 2);

// Recent calls
$stmt = $pdo->query("
    SELECT c.*, cust.name as customer_name, comp.name as company_name 
    FROM calls c
    LEFT JOIN customers cust ON c.customer_id = cust.id
    LEFT JOIN companies comp ON c.company_id = comp.id
    ORDER BY c.created_at DESC 
    LIMIT 10
");
$recent_calls = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }
        .header {
            background: white;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 24px;
            color: #3b82f6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #3b82f6;
        }
        .stat-card .sub {
            font-size: 14px;
            color: #9ca3af;
            margin-top: 5px;
        }
        .calls-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .calls-table h2 {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            padding: 12px 20px;
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        td {
            padding: 12px 20px;
            border-top: 1px solid #e5e7eb;
        }
        tr:hover {
            background: #f9fafb;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-warning {
            background: #fee2e2;
            color: #991b1b;
        }
        .logout {
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        .logout:hover {
            background: #dc2626;
        }
        .highlight {
            background: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fbbf24;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 AskProAI Emergency Dashboard</h1>
        <div>
            <span>Angemeldet als: <?php echo htmlspecialchars($_SESSION['admin_user_name']); ?></span>
            <a href="/admin-emergency-access.php?logout=1" class="logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="highlight">
            <strong>⚡ Emergency Dashboard</strong> - Dieses Dashboard zeigt Live-Daten direkt aus der Datenbank.
            Perfekt für Demos und schnelle Übersichten!
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Gesamte Anrufe</h3>
                <div class="value"><?php echo number_format($stats['total_calls']); ?></div>
                <div class="sub">Verarbeitete AI-Anrufe</div>
            </div>
            
            <div class="stat-card">
                <h3>After-Hours Calls</h3>
                <div class="value"><?php echo $stats['after_hours_percent']; ?>%</div>
                <div class="sub"><?php echo number_format($stats['after_hours']); ?> außerhalb Geschäftszeiten</div>
            </div>
            
            <div class="stat-card">
                <h3>Verwaltete Unternehmen</h3>
                <div class="value"><?php echo number_format($stats['total_companies']); ?></div>
                <div class="sub">Aktive Kunden</div>
            </div>
            
            <div class="stat-card">
                <h3>Gesamtguthaben</h3>
                <div class="value"><?php echo number_format($stats['total_balance'], 2, ',', '.'); ?>€</div>
                <div class="sub">20% Provision = <?php echo number_format($stats['total_balance'] * 0.2, 2, ',', '.'); ?>€</div>
            </div>
        </div>

        <div class="calls-table">
            <h2>📞 Letzte 10 Anrufe</h2>
            <table>
                <thead>
                    <tr>
                        <th>Zeitpunkt</th>
                        <th>Kunde</th>
                        <th>Unternehmen</th>
                        <th>Dauer</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_calls as $call): ?>
                    <tr>
                        <td><?php echo date('d.m.Y H:i', strtotime($call['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($call['customer_name'] ?? 'Unbekannt'); ?></td>
                        <td><?php echo htmlspecialchars($call['company_name'] ?? 'N/A'); ?></td>
                        <td><?php echo round($call['duration_sec'] / 60, 1); ?> Min</td>
                        <td>
                            <?php if($call['status'] == 'completed'): ?>
                                <span class="badge badge-success">Abgeschlossen</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><?php echo $call['status']; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>