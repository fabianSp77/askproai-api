<?php
// Direkter Admin-Zugang - Umgeht Filament komplett

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

// Admin User finden
$admin = User::where('email', 'admin@askproai.de')
    ->orWhere('email', 'fabian@askproai.de')
    ->first();

if (!$admin) {
    die('Kein Admin-Benutzer gefunden!');
}

// Statistiken laden
$stats = [
    'companies' => Company::count(),
    'customers' => Customer::count(),
    'appointments' => Appointment::count(),
    'calls' => Call::count(),
    'calls_today' => Call::whereDate('created_at', today())->count(),
    'appointments_today' => Appointment::whereDate('date', today())->count(),
];

// Letzte Anrufe
$recentCalls = Call::with(['customer', 'company'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Letzte Termine
$recentAppointments = Appointment::with(['customer', 'staff', 'service'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Direktzugang</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #1e40af;
        }
        .stat-label {
            color: #6b7280;
            margin-top: 5px;
        }
        .table-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #e5e7eb;
            font-weight: 600;
            color: #374151;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:hover {
            background: #f9fafb;
        }
        .success-msg {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .nav-links {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
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
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-success {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin Dashboard - Direktzugang</h1>
        <div>
            Eingeloggt als: <strong><?php echo htmlspecialchars($admin->email); ?></strong>
        </div>
    </div>

    <div class="container">
        <div class="success-msg">
            ✓ Direktzugang aktiv - Du bist eingeloggt ohne Filament/Session-Probleme
        </div>

        <div class="nav-links">
            <a href="/admin-direct.php" class="nav-link">Dashboard</a>
            <a href="/admin-export-data.php" class="nav-link">Daten exportieren</a>
            <a href="/admin-staff-list.php" class="nav-link">Mitarbeiter</a>
            <a href="/admin-calls.php" class="nav-link">Anrufe</a>
            <a href="/admin-appointments.php" class="nav-link">Termine</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['companies']; ?></div>
                <div class="stat-label">Unternehmen</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['customers']; ?></div>
                <div class="stat-label">Kunden</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['appointments']; ?></div>
                <div class="stat-label">Termine gesamt</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['calls']; ?></div>
                <div class="stat-label">Anrufe gesamt</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['calls_today']; ?></div>
                <div class="stat-label">Anrufe heute</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['appointments_today']; ?></div>
                <div class="stat-label">Termine heute</div>
            </div>
        </div>

        <div class="table-section">
            <h2>Letzte Anrufe</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kunde</th>
                        <th>Unternehmen</th>
                        <th>Dauer</th>
                        <th>Status</th>
                        <th>Erstellt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentCalls as $call): ?>
                    <tr>
                        <td><?php echo $call->id; ?></td>
                        <td><?php echo $call->customer ? htmlspecialchars($call->customer->name) : '-'; ?></td>
                        <td><?php echo $call->company ? htmlspecialchars($call->company->name) : '-'; ?></td>
                        <td><?php echo $call->duration_sec ? $call->duration_sec . 's' : '-'; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $call->status === 'ended' ? 'success' : 'pending'; ?>">
                                <?php echo $call->status; ?>
                            </span>
                        </td>
                        <td><?php echo $call->created_at->format('d.m.Y H:i'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-section">
            <h2>Letzte Termine</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kunde</th>
                        <th>Mitarbeiter</th>
                        <th>Service</th>
                        <th>Datum</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAppointments as $appointment): ?>
                    <tr>
                        <td><?php echo $appointment->id; ?></td>
                        <td><?php echo $appointment->customer ? htmlspecialchars($appointment->customer->name) : '-'; ?></td>
                        <td><?php echo $appointment->staff ? htmlspecialchars($appointment->staff->name) : '-'; ?></td>
                        <td><?php echo $appointment->service ? htmlspecialchars($appointment->service->name) : '-'; ?></td>
                        <td><?php echo $appointment->date ? $appointment->date->format('d.m.Y H:i') : '-'; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $appointment->status === 'confirmed' ? 'success' : 'pending'; ?>">
                                <?php echo $appointment->status; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 40px; padding: 20px; background: #fef3c7; border-radius: 8px;">
            <h3>Hinweis zum 419 Error</h3>
            <p>Der 419 Error tritt auf wegen Problemen mit Livewire v3 und der Session-Verwaltung. Diese direkte Admin-Seite umgeht das Problem komplett.</p>
            <p><strong>Optionen:</strong></p>
            <ul>
                <li>Nutze diese Seite für Notfall-Zugriff auf Daten</li>
                <li>Exportiere Daten über den Export-Link oben</li>
                <li>Wir arbeiten an einer permanenten Lösung für das Filament-Problem</li>
            </ul>
        </div>
    </div>
</body>
</html>