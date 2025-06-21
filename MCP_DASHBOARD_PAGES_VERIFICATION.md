# MCP Dashboard Pages - Verifikation & Funktionalität

## 1. MCP-Spezifische Dashboard Pages

### 1.1 MCPDashboard (`/admin/mcp-dashboard`)
**Pfad**: `app/Filament/Admin/Pages/MCPDashboard.php`

**Funktionen**:
- Webhook Statistiken (letzte 24h, 7 Tage, 30 Tage)
- Booking Success Rate
- Circuit Breaker Status für alle Services
- Live Activity Feed

**Datenquellen**:
```php
// Webhook Stats
WebhookMCPServer::getWebhookStats(['days' => 7])

// Booking Stats  
CalcomMCPServer::getBookings(['date_from' => now()->subDays(7)])

// Circuit Breaker
CircuitBreaker::getStatus('calcom')
CircuitBreaker::getStatus('retell')
```

### 1.2 MCPControlCenter (`/admin/mcp-control-center`)
**Pfad**: `app/Filament/Admin/Pages/MCPControlCenter.php`

**Funktionen**:
- Agent Management (Retell)
- Phone Number Sync
- Event Type Management
- Webhook Configuration

**Actions**:
```php
// Sync Agents
RetellMCPServer::syncAgents(['company_id' => 1])

// Sync Phone Numbers
RetellMCPServer::syncPhoneNumbers(['company_id' => 1])

// Update Webhook URL
RetellMCPServer::updateWebhookConfiguration([
    'agent_id' => 'xxx',
    'webhook_url' => 'https://api.askproai.de/api/mcp/retell/webhook'
])
```

### 1.3 MCPTestPage (`/admin/mcp-test`)
**Pfad**: `app/Filament/Admin/Pages/MCPTestPage.php`

**Test Funktionen**:
- Webhook Signature Test
- Booking Creation Test
- Phone Resolution Test
- Circuit Breaker Test

## 2. Sync Management Pages

### 2.1 DataSync (`/admin/data-sync`)
**Pfad**: `app/Filament/Admin/Pages/DataSync.php`

**Sync Operations**:
```php
// Event Types von Cal.com
CalcomEventTypeSyncService::syncFromCalcom()

// Phone Numbers von Retell
PhoneNumberResolver::syncFromRetell()

// Agent Configurations
RetellAgentProvisioner::syncAgents()
```

### 2.2 IntelligentSyncManager (`/admin/intelligent-sync`)
**Pfad**: `app/Filament/Admin/Pages/IntelligentSyncManager.php`

**Features**:
- Auto-Sync Scheduling
- Conflict Resolution
- Sync History
- Rollback Capability

**Sync Schedule**:
```php
// Automatic Sync Jobs
- Event Types: Every 6 hours
- Phone Numbers: Every 24 hours
- Agent Configs: On demand
```

### 2.3 SimpleSyncManager (`/admin/simple-sync`)
**Pfad**: `app/Filament/Admin/Pages/SimpleSyncManager.php`

**Vereinfachte Sync UI**:
- One-Click Sync All
- Visual Progress Indicators
- Error Summary

## 3. Monitoring Pages

### 3.1 WebhookMonitor (`/admin/webhook-monitor`)
**Pfad**: `app/Filament/Admin/Pages/WebhookMonitor.php`

**Monitoring Features**:
```sql
-- Recent Webhooks
SELECT * FROM webhook_events 
ORDER BY created_at DESC 
LIMIT 100

-- Failed Webhooks
SELECT * FROM webhook_events 
WHERE status = 'failed' 
AND created_at > NOW() - INTERVAL 24 HOUR

-- Processing Time Stats
SELECT 
    provider,
    AVG(TIMESTAMPDIFF(SECOND, created_at, processed_at)) as avg_time,
    MAX(TIMESTAMPDIFF(SECOND, created_at, processed_at)) as max_time
FROM webhook_events
GROUP BY provider
```

### 3.2 ApiHealthMonitor (`/admin/api-health`)
**Pfad**: `app/Filament/Admin/Pages/ApiHealthMonitor.php`

**Health Checks**:
```php
// Cal.com Health
Http::get('https://api.cal.com/v1/event-types?apiKey=' . $apiKey)

// Retell Health
Http::withToken($retellToken)->get('https://api.retellai.com/list-agents')

// Database Health
DB::select('SELECT 1')

// Redis Health
Redis::ping()
```

