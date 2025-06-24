# Monitoring Setup Guide

## Overview

This guide covers the complete monitoring setup for AskProAI, including application monitoring, infrastructure monitoring, alerting, and visualization dashboards.

## Monitoring Stack

### Components Overview

```yaml
Application Monitoring:
  - Laravel Telescope (Development)
  - Sentry (Error tracking)
  - New Relic APM (Performance)
  
Infrastructure Monitoring:
  - Prometheus (Metrics collection)
  - Grafana (Visualization)
  - Node Exporter (System metrics)
  - MySQL Exporter (Database metrics)
  
Log Management:
  - ELK Stack (Elasticsearch, Logstash, Kibana)
  - Fluentd (Log forwarding)
  
Alerting:
  - Alertmanager (Prometheus alerts)
  - PagerDuty (On-call management)
  - Slack (Team notifications)
```

## Application Monitoring

### Laravel Telescope Setup

```bash
# Install Telescope (development only)
composer require laravel/telescope --dev

# Publish assets
php artisan telescope:install

# Run migrations
php artisan migrate

# Configure access in TelescopeServiceProvider
public function gate()
{
    Gate::define('viewTelescope', function ($user) {
        return in_array($user->email, [
            'admin@askproai.de',
        ]);
    });
}
```

### Sentry Integration

```php
// config/sentry.php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    
    'release' => trim(exec('git describe --tags --abbrev=0 2>/dev/null')) ?: 'unknown',
    
    'environment' => env('APP_ENV'),
    
    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
        'queue_info' => true,
        'command_info' => true,
    ],
    
    'tracing' => [
        'transaction_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
        'trace_propagation_targets' => [
            'api.askproai.de',
            'api.retellai.com',
            'api.cal.com',
        ],
    ],
    
    'send_default_pii' => false,
    
    'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
        // Filter out sensitive data
        if ($exception = $hint->getException()) {
            if ($exception instanceof \Illuminate\Database\QueryException) {
                // Remove SQL with potential sensitive data
                $event->setExceptions([]);
            }
        }
        
        return $event;
    },
];
```

### Custom Metrics Collection

```php
// app/Services/Monitoring/MetricsCollector.php
class MetricsCollector
{
    private $prometheus;
    
    public function __construct()
    {
        $this->prometheus = app('prometheus');
    }
    
    public function recordApiRequest(string $endpoint, float $duration, int $statusCode)
    {
        // Record request duration
        $this->prometheus->histogram(
            'api_request_duration_seconds',
            'API request duration',
            ['endpoint', 'method', 'status'],
            [0.01, 0.05, 0.1, 0.5, 1, 2.5, 5, 10]
        )->observe($duration, [$endpoint, request()->method(), $statusCode]);
        
        // Increment request counter
        $this->prometheus->counter(
            'api_requests_total',
            'Total API requests',
            ['endpoint', 'method', 'status']
        )->inc([$endpoint, request()->method(), $statusCode]);
    }
    
    public function recordBusinessMetric(string $metric, float $value, array $labels = [])
    {
        $this->prometheus->gauge(
            "business_{$metric}",
            "Business metric: {$metric}",
            array_keys($labels)
        )->set($value, array_values($labels));
    }
}
```

## Infrastructure Monitoring

### Prometheus Setup

```yaml
# docker-compose.monitoring.yml
version: '3.8'

services:
  prometheus:
    image: prom/prometheus:latest
    container_name: prometheus
    volumes:
      - ./prometheus/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--web.console.libraries=/usr/share/prometheus/console_libraries'
      - '--web.console.templates=/usr/share/prometheus/consoles'
      - '--web.enable-lifecycle'
    ports:
      - "9090:9090"
    restart: unless-stopped

  node_exporter:
    image: prom/node-exporter:latest
    container_name: node_exporter
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.sysfs=/host/sys'
      - '--path.rootfs=/rootfs'
      - '--collector.filesystem.mount-points-exclude=^/(sys|proc|dev|host|etc)($$|/)'
    ports:
      - "9100:9100"
    restart: unless-stopped

  mysql_exporter:
    image: prom/mysqld-exporter:latest
    container_name: mysql_exporter
    environment:
      DATA_SOURCE_NAME: "exporter:password@(mysql:3306)/"
    ports:
      - "9104:9104"
    restart: unless-stopped

volumes:
  prometheus_data:
```

