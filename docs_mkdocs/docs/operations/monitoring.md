# Operations Monitoring Guide

## Overview

This guide covers the operational aspects of monitoring AskProAI in production, including system monitoring, application monitoring, alerting strategies, and incident response procedures.

## Monitoring Architecture

### Components Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     Monitoring Stack                          │
├─────────────────┬───────────────┬──────────────┬────────────┤
│   Metrics       │     Logs      │   Traces     │  Alerts    │
├─────────────────┼───────────────┼──────────────┼────────────┤
│  Prometheus     │ Elasticsearch │   Jaeger     │ Alertmanager│
│  Grafana        │   Logstash    │   Zipkin     │ PagerDuty  │
│  StatsD         │    Kibana     │              │   Slack    │
└─────────────────┴───────────────┴──────────────┴────────────┘
```

## System Monitoring

### Infrastructure Metrics

#### Server Monitoring
```yaml
# prometheus/targets/servers.yml
- targets:
    - 'web1.askproai.de:9100'
    - 'web2.askproai.de:9100'
    - 'db1.askproai.de:9100'
  labels:
    env: 'production'
    role: 'web'
    
- targets:
    - 'db-primary.askproai.de:9100'
    - 'db-replica.askproai.de:9100'
  labels:
    env: 'production'
    role: 'database'
```

#### Key System Metrics
```yaml
CPU Usage:
  - Threshold: < 80% sustained
  - Alert: > 90% for 5 minutes
  
Memory Usage:
  - Threshold: < 85%
  - Alert: > 90% for 5 minutes
  
Disk Usage:
  - Threshold: < 80%
  - Alert: > 85%
  
Network:
  - Bandwidth: Monitor for anomalies
  - Packet Loss: Alert > 1%
  
Load Average:
  - Threshold: < number of CPUs
  - Alert: > 2x CPUs for 5 minutes
```

### Database Monitoring

#### MySQL Metrics
```sql
-- Key performance queries
-- Connection status
SHOW STATUS WHERE Variable_name IN (
    'Threads_connected',
    'Max_used_connections',
    'Aborted_connects',
    'Connection_errors_internal'
);

-- Query performance
SHOW STATUS WHERE Variable_name IN (
    'Questions',
    'Slow_queries',
    'Select_scan',
    'Select_full_join'
);

-- InnoDB metrics
SHOW STATUS WHERE Variable_name LIKE 'Innodb%';
```

#### Database Dashboards
```json
{
  "dashboard": "MySQL Performance",
  "panels": [
    {
      "title": "Queries Per Second",
      "query": "rate(mysql_global_status_questions[5m])"
    },
    {
      "title": "Slow Queries",
      "query": "rate(mysql_global_status_slow_queries[5m])"
    },
    {
      "title": "Buffer Pool Hit Rate",
      "query": "100 - (rate(mysql_global_status_innodb_buffer_pool_reads[5m]) / rate(mysql_global_status_innodb_buffer_pool_read_requests[5m]) * 100)"
    },
    {
      "title": "Connections",
      "query": "mysql_global_status_threads_connected"
    }
  ]
}
```

### Redis Monitoring

#### Key Redis Metrics
```bash
# Monitor Redis performance
redis-cli INFO stats
redis-cli INFO memory
redis-cli INFO replication
redis-cli --latency
redis-cli --latency-history

# Key metrics to track
- Used memory vs max memory
- Hit rate (keyspace_hits / (keyspace_hits + keyspace_misses))
- Connected clients
- Operations per second
- Evicted keys
- Replication lag
```

## Application Monitoring

### Business Metrics

```php
// app/Services/Monitoring/BusinessMetrics.php
namespace App\Services\Monitoring;

class BusinessMetrics
{
    public function collectMetrics(): void
    {
        // Appointment metrics
        $this->gauge('appointments.total', Appointment::count());
        $this->gauge('appointments.today', Appointment::today()->count());
        $this->gauge('appointments.upcoming_week', Appointment::nextWeek()->count());
        
        // Call metrics
        $this->gauge('calls.active', Call::active()->count());
        $this->counter('calls.completed', Call::completed()->today()->count());
        $this->histogram('calls.duration', Call::today()->avg('duration'));
        
        // Revenue metrics
        $this->gauge('revenue.today', $this->getTodayRevenue());
        $this->gauge('revenue.mtd', $this->getMonthToDateRevenue());
        
        // Customer metrics
        $this->gauge('customers.total', Customer::count());
        $this->gauge('customers.new_today', Customer::createdToday()->count());
        
        // System health
        $this->gauge('queue.size.default', Redis::llen('queues:default'));
        $this->gauge('failed_jobs.count', DB::table('failed_jobs')->count());
    }
    
