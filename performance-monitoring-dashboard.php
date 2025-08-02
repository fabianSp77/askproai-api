<?php

/**
 * Performance Monitoring Dashboard
 * Simple dashboard to track business portal performance metrics
 */

// Set execution time limit
set_time_limit(60);

// Configuration
$config = [
    'base_url' => $_GET['url'] ?? 'https://api.askproai.de',
    'iterations' => (int)($_GET['iterations'] ?? 5),
    'test_email' => $_GET['email'] ?? 'demo@askproai.de',
    'test_password' => $_GET['password'] ?? 'password'
];

/**
 * Measure HTTP request performance
 */
function measureRequest($url, $method = 'GET', $data = null, $cookieFile = null) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Performance-Monitor/1.0',
        CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_NOBODY => false
    ]);
    
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $start = microtime(true);
    $response = curl_exec($ch);
    $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000;
    $connectTime = curl_getinfo($ch, CURLINFO_CONNECT_TIME) * 1000;
    $transferTime = curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME) * 1000;
    $downloadSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    
    curl_close($ch);
    
    return [
        'duration' => $duration,
        'http_code' => $httpCode,
        'total_time' => $totalTime,
        'connect_time' => $connectTime,
        'transfer_time' => $transferTime,
        'size' => $downloadSize,
        'success' => $httpCode >= 200 && $httpCode < 400,
        'response' => $response
    ];
}

/**
 * Extract CSRF token from HTML
 */