### Prometheus Configuration

```yaml
# prometheus/prometheus.yml
global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    environment: 'production'
    region: 'eu-central-1'

# Alerting
alerting:
  alertmanagers:
    - static_configs:
        - targets: ['alertmanager:9093']

# Rule files
rule_files:
  - "alerts/*.yml"

# Scrape configurations
scrape_configs:
  # Application metrics
  - job_name: 'askproai-app'
    static_configs:
      - targets: ['app:9091']
    relabel_configs:
      - source_labels: [__address__]
        target_label: instance
        replacement: 'askproai-web'

  # Node metrics
  - job_name: 'node'
    static_configs:
      - targets: ['node_exporter:9100']

  # MySQL metrics
  - job_name: 'mysql'
    static_configs:
      - targets: ['mysql_exporter:9104']

  # Redis metrics
  - job_name: 'redis'
    static_configs:
      - targets: ['redis_exporter:9121']

  # Nginx metrics
  - job_name: 'nginx'
    static_configs:
      - targets: ['nginx_exporter:9113']
```

### Grafana Dashboards

```json
// grafana/dashboards/askproai-overview.json
{
  "dashboard": {
    "title": "AskProAI Overview",
    "panels": [
      {
        "title": "Request Rate",
        "targets": [
          {
            "expr": "rate(api_requests_total[5m])",
            "legendFormat": "{{method}} {{endpoint}}"
          }
        ],
        "type": "graph"
      },
      {
        "title": "Response Time (95th percentile)",
        "targets": [
          {
            "expr": "histogram_quantile(0.95, rate(api_request_duration_seconds_bucket[5m]))",
            "legendFormat": "{{endpoint}}"
          }
        ],
        "type": "graph"
      },
      {
        "title": "Active Calls",
        "targets": [
          {
            "expr": "business_active_calls",
            "legendFormat": "Active Calls"
          }
        ],
        "type": "stat"
      },
      {
        "title": "Appointments Today",
        "targets": [
          {
            "expr": "business_appointments_today",
            "legendFormat": "Appointments"
          }
        ],
        "type": "stat"
      }
    ]
  }
}
```

## Log Management

### ELK Stack Setup

```yaml
# docker-compose.elk.yml
version: '3.8'

services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.8.0
    container_name: elasticsearch
    environment:
      - discovery.type=single-node
      - "ES_JAVA_OPTS=-Xms2g -Xmx2g"
      - xpack.security.enabled=false
    volumes:
      - elasticsearch_data:/usr/share/elasticsearch/data
    ports:
      - "9200:9200"

  logstash:
    image: docker.elastic.co/logstash/logstash:8.8.0
    container_name: logstash
    volumes:
      - ./logstash/pipeline:/usr/share/logstash/pipeline
    ports:
      - "5000:5000"
      - "9600:9600"
    depends_on:
      - elasticsearch

  kibana:
    image: docker.elastic.co/kibana/kibana:8.8.0
    container_name: kibana
    environment:
      - ELASTICSEARCH_HOSTS=http://elasticsearch:9200
    ports:
      - "5601:5601"
    depends_on:
      - elasticsearch

volumes:
  elasticsearch_data:
```

### Logstash Configuration

```ruby
# logstash/pipeline/logstash.conf
input {
  tcp {
    port => 5000
    codec => json
  }
  
  file {
    path => "/var/log/askproai/*.log"
    start_position => "beginning"
    codec => "json"
  }
}

filter {
  # Parse Laravel logs
  if [type] == "laravel" {
    grok {
      match => {
        "message" => "\[%{TIMESTAMP_ISO8601:timestamp}\] %{DATA:env}\.%{LOGLEVEL:level}: %{GREEDYDATA:log_message}"
      }
    }
    
    date {
      match => [ "timestamp", "ISO8601" ]
    }
  }
  
  # Parse nginx access logs
  if [type] == "nginx-access" {
    grok {
      match => {
        "message" => '%{IPORHOST:remote_addr} - %{DATA:remote_user} \[%{HTTPDATE:time_local}\] "%{WORD:method} %{URIPATHPARAM:request} HTTP/%{NUMBER:http_version}" %{NUMBER:status} %{NUMBER:body_bytes_sent} "%{DATA:http_referer}" "%{DATA:http_user_agent}"'
      }
    }
  }
  
  # Add GeoIP information
  if [remote_addr] {
    geoip {
      source => "remote_addr"
      target => "geoip"
    }
  }
}

output {
  elasticsearch {
    hosts => ["elasticsearch:9200"]
    index => "askproai-%{type}-%{+YYYY.MM.dd}"
  }
  
  # Send critical errors to monitoring
  if [level] == "ERROR" or [level] == "CRITICAL" {
    http {
      url => "http://monitoring-webhook/alert"
      http_method => "post"
      format => "json"
    }
  }
}
```