    private function gauge(string $metric, $value): void
    {
        app('prometheus')->gauge("askproai_{$metric}", $value);
    }
}
```

### API Monitoring

```php
// app/Http/Middleware/ApiMetrics.php
namespace App\Http\Middleware;

class ApiMetrics
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $start;
        
        // Record metrics
        $this->recordMetrics($request, $response, $duration);
        
        return $response;
    }
    
    private function recordMetrics($request, $response, $duration): void
    {
        $tags = [
            'method' => $request->method(),
            'endpoint' => $request->route()?->uri() ?? 'unknown',
            'status' => $response->status(),
        ];
        
        // Response time histogram
        app('statsd')->histogram('api.response_time', $duration * 1000, $tags);
        
        // Request counter
        app('statsd')->increment('api.requests', 1, $tags);
        
        // Error rate
        if ($response->status() >= 400) {
            app('statsd')->increment('api.errors', 1, $tags);
        }
        
        // Log slow requests
        if ($duration > 1) {
            Log::warning('Slow API request', [
                'url' => $request->fullUrl(),
                'duration' => $duration,
                'tags' => $tags,
            ]);
        }
    }
}
```

### Queue Monitoring

```php
// app/Console/Commands/MonitorQueues.php
class MonitorQueues extends Command
{
    protected $signature = 'monitor:queues';
    
