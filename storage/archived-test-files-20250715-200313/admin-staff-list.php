<?php
// Mitarbeiter Liste - Direktzugang

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Staff;
use App\Models\Company;

// Admin User prüfen
$admin = User::where('email', 'admin@askproai.de')
    ->orWhere('email', 'fabian@askproai.de')
    ->first();

if (!$admin) {
    die('Kein Admin-Benutzer gefunden!');
}

// Mitarbeiter laden
$staff = Staff::with(['company', 'homeBranch', 'services'])->get();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Mitarbeiter Liste</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f3f4f6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .back-link {
            color: #3b82f6;
            text-decoration: none;
        }
        .staff-table {
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
        .status-active {
            color: #065f46;
            background: #d1fae5;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .status-inactive {
            color: #991b1b;
            background: #fee2e2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .services {
            font-size: 12px;
            color: #6b7280;
        }
        .btn {
            padding: 8px 16px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #2563eb;
        }
        .info-box {
            background: #eff6ff;
            border: 1px solid #3b82f6;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Mitarbeiter Liste</h1>
            <a href="/admin-direct.php" class="back-link">← Zurück zum Dashboard</a>
        </div>
        
        <?php if ($staff->where('email', 'fabian@askproai.de')->count() > 1): ?>
        <div class="info-box">
            <strong>⚠️ Duplikate gefunden:</strong> Es gibt mehrere Mitarbeiter mit der E-Mail fabian@askproai.de
        </div>
        <?php endif; ?>
        
        <div class="staff-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Unternehmen</th>
                        <th>Filiale</th>
                        <th>Telefon</th>
                        <th>Services</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $member): ?>
                    <tr>
                        <td><?php echo $member->id; ?></td>
                        <td><strong><?php echo htmlspecialchars($member->name); ?></strong></td>
                        <td><?php echo htmlspecialchars($member->email ?? '-'); ?></td>
                        <td><?php echo $member->company ? htmlspecialchars($member->company->name) : '-'; ?></td>
                        <td><?php echo $member->homeBranch ? htmlspecialchars($member->homeBranch->name) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($member->phone ?? '-'); ?></td>
                        <td class="services">
                            <?php 
                            $serviceNames = $member->services->pluck('name')->join(', ');
                            echo htmlspecialchars($serviceNames ?: 'Keine Services');
                            ?>
                        </td>
                        <td>
                            <?php if ($member->is_active): ?>
                                <span class="status-active">Aktiv</span>
                            <?php else: ?>
                                <span class="status-inactive">Inaktiv</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn" onclick="editStaff(<?php echo $member->id; ?>)">Bearbeiten</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 40px; padding: 20px; background: #f9fafb; border-radius: 8px;">
            <h3>Statistiken</h3>
            <p>Gesamt Mitarbeiter: <strong><?php echo $staff->count(); ?></strong></p>
            <p>Aktive Mitarbeiter: <strong><?php echo $staff->where('is_active', true)->count(); ?></strong></p>
            <p>Unternehmen mit Mitarbeitern: <strong><?php echo $staff->pluck('company_id')->unique()->count(); ?></strong></p>
        </div>
    </div>
    
    <script>
        function editStaff(id) {
            alert('Bearbeitung über Filament Admin Panel: /admin/staff/' + id + '/edit\n\nHinweis: Kann 419 Error zeigen wegen Session-Problem.');
        }
    </script>
</body>
</html>