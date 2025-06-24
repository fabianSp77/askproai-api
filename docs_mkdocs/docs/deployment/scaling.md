# Scaling Guide

## Overview

This guide covers strategies for scaling AskProAI to handle increased load, from vertical scaling of a single server to horizontal scaling across multiple servers and regions.

## Scaling Strategies

### Current Load Metrics

Before scaling, understand your current load:

```php
// app/Console/Commands/AnalyzeLoad.php
class AnalyzeLoad extends Command
{
    protected $signature = 'analyze:load';
    
    public function handle()
    {
        $metrics = [
            'requests_per_minute' => $this->getRequestRate(),
            'concurrent_users' => $this->getConcurrentUsers(),
            'database_queries_per_second' => $this->getDatabaseLoad(),
            'queue_jobs_per_minute' => $this->getQueueThroughput(),
            'average_response_time' => $this->getAverageResponseTime(),
            'peak_memory_usage' => $this->getPeakMemoryUsage(),
        ];
        
        $this->table(['Metric', 'Value', 'Threshold', 'Status'], 
            collect($metrics)->map(function ($value, $metric) {
                $threshold = config("scaling.thresholds.{$metric}");
                $status = $value > $threshold * 0.8 ? '⚠️ Warning' : '✅ OK';
                return [$metric, $value, $threshold, $status];
            })->toArray()
        );
    }
}
```

## Vertical Scaling

### Server Upgrade Path

```yaml
# Scaling tiers
Small (Current):
  CPU: 4 cores
  RAM: 8 GB
  Storage: 100 GB SSD
  Users: Up to 100 concurrent

Medium:
  CPU: 8 cores
  RAM: 16 GB
  Storage: 250 GB SSD
  Users: Up to 500 concurrent

Large:
  CPU: 16 cores
  RAM: 32 GB
  Storage: 500 GB SSD
  Users: Up to 1000 concurrent

Extra Large:
  CPU: 32 cores
  RAM: 64 GB
  Storage: 1 TB NVMe
  Users: Up to 2500 concurrent
```

### Optimization Before Scaling

```bash
# PHP-FPM tuning for more cores
# /etc/php/8.2/fpm/pool.d/www.conf

pm = dynamic
pm.max_children = 100  # Was 50
pm.start_servers = 20  # Was 10
pm.min_spare_servers = 10  # Was 5
pm.max_spare_servers = 30  # Was 15
pm.max_requests = 1000

# MySQL tuning for more RAM
# /etc/mysql/mysql.conf.d/mysqld.cnf
innodb_buffer_pool_size = 16G  # 70% of RAM
innodb_buffer_pool_instances = 8
innodb_log_file_size = 2G
thread_cache_size = 100
```

## Horizontal Scaling

### Architecture Overview

```
                    ┌─────────────────┐
                    │  Load Balancer  │
                    │   (HAProxy)     │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
   ┌────▼─────┐        ┌────▼─────┐        ┌────▼─────┐
   │  Web 1   │        │  Web 2   │        │  Web 3   │
   │  Nginx   │        │  Nginx   │        │  Nginx   │
   │  PHP-FPM │        │  PHP-FPM │        │  PHP-FPM │
   └──────────┘        └──────────┘        └──────────┘
        │                    │                    │
        └────────────────────┼────────────────────┘
                             │
                    ┌────────▼────────┐
                    │   Shared Redis  │
                    │  Session Store  │
                    └─────────────────┘
                             │
                    ┌────────▼────────┐
                    │    MySQL       │
                    │  Primary/Replica│
                    └─────────────────┘
```

### Load Balancer Configuration

```nginx
# /etc/haproxy/haproxy.cfg
global
    maxconn 4096
    log /dev/log local0
    chroot /var/lib/haproxy
    stats socket /run/haproxy/admin.sock mode 660 level admin
    stats timeout 30s

defaults
    mode http
    log global
    option httplog
    option dontlognull
    timeout connect 5000
    timeout client 50000
    timeout server 50000

frontend web_frontend
    bind *:80
    bind *:443 ssl crt /etc/ssl/certs/askproai.pem
    redirect scheme https if !{ ssl_fc }
    
    # Rate limiting
    stick-table type ip size 100k expire 30s store http_req_rate(10s)
    http-request track-sc0 src
    http-request deny if { sc_http_req_rate(0) gt 100 }
    
    default_backend web_servers

backend web_servers
    balance roundrobin
    option httpchk GET /health
    
    # Session affinity
    cookie SERVERID insert indirect nocache
    
    server web1 10.0.1.10:80 check cookie web1
    server web2 10.0.1.11:80 check cookie web2
    server web3 10.0.1.12:80 check cookie web3
```