## Alerting Rules

### Prometheus Alert Rules

```yaml
# prometheus/alerts/application.yml
groups:
  - name: application
    interval: 30s
    rules:
      - alert: HighErrorRate
        expr: rate(api_requests_total{status=~"5.."}[5m]) > 0.05
        for: 5m
        labels:
          severity: critical
          team: backend
        annotations:
          summary: "High error rate detected"
          description: "Error rate is {{ $value | humanizePercentage }} for {{ $labels.endpoint }}"

      - alert: SlowResponseTime
        expr: histogram_quantile(0.95, rate(api_request_duration_seconds_bucket[5m])) > 1
        for: 10m
        labels:
          severity: warning
          team: backend
        annotations:
          summary: "Slow API response time"
          description: "95th percentile response time is {{ $value }}s for {{ $labels.endpoint }}"

      - alert: HighMemoryUsage
        expr: (node_memory_MemTotal_bytes - node_memory_MemAvailable_bytes) / node_memory_MemTotal_bytes > 0.9
        for: 5m
        labels:
          severity: warning
          team: ops
        annotations:
          summary: "High memory usage"
          description: "Memory usage is {{ $value | humanizePercentage }}"

  - name: business
    interval: 60s
    rules:
      - alert: NoCallsProcessed
        expr: increase(business_calls_processed[1h]) == 0
        for: 1h
        labels:
          severity: warning
          team: business
        annotations:
          summary: "No calls processed in the last hour"
          description: "Check Retell.ai integration"

      - alert: HighBookingFailureRate
        expr: rate(business_booking_failures[5m]) / rate(business_booking_attempts[5m]) > 0.1
        for: 15m
        labels:
          severity: critical
          team: backend
        annotations:
          summary: "High booking failure rate"
          description: "Booking failure rate is {{ $value | humanizePercentage }}"
```

### Alertmanager Configuration

```yaml
# alertmanager/alertmanager.yml
global:
  resolve_timeout: 5m
  slack_api_url: 'YOUR_SLACK_WEBHOOK_URL'

route:
  group_by: ['alertname', 'cluster', 'service']
  group_wait: 10s
  group_interval: 10s
  repeat_interval: 12h
  receiver: 'default'
  
  routes:
    - match:
        severity: critical
      receiver: 'pagerduty-critical'
      continue: true
      
    - match:
        team: backend
      receiver: 'backend-team'
      
    - match:
        team: ops
      receiver: 'ops-team'

receivers:
  - name: 'default'
    slack_configs:
      - channel: '#alerts'
        title: 'AskProAI Alert'
        text: '{{ range .Alerts }}{{ .Annotations.summary }}{{ end }}'

  - name: 'pagerduty-critical'
    pagerduty_configs:
      - service_key: 'YOUR_PAGERDUTY_SERVICE_KEY'
        description: '{{ .GroupLabels.alertname }}'

  - name: 'backend-team'
    slack_configs:
      - channel: '#backend-alerts'
        send_resolved: true

  - name: 'ops-team'
    slack_configs:
      - channel: '#ops-alerts'
        send_resolved: true
```

## Custom Monitoring Dashboards

### Business Metrics Dashboard

