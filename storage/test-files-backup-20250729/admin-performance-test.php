<?php
/**
 * Admin Performance Test Page
 * 
 * This minimal page helps identify what's causing browser overload
 * in the admin panel. Start with basic HTML and gradually add features.
 */

// Test stages:
// 1. Basic HTML only (no JS, no CSS)
// 2. Add Filament CSS
// 3. Add Alpine.js
// 4. Add Livewire
// 5. Add wire:poll
// 6. Add multiple widgets

$stage = $_GET['stage'] ?? 1;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Performance Test - Stage <?= $stage ?></title>
    
    <?php if ($stage >= 2): ?>
    <!-- Stage 2: Add Filament CSS -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; padding: 2rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .metric { display: inline-block; margin-right: 2rem; }
        .metric-value { font-size: 2rem; font-weight: 700; color: #1f2937; }
        .metric-label { font-size: 0.875rem; color: #6b7280; }
    </style>
    <?php endif; ?>
    
    <?php if ($stage >= 3): ?>
    <!-- Stage 3: Add Alpine.js -->
    <script src="//unpkg.com/alpinejs" defer></script>
    <?php endif; ?>
    
    <?php if ($stage >= 4): ?>
    <!-- Stage 4: Add Livewire -->
    <script>
        // Simulate Livewire presence
        window.Livewire = { 
            on: function() {}, 
            emit: function() {},
            start: function() { console.log('Livewire mock started'); }
        };
    </script>
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 2rem;">
            Admin Performance Test - Stage <?= $stage ?>
        </h1>
        
        <div class="card">
            <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Test Information</h2>
            <p>Current Stage: <strong><?= $stage ?></strong></p>
            <p>Features Active:</p>
            <ul style="list-style: disc; margin-left: 2rem;">
                <li>Basic HTML ✓</li>
                <?php if ($stage >= 2): ?><li>Filament CSS ✓</li><?php endif; ?>
                <?php if ($stage >= 3): ?><li>Alpine.js ✓</li><?php endif; ?>
                <?php if ($stage >= 4): ?><li>Livewire (Mock) ✓</li><?php endif; ?>
                <?php if ($stage >= 5): ?><li>Polling Simulation ✓</li><?php endif; ?>
                <?php if ($stage >= 6): ?><li>Multiple Widgets ✓</li><?php endif; ?>
            </ul>
        </div>
        
        <div class="card">
            <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Performance Metrics</h2>
            <div class="metric">
                <div class="metric-value" id="cpu">0%</div>
                <div class="metric-label">CPU Usage</div>
            </div>
            <div class="metric">
                <div class="metric-value" id="memory">0 MB</div>
                <div class="metric-label">Memory</div>
            </div>
            <div class="metric">
                <div class="metric-value" id="fps">60</div>
                <div class="metric-label">FPS</div>
            </div>
            <div class="metric">
                <div class="metric-value" id="requests">0</div>
                <div class="metric-label">Requests/sec</div>
            </div>
        </div>
        
        <?php if ($stage >= 5): ?>
        <!-- Stage 5: Add Polling Simulation -->
        <div class="card" id="polling-widget">
            <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Polling Widget (5s interval)</h2>
            <p>Updates: <span id="poll-count">0</span></p>
            <p>Last Update: <span id="poll-time">Never</span></p>
        </div>
        <?php endif; ?>
        
        <?php if ($stage >= 6): ?>
        <!-- Stage 6: Multiple Widgets -->
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <div class="card widget-<?= $i ?>">
            <h3>Widget <?= $i ?></h3>
            <div class="metric">
                <div class="metric-value widget-value-<?= $i ?>">0</div>
                <div class="metric-label">Random Metric</div>
            </div>
        </div>
        <?php endfor; ?>
        <?php endif; ?>
        
        <div class="card">
            <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Navigation</h2>
            <p>Test different stages to identify when performance degrades:</p>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 1rem;">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                <a href="?stage=<?= $i ?>" 
                   style="padding: 0.5rem 1rem; background: <?= $i == $stage ? '#3b82f6' : '#e5e7eb' ?>; 
                          color: <?= $i == $stage ? 'white' : '#374151' ?>; 
                          text-decoration: none; border-radius: 0.375rem;">
                    Stage <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Performance monitoring
        let requestCount = 0;
        let pollCount = 0;
        
        // Monitor network requests
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            requestCount++;
            return originalFetch.apply(this, args);
        };
        
        // Monitor XHR
        const originalXHR = window.XMLHttpRequest;
        window.XMLHttpRequest = function() {
            const xhr = new originalXHR();
            const originalSend = xhr.send;
            xhr.send = function(...args) {
                requestCount++;
                return originalSend.apply(this, args);
            };
            return xhr;
        };
        
        // FPS counter
        let fps = 60;
        let lastTime = performance.now();
        let frames = 0;
        
        function measureFPS() {
            frames++;
            const currentTime = performance.now();
            if (currentTime >= lastTime + 1000) {
                fps = Math.round((frames * 1000) / (currentTime - lastTime));
                frames = 0;
                lastTime = currentTime;
            }
            requestAnimationFrame(measureFPS);
        }
        measureFPS();
        
        // Update metrics
        setInterval(() => {
            // Update request counter
            document.getElementById('requests').textContent = requestCount;
            requestCount = 0;
            
            // Update FPS
            document.getElementById('fps').textContent = fps;
            
            // Estimate CPU (based on main thread blocking)
            const start = performance.now();
            let iterations = 0;
            while (performance.now() - start < 10) {
                iterations++;
            }
            const cpu = Math.max(0, 100 - Math.round(iterations / 1000));
            document.getElementById('cpu').textContent = cpu + '%';
            
            // Memory usage (if available)
            if (performance.memory) {
                const mb = Math.round(performance.memory.usedJSHeapSize / 1024 / 1024);
                document.getElementById('memory').textContent = mb + ' MB';
            }
        }, 1000);
        
        <?php if ($stage >= 5): ?>
        // Polling simulation
        setInterval(() => {
            pollCount++;
            document.getElementById('poll-count').textContent = pollCount;
            document.getElementById('poll-time').textContent = new Date().toLocaleTimeString();
            
            // Simulate data processing
            const data = Array.from({length: 1000}, () => Math.random());
            const sum = data.reduce((a, b) => a + b, 0);
        }, 5000);
        <?php endif; ?>
        
        <?php if ($stage >= 6): ?>
        // Multiple widget updates
        <?php for ($i = 1; $i <= 5; $i++): ?>
        setInterval(() => {
            const value = Math.round(Math.random() * 1000);
            const element = document.querySelector('.widget-value-<?= $i ?>');
            if (element) {
                element.textContent = value;
                // Simulate DOM manipulation
                element.style.color = value > 500 ? '#10b981' : '#ef4444';
            }
        }, <?= 1000 + ($i * 500) ?>);
        <?php endfor; ?>
        <?php endif; ?>
        
        // Log stage info
        console.log('Performance Test - Stage <?= $stage ?>');
        console.log('Monitor CPU, Memory, and Network activity in DevTools');
    </script>
</body>
</html>