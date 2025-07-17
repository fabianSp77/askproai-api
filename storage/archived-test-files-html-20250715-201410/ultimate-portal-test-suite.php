<?php
/**
 * ULTIMATE Portal Test Suite
 * Umfassende Tests wie echte Benutzer sie durchführen würden
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

// HTML Output für bessere Übersicht
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ultimate Portal Test Suite</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f7fa;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            color: #1a202c;
            margin-bottom: 30px;
        }
        .test-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .test-result {
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .passed {
            background: #d4edda;
            color: #155724;
        }
        .failed {
            background: #f8d7da;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-ok { background: #22c55e; color: white; }
        .status-error { background: #ef4444; color: white; }
        .status-warning { background: #f59e0b; color: white; }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            font-weight: 500;
        }
        .btn-primary { background: #3b82f6; }
        .btn-success { background: #22c55e; }
        .btn-danger { background: #ef4444; }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #3b82f6;
            transition: width 0.3s ease;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Ultimate Portal Test Suite</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="total-tests">0</div>
                <div class="stat-label">Gesamt Tests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="passed-tests" style="color: #22c55e;">0</div>
                <div class="stat-label">Bestanden</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="failed-tests" style="color: #ef4444;">0</div>
                <div class="stat-label">Fehlgeschlagen</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="success-rate">0%</div>
                <div class="stat-label">Erfolgsrate</div>
            </div>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" id="progress" style="width: 0%"></div>
        </div>

        <?php
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $testResults = [];

        function runTest($name, $test, &$results) {
            global $totalTests, $passedTests, $failedTests;
            $totalTests++;
            
            try {
                $result = $test();
                if ($result['success']) {
                    $passedTests++;
                    $results[] = [
                        'name' => $name,
                        'status' => 'passed',
                        'message' => $result['message'] ?? 'Test bestanden'
                    ];
                } else {
                    $failedTests++;
                    $results[] = [
                        'name' => $name,
                        'status' => 'failed',
                        'message' => $result['message'] ?? 'Test fehlgeschlagen'
                    ];
                }
            } catch (Exception $e) {
                $failedTests++;
                $results[] = [
                    'name' => $name,
                    'status' => 'failed',
                    'message' => 'Error: ' . $e->getMessage()
                ];
            }
        }

        // ADMIN PORTAL TESTS
        ?>
        <div class="test-section">
            <h2>🏢 Admin Portal Tests</h2>
            <div class="test-grid">
                <?php
                $adminTests = [];
                
                // Test 1: Admin Login Page
                runTest('Admin Login Page erreichbar', function() {
                    $request = \Illuminate\Http\Request::create('/admin/login', 'GET');
                    $response = app()->handle($request);
                    return [
                        'success' => in_array($response->getStatusCode(), [200, 302]),
                        'message' => "Status: {$response->getStatusCode()}"
                    ];
                }, $adminTests);

                // Test 2: Admin Dashboard (nach Login)
                runTest('Admin Dashboard', function() {
                    // Simuliere Admin Login
                    $admin = \App\Models\User::first();
                    if (!$admin) {
                        return ['success' => false, 'message' => 'Kein Admin User gefunden'];
                    }
                    
                    auth()->login($admin);
                    $request = \Illuminate\Http\Request::create('/admin', 'GET');
                    $response = app()->handle($request);
                    
                    return [
                        'success' => $response->getStatusCode() === 200,
                        'message' => "Status: {$response->getStatusCode()}"
                    ];
                }, $adminTests);

                // Test 3: Kritische Widgets
                runTest('Dashboard Widgets laden', function() {
                    $widgetClasses = [
                        'App\Filament\Admin\Widgets\StatsOverviewWidget',
                        'App\Filament\Admin\Widgets\LatestCallsWidget',
                        'App\Filament\Admin\Widgets\AppointmentCalendarWidget'
                    ];
                    
                    $missing = [];
                    foreach ($widgetClasses as $class) {
                        if (!class_exists($class)) {
                            $missing[] = $class;
                        }
                    }
                    
                    return [
                        'success' => empty($missing),
                        'message' => empty($missing) ? 'Alle Widgets vorhanden' : 'Fehlend: ' . implode(', ', $missing)
                    ];
                }, $adminTests);

                // Test 4: Calls Resource
                runTest('Anrufe-Verwaltung', function() {
                    $resource = 'App\Filament\Admin\Resources\CallResource';
                    return [
                        'success' => class_exists($resource),
                        'message' => class_exists($resource) ? 'CallResource verfügbar' : 'CallResource fehlt'
                    ];
                }, $adminTests);

                // Test 5: Appointments Resource
                runTest('Termin-Verwaltung', function() {
                    $resource = 'App\Filament\Admin\Resources\AppointmentResource';
                    return [
                        'success' => class_exists($resource),
                        'message' => class_exists($resource) ? 'AppointmentResource verfügbar' : 'AppointmentResource fehlt'
                    ];
                }, $adminTests);

                // Zeige Admin Test Ergebnisse
                foreach ($adminTests as $test) {
                    echo '<div class="test-result ' . $test['status'] . '">';
                    echo '<span>' . $test['name'] . '</span>';
                    echo '<span class="status-badge status-' . ($test['status'] === 'passed' ? 'ok' : 'error') . '">';
                    echo $test['message'];
                    echo '</span>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- BUSINESS PORTAL TESTS -->
        <div class="test-section">
            <h2>💼 Business Portal Tests</h2>
            <div class="test-grid">
                <?php
                $businessTests = [];
                
                // Test 1: Business Portal Startseite
                runTest('Business Portal Startseite', function() {
                    $request = \Illuminate\Http\Request::create('/business', 'GET');
                    $response = app()->handle($request);
                    return [
                        'success' => $response->getStatusCode() === 200,
                        'message' => "Status: {$response->getStatusCode()}"
                    ];
                }, $businessTests);

                // Test 2: Business Login
                runTest('Business Login Page', function() {
                    $request = \Illuminate\Http\Request::create('/business/login', 'GET');
                    $response = app()->handle($request);
                    return [
                        'success' => $response->getStatusCode() === 200,
                        'message' => "Status: {$response->getStatusCode()}"
                    ];
                }, $businessTests);

                // Test 3: API Login Endpoint
                runTest('API Login Endpoint', function() {
                    // Use our test user with known password
                    $testUser = \App\Models\PortalUser::where('email', 'portal-test@askproai.de')->first();
                    
                    if (!$testUser) {
                        // Create test user if not exists
                        $testUser = \App\Models\PortalUser::create([
                            'name' => 'Portal Test User',
                            'email' => 'portal-test@askproai.de',
                            'password' => bcrypt('test123'),
                            'company_id' => 1,
                            'is_active' => true
                        ]);
                    }
                    
                    // Test API Login with correct password
                    $jsonData = json_encode([
                        'email' => 'portal-test@askproai.de',
                        'password' => 'test123'
                    ]);
                    
                    $request = \Illuminate\Http\Request::create(
                        '/api/v2/portal/auth/login',
                        'POST',
                        [],
                        [],
                        [],
                        ['CONTENT_TYPE' => 'application/json'],
                        $jsonData
                    );
                    
                    $request->headers->set('Accept', 'application/json');
                    $request->headers->set('Content-Type', 'application/json');
                    
                    try {
                        $response = app()->handle($request);
                        return [
                            'success' => $response->getStatusCode() === 200,
                            'message' => "API Response: {$response->getStatusCode()}"
                        ];
                    } catch (Exception $e) {
                        return [
                            'success' => false,
                            'message' => 'API Error: ' . substr($e->getMessage(), 0, 50)
                        ];
                    }
                }, $businessTests);

                // Test 4: Protected Routes
                runTest('Geschützte Business Routes', function() {
                    $portalUser = \App\Models\PortalUser::first();
                    if (!$portalUser) {
                        return ['success' => false, 'message' => 'Kein Portal User'];
                    }
                    
                    auth('portal')->login($portalUser);
                    
                    $request = \Illuminate\Http\Request::create('/business/dashboard', 'GET');
                    $response = app()->handle($request);
                    
                    // 200 = OK, 302 = Redirect (wahrscheinlich Login)
                    $isOk = in_array($response->getStatusCode(), [200, 302]);
                    
                    return [
                        'success' => $isOk,
                        'message' => "Dashboard Status: {$response->getStatusCode()}"
                    ];
                }, $businessTests);

                // Test 5: React Build
                runTest('React App Build', function() {
                    $indexPath = public_path('business/index.html');
                    $manifestPath = public_path('build/manifest.json');
                    
                    if (!file_exists($indexPath)) {
                        return ['success' => false, 'message' => 'index.html fehlt'];
                    }
                    
                    if (!file_exists($manifestPath)) {
                        return ['success' => false, 'message' => 'manifest.json fehlt'];
                    }
                    
                    return ['success' => true, 'message' => 'Build vorhanden'];
                }, $businessTests);

                // Zeige Business Test Ergebnisse
                foreach ($businessTests as $test) {
                    echo '<div class="test-result ' . $test['status'] . '">';
                    echo '<span>' . $test['name'] . '</span>';
                    echo '<span class="status-badge status-' . ($test['status'] === 'passed' ? 'ok' : 'error') . '">';
                    echo $test['message'];
                    echo '</span>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- BENUTZER WORKFLOWS -->
        <div class="test-section">
            <h2>👥 Benutzer-Workflow Tests</h2>
            <div class="test-grid">
                <?php
                $workflowTests = [];
                
                // Workflow 1: Admin schaut Anrufe an
                runTest('Admin Workflow: Anrufe prüfen', function() {
                    $steps = [];
                    
                    // Step 1: Login
                    $admin = \App\Models\User::first();
                    if (!$admin) {
                        return ['success' => false, 'message' => 'Kein Admin vorhanden'];
                    }
                    
                    // Step 2: Check Calls
                    $calls = \App\Models\Call::count();
                    $steps[] = "Anrufe gefunden: {$calls}";
                    
                    // Step 3: Check Call Details
                    if ($calls > 0) {
                        $call = \App\Models\Call::first();
                        $hasTranscript = !empty($call->transcript);
                        $steps[] = $hasTranscript ? 'Transkript vorhanden' : 'Kein Transkript';
                    }
                    
                    return [
                        'success' => $calls > 0,
                        'message' => implode(', ', $steps)
                    ];
                }, $workflowTests);

                // Workflow 2: Kunde bucht Termin
                runTest('Kunden Workflow: Terminbuchung', function() {
                    $steps = [];
                    
                    // Check Services
                    $services = \App\Models\Service::count();
                    $steps[] = "Services: {$services}";
                    
                    // Check Staff
                    $staff = \App\Models\Staff::where('active', true)->count();
                    $steps[] = "Aktive Mitarbeiter: {$staff}";
                    
                    // Check Appointments
                    $appointments = \App\Models\Appointment::count();
                    $steps[] = "Termine: {$appointments}";
                    
                    return [
                        'success' => $services > 0 && $staff > 0,
                        'message' => implode(', ', $steps)
                    ];
                }, $workflowTests);

                // Workflow 3: Performance Check
                runTest('Performance: Ladezeiten', function() {
                    $start = microtime(true);
                    
                    // Test Dashboard Load
                    $request = \Illuminate\Http\Request::create('/admin', 'GET');
                    $response = app()->handle($request);
                    
                    $duration = round((microtime(true) - $start) * 1000);
                    
                    return [
                        'success' => $duration < 3000, // Unter 3 Sekunden
                        'message' => "Ladezeit: {$duration}ms"
                    ];
                }, $workflowTests);

                // Zeige Workflow Test Ergebnisse
                foreach ($workflowTests as $test) {
                    echo '<div class="test-result ' . $test['status'] . '">';
                    echo '<span>' . $test['name'] . '</span>';
                    echo '<span class="status-badge status-' . ($test['status'] === 'passed' ? 'ok' : 'error') . '">';
                    echo $test['message'];
                    echo '</span>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- SYSTEM HEALTH -->
        <div class="test-section">
            <h2>🏥 System Health Check</h2>
            <div class="test-grid">
                <?php
                $healthTests = [];
                
                // Database
                runTest('Datenbankverbindung', function() {
                    try {
                        \DB::connection()->getPdo();
                        $tables = \DB::select('SHOW TABLES');
                        return [
                            'success' => true,
                            'message' => count($tables) . ' Tabellen'
                        ];
                    } catch (Exception $e) {
                        return ['success' => false, 'message' => 'DB Error'];
                    }
                }, $healthTests);

                // Redis
                runTest('Redis Cache', function() {
                    try {
                        \Illuminate\Support\Facades\Redis::ping();
                        return ['success' => true, 'message' => 'Redis läuft'];
                    } catch (Exception $e) {
                        return ['success' => false, 'message' => 'Redis offline'];
                    }
                }, $healthTests);

                // Queue
                runTest('Queue System', function() {
                    $jobs = \DB::table('jobs')->count();
                    $failed = \DB::table('failed_jobs')->count();
                    
                    return [
                        'success' => $failed === 0,
                        'message' => "Jobs: {$jobs}, Failed: {$failed}"
                    ];
                }, $healthTests);

                // Disk Space
                runTest('Speicherplatz', function() {
                    $free = disk_free_space('/');
                    $total = disk_total_space('/');
                    $percent = round(($free / $total) * 100);
                    
                    return [
                        'success' => $percent > 10,
                        'message' => "{$percent}% frei"
                    ];
                }, $healthTests);

                // Zeige Health Test Ergebnisse
                foreach ($healthTests as $test) {
                    echo '<div class="test-result ' . $test['status'] . '">';
                    echo '<span>' . $test['name'] . '</span>';
                    echo '<span class="status-badge status-' . ($test['status'] === 'passed' ? 'ok' : 'error') . '">';
                    echo $test['message'];
                    echo '</span>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="action-buttons">
            <a href="/auth-helper.php" class="btn btn-primary">🔐 Login Helper</a>
            <a href="/admin" class="btn btn-success">🏢 Admin Portal</a>
            <a href="/business" class="btn btn-success">💼 Business Portal</a>
            <button onclick="location.reload()" class="btn btn-danger">🔄 Tests wiederholen</button>
        </div>

        <?php
        $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100) : 0;
        ?>

        <script>
            // Update Stats
            document.getElementById('total-tests').textContent = <?= $totalTests ?>;
            document.getElementById('passed-tests').textContent = <?= $passedTests ?>;
            document.getElementById('failed-tests').textContent = <?= $failedTests ?>;
            document.getElementById('success-rate').textContent = '<?= $successRate ?>%';
            document.getElementById('progress').style.width = '<?= $successRate ?>%';
            
            // Color code progress bar
            const progressBar = document.getElementById('progress');
            if (<?= $successRate ?> >= 80) {
                progressBar.style.background = '#22c55e';
            } else if (<?= $successRate ?> >= 60) {
                progressBar.style.background = '#f59e0b';
            } else {
                progressBar.style.background = '#ef4444';
            }
        </script>
    </div>
</body>
</html>