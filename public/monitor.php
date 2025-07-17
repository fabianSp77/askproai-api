<?php
// Simple auth check
$validUser = 'admin';
$validPass = 'monitor2025!';

if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== $validUser || 
    $_SERVER['PHP_AUTH_PW'] !== $validPass) {
    header('WWW-Authenticate: Basic realm="AskProAI Monitor"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <title>AskProAI System Monitor</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            color: #1a1a1a;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        h1 {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
        }
        .status-bar {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 10px;
            font-size: 14px;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .metric-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        .metric-title {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .metric-value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .metric-subtitle {
            font-size: 13px;
            color: #6c757d;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .healthy { background: #22c55e; }
        .degraded { background: #f59e0b; }
        .unhealthy { background: #ef4444; }
        .text-healthy { color: #22c55e; }
        .text-degraded { color: #f59e0b; }
        .text-unhealthy { color: #ef4444; }
        .details-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .detail-item {
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .detail-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-top: 4px;
        }
        .refresh-info {
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            margin-top: 30px;
        }
        .loading {
            opacity: 0.5;
            pointer-events: none;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .updating {
            animation: pulse 1s infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🚀 AskProAI System Monitor</h1>
            <div class="status-bar">
                <div id="overall-status">
                    <span class="status-indicator healthy"></span>
                    <span>System Status: <strong class="text-healthy">Checking...</strong></span>
                </div>
                <div>|</div>
                <div id="last-update">Last Update: <strong>-</strong></div>
                <div>|</div>
                <div>Response Time: <strong id="response-time">-</strong>ms</div>
            </div>
        </header>

        <div class="metrics-grid" id="metrics-grid">
            <div class="metric-card">
                <div class="metric-title">Database</div>
                <div class="metric-value">-</div>
                <div class="metric-subtitle">Checking...</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Redis Cache</div>
                <div class="metric-value">-</div>
                <div class="metric-subtitle">Checking...</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Queue Jobs</div>
                <div class="metric-value">-</div>
                <div class="metric-subtitle">Checking...</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Disk Usage</div>
                <div class="metric-value">-</div>
                <div class="metric-subtitle">Checking...</div>
            </div>
        </div>

        <div class="details-section">
            <h2>System Details</h2>
            <div class="detail-grid" id="details-grid">
                <!-- Details will be populated here -->
            </div>
        </div>

        <div class="refresh-info">
            Auto-refresh every 5 seconds | <a href="#" onclick="updateMetrics(); return false;">Refresh Now</a>
        </div>
    </div>

    <script>
    let isUpdating = false;

    async function updateMetrics() {
        if (isUpdating) return;
        isUpdating = true;
        
        document.body.classList.add('updating');
        
        try {
            const response = await fetch('/health.php');
            const data = await response.json();
            
            // Update overall status
            const statusElement = document.querySelector('#overall-status');
            const statusIndicator = statusElement.querySelector('.status-indicator');
            const statusText = statusElement.querySelector('strong');
            
            statusIndicator.className = 'status-indicator ' + data.status;
            statusText.className = 'text-' + data.status;
            statusText.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
            
            // Update response time
            document.getElementById('response-time').textContent = data.response_time_ms;
            
            // Update last update time
            document.getElementById('last-update').querySelector('strong').textContent = 
                new Date().toLocaleTimeString('de-DE');
            
            // Update metric cards
            const metricsGrid = document.getElementById('metrics-grid');
            metricsGrid.innerHTML = '';
            
            // Database
            const dbCard = createMetricCard(
                'Database',
                data.checks.database ? '✓' : '✗',
                data.checks.database ? 
                    `${data.details.database.active_companies} active companies` : 
                    'Connection failed',
                data.checks.database ? 'healthy' : 'unhealthy'
            );
            metricsGrid.appendChild(dbCard);
            
            // Redis
            const redisCard = createMetricCard(
                'Redis Cache',
                data.checks.redis ? '✓' : '✗',
                data.checks.redis ? 
                    `${data.details.redis.connected_clients} clients, ${data.details.redis.used_memory_human}` : 
                    'Connection failed',
                data.checks.redis ? 'healthy' : 'unhealthy'
            );
            metricsGrid.appendChild(redisCard);
            
            // Queue
            const queueCard = createMetricCard(
                'Queue Jobs',
                data.details.queue ? data.details.queue.recent_jobs : '-',
                data.details.queue ? 
                    `${data.details.queue.failed_jobs_24h} failed in 24h` : 
                    'No data',
                data.details.queue && data.details.queue.failed_jobs_24h < 10 ? 'healthy' : 'degraded'
            );
            metricsGrid.appendChild(queueCard);
            
            // Disk
            const diskCard = createMetricCard(
                'Disk Usage',
                data.details.disk_space.used_percent + '%',
                `${data.details.disk_space.free_gb}GB free of ${data.details.disk_space.total_gb}GB`,
                data.checks.disk_space ? 'healthy' : 'unhealthy'
            );
            metricsGrid.appendChild(diskCard);
            
            // Update details grid
            const detailsGrid = document.getElementById('details-grid');
            detailsGrid.innerHTML = '';
            
            // PHP Version
            detailsGrid.appendChild(createDetailItem('PHP Version', data.details.php_version));
            
            // Memory Usage
            detailsGrid.appendChild(createDetailItem(
                'Memory Usage', 
                `${data.details.memory.current_mb}MB / ${data.details.memory.limit}`
            ));
            
            // Laravel Cache
            detailsGrid.appendChild(createDetailItem(
                'Laravel Cache',
                data.details.laravel_cache.config_cached ? 'Optimized ✓' : 'Not cached ⚠️'
            ));
            
            // Environment
            detailsGrid.appendChild(createDetailItem(
                'Environment',
                `${data.environment.app_env} (Debug: ${data.environment.app_debug ? 'ON ⚠️' : 'OFF ✓'})`
            ));
            
        } catch (error) {
            console.error('Failed to fetch metrics:', error);
            document.querySelector('#overall-status strong').textContent = 'Error';
        } finally {
            document.body.classList.remove('updating');
            isUpdating = false;
        }
    }
    
    function createMetricCard(title, value, subtitle, status) {
        const card = document.createElement('div');
        card.className = 'metric-card';
        card.innerHTML = `
            <div class="metric-title">${title}</div>
            <div class="metric-value text-${status}">${value}</div>
            <div class="metric-subtitle">${subtitle}</div>
        `;
        return card;
    }
    
    function createDetailItem(label, value) {
        const item = document.createElement('div');
        item.className = 'detail-item';
        item.innerHTML = `
            <div class="detail-label">${label}</div>
            <div class="detail-value">${value}</div>
        `;
        return item;
    }
    
    // Initial load
    updateMetrics();
    
    // Auto-refresh every 5 seconds
    setInterval(updateMetrics, 5000);
    </script>
</body>
</html>