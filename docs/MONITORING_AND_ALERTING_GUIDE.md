# Production Monitoring and Alerting Guide

This guide documents the comprehensive monitoring and alerting system implemented for the AskProAI platform, with a focus on the Stripe integration and customer portal.

## Overview

The monitoring system provides:
- Real-time health checks
- Performance monitoring
- Security tracking
- Business metrics
- Automated alerting
- Prometheus/Grafana integration

## Architecture

### Core Components

1. **Health Check Service** (`HealthCheckService`)
   - Database connectivity
   - Redis availability
   - Stripe API status
   - Queue sizes
   - Disk space
   - Memory usage

2. **Performance Monitor** (`PerformanceMonitor`)
   - Transaction tracking
   - API call monitoring
   - Query performance
   - Slow transaction detection

3. **Security Monitor** (`SecurityMonitor`)
   - Failed login tracking
   - Suspicious activity detection
   - API key usage monitoring
   - Rate limit violations
   - IP blocking

4. **Alerting Service** (`AlertingService`)
   - Multi-channel alerts (Email, Slack, SMS)
   - Threshold-based triggering
   - Alert throttling
   - Active alert management

## Configuration

### Environment Variables

```env
# Monitoring
MONITORING_ENABLED=true

# Sentry Error Tracking
SENTRY_LARAVEL_DSN=your-sentry-dsn
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.1

# Alert Recipients
ALERT_EMAIL_RECIPIENTS=admin@example.com,ops@example.com
ALERT_SLACK_WEBHOOK=https://hooks.slack.com/services/xxx
ALERT_SMS_RECIPIENTS=+1234567890

# Health Check Secret
HEALTH_CHECK_SECRET=your-secret-key
METRICS_SECRET=your-metrics-secret

# Log Levels
LOG_LEVEL_MONITORING=debug
LOG_LEVEL_SECURITY=warning
LOG_LEVEL_PERFORMANCE=info
LOG_LEVEL_STRIPE=info
LOG_LEVEL_PORTAL=info
```

### Configuration File

The main configuration is in `config/monitoring.php`:

```php
return [
    'enabled' => true,
    'sentry' => [...],
    'apm' => [...],
    'health_checks' => [...],
    'alerts' => [...],
    'logging' => [...],
    'metrics' => [...],
    'security' => [...],
];
```

## Health Checks

### Endpoint

```
GET /api/health
X-Health-Check-Secret: your-secret-key
```

### Response Example

```json
{
  "status": "healthy",
  "timestamp": "2025-06-19T10:00:00Z",
  "checks": [
    {
      "name": "database",
      "critical": true,
      "status": "ok",
      "message": "Database connection is healthy",
      "duration": 5.23
    },
    {
      "name": "stripe_api",
      "critical": true,
      "status": "ok",
      "message": "Stripe API connection is healthy",
      "meta": {
        "mode": "test"
      },
      "duration": 145.67
    }
  ]
}
```

### Critical vs Warning Checks

- **Critical**: System marked as unhealthy if these fail
  - Database
  - Redis
  - Stripe API
  
- **Warning**: System operational but degraded
  - Queue size > 1000
  - Disk usage > 90%
  - Memory usage > 85%

## Performance Monitoring

### Transaction Monitoring

```php
// In your controller or service
$monitor = app(PerformanceMonitor::class);
$monitor->startTransaction('stripe_webhook');

// Your code here...

$monitor->endTransaction('stripe_webhook', [
    'status_code' => 200,
    'webhook_type' => 'payment_intent.succeeded'
]);
```

### API Call Monitoring

```php
$result = $monitor->monitorApiCall('stripe', function () {
    return \Stripe\PaymentIntent::create([...]);
});
```

### Slow Query Detection

Queries slower than 100ms are automatically logged to `storage/logs/performance.log`.

## Security Monitoring

### Events Tracked

- Failed login attempts
- Suspicious activity
- API key usage patterns
- Privilege escalation attempts
- Large data exports
- Rate limit violations

### Automatic IP Blocking

IPs are automatically blocked for 24 hours after:
- 5 failed login attempts in 15 minutes
- 20 security events in 1 hour
- Excessive rate limit violations

### Security Logs

All security events are logged to:
- Database: `security_logs` table
- File: `storage/logs/security.log`

## Alerting Rules

### Payment Failures
- **Trigger**: 3 failures in 5 minutes
- **Severity**: Critical
- **Channels**: Email, Slack, SMS

### Security Breach Attempts
- **Trigger**: 5 attempts in 1 minute
- **Severity**: Critical
- **Channels**: Email, Slack, SMS

### Stripe Webhook Failures
- **Trigger**: 5 failures in 5 minutes
- **Severity**: High
- **Channels**: Email, Slack

### High Error Rate
- **Trigger**: > 5% error rate
- **Severity**: High
- **Channels**: Email, Slack