### Database Scaling

#### Read Replica Setup

```sql
-- On primary server
CREATE USER 'replication'@'%' IDENTIFIED BY 'strong_password';
GRANT REPLICATION SLAVE ON *.* TO 'replication'@'%';
FLUSH PRIVILEGES;

-- Get binary log position
SHOW MASTER STATUS;
```

```sql
-- On replica server
CHANGE MASTER TO
    MASTER_HOST='10.0.1.20',
    MASTER_USER='replication',
    MASTER_PASSWORD='strong_password',
    MASTER_LOG_FILE='mysql-bin.000001',
    MASTER_LOG_POS=154;

START SLAVE;
SHOW SLAVE STATUS\G;
```

#### Laravel Configuration for Read Replicas

```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [
            env('DB_READ_HOST_1', '10.0.1.21'),
            env('DB_READ_HOST_2', '10.0.1.22'),
        ],
    ],
    'write' => [
        'host' => [
            env('DB_WRITE_HOST', '10.0.1.20'),
        ],
    ],
    'sticky' => true,
    'driver' => 'mysql',
    // ... rest of config
],
```

### Redis Cluster

#### Redis Sentinel Setup

```bash
# /etc/redis/sentinel.conf
port 26379
sentinel monitor mymaster 10.0.1.30 6379 2
sentinel down-after-milliseconds mymaster 5000
sentinel parallel-syncs mymaster 1
sentinel failover-timeout mymaster 10000
```

#### Laravel Redis Cluster Configuration

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    'clusters' => [
        'default' => [
            [
                'host' => env('REDIS_HOST_1', '10.0.1.30'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => 0,
            ],
            [
                'host' => env('REDIS_HOST_2', '10.0.1.31'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => 0,
            ],
        ],
    ],
    
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'askproai_'),
    ],
],
```

### Queue Scaling

#### Dedicated Queue Workers

```yaml
# docker-compose.queue.yml
version: '3.8'

services:
  queue-high:
    image: askproai:latest
    command: php artisan horizon
    environment:
      - HORIZON_PREFIX=high
      - HORIZON_QUEUE=critical,high
    deploy:
      replicas: 3
      
  queue-default:
    image: askproai:latest
    command: php artisan horizon
    environment:
      - HORIZON_PREFIX=default
      - HORIZON_QUEUE=default
    deploy:
      replicas: 5
      
  queue-low:
    image: askproai:latest
    command: php artisan horizon
    environment:
      - HORIZON_PREFIX=low
      - HORIZON_QUEUE=low,reports
    deploy:
      replicas: 2
```

### File Storage Scaling

#### S3-Compatible Storage

```php
// config/filesystems.php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    ],
    
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
        'throw' => false,
    ],
],

'default' => env('FILESYSTEM_DISK', 's3'),
```

## Auto-Scaling

### AWS Auto Scaling Group

```yaml
# terraform/autoscaling.tf
resource "aws_autoscaling_group" "web_asg" {
  name                = "askproai-web-asg"
  vpc_zone_identifier = aws_subnet.private.*.id
  target_group_arns   = [aws_lb_target_group.web.arn]
  health_check_type   = "ELB"
  health_check_grace_period = 300
  
  min_size         = 2
  max_size         = 10
  desired_capacity = 3
  
  launch_template {
    id      = aws_launch_template.web.id
    version = "$Latest"
  }
  
  tag {
    key                 = "Name"
    value               = "askproai-web"
    propagate_at_launch = true
  }
}

resource "aws_autoscaling_policy" "scale_up" {
  name                   = "askproai-scale-up"
  scaling_adjustment     = 2
  adjustment_type        = "ChangeInCapacity"
  cooldown              = 300
  autoscaling_group_name = aws_autoscaling_group.web_asg.name
}

