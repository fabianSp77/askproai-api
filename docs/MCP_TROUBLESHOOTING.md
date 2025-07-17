# MCP Troubleshooting Guide

## Common Issues and Solutions

This guide helps you diagnose and fix common MCP-related issues.

## 🚨 Quick Diagnostics

### 1. Check Overall Health
```bash
php artisan mcp:health
php artisan mcp:health --json | jq '.'
```

### 2. Test Specific Server
```bash
php artisan mcp exec --server=retell --tool=health_check
```

### 3. View Recent Errors
```bash
tail -f storage/logs/laravel.log | grep -E "(MCP|mcp)"
tail -f storage/logs/laravel.log | grep -i error
```

### 4. Clear All Caches
```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
```

## ❌ Common Error Messages

### "Call to undefined method MCPOrchestrator::..."

**Cause**: Method doesn't exist or typo in method name

**Solution**:
```bash
# Clear autoload cache
composer dump-autoload

# Check if class exists
php artisan tinker
>>> class_exists('App\Services\MCP\MCPOrchestrator')
>>> get_class_methods(app('App\Services\MCP\MCPOrchestrator'))
```

### "Server not found: xxx"

**Cause**: Server not registered or disabled

**Solution**:
1. Check configuration:
```php
// config/mcp-servers.php
'my_server' => [
    'enabled' => true, // Must be true
    'class' => \App\Services\MCP\MyServerMCPServer::class,
],
```

2. Register in provider:
```php
// app/Providers/MCPServiceProvider.php
$this->app->singleton(MyServerMCPServer::class);
```

3. Clear config cache:
```bash
php artisan config:clear
```

### "Tool not found: xxx"

**Cause**: Tool not defined in server

**Solution**:
```php
// Check available tools
$server = app(MyServerMCPServer::class);
$tools = $server->getTools();
dd(array_column($tools, 'name'));
```

### "Missing required parameter: xxx"

**Cause**: Required parameter not provided

**Solution**:
```bash
# Check tool definition
php artisan tinker
>>> $server = app('App\Services\MCP\AppointmentMCPServer')
>>> $tools = $server->getTools()
>>> collect($tools)->where('name', 'create_appointment')->first()
```

## 🔧 Server-Specific Issues

### Retell.ai Integration

#### Problem: "No calls imported"
```bash
# Check API connection
php artisan mcp exec --server=retell --tool=test_connection

# Check webhook registration
curl -X GET https://api.retellai.com/v2/list-calls \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json"

# Manual import
php import-retell-calls-manual.php
```

#### Problem: "Webhook not processing"
```bash
# Test webhook endpoint
curl -X POST https://api.askproai.de/api/retell/webhook \
  -H "Content-Type: application/json" \
  -H "x-retell-signature: test" \
  -d '{"event": "call_ended", "call": {"id": "test"}}'

# Check webhook logs
tail -f storage/logs/laravel.log | grep -i webhook
```

### Cal.com Integration

#### Problem: "Calendar sync failed"
```bash
# Check API key
php artisan tinker
>>> config('services.calcom.api_key')

# Test connection
php artisan mcp exec --server=calcom --tool=list_calendars

# Force sync
php artisan calcom:sync --force
```

#### Problem: "Event not found"
```php
// Debug event lookup
$result = app(MCPOrchestrator::class)->execute('calcom', 'get_event', [
    'event_id' => 12345,
    'debug' => true
]);
dd($result);
```

### Database Server

#### Problem: "Query timeout"
```php
// Increase timeout for specific query
$result = $orchestrator->execute('database', 'execute_query', [
    'query' => 'SELECT ...',
    'timeout' => 60, // 60 seconds
    'chunked' => true, // Process in chunks
    'chunk_size' => 1000
]);
```

#### Problem: "Access denied"
```bash
# Check database permissions
mysql -u askproai_user -p
SHOW GRANTS;

# Test connection
php artisan tinker
>>> DB::select('SELECT 1');
```

### Memory Bank

#### Problem: "Memory not found"
```bash
# List all memories
php artisan memory:list

# Search with debug
php artisan memory:search --query="test" --debug

# Clear corrupted entries
php artisan memory:cleanup --type=corrupted
```

#### Problem: "Storage full"
```bash
# Check storage usage
php artisan memory:stats

# Cleanup old entries
php artisan memory:cleanup --older-than=30 --type=session_context
```

## 🐛 Debugging Techniques

### 1. Enable Debug Mode

```php
// In your code
$result = $orchestrator->execute('server', 'tool', [
    'debug' => true,
    // other params
]);

// Via CLI
MCP_DEBUG=true php artisan mcp exec --server=X --tool=Y
```

### 2. Trace Execution

```php
// Add to AppServiceProvider
if (config('app.debug')) {
    Event::listen('mcp.executing', function ($server, $tool, $params) {
        Log::debug('MCP Executing', [
            'server' => $server,
            'tool' => $tool,
            'params' => $params,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ]);
    });
}
```

### 3. Profile Performance

```php
$start = microtime(true);
$result = $orchestrator->execute($server, $tool, $params);
$duration = (microtime(true) - $start) * 1000;

Log::info('MCP Performance', [
    'server' => $server,
    'tool' => $tool,
    'duration_ms' => $duration
]);
```

### 4. Monitor Memory Usage

