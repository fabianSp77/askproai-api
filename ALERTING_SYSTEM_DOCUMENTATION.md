# AskProAI Alerting System Documentation

## Overview

The AskProAI Alerting System provides comprehensive monitoring and notification capabilities to ensure system reliability and rapid incident response. It supports multiple notification channels and intelligent alert routing based on severity and type.

## Features

- **Multi-Channel Notifications**: Email, Slack, SMS (configurable)
- **Intelligent Throttling**: Prevents alert spam
- **Severity-Based Routing**: Critical alerts get priority treatment
- **Rich Formatting**: Detailed context in notifications
- **Asynchronous Processing**: Non-blocking alert delivery
- **Health Check Automation**: Proactive issue detection

## Alert Types

### 1. Payment Failures
- **Trigger**: Multiple payment processing failures
- **Threshold**: 3 failures in 5 minutes
- **Severity**: Critical
- **Channels**: Email, Slack, SMS

### 2. Security Breach Attempts
- **Trigger**: Suspicious activity or unauthorized access attempts
- **Threshold**: 5 attempts in 60 seconds
- **Severity**: Critical
- **Channels**: Email, Slack, SMS

### 3. Stripe Webhook Failures
- **Trigger**: Failed webhook processing
- **Threshold**: 5 failures in 5 minutes
- **Severity**: High
- **Channels**: Email, Slack

### 4. High Error Rate
- **Trigger**: System-wide error rate exceeds threshold
- **Threshold**: 5% error rate in 5 minutes
- **Severity**: High
- **Channels**: Email, Slack

### 5. Database Connection Failures
- **Trigger**: Unable to connect to database
- **Threshold**: 3 failures in 60 seconds
- **Severity**: Critical
- **Channels**: Email, Slack, SMS

### 6. Queue Backlog
- **Trigger**: Excessive jobs in queue
- **Threshold**: 1000 pending jobs
- **Severity**: Medium
- **Channels**: Email

### 7. Portal Downtime
- **Trigger**: Customer portal unavailable
- **Threshold**: Any downtime
- **Severity**: Critical
- **Channels**: Email, Slack, SMS

## Configuration

### Environment Variables

```bash
# Email Alerts
ALERT_EMAIL_ENABLED=true
ALERT_EMAIL_RECIPIENTS=admin@example.com,ops@example.com

# Slack Alerts
ALERT_SLACK_ENABLED=true
ALERT_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
ALERT_SLACK_CHANNEL=#alerts

# SMS Alerts
ALERT_SMS_ENABLED=true
ALERT_SMS_RECIPIENTS=+491234567890,+491234567891

# Thresholds
ALERT_API_SUCCESS_THRESHOLD=90
ALERT_ERROR_RATE_THRESHOLD=5
ALERT_RESPONSE_TIME_THRESHOLD=2000
ALERT_QUEUE_BACKLOG_THRESHOLD=1000
```

### Slack Webhook Setup

1. Go to https://api.slack.com/apps
2. Create a new app or select existing
3. Add "Incoming Webhooks" feature
4. Create webhook for your channel
5. Copy webhook URL to `.env`

## Usage

### Manual Alert Testing

```bash
# Test all channels
php artisan alerts:test

# Test specific channel
php artisan alerts:test email
php artisan alerts:test slack
php artisan alerts:test sms

# Test specific alert rule
php artisan alerts:test --rule=payment_failure
php artisan alerts:test --rule=high_error_rate
```

### Health Check Command

```bash
# Run health check manually
php artisan monitoring:health-check

# Dry run (no alerts sent)
php artisan monitoring:health-check --dry-run
```

### Programmatic Usage

```php
use App\Services\Monitoring\UnifiedAlertingService;

// Inject service
public function __construct(UnifiedAlertingService $alertingService)
{
    $this->alertingService = $alertingService;
}

// Trigger custom alert
$this->alertingService->alert('custom_rule', [
    'message' => 'Custom alert message',
    'context' => ['key' => 'value']
]);

// Record event for threshold checking
$this->alertingService->recordEvent('payment_failure');
```

### Payment Failure Tracking

```php
use App\Traits\TracksPaymentFailures;

class PaymentService
{
    use TracksPaymentFailures;
    
    public function processPayment()
    {
        try {
            // Payment logic
        } catch (PaymentException $e) {
            $this->recordPaymentFailure(
                'stripe',
                $e->getCode(),
                $e->getMessage(),
                $customerId,
                ['amount' => $amount]
            );
        }
    }
}
```

## Alert Message Templates

### Email Template
Location: `resources/views/emails/monitoring-alert.blade.php`

Features:
- Color-coded severity indicators
- Structured data presentation
- Direct link to monitoring dashboard
- Action guidelines based on severity

### Slack Message Format
- Rich attachments with color coding
- Interactive buttons for quick actions
- Contextual fields
- Timestamp and footer information

## Monitoring Dashboard

Access the monitoring dashboard at `/admin/monitoring` to:
- View active alerts
- Check system metrics
- Review alert history
- Configure alert rules
- Test notification channels

## Scheduled Health Checks

The system automatically runs health checks every 5 minutes via:
```php
$schedule->command('monitoring:health-check')
    ->everyFiveMinutes()
    ->withoutOverlapping()
```

## Alert Throttling

To prevent alert fatigue:
- Same alert type is throttled for 15 minutes
- Threshold-based alerts require minimum occurrences
- Critical alerts bypass some throttling rules

## Database Schema

### system_alerts table
- `id`: UUID
- `rule`: Alert rule name
- `severity`: critical/high/medium/low
- `message`: Alert message
- `data`: JSON context data
- `created_at`: Timestamp

### payment_failures table
- `id`: Primary key
- `payment_method`: stripe/paypal/etc
- `error_code`: Provider error code
- `error_message`: Error details
- `customer_id`: Optional customer reference
- `metadata`: JSON additional data
- `created_at`: Timestamp

### security_logs table
- `type`: Event type
- `ip_address`: Source IP
- `user_agent`: Browser info
- `url`: Requested URL
- `method`: HTTP method
- `user_id`: Optional user ID
- `created_at`: Timestamp

## Integration with Circuit Breaker

The alerting system integrates with the circuit breaker pattern:
- Open circuit breakers trigger immediate alerts
- Service degradation is monitored
- Automatic recovery notifications

## Performance Considerations

- Alerts are processed asynchronously via queues
- Database queries are optimized with indexes
- Metrics are cached to reduce load
- Webhook calls have 5-second timeout

## Troubleshooting

### Alerts Not Sending

1. Check environment configuration
2. Verify webhook URLs are accessible
3. Check queue workers are running
4. Review logs in `storage/logs/monitoring.log`

### Too Many Alerts

1. Adjust thresholds in `.env`
2. Increase throttle duration
3. Review alert rules configuration
4. Check for underlying system issues

### Slack Integration Issues

1. Verify webhook URL is correct
2. Check Slack app permissions
3. Test with curl command:
```bash
curl -X POST -H 'Content-type: application/json' \
  --data '{"text":"Test message"}' \
  YOUR_WEBHOOK_URL
```

## Best Practices

1. **Start Conservative**: Begin with higher thresholds and adjust down
2. **Test Regularly**: Use test commands to verify channels work
3. **Document Changes**: Log threshold adjustments and reasons
4. **Review Metrics**: Check dashboard weekly for patterns
5. **Automate Responses**: Create runbooks for common alerts

## Future Enhancements

- PagerDuty integration
- Custom alert rules via UI
- Alert acknowledgment workflow
- Automated remediation actions
- Machine learning for anomaly detection