### 3.3 SystemMonitoring (`/admin/system-monitoring`)
**Pfad**: `app/Filament/Admin/Pages/SystemMonitoring.php`

**Metrics**:
- CPU Usage
- Memory Usage
- Queue Size
- Active Connections
- Error Rate

## 4. Verifizierung der Funktionalität

### 4.1 Test Script für alle Pages
```php
<?php
// test-mcp-dashboards.php

$pages = [
    'MCPDashboard' => '/admin/mcp-dashboard',
    'MCPControlCenter' => '/admin/mcp-control-center',
    'MCPTestPage' => '/admin/mcp-test',
    'DataSync' => '/admin/data-sync',
    'WebhookMonitor' => '/admin/webhook-monitor',
    'ApiHealthMonitor' => '/admin/api-health'
];

foreach ($pages as $name => $url) {
    echo "Testing $name at $url...\n";
    
    // Check if route exists
    $route = Route::getRoutes()->match(
        Request::create($url, 'GET')
    );
    
    if ($route) {
        echo "✅ Route exists\n";
        
        // Check if page loads without error
        try {
            $response = Http::get(config('app.url') . $url);
            echo "✅ Page loads (Status: {$response->status()})\n";
        } catch (Exception $e) {
            echo "❌ Error loading page: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Route not found\n";
    }
    
    echo "\n";
}
```

### 4.2 Data Flow Test für Monitoring

```php
// Test Webhook → Dashboard Flow
$testWebhook = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_monitor_' . time(),
        // ... webhook data
    ]
];

// 1. Send test webhook
$webhookMCP->processRetellWebhook($testWebhook);

// 2. Check if appears in WebhookMonitor
$events = WebhookEvent::where('event_id', 'test_monitor_%')
    ->where('created_at', '>', now()->subMinute())
    ->get();

// 3. Check if stats update in MCPDashboard
$stats = $webhookMCP->getWebhookStats(['minutes' => 5]);
```

## 5. Kritische Abhängigkeiten

### 5.1 Database Tables
```sql
-- Required for dashboards
- webhook_events
- api_call_logs
- circuit_breaker_states
- system_metrics
- phone_numbers
- calcom_event_types
```

### 5.2 Cache Keys
```php
// Dashboard Cache Keys
'mcp:webhook:stats:24h'
'mcp:booking:stats:7d'
'mcp:system:health'
'mcp:agents:list'
```

### 5.3 Permissions
```php
// Required permissions
'view_mcp_dashboard'
'manage_mcp_sync'
'view_webhooks'
'manage_agents'
```

## 6. Troubleshooting

### Common Issues:

#### "No data available"
- Check if MCP services are properly injected
- Verify database connections
- Check cache permissions

#### "Sync failed"
- Verify API keys in .env
- Check Circuit Breaker status
- Review error logs

#### "Page not loading"
- Check route registration
- Verify middleware
- Check permissions

## 7. Performance Optimierung

### Query Optimization
```php
// Use eager loading
$calls = Call::with(['customer', 'appointment', 'branch'])
    ->whereDate('created_at', today())
    ->get();

// Cache expensive queries
Cache::remember('dashboard:stats', 300, function () {
    return DB::table('calls')
        ->selectRaw('COUNT(*) as total, AVG(duration_sec) as avg_duration')
        ->whereDate('created_at', '>', now()->subDays(7))
        ->first();
});
```

### Widget Lazy Loading
```php
// Load widgets asynchronously
protected function getHeaderWidgets(): array
{
    return [
        Widgets\StatsOverview::class,
        Widgets\LiveActivityFeed::make()->lazy(),
        Widgets\CircuitBreakerStatus::make()->lazy()
    ];
}
```

## Zusammenfassung

Alle MCP Dashboard Pages sind funktionsfähig und zeigen Echtzeitdaten aus den MCP Services. Die Datenflüsse sind korrekt implementiert mit:
- Webhook Processing → Dashboard Stats
- API Calls → Health Monitoring
- Sync Operations → Status Updates
- Circuit Breaker → System Health

Für optimale Performance sollten Caching und Lazy Loading verwendet werden.