    public function handle()
    {
        $queues = ['default', 'high', 'low', 'webhooks', 'emails'];
        
        foreach ($queues as $queue) {
            $size = Redis::llen("queues:{$queue}");
            $processing = Redis::llen("queues:{$queue}:processing");
            $failed = Redis::get("queues:{$queue}:failed") ?? 0;
            
            // Send metrics
            app('prometheus')->gauge("queue_size", $size, ['queue' => $queue]);
            app('prometheus')->gauge("queue_processing", $processing, ['queue' => $queue]);
            app('prometheus')->gauge("queue_failed", $failed, ['queue' => $queue]);
            
            // Alert on high queue size
            if ($size > 1000) {
                $this->alert("Queue {$queue} has {$size} jobs pending");
            }
        }
    }
}
```

## Log Management

### Centralized Logging

```yaml
# filebeat.yml
filebeat.inputs:
- type: log
  enabled: true
  paths:
    - /var/www/api-gateway/storage/logs/*.log
  multiline.pattern: '^\[[0-9]{4}-[0-9]{2}-[0-9]{2}'
  multiline.negate: true
  multiline.match: after
  fields:
    app: askproai
    env: production
    
- type: log
  enabled: true
  paths:
    - /var/log/nginx/*.log
  fields:
    app: nginx
    
output.elasticsearch:
  hosts: ["elasticsearch:9200"]
  index: "askproai-%{[fields.app]}-%{+yyyy.MM.dd}"
```

### Structured Logging

```php
// config/logging.php
'channels' => [
    'production' => [
        'driver' => 'stack',
        'channels' => ['daily', 'elasticsearch'],
    ],
    
    'elasticsearch' => [
        'driver' => 'custom',
        'via' => App\Logging\ElasticsearchLogger::class,
        'hosts' => [env('ELASTICSEARCH_HOST', 'localhost:9200')],
        'index' => 'askproai',
    ],
    
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'info',
        'days' => 14,
        'formatter' => Monolog\Formatter\JsonFormatter::class,
    ],
];
```

### Log Analysis Queries

```json
// Kibana saved searches
{
  "error_logs": {
    "query": "level:error AND app:askproai",
    "columns": ["timestamp", "message", "context.user_id", "context.exception"]
  },
  
  "slow_queries": {
    "query": "message:\"slow query\" AND duration:>1000",
    "columns": ["timestamp", "context.sql", "context.duration"]
  },
  
  "failed_webhooks": {
    "query": "channel:webhooks AND status:failed",
    "columns": ["timestamp", "context.webhook_type", "context.error"]
  },
  
  "api_errors": {
    "query": "status:>=400 AND type:api_request",
    "columns": ["timestamp", "context.url", "context.status", "context.message"]
  }
}
```

## Alerting Strategy

### Alert Levels

```yaml
# alertmanager/config.yml
global:
  resolve_timeout: 5m

route:
  group_by: ['alertname', 'severity']
  group_wait: 10s
  group_interval: 5m
  repeat_interval: 12h
  receiver: 'default'
  
  routes:
    - match:
        severity: critical
      receiver: pagerduty
      continue: true
      
    - match:
        severity: warning
      receiver: slack
      
    - match:
        severity: info
      receiver: email

receivers:
  - name: 'pagerduty'
    pagerduty_configs:
      - service_key: 'YOUR_PAGERDUTY_KEY'
        severity: 'error'
        
  - name: 'slack'
    slack_configs:
      - api_url: 'YOUR_SLACK_WEBHOOK'
        channel: '#alerts'
        title: 'AskProAI Alert'
        
  - name: 'email'
    email_configs:
      - to: 'ops@askproai.de'
        from: 'alerts@askproai.de'
```

### Alert Rules

```yaml
# prometheus/alerts/application.yml
groups:
  - name: application
    rules:
      - alert: HighErrorRate
        expr: rate(api_errors_total[5m]) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "High API error rate"
          description: "Error rate is {{ $value | humanizePercentage }}"
          
      - alert: SlowResponseTime
        expr: histogram_quantile(0.95, api_response_time_bucket) > 1
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "API response time degraded"
          
      - alert: QueueBacklog
        expr: queue_size > 1000
        for: 15m
        labels:
          severity: warning
        annotations:
          summary: "Queue {{ $labels.queue }} has high backlog"
          
      - alert: DatabaseConnectionPool
        expr: mysql_global_status_threads_connected / mysql_global_variables_max_connections > 0.8
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Database connection pool near limit"
```

## Incident Response

### Incident Levels

```yaml
Severity 1 (Critical):
  - Complete service outage
  - Data loss or corruption
  - Security breach
  - Response time: Immediate
  - Escalation: Immediate

Severity 2 (High):
  - Partial service outage
  - Significant performance degradation
  - Major feature unavailable
  - Response time: 30 minutes
  - Escalation: 1 hour

Severity 3 (Medium):
  - Minor feature issues
  - Moderate performance impact
  - Non-critical errors
  - Response time: 2 hours
  - Escalation: 4 hours

Severity 4 (Low):
  - Cosmetic issues
  - Minor bugs
  - Documentation updates
  - Response time: Next business day
  - Escalation: 2 days
```

### Incident Response Playbook

```markdown
## Initial Response (0-15 minutes)
1. **Acknowledge** the alert
2. **Assess** the impact and severity
3. **Communicate** initial status
4. **Create** incident channel/ticket

## Investigation (15-30 minutes)
1. **Gather** information from monitoring
2. **Check** recent deployments
3. **Review** logs and metrics
4. **Identify** root cause

## Mitigation (30+ minutes)
1. **Implement** immediate fix if possible
2. **Scale** resources if needed
3. **Rollback** if necessary
4. **Monitor** for improvement

## Resolution
1. **Verify** issue is resolved
2. **Monitor** for recurrence
3. **Update** status page
4. **Document** findings

## Post-Incident
1. **Schedule** post-mortem (within 48 hours)
2. **Create** incident report
3. **Identify** action items
4. **Update** runbooks
```

### Runbooks

#### High CPU Usage
```bash
#!/bin/bash
# runbook/high-cpu.sh

echo "=== High CPU Usage Runbook ==="

# 1. Identify top processes
echo "Top CPU consuming processes:"
top -b -n 1 | head -20

# 2. Check PHP-FPM processes
echo "PHP-FPM process count:"
ps aux | grep php-fpm | wc -l

# 3. Check for runaway queries
echo "Active MySQL queries:"
mysql -e "SHOW PROCESSLIST" | grep -v Sleep

# 4. Check queue workers
echo "Queue worker status:"
supervisorctl status

# Actions:
# - Restart PHP-FPM if needed: sudo systemctl restart php8.2-fpm
# - Kill long-running queries: mysql -e "KILL QUERY <id>"
# - Scale horizontally if sustained high load
```

#### Database Issues
```sql
-- runbook/database-issues.sql

-- Check connection count
SHOW STATUS WHERE Variable_name = 'Threads_connected';
SHOW VARIABLES WHERE Variable_name = 'max_connections';

-- Check for locks
SELECT * FROM INFORMATION_SCHEMA.INNODB_LOCKS;
SELECT * FROM INFORMATION_SCHEMA.INNODB_LOCK_WAITS;

-- Check slow queries
SELECT * FROM mysql.slow_log ORDER BY query_time DESC LIMIT 10;

-- Check table sizes
SELECT 
    table_schema AS 'Database',
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'askproai_db'
ORDER BY (data_length + index_length) DESC
LIMIT 10;
```

## Performance Monitoring

### Application Performance Monitoring (APM)

```php
// app/Services/APM/PerformanceMonitor.php
class PerformanceMonitor
{
    public function measureDatabasePerformance(): array
    {
        $queries = DB::getQueryLog();
        
        return [
            'total_queries' => count($queries),
            'total_time' => collect($queries)->sum('time'),
            'slow_queries' => collect($queries)->filter(fn($q) => $q['time'] > 100)->count(),
            'average_time' => collect($queries)->avg('time'),
        ];
    }
    
    public function measureCachePerformance(): array
    {
        $stats = Cache::getStore()->getRedis()->info();
        
        return [
            'hit_rate' => $this->calculateHitRate($stats),
            'memory_usage' => $stats['used_memory_human'],
            'evicted_keys' => $stats['evicted_keys'],
            'connected_clients' => $stats['connected_clients'],
        ];
    }
    
    public function measureApiPerformance(): array
    {
        return [
            'requests_per_minute' => Cache::get('api.rpm', 0),
            'average_response_time' => Cache::get('api.avg_response_time', 0),
            'error_rate' => Cache::get('api.error_rate', 0),
            'active_users' => Cache::get('active_users', 0),
        ];
    }
}
```

### Performance Dashboards

```json
// grafana/dashboards/performance.json
{
  "dashboard": {
    "title": "AskProAI Performance",
    "panels": [
      {
        "title": "Request Rate",
        "targets": [{
          "expr": "rate(api_requests_total[5m])"
        }]
      },
      {
        "title": "Response Time Percentiles",
        "targets": [
          {"expr": "histogram_quantile(0.50, api_response_time_bucket)", "legendFormat": "p50"},
          {"expr": "histogram_quantile(0.95, api_response_time_bucket)", "legendFormat": "p95"},
          {"expr": "histogram_quantile(0.99, api_response_time_bucket)", "legendFormat": "p99"}
        ]
      },
      {
        "title": "Database Query Time",
        "targets": [{
          "expr": "rate(mysql_query_duration_seconds_sum[5m]) / rate(mysql_query_duration_seconds_count[5m])"
        }]
      }
    ]
  }
}
```

## Monitoring Automation

### Health Check Automation

```php
// app/Console/Commands/AutomatedHealthCheck.php
class AutomatedHealthCheck extends Command
{
    protected $signature = 'health:check {--alert}';
    
    public function handle()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'disk' => $this->checkDiskSpace(),
            'external_services' => $this->checkExternalServices(),
        ];
        
        $failed = collect($checks)->filter(fn($check) => !$check['healthy']);
        
        if ($failed->isNotEmpty() && $this->option('alert')) {
            $this->sendAlert($failed);
        }
        
        return $failed->isEmpty() ? 0 : 1;
    }
    
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $time = (microtime(true) - $start) * 1000;
            
            return [
                'healthy' => $time < 100,
                'response_time' => $time,
                'message' => $time < 100 ? 'OK' : 'Slow response',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
```

### Monitoring Scripts

```bash
#!/bin/bash
# scripts/monitor-system.sh

# Function to check service
check_service() {
    service=$1
    if systemctl is-active --quiet $service; then
        echo "✓ $service is running"
    else
        echo "✗ $service is down"
        systemctl start $service
    fi
}

# Check critical services
check_service nginx
check_service php8.2-fpm
check_service mysql
check_service redis
check_service supervisor

# Check disk space
df -h | grep -E '^/dev/' | awk '{print $5 " " $6}' | while read usage mount; do
    usage_int=${usage%\%}
    if [ $usage_int -gt 80 ]; then
        echo "⚠️  Disk usage on $mount is $usage"
    fi
done

# Check memory
free_mem=$(free -m | awk 'NR==2{printf "%.2f", $3*100/$2}')
echo "Memory usage: $free_mem%"

# Check load average
load=$(uptime | awk -F'load average:' '{print $2}')
echo "Load average: $load"
```

## Monitoring Best Practices

1. **Set Meaningful Alerts**: Avoid alert fatigue with relevant thresholds
2. **Use SLIs/SLOs**: Define Service Level Indicators and Objectives
3. **Monitor Business Metrics**: Track what matters to the business
4. **Automate Responses**: Create runbooks and automated remediation
5. **Regular Reviews**: Review and tune alerts monthly
6. **Capacity Planning**: Use metrics for proactive scaling
7. **Documentation**: Keep runbooks and procedures updated

## Related Documentation

- [Deployment Monitoring](../deployment/monitoring.md)
- [Performance Optimization](performance.md)
- [Troubleshooting Guide](troubleshooting.md)
- [Incident Response](../operations/incident-response.md)