resource "aws_cloudwatch_metric_alarm" "high_cpu" {
  alarm_name          = "askproai-high-cpu"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "2"
  metric_name         = "CPUUtilization"
  namespace           = "AWS/EC2"
  period              = "120"
  statistic           = "Average"
  threshold           = "80"
  alarm_description   = "This metric monitors ec2 cpu utilization"
  alarm_actions       = [aws_autoscaling_policy.scale_up.arn]
}
```

### Kubernetes Horizontal Pod Autoscaler

```yaml
# k8s/hpa.yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: askproai-web-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: askproai-web
  minReplicas: 3
  maxReplicas: 20
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
  - type: Resource
    resource:
      name: memory
      target:
        type: Utilization
        averageUtilization: 80
  - type: Pods
    pods:
      metric:
        name: http_requests_per_second
      target:
        type: AverageValue
        averageValue: "1000"
```

## Caching Strategy

### Multi-Level Caching

```php
// app/Services/Cache/MultiLevelCache.php
class MultiLevelCache
{
    private array $levels = [];
    
    public function __construct()
    {
        $this->levels = [
            'l1' => new APCuCache(),      // Local memory cache
            'l2' => new RedisCache(),      // Shared Redis cache
            'l3' => new DatabaseCache(),   // Database fallback
        ];
    }
    
    public function get(string $key)
    {
        foreach ($this->levels as $level => $cache) {
            if ($value = $cache->get($key)) {
                // Populate higher levels
                $this->populateUpperLevels($level, $key, $value);
                return $value;
            }
        }
        
        return null;
    }
    
