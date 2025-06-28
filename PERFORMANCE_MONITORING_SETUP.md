# Performance Monitoring Setup - Completed

## Overview
The AskProAI platform now has a complete Prometheus + Grafana performance monitoring stack configured and ready for production use.

## What Was Implemented

### 1. Prometheus Metrics Collection
- **MetricsCollector Service**: Full-featured metrics collection using the Prometheus PHP library
- **MetricsMiddleware**: Automatically tracks HTTP request metrics
- **Custom Business Metrics**: Tracks bookings, calls, webhooks, and more
- **System Metrics**: Monitors queue sizes, database connections, and active tenants

### 2. Metrics Endpoint
- **URL**: `/api/metrics`
- **Authentication**: Bearer token (configured via `METRICS_AUTH_TOKEN` env variable)
- **Format**: Prometheus text format (OpenMetrics compatible)

### 3. Automated Collection
- **Scheduled Job**: Runs every minute to collect and update metrics
- **Command**: `php artisan metrics:collect`
- **Manual Run**: `php artisan metrics:collect --immediate`

### 4. Docker Compose Stack
- **Prometheus**: Metrics storage and querying
- **Grafana**: Visualization and dashboards
- **Loki**: Log aggregation
- **Alertmanager**: Alert routing and notifications
- **Node Exporter**: System metrics
- **Redis Exporter**: Redis metrics

### 5. Grafana Dashboard
Created a comprehensive AskProAI System Dashboard with:
- API Response Time (95th percentile)
- Request Rate gauge
- Queue Sizes over time
- Database Connections graph
- Bookings Today counter
- Error Rate percentage

## Configuration

### Environment Variables
```env
# Monitoring and Metrics
METRICS_ENABLED=true
METRICS_AUTH_TOKEN=your-secure-token-here
MONITORING_ENABLED=true
SENTRY_LARAVEL_DSN=your-sentry-dsn
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### Starting the Monitoring Stack
```bash
# Start all monitoring services
docker-compose -f docker-compose.observability.yml up -d

# Check status
docker-compose -f docker-compose.observability.yml ps

# View logs
docker-compose -f docker-compose.observability.yml logs -f
```

### Accessing Dashboards
- **Prometheus**: http://localhost:9090
- **Grafana**: http://localhost:3000 (admin/admin)
- **Alertmanager**: http://localhost:9093

## Key Metrics Being Tracked

### HTTP Metrics
- `askproai_http_requests_total` - Total HTTP requests by method, endpoint, and status
- `askproai_http_request_duration_seconds` - Request duration histogram

### Business Metrics
- `askproai_bookings_total` - Total bookings by status and source
- `askproai_calls_total` - Total calls by status and company
- `askproai_webhooks_total` - Webhook events by provider and status
- `askproai_webhook_processing_duration_seconds` - Webhook processing time

### System Metrics
- `askproai_queue_size` - Current queue sizes by queue name
- `askproai_database_connections` - Active database connections
- `askproai_active_tenants` - Number of active tenants
- `askproai_errors_total` - Error counts by type and severity

## Performance Alerts Configured

Based on SLA targets defined in CLAUDE.md:

### API Performance
- **Warning**: Response time > 200ms (p95)
- **Critical**: Response time > 500ms (p95)

### Admin Dashboard
- **Warning**: Page load time > 1s (p95)

### Webhooks
- **Warning**: Processing time > 500ms (p95)

### Database
- **Warning**: Query time > 100ms
- **Warning**: Connections > 80 (limit is 100)

### Queue Processing
- **Warning**: Queue size > 1000 jobs
- **Critical**: Job timeout > 30s

### System Resources
- **Warning**: Memory usage > 85%
- **Warning**: CPU usage > 80%
- **Warning**: Disk usage > 90%

## Usage Examples

### Testing Metrics Collection
```bash
# Manually trigger metrics collection
php artisan metrics:collect --immediate

# View raw metrics
curl -H "Authorization: Bearer your-token-here" https://api.askproai.de/api/metrics
```

### Recording Custom Metrics
```php
// In your application code
$collector = app(MetricsCollector::class);

// Record a booking
$collector->recordBooking('confirmed', 'phone');

// Record webhook processing
$collector->recordWebhook('retell', 'call_ended', 'success', 0.350);

// Update queue size
$collector->updateQueueSize('default', 42);

// Record an error
$collector->recordError('payment_failed', 'critical');
```

## Troubleshooting

### Redis Connection Issues
If you see Redis authentication errors:
1. Check Redis password in `.env`
2. Ensure Redis is running: `redis-cli ping`
3. Verify Redis database 2 is available for metrics

### Missing Metrics
If metrics aren't appearing:
1. Check if the scheduled job is running: `php artisan schedule:list`
2. Manually run collection: `php artisan metrics:collect --immediate`
3. Check logs: `tail -f storage/logs/laravel.log`

### Grafana Dashboard Issues
If dashboards aren't loading:
1. Verify Prometheus datasource is configured
2. Check Prometheus is scraping: http://localhost:9090/targets
3. Ensure Laravel metrics endpoint is accessible

## Next Steps

1. **Configure Alerting**:
   - Set up email/Slack notifications in Alertmanager
   - Define alert routing rules
   - Test alert notifications

2. **Custom Dashboards**:
   - Create business-specific dashboards
   - Add customer success metrics
   - Build executive dashboards

3. **Long-term Storage**:
   - Configure Prometheus retention policies
   - Set up remote storage for historical data
   - Plan capacity for metrics growth

## Security Considerations

1. **Metrics Endpoint**: Always use strong authentication token
2. **Network**: Consider putting monitoring stack on internal network
3. **Data Sensitivity**: Avoid exposing customer PII in metrics labels
4. **Access Control**: Restrict Grafana access to authorized personnel

---

*Completed: 2025-06-28*
*Next Task: Alerting System Implementation*