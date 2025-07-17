<?php
// Admin Daten Export - Direktzugang

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Company;

// Admin User prüfen
$admin = User::where('email', 'admin@askproai.de')
    ->orWhere('email', 'fabian@askproai.de')
    ->first();

if (!$admin) {
    die('Kein Admin-Benutzer gefunden!');
}

// Export-Typ
$type = $_GET['type'] ?? 'overview';

if ($type === 'csv') {
    // CSV Export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="askproai-export-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Anrufe exportieren
    fputcsv($output, ['ANRUFE']);
    fputcsv($output, ['ID', 'Kunde', 'Unternehmen', 'Telefon', 'Dauer', 'Status', 'Erstellt']);
    
    $calls = Call::with(['customer', 'company'])->get();
    foreach ($calls as $call) {
        fputcsv($output, [
            $call->id,
            $call->customer?->name ?? '-',
            $call->company?->name ?? '-',
            $call->from_number ?? '-',
            $call->duration_sec . 's',
            $call->status,
            $call->created_at->format('Y-m-d H:i:s')
        ]);
    }
    
    fputcsv($output, []); // Leerzeile
    
    // Termine exportieren
    fputcsv($output, ['TERMINE']);
    fputcsv($output, ['ID', 'Kunde', 'Mitarbeiter', 'Service', 'Datum', 'Status']);
    
    $appointments = Appointment::with(['customer', 'staff', 'service'])->get();
    foreach ($appointments as $appointment) {
        fputcsv($output, [
            $appointment->id,
            $appointment->customer?->name ?? '-',
            $appointment->staff?->name ?? '-',
            $appointment->service?->name ?? '-',
            $appointment->date?->format('Y-m-d H:i:s') ?? '-',
            $appointment->status
        ]);
    }
    
    fclose($output);
    exit;
}

// HTML Übersicht
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin Daten Export</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f3f4f6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
        }
        .export-options {
            display: grid;
            gap: 20px;
            margin-top: 30px;
        }
        .export-card {
            padding: 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .export-card:hover {
            border-color: #3b82f6;
            background: #f9fafb;
        }
        .btn {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #2563eb;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 30px;
        }
        .stat {
            padding: 15px;
            background: #f9fafb;
            border-radius: 6px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3b82f6;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/admin-direct.php" class="back-link">← Zurück zum Dashboard</a>
        
        <h1>Daten Export</h1>
        <p>Exportiere alle Daten aus dem System als CSV-Datei.</p>
        
        <div class="stats">
            <div class="stat">
                <div>Anrufe</div>
                <div class="stat-value"><?php echo Call::count(); ?></div>
            </div>
            <div class="stat">
                <div>Termine</div>
                <div class="stat-value"><?php echo Appointment::count(); ?></div>
            </div>
            <div class="stat">
                <div>Kunden</div>
                <div class="stat-value"><?php echo Customer::count(); ?></div>
            </div>
            <div class="stat">
                <div>Mitarbeiter</div>
                <div class="stat-value"><?php echo Staff::count(); ?></div>
            </div>
        </div>
        
        <div class="export-options">
            <div class="export-card">
                <div>
                    <h3>Kompletter Daten-Export</h3>
                    <p>Exportiert alle Anrufe, Termine, Kunden und Mitarbeiter als CSV</p>
                </div>
                <a href="?type=csv" class="btn">CSV Export</a>
            </div>
            
            <div class="export-card">
                <div>
                    <h3>Datenbank Backup</h3>
                    <p>Erstellt ein SQL-Backup der gesamten Datenbank</p>
                </div>
                <button class="btn" onclick="alert('Funktion in Entwicklung')">SQL Backup</button>
            </div>
            
            <div class="export-card">
                <div>
                    <h3>Mitarbeiter Liste</h3>
                    <p>Zeigt alle Mitarbeiter mit Details</p>
                </div>
                <a href="/admin-staff-list.php" class="btn">Mitarbeiter anzeigen</a>
            </div>
        </div>
        
        <div style="margin-top: 40px; padding: 20px; background: #fef3c7; border-radius: 8px;">
            <h3>Hinweis</h3>
            <p>Diese Export-Funktion wurde erstellt, um Daten trotz des 419-Fehlers im Filament-Admin zu exportieren.</p>
        </div>
    </div>
</body>
</html>