```php
// app/Http/Controllers/Admin/MonitoringController.php
class MonitoringController extends Controller
{
    public function dashboard()
    {
        $metrics = [
            'real_time' => $this->getRealTimeMetrics(),
            'hourly' => $this->getHourlyMetrics(),
            'daily' => $this->getDailyMetrics(),
            'alerts' => $this->getActiveAlerts(),
        ];
        
        return view('admin.monitoring.dashboard', compact('metrics'));
    }
    
    private function getRealTimeMetrics()
    {
        return Cache::remember('metrics:realtime', 10, function () {
            return [
                'active_calls' => Call::where('status', 'in_progress')->count(),
                'queue_size' => Redis::llen('queues:default'),
                'response_time' => $this->getAverageResponseTime(),
                'error_rate' => $this->getCurrentErrorRate(),
                'active_users' => Redis::scard('active_users'),
            ];
        });
    }
    
    private function getHourlyMetrics()
    {
        return Cache::remember('metrics:hourly', 300, function () {
            $hour = now()->subHour();
            
            return [
                'calls_received' => Call::where('created_at', '>=', $hour)->count(),
                'appointments_booked' => Appointment::where('created_at', '>=', $hour)->count(),
                'conversion_rate' => $this->getConversionRate($hour),
                'revenue' => $this->getRevenue($hour),
            ];
        });
    }
}
```

### Real-time Monitoring View

```blade
{{-- resources/views/admin/monitoring/dashboard.blade.php --}}
@extends('layouts.admin')

@section('content')
<div class="monitoring-dashboard" x-data="monitoringDashboard()">
    <!-- Real-time Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="metric-card">
            <h3>Active Calls</h3>
            <div class="metric-value" x-text="metrics.active_calls">-</div>
        </div>
        
        <div class="metric-card">
            <h3>Queue Size</h3>
            <div class="metric-value" x-text="metrics.queue_size">-</div>
        </div>
        
        <div class="metric-card">
            <h3>Response Time</h3>
            <div class="metric-value">
                <span x-text="metrics.response_time"></span>ms
            </div>
        </div>
        
        <div class="metric-card">
            <h3>Error Rate</h3>
            <div class="metric-value" 
                 :class="{'text-red-600': metrics.error_rate > 5}">
                <span x-text="metrics.error_rate"></span>%
            </div>
        </div>
        
        <div class="metric-card">
            <h3>Active Users</h3>
            <div class="metric-value" x-text="metrics.active_users">-</div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="chart-container">
            <h3>Request Rate (Last Hour)</h3>
            <canvas id="requestRateChart"></canvas>
        </div>
        
        <div class="chart-container">
            <h3>Response Times (Last Hour)</h3>
            <canvas id="responseTimeChart"></canvas>
        </div>
    </div>
    
    <!-- Active Alerts -->
    <div class="alerts-section mt-6">
        <h3>Active Alerts</h3>
        <div class="alerts-list">
            <template x-for="alert in alerts" :key="alert.id">
                <div class="alert-item" 
                     :class="'alert-' + alert.severity">
                    <span class="alert-time" x-text="alert.time"></span>
                    <span class="alert-message" x-text="alert.message"></span>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function monitoringDashboard() {
    return {
        metrics: @json($metrics['real_time']),
        alerts: @json($metrics['alerts']),
        
        init() {
            // Update metrics every 10 seconds
            setInterval(() => this.updateMetrics(), 10000);
            
            // Initialize charts
            this.initCharts();
        },
        
        async updateMetrics() {
            try {
                const response = await fetch('/admin/monitoring/metrics');
                const data = await response.json();
                this.metrics = data.real_time;
                this.alerts = data.alerts;
            } catch (error) {
                console.error('Failed to update metrics:', error);
            }
        },
        
        initCharts() {
            // Request rate chart
            new Chart(document.getElementById('requestRateChart'), {
                type: 'line',
                data: {
                    labels: @json($metrics['hourly']['labels']),
                    datasets: [{
                        label: 'Requests/min',
                        data: @json($metrics['hourly']['request_rate']),
                        borderColor: 'rgb(59, 130, 246)',
                        tension: 0.1
                    }]
                }
            });
            
            // Response time chart
            new Chart(document.getElementById('responseTimeChart'), {
                type: 'line',
                data: {
                    labels: @json($metrics['hourly']['labels']),
                    datasets: [{
                        label: 'p50',
                        data: @json($metrics['hourly']['p50']),
                        borderColor: 'rgb(34, 197, 94)'
                    }, {
                        label: 'p95',
                        data: @json($metrics['hourly']['p95']),
                        borderColor: 'rgb(251, 191, 36)'
                    }, {
                        label: 'p99',
                        data: @json($metrics['hourly']['p99']),
                        borderColor: 'rgb(239, 68, 68)'
                    }]
                }
            });
        }
    };
}
</script>
@endsection
```

## Health Checks

### Application Health Endpoint

