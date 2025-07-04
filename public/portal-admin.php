<?php
// Admin Portal Access - Standalone
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Check if user is authenticated as admin
if (!auth()->check() || !auth()->user()->hasRole('Super Admin')) {
    header('Location: /admin/login');
    exit;
}

use App\Models\Company;
use App\Models\PrepaidBalance;
use App\Models\BillingRate;

// Generate admin token for portal access
function generateAdminToken($companyId) {
    $token = bin2hex(random_bytes(32));
    cache()->put('admin_portal_access_' . $token, [
        'admin_id' => auth()->id(),
        'company_id' => $companyId,
        'created_at' => now(),
    ], now()->addMinutes(15));
    return $token;
}

// Handle AJAX token generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_token') {
    header('Content-Type: application/json');
    $companyId = intval($_POST['company_id']);
    $token = generateAdminToken($companyId);
    echo json_encode(['success' => true, 'token' => $token]);
    exit;
}

// Get all companies with prepaid data
$companies = Company::with(['prepaidBalance', 'billingRate'])->get();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2B Portal Admin - Prepaid Guthaben Verwaltung</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            line-height: 1.6;
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .header p {
            color: #6b7280;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: #1f2937;
        }
        .stat-value.positive { color: #10b981; }
        .stat-value.negative { color: #ef4444; }
        .stat-value.warning { color: #f59e0b; }
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
            background-color: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:hover {
            background-color: #f9fafb;
        }
        .balance { font-weight: 600; }
        .balance.positive { color: #10b981; }
        .balance.negative { color: #ef4444; }
        .balance.warning { color: #f59e0b; }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2563eb;
        }
        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }
        .btn-secondary:hover {
            background-color: #d1d5db;
        }
        .btn-group {
            display: flex;
            gap: 8px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f4f6;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏢 B2B Portal Admin - Prepaid Guthaben Verwaltung</h1>
            <p>Verwalten Sie Prepaid-Guthaben und greifen Sie auf Kundenportale zu</p>
            <div style="margin-top: 10px;">
                <a href="/admin" class="btn btn-secondary">← Zurück zum Admin Panel</a>
            </div>
        </div>

        <?php
        // Calculate statistics
        $totalBalance = 0;
        $totalReserved = 0;
        $companiesWithLowBalance = 0;
        $companiesWithNoBalance = 0;
        
        foreach ($companies as $company) {
            if ($balance = $company->prepaidBalance) {
                $totalBalance += $balance->balance;
                $totalReserved += $balance->reserved_balance;
                $effective = $balance->getEffectiveBalance();
                
                if ($effective <= 0) {
                    $companiesWithNoBalance++;
                } elseif ($effective < $balance->low_balance_threshold) {
                    $companiesWithLowBalance++;
                }
            }
        }
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Gesamtguthaben</h3>
                <div class="stat-value positive">€ <?= number_format($totalBalance, 2, ',', '.') ?></div>
                <p style="color: #6b7280; font-size: 14px; margin-top: 4px;">
                    Reserviert: € <?= number_format($totalReserved, 2, ',', '.') ?>
                </p>
            </div>
            <div class="stat-card">
                <h3>Aktive Firmen</h3>
                <div class="stat-value"><?= count($companies) ?></div>
                <p style="color: #6b7280; font-size: 14px; margin-top: 4px;">
                    Mit Prepaid Billing
                </p>
            </div>
            <div class="stat-card">
                <h3>Niedriges Guthaben</h3>
                <div class="stat-value <?= $companiesWithLowBalance > 0 ? 'warning' : 'positive' ?>">
                    <?= $companiesWithLowBalance ?>
                </div>
                <p style="color: #6b7280; font-size: 14px; margin-top: 4px;">
                    Firmen unter Warnschwelle
                </p>
            </div>
            <div class="stat-card">
                <h3>Kein Guthaben</h3>
                <div class="stat-value <?= $companiesWithNoBalance > 0 ? 'negative' : 'positive' ?>">
                    <?= $companiesWithNoBalance ?>
                </div>
                <p style="color: #6b7280; font-size: 14px; margin-top: 4px;">
                    Firmen ohne Guthaben
                </p>
            </div>
        </div>

        <?php if ($companiesWithLowBalance > 0 || $companiesWithNoBalance > 0): ?>
        <div class="alert alert-warning">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span><?= $companiesWithLowBalance ?> Firmen haben niedriges Guthaben und <?= $companiesWithNoBalance ?> Firmen haben kein Guthaben mehr.</span>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Firma</th>
                        <th style="text-align: right;">Guthaben</th>
                        <th style="text-align: right;">Reserviert</th>
                        <th style="text-align: right;">Verfügbar</th>
                        <th style="text-align: center;">Tarif/Min</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <?php 
                        $balance = $company->prepaidBalance;
                        $rate = $company->billingRate;
                        $effectiveBalance = $balance ? $balance->getEffectiveBalance() : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($company->name) ?></strong><br>
                                <small style="color: #6b7280;">ID: <?= $company->id ?></small>
                            </td>
                            <td style="text-align: right;">
                                <span class="balance <?= ($balance && $balance->balance > 0) ? 'positive' : 'negative' ?>">
                                    € <?= $balance ? number_format($balance->balance, 2, ',', '.') : '0,00' ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                € <?= $balance ? number_format($balance->reserved_balance, 2, ',', '.') : '0,00' ?>
                            </td>
                            <td style="text-align: right;">
                                <span class="balance <?= $effectiveBalance > 20 ? 'positive' : ($effectiveBalance > 0 ? 'warning' : 'negative') ?>">
                                    € <?= number_format($effectiveBalance, 2, ',', '.') ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?= $rate ? '€ ' . number_format($rate->rate_per_minute, 2, ',', '.') : '-' ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if (!$balance || $effectiveBalance <= 0): ?>
                                    <span class="badge badge-danger">Kein Guthaben</span>
                                <?php elseif ($effectiveBalance < ($balance->low_balance_threshold ?? 20)): ?>
                                    <span class="badge badge-warning">Niedrig</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Aktiv</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="btn-group">
                                    <button onclick="openPortal(<?= $company->id ?>)" class="btn btn-primary">
                                        Portal öffnen
                                    </button>
                                    <?php if (!$balance): ?>
                                    <button onclick="initializeBilling(<?= $company->id ?>)" class="btn btn-secondary">
                                        Billing aktivieren
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function openPortal(companyId) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="loading"></span> Öffne...';
        btn.disabled = true;
        
        fetch('portal-admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=generate_token&company_id=' + companyId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.token) {
                window.location.href = '/business/admin-access?token=' + data.token;
            } else {
                alert('Fehler beim Generieren des Tokens');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            alert('Fehler: ' + error.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
    
    function initializeBilling(companyId) {
        if (confirm('Prepaid Billing für diese Firma aktivieren?')) {
            alert('Bitte nutzen Sie das Admin Panel um Billing zu konfigurieren.');
        }
    }
    </script>
</body>
</html>