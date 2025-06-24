# Analytics & Reporting

## Overview

AskProAI provides comprehensive analytics and reporting features to help businesses understand their call patterns, appointment trends, and customer behavior.

## Dashboard Metrics

### Real-Time Statistics
- Active calls in progress
- Today's appointments
- Call volume trends
- Conversion rates

### Key Performance Indicators (KPIs)
```php
// Available metrics
$metrics = [
    'total_calls' => Call::today()->count(),
    'appointments_booked' => Appointment::today()->count(),
    'conversion_rate' => $appointments / $calls * 100,
    'average_call_duration' => Call::today()->avg('duration'),
    'no_show_rate' => Appointment::noShow()->percentage(),
];
```

## Reports

### Call Analytics
- Call volume by hour/day/week
- Peak calling times
- Average call duration
- Call outcomes distribution
- Failed call reasons

### Appointment Analytics
- Booking trends
- Service popularity
- Staff utilization
- No-show patterns
- Revenue analysis

### Customer Analytics
- New vs returning customers
- Customer lifetime value
- Appointment frequency
- Geographic distribution

## Report Generation

### Automated Reports
```php
// Schedule weekly report
$report = new WeeklyAnalyticsReport($company);
$report->generate()
    ->sendTo($company->admin_email);
```

### Custom Reports
```php
// Custom report builder
$report = ReportBuilder::create()
    ->forCompany($company)
    ->dateRange($start, $end)
    ->metrics(['calls', 'appointments', 'revenue'])
    ->groupBy('branch')
    ->generate();
```

## Visualizations

### Charts & Graphs
- Line charts for trends
- Bar charts for comparisons
- Pie charts for distributions
- Heat maps for busy times

### Export Options
- PDF reports
- Excel spreadsheets
- CSV data exports
- API access for BI tools

## Real-Time Monitoring

### Live Dashboard
```javascript
// WebSocket connection for live updates
Echo.channel(`company.${companyId}`)
    .listen('CallStarted', (e) => {
        updateActiveCalls(e.call);
    })
    .listen('AppointmentBooked', (e) => {
        updateBookingStats(e.appointment);
    });
```

### Alerts & Notifications
```php
// Configure alerts
Alert::create([
    'type' => 'high_call_volume',
    'threshold' => 50, // calls per hour
    'notify_via' => ['email', 'sms'],
    'recipients' => [$admin->email]
]);
```

## Performance Metrics

### System Performance
- API response times
- Webhook processing delays
- Queue sizes and wait times
- Error rates and types

### Integration Health
```php
// Monitor external services
$health = [
    'retell' => app(RetellHealthCheck::class)->status(),
    'calcom' => app(CalcomHealthCheck::class)->status(),
    'database' => app(DatabaseHealthCheck::class)->status(),
];
```

## Data Retention

### Storage Policies
- Raw data: 90 days
- Aggregated data: 2 years
- Compliance with GDPR
- Automated cleanup jobs

### Archival Process
```php
// Archive old data
php artisan analytics:archive --older-than=90
```

## API Access

### Analytics API
```yaml
# Analytics endpoints
GET /api/analytics/calls?period=week
GET /api/analytics/appointments?group_by=service
GET /api/analytics/revenue?branch_id=123
GET /api/analytics/export?format=csv
```

### Webhook Events
```json
{
  "event": "analytics.daily_summary",
  "data": {
    "calls": 150,
    "appointments": 45,
    "revenue": 4500.00,
    "conversion_rate": 30
  }
}
```

## Business Intelligence Integration

### Supported BI Tools
- Google Analytics
- Microsoft Power BI
- Tableau
- Custom dashboards via API

### Data Warehouse Export
```sql
-- Example data structure
CREATE VIEW analytics_fact_table AS
SELECT 
    DATE(created_at) as date,
    company_id,
    branch_id,
    COUNT(calls) as call_count,
    COUNT(appointments) as appointment_count,
    SUM(revenue) as total_revenue
FROM analytics_events
GROUP BY date, company_id, branch_id;
```

## Related Documentation
- [API Reference](../api/reference.md)
- [Webhook Events](../api/webhooks.md)
- [Performance Optimization](../operations/performance.md)