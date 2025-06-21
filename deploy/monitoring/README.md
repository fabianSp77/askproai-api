# AskProAI Monitoring Stack

## Overview

This monitoring stack provides comprehensive observability for the AskProAI platform using:
- **Prometheus**: Metrics collection and storage
- **Grafana**: Visualization and dashboards
- **Alertmanager**: Alert routing and notifications
- **Exporters**: System and service metrics collection

## Quick Start

### 1. Setup

```bash
# Run the setup script
cd /var/www/api-gateway/deploy/monitoring
sudo ./setup-monitoring.sh
```

### 2. Access

- **Grafana**: https://monitoring.askproai.de
  - Default credentials: `admin` / `askproai-secure-password`
- **Prometheus**: http://localhost:9090 (internal only)
- **Alertmanager**: http://localhost:9093 (internal only)

## Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   AskProAI  │────▶│ Prometheus  │────▶│   Grafana   │
│     App     │     │   :9090     │     │    :3000    │
└─────────────┘     └─────────────┘     └─────────────┘
                           │
                           ▼
                    ┌─────────────┐
                    │Alertmanager │
                    │   :9093     │
                    └─────────────┘
```

## Metrics Collected

### Application Metrics
- HTTP request rate and duration
- Error rates by status code
- Queue sizes and processing times
- Webhook processing statistics
- Booking creation rates
- Database connection pool usage

### System Metrics
- CPU and memory usage
- Disk I/O and space
- Network traffic
- Process statistics

### Service Metrics
- MySQL performance
- Redis operations
- PHP-FPM status
- Nginx requests

## Dashboards

### Main Dashboard
The main dashboard (`askproai-prod`) includes:
- HTTP response time (p95, p99)
- Success rate gauge
- Request rate by status
- Queue size statistics
- Webhook processing rate
- Booking creation rate
- Database connections
- Error rate tracking

### Custom Dashboards
To add custom dashboards:
1. Create dashboard in Grafana UI
2. Export as JSON
3. Save to `grafana-dashboards/` directory
4. Restart Grafana container

## Alerting

### Alert Rules
Critical alerts:
- High error rate (>5%)
- Database connection exhaustion (>90%)
- Cal.com API unreachable
- MySQL/Redis down

Warning alerts:
- Slow response times (>1s p95)
- High queue backlog (>1000)
- No bookings for 1 hour
- Low disk space (<15%)

### Alert Channels
Configure in Alertmanager:
- Email notifications
- Slack webhooks
- PagerDuty integration
- Custom webhooks

## Configuration

### Environment Variables
Create `.env` file with:
```env
# SMTP
SMTP_USER=your-smtp-user
SMTP_PASSWORD=your-smtp-password

# Exporters
MYSQL_EXPORTER_PASSWORD=secure-password
REDIS_PASSWORD=redis-password

# Alerting
SLACK_WEBHOOK_CRITICAL=https://hooks.slack.com/...
```

### Prometheus Configuration
Edit `prometheus.yml` to:
- Adjust scrape intervals
- Add new targets
- Configure service discovery

### Grafana Configuration
- Update admin password after first login
- Configure LDAP/OAuth if needed
- Set up notification channels
- Customize dashboard refresh rates

## Maintenance

### Backup
Grafana dashboards and settings:
```bash
docker exec askproai-grafana grafana-cli admin export-dashboard
```

### Updates
```bash
cd /var/www/api-gateway/deploy/monitoring
docker-compose pull
docker-compose up -d
```

### Logs
```bash
# View logs
docker-compose logs -f grafana
docker-compose logs -f prometheus
docker-compose logs -f alertmanager

# Log locations
/var/lib/docker/containers/*/logs
```

### Storage
Prometheus retention: 30 days (configurable)
```bash
# Check storage usage
docker exec askproai-prometheus df -h /prometheus

# Clean old data manually if needed
docker exec askproai-prometheus rm -rf /prometheus/wal/*
```

## Troubleshooting

### Grafana not showing data
1. Check Prometheus targets: http://localhost:9090/targets
2. Verify metrics endpoint: `curl http://localhost/api/metrics`
3. Check datasource configuration in Grafana

### Alerts not firing
1. Check alert rules: http://localhost:9090/alerts
2. Verify Alertmanager config: http://localhost:9093/#/status
3. Test notification channels manually

### High memory usage
1. Reduce Prometheus retention time
2. Increase scrape intervals
3. Disable unnecessary exporters

### Container crashes
```bash
# Check container status
docker-compose ps

# View error logs
docker-compose logs --tail=100 [service-name]

# Restart specific service
docker-compose restart [service-name]
```

## Security

### Access Control
- Grafana: Configure user roles and permissions
- Prometheus: Use reverse proxy with auth
- Exporters: Bind to localhost only

### SSL/TLS
- Use Let's Encrypt for monitoring subdomain
- Enable HTTPS in Grafana settings
- Secure exporter endpoints

### Secrets Management
- Use Docker secrets for passwords
- Rotate credentials regularly
- Audit access logs

## Performance Tuning

### Prometheus
```yaml
# Adjust in prometheus.yml
global:
  scrape_interval: 30s  # Increase for lower load
  evaluation_interval: 30s
  
# Limit metrics
metric_relabel_configs:
  - source_labels: [__name__]
    regex: 'go_.*'
    action: drop
```

### Grafana
```ini
# In grafana.ini
[database]
max_open_conn = 25
max_idle_conn = 25

[alerting]
evaluation_timeout = 30s
max_attempts = 3
```

## Integration

### API Access
```bash
# Query Prometheus
curl http://localhost:9090/api/v1/query?query=up

# Create Grafana API key
curl -X POST -H "Content-Type: application/json" \
  -u admin:password \
  -d '{"name":"apikey", "role":"Viewer"}' \
  http://localhost:3000/api/auth/keys
```

### Custom Metrics
Add to your application:
```php
$histogram = $registry->getOrRegisterHistogram(
    'askproai',
    'custom_metric',
    'Description',
    ['label1', 'label2']
);
$histogram->observe(0.5, ['value1', 'value2']);
```

---

For support, contact the DevOps team or check the main documentation.