### Database Connection Failures
- **Trigger**: 3 failures in 1 minute
- **Severity**: Critical
- **Channels**: Email, Slack, SMS

## Metrics Collection

### Prometheus Endpoint

```
GET /api/metrics
X-Metrics-Secret: your-metrics-secret
```

### Available Metrics

```
# System
askproai_up
askproai_php_memory_usage_bytes
askproai_php_memory_peak_bytes

# HTTP
askproai_http_requests_total
askproai_http_request_duration_ms
askproai_http_requests_errors_total

# Queue
askproai_queue_size{queue="default"}
askproai_queue_size{queue="webhooks"}
askproai_queue_size{queue="stripe"}

# Business
askproai_subscriptions_created_total
askproai_revenue_processed_cents
askproai_portal_registrations_total
askproai_portal_logins_total

# Security
askproai_failed_logins_total
askproai_blocked_ips_total
askproai_rate_limit_violations_total

# External APIs
askproai_external_api_calls_total{service="stripe"}
askproai_external_api_errors_total{service="stripe"}
askproai_external_api_duration_ms{service="stripe"}
```

## Logging Strategy

### Log Channels

1. **monitoring** - General monitoring events
2. **security** - Security-related events
3. **performance** - Performance issues
4. **stripe** - Stripe-specific logs
5. **portal** - Customer portal logs
6. **webhooks** - Webhook processing logs

### Structured Logging

All logs include:
- Correlation ID
- User/Company context
- Request metadata
- Timestamp

### Sensitive Data Masking

The following fields are automatically masked:
- password
- stripe_secret
- api_key
- token
- card_number
- cvv
- ssn
- tax_id

## Grafana Dashboards

### System Overview
- Request rate
- Error rate
- Response times
- Queue sizes
- Active users

### Stripe Integration
- Payment success rate
- Webhook processing time
- Failed payments
- Dispute rate
- Revenue metrics

### Customer Portal
- Registration rate
- Login frequency
- Page load times
- User actions
- Error tracking

### Security Dashboard
- Failed login attempts
- Blocked IPs
- Rate limit violations
- Suspicious activities
- Security trends

## Testing Monitoring

### Command Line Test

```bash
php artisan monitoring:test --all
```

Options:
- `--health` - Test health checks
- `--alert` - Test alerting
- `--performance` - Test performance monitoring
- `--security` - Test security monitoring
- `--all` - Test all components

### Manual Alert Test

```php
app(AlertingService::class)->alert('payment_failure', [
    'count' => 5,
    'window' => 5,
]);
```

## Production Deployment Checklist

1. **Environment Configuration**
   - [ ] Set all monitoring environment variables
   - [ ] Configure Sentry DSN
   - [ ] Set up alert recipients
   - [ ] Configure health check secret

2. **External Services**
   - [ ] Verify Sentry project exists
   - [ ] Test Slack webhook
   - [ ] Configure SMS provider (if using)
   - [ ] Set up Prometheus/Grafana

3. **Database**
   - [ ] Run migration for security_logs table
   - [ ] Verify indexes are created

4. **Testing**
   - [ ] Run `php artisan monitoring:test --all`
   - [ ] Verify health endpoint responds
   - [ ] Test alert delivery
   - [ ] Check metrics endpoint

5. **Monitoring**
   - [ ] Import Grafana dashboards
   - [ ] Set up Prometheus scraping
   - [ ] Configure alert rules in Grafana
   - [ ] Test end-to-end monitoring flow

## Troubleshooting

### Health Check Failing

1. Check logs: `tail -f storage/logs/monitoring.log`
2. Verify services: `php artisan monitoring:test --health`
3. Check connectivity to external services

### Alerts Not Sending

1. Verify configuration: `php artisan config:cache`
2. Check alert channels are configured
3. Test manually: `php artisan monitoring:test --alert`
4. Check logs: `tail -f storage/logs/laravel.log`

### Performance Issues

1. Check slow query log: `tail -f storage/logs/performance.log`
2. Review metrics: `/api/metrics`
3. Check APM data in monitoring dashboard
4. Enable query explain: `EXPLAIN_QUERIES=true`

### Security Events

1. Review security log: `tail -f storage/logs/security.log`
2. Check blocked IPs: Query `security_logs` table
3. Review rate limiting configuration
4. Check for patterns in failed attempts

## Best Practices

1. **Regular Reviews**
   - Weekly review of security logs
   - Daily check of critical alerts
   - Monthly performance analysis

2. **Alert Fatigue Prevention**
   - Tune thresholds based on actual data
   - Use appropriate severity levels
   - Implement smart throttling

3. **Documentation**
   - Document all custom alerts
   - Keep runbooks updated
   - Train team on monitoring tools

4. **Continuous Improvement**
   - Monitor false positive rate
   - Adjust thresholds quarterly
   - Add new metrics as needed
   - Regular disaster recovery drills