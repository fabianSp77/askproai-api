<?php
// Quick Admin Portal Access Script
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Company;
use App\Models\PrepaidBalance;

// Start the app
$kernel->terminate($request, $response);

// Get all companies with prepaid balance
$companies = Company::with(['prepaidBalance'])->get();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Portal Access - Quick View</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { 
            background-color: #4CAF50; 
            color: white; 
            padding: 6px 12px; 
            text-decoration: none; 
            border-radius: 4px;
            display: inline-block;
        }
        .btn:hover { background-color: #45a049; }
        .balance-positive { color: green; }
        .balance-negative { color: red; }
    </style>
</head>
<body>
    <h1>Prepaid Billing - Admin Quick Access</h1>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Firma</th>
                <th>Guthaben</th>
                <th>Reserviert</th>
                <th>Verfügbar</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($companies as $company): ?>
                <?php $balance = $company->prepaidBalance; ?>
                <tr>
                    <td><?= $company->id ?></td>
                    <td><?= htmlspecialchars($company->name) ?></td>
                    <td class="<?= ($balance && $balance->balance > 0) ? 'balance-positive' : 'balance-negative' ?>">
                        <?= $balance ? number_format($balance->balance, 2, ',', '.') . ' €' : '0,00 €' ?>
                    </td>
                    <td><?= $balance ? number_format($balance->reserved_balance, 2, ',', '.') . ' €' : '0,00 €' ?></td>
                    <td class="<?= ($balance && $balance->getEffectiveBalance() > 0) ? 'balance-positive' : 'balance-negative' ?>">
                        <?= $balance ? number_format($balance->getEffectiveBalance(), 2, ',', '.') . ' €' : '0,00 €' ?>
                    </td>
                    <td>
                        <a href="#" onclick="openPortal(<?= $company->id ?>)" class="btn">Portal öffnen</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <script>
    function openPortal(companyId) {
        // Generate token
        fetch('/api/admin/generate-portal-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= csrf_token() ?>'
            },
            body: JSON.stringify({ company_id: companyId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.token) {
                window.location.href = '/business/admin-access?token=' + data.token;
            } else {
                alert('Fehler beim Generieren des Tokens');
            }
        });
    }
    </script>
    
    <p style="margin-top: 30px;">
        <a href="/admin">← Zurück zum Admin Panel</a>
    </p>
</body>
</html>