    public function set(string $key, $value, int $ttl = 3600)
    {
        foreach ($this->levels as $cache) {
            $cache->set($key, $value, $ttl);
        }
    }
}
```

### CDN Integration

```nginx
# Cloudflare Page Rules
/*
Cache Level: Cache Everything
Edge Cache TTL: 1 month

/api/*
Cache Level: Bypass
Always Use HTTPS: On

/assets/*
Browser Cache TTL: 1 year
Edge Cache TTL: 1 month
```

## Database Optimization

### Query Optimization

```php
// app/Services/QueryOptimizer.php
class QueryOptimizer
{
    public function optimizeForScale()
    {
        // Use covering indexes
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['company_id', 'date', 'status', 'created_at'], 'covering_idx');
        });
        
        // Partition large tables
        DB::statement('
            ALTER TABLE calls
            PARTITION BY RANGE (YEAR(created_at)) (
                PARTITION p2023 VALUES LESS THAN (2024),
                PARTITION p2024 VALUES LESS THAN (2025),
                PARTITION p2025 VALUES LESS THAN (2026),
                PARTITION p_future VALUES LESS THAN MAXVALUE
            )
        ');
        
        // Archive old data
        DB::statement('
            CREATE TABLE calls_archive LIKE calls;
            INSERT INTO calls_archive 
            SELECT * FROM calls 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
            DELETE FROM calls 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
        ');
    }
}
```

### Connection Pooling

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST'),
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'",
    ],
    'pool' => [
        'min' => 5,
        'max' => 20,
    ],
],
```

## Microservices Architecture

### Service Decomposition

```yaml
# Services breakdown
api-gateway:
  - Authentication
  - Rate limiting
  - Request routing

appointment-service:
  - Appointment CRUD
  - Availability checking
  - Calendar sync

communication-service:
  - Email sending
  - SMS sending
  - WhatsApp integration

billing-service:
  - Subscription management
  - Payment processing
  - Invoice generation

analytics-service:
  - Metrics collection
  - Report generation
  - Real-time dashboards
```

### Service Communication

```php
// app/Services/Microservices/AppointmentClient.php
class AppointmentClient
{
    private $httpClient;
    
    public function __construct()
    {
        $this->httpClient = Http::timeout(5)
            ->retry(3, 100)
            ->baseUrl(config('services.appointment.url'))
            ->withHeaders([
                'X-Service-Token' => config('services.appointment.token'),
            ]);
    }
    
    public function createAppointment(array $data): Appointment
    {
        $response = $this->httpClient->post('/appointments', $data);
        
        if ($response->failed()) {
            throw new ServiceException('Appointment service unavailable');
        }
        
        return new Appointment($response->json());
    }
}
```

## Monitoring at Scale

### Distributed Tracing

```php
// app/Http/Middleware/DistributedTracing.php
class DistributedTracing
{
    public function handle($request, Closure $next)
    {
        $traceId = $request->header('X-Trace-ID') ?? Str::uuid();
        $spanId = Str::uuid();
        
        // Add to context
        Log::shareContext([
            'trace_id' => $traceId,
            'span_id' => $spanId,
        ]);
        
        // Add to response
        $response = $next($request);
        $response->headers->set('X-Trace-ID', $traceId);
        
        // Send to tracing service
        dispatch(function () use ($traceId, $spanId, $request, $response) {
            TracingService::record([
                'trace_id' => $traceId,
                'span_id' => $spanId,
                'service' => 'api-gateway',
                'operation' => $request->route()->getName(),
                'duration' => microtime(true) - LARAVEL_START,
                'status_code' => $response->status(),
            ]);
        })->afterResponse();
        
        return $response;
    }
}
```

### Metrics Aggregation

```yaml
# prometheus.yml
global:
  scrape_interval: 15s

scrape_configs:
  - job_name: 'askproai-web'
    static_configs:
      - targets: ['web1:9090', 'web2:9090', 'web3:9090']
      
  - job_name: 'askproai-queue'
    static_configs:
      - targets: ['queue1:9090', 'queue2:9090']
      
  - job_name: 'mysql'
    static_configs:
      - targets: ['mysql-primary:9104', 'mysql-replica:9104']
```

## Performance Testing

### Load Testing Script

```bash
#!/bin/bash
# load-test.sh

# Test configuration
CONCURRENT_USERS=1000
DURATION=300
RAMP_UP=60

# Run load test
k6 run \
  --vus $CONCURRENT_USERS \
  --duration ${DURATION}s \
  --ramp-up ${RAMP_UP}s \
  load-test.js

# Analyze results
k6 cloud --token $K6_CLOUD_TOKEN
```

```javascript
// load-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '1m', target: 100 },
    { duration: '3m', target: 1000 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
    http_req_failed: ['rate<0.01'],
  },
};

export default function () {
  // Test appointment booking flow
  let response = http.post('https://api.askproai.de/api/appointments', {
    customer_name: 'Test User',
    service_id: 1,
    date: '2025-07-01',
    time: '14:00',
  });
  
  check(response, {
    'status is 201': (r) => r.status === 201,
    'response time < 500ms': (r) => r.timings.duration < 500,
  });
  
  sleep(1);
}
```

## Cost Optimization

### Resource Right-Sizing

```php
// app/Console/Commands/AnalyzeResourceUsage.php
class AnalyzeResourceUsage extends Command
{
    public function handle()
    {
        $metrics = [
            'cpu_utilization' => $this->getCpuUtilization(),
            'memory_utilization' => $this->getMemoryUtilization(),
            'storage_utilization' => $this->getStorageUtilization(),
            'network_utilization' => $this->getNetworkUtilization(),
        ];
        
        $recommendations = [];
        
        if ($metrics['cpu_utilization'] < 20) {
            $recommendations[] = 'Consider downsizing CPU resources';
        }
        
        if ($metrics['memory_utilization'] < 50) {
            $recommendations[] = 'Consider reducing memory allocation';
        }
        
        $this->info('Resource Usage Analysis:');
        $this->table(['Resource', 'Usage', 'Recommendation'], 
            collect($metrics)->map(function ($usage, $resource) use ($recommendations) {
                return [$resource, "{$usage}%", $recommendations[$resource] ?? 'Optimal'];
            })->toArray()
        );
    }
}
```

### Spot Instance Strategy

```yaml
# terraform/spot-instances.tf
resource "aws_spot_fleet_request" "workers" {
  iam_fleet_role = aws_iam_role.spot_fleet.arn
  
  allocation_strategy = "diversified"
  target_capacity     = 10
  valid_until        = "2025-12-31T23:59:59Z"
  
  launch_specification {
    instance_type     = "c5.large"
    ami              = data.aws_ami.askproai.id
    key_name         = aws_key_pair.deployer.key_name
    availability_zone = "eu-central-1a"
    
    user_data = base64encode(templatefile("userdata.sh", {
      role = "queue-worker"
    }))
  }
  
  launch_specification {
    instance_type     = "c5.xlarge"
    ami              = data.aws_ami.askproai.id
    key_name         = aws_key_pair.deployer.key_name
    availability_zone = "eu-central-1b"
    
    user_data = base64encode(templatefile("userdata.sh", {
      role = "queue-worker"
    }))
  }
}
```

## Related Documentation

- [Performance Optimization](../operations/performance.md)
- [Monitoring](../operations/monitoring.md)
- [Production Deployment](production.md)
- [Database Configuration](../configuration/database.md)