```php
// app/Http/Controllers/HealthController.php
class HealthController extends Controller
{
    public function check()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'filesystem' => $this->checkFilesystem(),
            'services' => $this->checkExternalServices(),
            'queue' => $this->checkQueue(),
        ];
        
        $status = collect($checks)->every(fn($check) => $check['status'] === 'healthy') 
            ? 'healthy' 
            : 'unhealthy';
        
        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version'),
            'checks' => $checks,
        ], $status === 'healthy' ? 200 : 503);
    }
    
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $duration = (microtime(true) - $start) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round($duration, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    private function checkExternalServices(): array
    {
        $services = [
            'retell' => $this->checkRetell(),
            'calcom' => $this->checkCalcom(),
            'stripe' => $this->checkStripe(),
        ];
        
        $healthyCount = collect($services)->filter(fn($s) => $s['status'] === 'healthy')->count();
        
        return [
            'status' => $healthyCount === count($services) ? 'healthy' : 'degraded',
            'services' => $services,
        ];
    }
}
```

### Automated Health Monitoring

```php
// app/Console/Commands/MonitorHealth.php
class MonitorHealth extends Command
{
    protected $signature = 'monitor:health';
    
    public function handle()
    {
        $response = Http::timeout(5)->get(config('app.url') . '/health');
        
        if ($response->failed() || $response->json('status') !== 'healthy') {
            // Send alert
            Notification::route('slack', config('monitoring.slack_webhook'))
                ->notify(new HealthCheckFailed($response->json()));
            
            // Log to monitoring system
            Log::channel('monitoring')->error('Health check failed', [
                'response' => $response->json(),
                'status_code' => $response->status(),
            ]);
            
            return 1;
        }
        
        // Record successful health check
        Cache::put('last_health_check', now(), 300);
        
        return 0;
    }
}
```

## Performance Monitoring

### APM Integration

```php
// app/Providers/MonitoringServiceProvider.php
class MonitoringServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Track database queries
        if (config('monitoring.track_queries')) {
            DB::listen(function ($query) {
                if ($query->time > 100) { // Log slow queries
                    Log::channel('slow-queries')->warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                }
                
                // Send to APM
                if (app()->bound('newrelic')) {
                    app('newrelic')->recordDatastoreSegment(
                        'MySQL',
                        $query->sql,
                        $query->time
                    );
                }
            });
        }
        
        // Track HTTP requests
        Event::listen(RequestHandled::class, function ($event) {
            $duration = microtime(true) - LARAVEL_START;
            
            app(MetricsCollector::class)->recordApiRequest(
                $event->request->route()->uri ?? 'unknown',
                $duration,
                $event->response->getStatusCode()
            );
        });
    }
}
```

## Monitoring Best Practices

### 1. **Alert Fatigue Prevention**
- Set appropriate thresholds
- Use alert grouping and deduplication
- Implement escalation policies
- Regular alert review and tuning

### 2. **Data Retention**
```yaml
Metrics retention:
  - Real-time: 24 hours
  - 5-minute aggregates: 7 days
  - Hourly aggregates: 30 days
  - Daily aggregates: 1 year

Log retention:
  - Application logs: 30 days
  - Access logs: 90 days
  - Audit logs: 2 years
  - Debug logs: 7 days
```

### 3. **Dashboard Design**
- Focus on actionable metrics
- Use consistent color coding
- Implement drill-down capabilities
- Mobile-responsive design

### 4. **Incident Response**
```markdown
## Incident Response Playbook

1. **Detection**
   - Automated alert received
   - Verify issue is real (not false positive)
   - Assess severity and impact

2. **Response**
   - Acknowledge alert in PagerDuty
   - Join incident channel in Slack
   - Begin investigation using dashboards

3. **Mitigation**
   - Apply immediate fixes if possible
   - Scale resources if needed
   - Communicate with stakeholders

4. **Resolution**
   - Verify issue is resolved
   - Monitor for recurrence
   - Document root cause

5. **Post-mortem**
   - Schedule blameless post-mortem
   - Identify action items
   - Update monitoring/alerts
```

## Related Documentation

- [Performance Optimization](../operations/performance.md)
- [Alerting Configuration](../operations/alerting.md)
- [Logging Strategy](../operations/logging.md)
- [Incident Response](../operations/incident-response.md)