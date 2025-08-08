<?php
// Standalone Test des Analytics Dashboards
require_once '../vendor/autoload.php';
$app = require_once '../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

// Login as admin
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
auth()->login($user);

// Sammle Daten
$companies = \App\Models\Company::all()->map(function($c) {
    return ['id' => $c->id, 'name' => $c->name];
})->toArray();

$stats = [
    'revenue' => 2847.50,
    'calls_today' => 47,
    'appointments_today' => 23,
    'conversion_rate' => 68.5
];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Test</title>
</head>
<body>
    <!-- DEBUG Banner -->
    <div style="background: #10b981; color: white; padding: 10px; margin-bottom: 20px;">
        ✓ Standalone Dashboard Test - <?php echo date('H:i:s'); ?>
    </div>

    <!-- Dashboard Container -->
    <div style="min-height: 100vh; background: white; padding: 20px;">
        
        <!-- Filter Bereich -->
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e5e7eb;">
            <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 15px; color: #111827;">Filter</h2>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #6b7280; font-size: 14px;">Unternehmen</label>
                    <select style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; min-width: 200px;">
                        <option value="">Alle Unternehmen</option>
                        <?php foreach($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #6b7280; font-size: 14px;">Zeitraum</label>
                    <select style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;">
                        <option>Heute</option>
                        <option selected>Diese Woche</option>
                        <option>Dieser Monat</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- KPI Karten -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            
            <!-- Gesamt-Umsatz -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <p style="color: #6b7280; font-size: 14px; margin: 0 0 8px 0;">Gesamt-Umsatz</p>
                <p style="font-size: 32px; font-weight: 700; color: #111827; margin: 0;">
                    <?php echo number_format($stats['revenue'], 2, ',', '.'); ?> €
                </p>
                <p style="color: #10b981; font-size: 14px; margin: 8px 0 0 0;">+12,3% zum Vormonat</p>
            </div>

            <!-- Anrufe Heute -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <p style="color: #6b7280; font-size: 14px; margin: 0 0 8px 0;">Anrufe Heute</p>
                <p style="font-size: 32px; font-weight: 700; color: #111827; margin: 0;">
                    <?php echo $stats['calls_today']; ?>
                </p>
                <p style="color: #10b981; font-size: 14px; margin: 8px 0 0 0;">+8 seit gestern</p>
            </div>

            <!-- Neue Termine -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <p style="color: #6b7280; font-size: 14px; margin: 0 0 8px 0;">Neue Termine</p>
                <p style="font-size: 32px; font-weight: 700; color: #111827; margin: 0;">
                    <?php echo $stats['appointments_today']; ?>
                </p>
                <p style="color: #10b981; font-size: 14px; margin: 8px 0 0 0;">+15% diese Woche</p>
            </div>

            <!-- Conversion Rate -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <p style="color: #6b7280; font-size: 14px; margin: 0 0 8px 0;">Conversion Rate</p>
                <p style="font-size: 32px; font-weight: 700; color: #111827; margin: 0;">
                    <?php echo number_format($stats['conversion_rate'], 1, ',', '.'); ?>%
                </p>
                <p style="color: #10b981; font-size: 14px; margin: 8px 0 0 0;">+2,1% Verbesserung</p>
            </div>
        </div>

        <!-- Charts -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
            
            <!-- Umsatz Chart -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 20px 0; color: #111827;">Umsatzentwicklung</h3>
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>

            <!-- Anrufe Chart -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 20px 0; color: #111827;">Anrufstatistik</h3>
                <canvas id="callsChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Umsatz Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                datasets: [{
                    label: 'Umsatz in €',
                    data: [1200, 1900, 3000, 2500, 2700, 3200, 2900],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: value => value.toLocaleString('de-DE', {
                                style: 'currency',
                                currency: 'EUR'
                            })
                        }
                    }
                }
            }
        });

        // Anrufe Chart
        new Chart(document.getElementById('callsChart'), {
            type: 'bar',
            data: {
                labels: ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'],
                datasets: [{
                    label: 'Anrufe',
                    data: [12, 19, 23, 17, 25, 15],
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>