```php
$before = memory_get_usage();
$result = $orchestrator->execute($server, $tool, $params);
$after = memory_get_usage();

Log::info('MCP Memory', [
    'server' => $server,
    'tool' => $tool,
    'memory_used' => ($after - $before) / 1024 / 1024 . ' MB'
]);
```

## 🔍 Advanced Debugging

### Using Telescope

```php
// Install Laravel Telescope
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

// View MCP requests at /telescope/requests
```

### Custom Debug Headers

```php
// In MCPOrchestrator
public function execute($server, $tool, $params)
{
    $requestId = Str::uuid();
    
    Log::withContext([
        'mcp_request_id' => $requestId,
        'mcp_server' => $server,
        'mcp_tool' => $tool
    ]);
    
    // ... execution logic
}
```

### Debug Dashboard Widget

```php
// Create debug widget
class MCPDebugWidget extends Widget
{
    public function render()
    {
        $recentErrors = Cache::get('mcp_recent_errors', []);
        $slowQueries = Cache::get('mcp_slow_queries', []);
        
        return view('widgets.mcp-debug', [
            'errors' => $recentErrors,
            'slowQueries' => $slowQueries
        ]);
    }
}
```

## 🚑 Emergency Procedures

### 1. Server Crash Recovery

```bash
# Kill stuck processes
pkill -f "mcp-server"

# Clear corrupted cache
redis-cli FLUSHDB

# Restart services
php artisan horizon:terminate
php artisan horizon
```

### 2. Database Lock Resolution

```sql
-- Find locked queries
SHOW PROCESSLIST;

-- Kill specific query
KILL QUERY <process_id>;

-- Clear all locks (CAREFUL!)
UNLOCK TABLES;
```

### 3. Queue Failure Recovery

```bash
# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs older than 24h
php artisan queue:flush 1440

# Process specific queue
php artisan queue:work --queue=mcp --tries=1
```

## 📊 Performance Issues

### Slow Execution

1. **Check metrics**:
```bash
php artisan mcp:metrics --server=appointment
```

2. **Enable query logging**:
```php
DB::enableQueryLog();
$result = $orchestrator->execute($server, $tool, $params);
$queries = DB::getQueryLog();
```

3. **Profile code**:
```php
$profiler = new Profiler();
$profiler->start('mcp_execution');
// ... code ...
$profiler->stop('mcp_execution');
$profiler->report();
```

### High Memory Usage

1. **Check for leaks**:
```php
// In long-running process
gc_collect_cycles();
$memBefore = memory_get_usage();
// ... operation ...
$memAfter = memory_get_usage();
if (($memAfter - $memBefore) > 10 * 1024 * 1024) { // 10MB
    Log::warning('Possible memory leak detected');
}
```

2. **Optimize queries**:
```php
// Instead of loading all
$allCustomers = Customer::all();

// Use chunking
Customer::chunk(100, function ($customers) {
    // Process chunk
});
```

## 🛠️ Maintenance Scripts

### Daily Health Check
```bash
#!/bin/bash
# health-check.sh

echo "MCP Health Check - $(date)"
echo "========================"

# Check all servers
php artisan mcp:health --json > /tmp/mcp-health.json

# Check for errors
if grep -q '"healthy":false' /tmp/mcp-health.json; then
    echo "ERROR: Unhealthy servers detected!"
    cat /tmp/mcp-health.json | jq '.servers | to_entries[] | select(.value.healthy == false)'
    
    # Send alert
    curl -X POST https://hooks.slack.com/services/YOUR/WEBHOOK/URL \
        -H 'Content-type: application/json' \
        -d '{"text":"MCP Health Check Failed!"}'
fi

# Check error rate
ERROR_COUNT=$(tail -1000 storage/logs/laravel.log | grep -c "MCP.*error")
if [ $ERROR_COUNT -gt 50 ]; then
    echo "WARNING: High error rate detected: $ERROR_COUNT errors in last 1000 lines"
fi

echo "Health check complete"
```

### Performance Monitor
```php
// app/Console/Commands/MCPMonitorCommand.php
class MCPMonitorCommand extends Command
{
    protected $signature = 'mcp:monitor';
    
    public function handle()
    {
        $this->info('Monitoring MCP performance...');
        
        while (true) {
            $metrics = [];
            
            foreach (config('mcp-servers.servers') as $name => $config) {
                if (!$config['enabled']) continue;
                
                $start = microtime(true);
                try {
                    $result = app(MCPOrchestrator::class)->execute($name, 'health_check', []);
                    $metrics[$name] = [
                        'healthy' => $result['success'] ?? false,
                        'response_time' => (microtime(true) - $start) * 1000
                    ];
                } catch (\Exception $e) {
                    $metrics[$name] = [
                        'healthy' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            Cache::put('mcp_monitor_metrics', $metrics, 300);
            $this->table(['Server', 'Status', 'Response Time'], 
                collect($metrics)->map(fn($m, $s) => [
                    $s, 
                    $m['healthy'] ? '✅' : '❌',
                    ($m['response_time'] ?? 0) . 'ms'
                ])->toArray()
            );
            
            sleep(60); // Check every minute
        }
    }
}
```

## 📞 Getting Help

1. **Check documentation**: `/docs/MCP_*.md`
2. **Run diagnostics**: `php artisan mcp:diagnose`
3. **Ask AI**: `php artisan mcp discover`
4. **Contact team**: Use `#mcp-support` Slack channel

Remember: Most issues can be resolved by:
- Clearing caches
- Checking configurations
- Reviewing logs
- Running health checks