function extractCsrfToken($html) {
    if (preg_match('/_token[^>]*value="([^"]*)"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * Run performance tests
 */
function runPerformanceTests($config) {
    $results = [
        'login_page' => [],
        'login_submit' => [],
        'dashboard' => [],
        'api_calls' => []
    ];
    
    $cookieFile = tempnam(sys_get_temp_dir(), 'perf_cookies_');
    
    // Test login performance
    for ($i = 0; $i < $config['iterations']; $i++) {
        // Clear cookies for each iteration
        file_put_contents($cookieFile, '');
        
        // Test login page load
        $loginPageResult = measureRequest($config['base_url'] . '/business/login', 'GET', null, $cookieFile);
        
        if ($loginPageResult['success']) {
            $results['login_page'][] = $loginPageResult['duration'];
            
            // Extract CSRF token
            $csrfToken = extractCsrfToken($loginPageResult['response']);
            
            if ($csrfToken) {
                // Test login form submission
                $loginData = [
                    '_token' => $csrfToken,
                    'email' => $config['test_email'],
                    'password' => $config['test_password']
                ];
                
                $loginSubmitResult = measureRequest($config['base_url'] . '/business/login', 'POST', $loginData, $cookieFile);
                
                if ($loginSubmitResult['success'] || $loginSubmitResult['http_code'] == 302) {
                    $results['login_submit'][] = $loginSubmitResult['duration'];
                }
            }
        }
    }
    
    // Test dashboard performance
    for ($i = 0; $i < $config['iterations']; $i++) {
        // Fresh login for dashboard test
        file_put_contents($cookieFile, '');
        
        // Quick login
        $loginPageResult = measureRequest($config['base_url'] . '/business/login', 'GET', null, $cookieFile);
        $csrfToken = extractCsrfToken($loginPageResult['response']);
        
        if ($csrfToken) {
            $loginData = [
                '_token' => $csrfToken,
                'email' => $config['test_email'],
                'password' => $config['test_password']
            ];
            
            measureRequest($config['base_url'] . '/business/login', 'POST', $loginData, $cookieFile);
            
            // Test dashboard load
            $dashboardResult = measureRequest($config['base_url'] . '/business/dashboard', 'GET', null, $cookieFile);
            
            if ($dashboardResult['success']) {
                $results['dashboard'][] = $dashboardResult['duration'];
            }
        }
    }
    
    // Test API performance (one session)
    file_put_contents($cookieFile, '');
    $loginPageResult = measureRequest($config['base_url'] . '/business/login', 'GET', null, $cookieFile);
    $csrfToken = extractCsrfToken($loginPageResult['response']);
    
    if ($csrfToken) {
        $loginData = [
            '_token' => $csrfToken,
            'email' => $config['test_email'],
            'password' => $config['test_password']
        ];
        
        measureRequest($config['base_url'] . '/business/login', 'POST', $loginData, $cookieFile);
        
        // Test API endpoints
        $apiEndpoints = [
            '/business/api/dashboard/recent-calls'
        ];
        
        foreach ($apiEndpoints as $endpoint) {
            $apiTimes = [];
            
            for ($i = 0; $i < $config['iterations']; $i++) {
                $apiResult = measureRequest($config['base_url'] . $endpoint, 'GET', null, $cookieFile);
                
                if ($apiResult['success']) {
                    $apiTimes[] = $apiResult['duration'];
                }
            }
            
            if (!empty($apiTimes)) {
                $results['api_calls'] = array_merge($results['api_calls'], $apiTimes);
            }
        }
    }
    
    // Clean up
    unlink($cookieFile);
    
    return $results;
}

/**
 * Calculate statistics
 */
function calculateStats($data) {
    if (empty($data)) {
        return null;
    }
    
    sort($data);
    $count = count($data);
    
    return [
        'count' => $count,
        'avg' => array_sum($data) / $count,
        'min' => min($data),
        'max' => max($data),
        'median' => $data[intval($count / 2)],
        'p95' => $data[intval($count * 0.95)],
        'success_rate' => ($count / $config['iterations']) * 100
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Portal Performance Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .metric-card { transition: all 0.3s ease; }
        .metric-card:hover { transform: translateY(-2px); }
        .loading { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">🚀 Business Portal Performance Monitor</h1>
            <p class="text-gray-600">Real-time performance monitoring for login and dashboard functionality</p>
        </div>

        <!-- Configuration Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-semibold mb-4">Test Configuration</h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Base URL</label>
                    <input type="url" name="url" value="<?= htmlspecialchars($config['base_url']) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Iterations</label>
                    <input type="number" name="iterations" value="<?= $config['iterations'] ?>" min="1" max="20"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Test Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($config['test_email']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button type="submit" id="runTest" 
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="run-text">Run Performance Test</span>
                        <span class="loading-text hidden">Testing... <span class="loading inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span></span>
                    </button>
                </div>
            </form>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['url']) || isset($_GET['iterations']))): ?>
            
            <?php
            echo "<div class='bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6'>";
            echo "<p class='text-blue-800'>🔄 Running performance tests with {$config['iterations']} iterations...</p>";
            echo "</div>";
            
            $startTime = microtime(true);
            $results = runPerformanceTests($config);
            $testDuration = microtime(true) - $startTime;
            
            $loginPageStats = calculateStats($results['login_page']);
            $loginSubmitStats = calculateStats($results['login_submit']);
            $dashboardStats = calculateStats($results['dashboard']);
            $apiStats = calculateStats($results['api_calls']);
            ?>

            <!-- Test Results -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Login Page Performance -->
                <?php if ($loginPageStats): ?>
                <div class="metric-card bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">🔐 Login Page</h3>
                        <span class="<?= $loginPageStats['avg'] < 1000 ? 'text-green-600' : ($loginPageStats['avg'] < 2000 ? 'text-yellow-600' : 'text-red-600') ?>">
                            <?= $loginPageStats['avg'] < 1000 ? '🟢' : ($loginPageStats['avg'] < 2000 ? '🟡' : '🔴') ?>
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Average:</span>
                            <span class="font-semibold"><?= number_format($loginPageStats['avg'], 0) ?>ms</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">95th percentile:</span>
                            <span class="font-medium"><?= number_format($loginPageStats['p95'], 0) ?>ms</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Success rate:</span>
                            <span class="font-medium"><?= $loginPageStats['count'] ?>/<?= $config['iterations'] ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Login Submit Performance -->
                <?php if ($loginSubmitStats): ?>
                <div class="metric-card bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">📝 Login Submit</h3>
                        <span class="<?= $loginSubmitStats['avg'] < 1000 ? 'text-green-600' : ($loginSubmitStats['avg'] < 2000 ? 'text-yellow-600' : 'text-red-600') ?>">
                            <?= $loginSubmitStats['avg'] < 1000 ? '🟢' : ($loginSubmitStats['avg'] < 2000 ? '🟡' : '🔴') ?>
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Average:</span>
                            <span class="font-semibold"><?= number_format($loginSubmitStats['avg'], 0) ?>ms</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">95th percentile:</span>
                            <span class="font-medium"><?= number_format($loginSubmitStats['p95'], 0) ?>ms</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Success rate:</span>
                            <span class="font-medium"><?= $loginSubmitStats['count'] ?>/<?= $config['iterations'] ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Dashboard Performance -->
                <?php if ($dashboardStats): ?>
                <div class="metric-card bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">📊 Dashboard</h3>
                        <span class="<?= $dashboardStats['avg'] < 1500 ? 'text-green-600' : ($dashboardStats['avg'] < 3000 ? 'text-yellow-600' : 'text-red-600') ?>">
                            <?= $dashboardStats['avg'] < 1500 ? '🟢' : ($dashboardStats['avg'] < 3000 ? '🟡' : '🔴') ?>
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Average:</span>
                            <span class="font-semibold"><?= number_format($dashboardStats['avg'], 0) ?>ms</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">95th percentile:</span>
                            <span class="font-medium"><?= number_format($dashboardStats['p95'], 0) ?>ms</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Success rate:</span>
                            <span class="font-medium"><?= $dashboardStats['count'] ?>/<?= $config['iterations'] ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- API Performance -->
                <?php if ($apiStats): ?>
                <div class="metric-card bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">🌐 API Calls</h3>
                        <span class="<?= $apiStats['avg'] < 200 ? 'text-green-600' : ($apiStats['avg'] < 500 ? 'text-yellow-600' : 'text-red-600') ?>">
                            <?= $apiStats['avg'] < 200 ? '🟢' : ($apiStats['avg'] < 500 ? '🟡' : '🔴') ?>
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Average:</span>
                            <span class="font-semibold"><?= number_format($apiStats['avg'], 0) ?>ms</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">95th percentile:</span>
                            <span class="font-medium"><?= number_format($apiStats['p95'], 0) ?>ms</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Success rate:</span>
                            <span class="font-medium"><?= $apiStats['count'] ?>/<?= $config['iterations'] ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Performance Chart -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-semibold mb-4">📈 Performance Distribution</h2>
                <canvas id="performanceChart" width="400" height="100"></canvas>
            </div>

            <!-- Recommendations -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-semibold mb-4">💡 Performance Recommendations</h2>
                
                <?php
                $recommendations = [];
                
                if ($loginPageStats && $loginPageStats['avg'] > 2000) {
                    $recommendations[] = "🔴 LOGIN PAGE: Optimize page loading - consider CDN and asset optimization";
                }
                
                if ($loginSubmitStats && $loginSubmitStats['avg'] > 1000) {
                    $recommendations[] = "🔴 LOGIN PROCESSING: Optimize authentication - check database queries";
                }
                
                if ($dashboardStats && $dashboardStats['avg'] > 3000) {
                    $recommendations[] = "🔴 DASHBOARD: Implement lazy loading and code splitting";
                } elseif ($dashboardStats && $dashboardStats['avg'] > 1500) {
                    $recommendations[] = "🟡 DASHBOARD: Consider bundle size optimization";
                }
                
                if ($apiStats && $apiStats['avg'] > 500) {
                    $recommendations[] = "🔴 API: Optimize response times - implement caching";
                }
                
                if (empty($recommendations)) {
                    echo "<p class='text-green-600 font-medium'>🎉 No critical performance issues detected! Your application performs excellently.</p>";
                } else {
                    echo "<ul class='space-y-2'>";
                    foreach ($recommendations as $rec) {
                        echo "<li class='flex items-start'><span class='mr-2'>•</span><span>$rec</span></li>";
                    }
                    echo "</ul>";
                }
                ?>
            </div>

            <!-- Test Summary -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-3">📋 Test Summary</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Test Duration:</span>
                        <span class="font-medium ml-2"><?= number_format($testDuration, 1) ?>s</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Total Tests:</span>
                        <span class="font-medium ml-2"><?= $config['iterations'] * 3 ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Base URL:</span>
                        <span class="font-medium ml-2"><?= parse_url($config['base_url'], PHP_URL_HOST) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Timestamp:</span>
                        <span class="font-medium ml-2"><?= date('H:i:s') ?></span>
                    </div>
                </div>
            </div>

            <script>
                // Create performance chart
                const ctx = document.getElementById('performanceChart').getContext('2d');
                
                const chartData = {
                    labels: ['Login Page', 'Login Submit', 'Dashboard', 'API Calls'],
                    datasets: [{
                        label: 'Average Response Time (ms)',
                        data: [
                            <?= $loginPageStats ? $loginPageStats['avg'] : 0 ?>,
                            <?= $loginSubmitStats ? $loginSubmitStats['avg'] : 0 ?>,
                            <?= $dashboardStats ? $dashboardStats['avg'] : 0 ?>,
                            <?= $apiStats ? $apiStats['avg'] : 0 ?>
                        ],
                        backgroundColor: [
                            <?= $loginPageStats ? ($loginPageStats['avg'] < 1000 ? "'rgba(34, 197, 94, 0.6)'" : ($loginPageStats['avg'] < 2000 ? "'rgba(234, 179, 8, 0.6)'" : "'rgba(239, 68, 68, 0.6)'")) : "'rgba(156, 163, 175, 0.6)'" ?>,
                            <?= $loginSubmitStats ? ($loginSubmitStats['avg'] < 1000 ? "'rgba(34, 197, 94, 0.6)'" : ($loginSubmitStats['avg'] < 2000 ? "'rgba(234, 179, 8, 0.6)'" : "'rgba(239, 68, 68, 0.6)'")) : "'rgba(156, 163, 175, 0.6)'" ?>,
                            <?= $dashboardStats ? ($dashboardStats['avg'] < 1500 ? "'rgba(34, 197, 94, 0.6)'" : ($dashboardStats['avg'] < 3000 ? "'rgba(234, 179, 8, 0.6)'" : "'rgba(239, 68, 68, 0.6)'")) : "'rgba(156, 163, 175, 0.6)'" ?>,
                            <?= $apiStats ? ($apiStats['avg'] < 200 ? "'rgba(34, 197, 94, 0.6)'" : ($apiStats['avg'] < 500 ? "'rgba(234, 179, 8, 0.6)'" : "'rgba(239, 68, 68, 0.6)'")) : "'rgba(156, 163, 175, 0.6)'" ?>
                        ],
                        borderColor: [
                            <?= $loginPageStats ? ($loginPageStats['avg'] < 1000 ? "'rgba(34, 197, 94, 1)'" : ($loginPageStats['avg'] < 2000 ? "'rgba(234, 179, 8, 1)'" : "'rgba(239, 68, 68, 1)'")) : "'rgba(156, 163, 175, 1)'" ?>,
                            <?= $loginSubmitStats ? ($loginSubmitStats['avg'] < 1000 ? "'rgba(34, 197, 94, 1)'" : ($loginSubmitStats['avg'] < 2000 ? "'rgba(234, 179, 8, 1)'" : "'rgba(239, 68, 68, 1)'")) : "'rgba(156, 163, 175, 1)'" ?>,
                            <?= $dashboardStats ? ($dashboardStats['avg'] < 1500 ? "'rgba(34, 197, 94, 1)'" : ($dashboardStats['avg'] < 3000 ? "'rgba(234, 179, 8, 1)'" : "'rgba(239, 68, 68, 1)'")) : "'rgba(156, 163, 175, 1)'" ?>,
                            <?= $apiStats ? ($apiStats['avg'] < 200 ? "'rgba(34, 197, 94, 1)'" : ($apiStats['avg'] < 500 ? "'rgba(234, 179, 8, 1)'" : "'rgba(239, 68, 68, 1)'")) : "'rgba(156, 163, 175, 1)'" ?>
                        ],
                        borderWidth: 2
                    }]
                };

                new Chart(ctx, {
                    type: 'bar',
                    data: chartData,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Response Time (ms)'
                                }
                            }
                        }
                    }
                });
            </script>

        <?php endif; ?>
    </div>

    <script>
        // Handle form submission with loading state
        document.getElementById('runTest').addEventListener('click', function() {
            const runText = this.querySelector('.run-text');
            const loadingText = this.querySelector('.loading-text');
            
            runText.classList.add('hidden');
            loadingText.classList.remove('hidden');
            this.disabled = true;
            
            // Re-enable after form submission
            setTimeout(() => {
                this.disabled = false;
            }, 1000);
        });
        
        // Auto-refresh every 5 minutes if desired
        <?php if (isset($_GET['autorefresh']) && $_GET['autorefresh'] === '1'): ?>
        setTimeout(() => {
            window.location.reload();
        }, 300000); // 5 minutes
        <?php endif; ?>
    </script>
</body>
</html>