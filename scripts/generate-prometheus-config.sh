#!/bin/bash

#############################################################################
# Generate Prometheus Configuration for MCP Monitoring
#############################################################################

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CONFIG_DIR="$PROJECT_DIR/config"
PROMETHEUS_CONFIG_FILE="$CONFIG_DIR/prometheus-mcp.yml"
ALERT_RULES_FILE="$CONFIG_DIR/mcp_alerts.yml"

echo "Generating Prometheus configuration for MCP monitoring..."

# Create configuration directory if it doesn't exist
mkdir -p "$CONFIG_DIR"

# Source environment variables
if [[ -f "$PROJECT_DIR/.env" ]]; then
    set -a
    source "$PROJECT_DIR/.env"
    set +a
fi

# Get monitoring token
MONITORING_TOKEN="${MONITORING_METRICS_TOKEN:-askproai_metrics_token_2025}"
APP_URL="${APP_URL:-https://api.askproai.de}"
APP_DOMAIN=$(echo "$APP_URL" | sed 's|https\?://||')

echo "Configuring for domain: $APP_DOMAIN"
echo "Using monitoring token: ${MONITORING_TOKEN:0:10}..."

# Generate Prometheus configuration
cat > "$PROMETHEUS_CONFIG_FILE" << EOF
# Prometheus configuration for MCP integration monitoring
# Generated on $(date)

global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    environment: '${APP_ENV:-production}'
    service: 'askproai'

rule_files:
  - "mcp_alerts.yml"

# Alert manager configuration
alerting:
  alertmanagers:
    - static_configs:
        - targets:
          - localhost:9093
      path_prefix: /
      scheme: http
      timeout: 10s

scrape_configs:
  # AskProAI MCP Endpoints
  - job_name: 'askproai-mcp'
    static_configs:
      - targets: ['$APP_DOMAIN']
    scheme: https
    metrics_path: /api/metrics
    params:
      service: ['mcp']
    scrape_interval: 10s
    scrape_timeout: 5s
    honor_labels: true
    bearer_token: '$MONITORING_TOKEN'
    metric_relabel_configs:
      # Add integration type labels
      - source_labels: [__name__, endpoint]
        regex: 'askproai_http_requests_total;.*mcp.*'
        target_label: integration_type
        replacement: 'mcp'
      - source_labels: [__name__, endpoint]
        regex: 'askproai_http_requests_total;.*webhook.*'
        target_label: integration_type
        replacement: 'webhook'
    
  # MCP Health Checks
  - job_name: 'askproai-mcp-health'
    static_configs:
      - targets: ['$APP_DOMAIN']
    scheme: https
    metrics_path: /api/mcp/retell/health
    scrape_interval: 30s
    scrape_timeout: 10s
    honor_timestamps: true
    
  # Legacy Webhook Monitoring (for comparison)
  - job_name: 'askproai-webhooks'
    static_configs:
      - targets: ['$APP_DOMAIN']
    scheme: https
    metrics_path: /api/metrics
    params:
      service: ['webhook']
    scrape_interval: 15s
    scrape_timeout: 5s
    bearer_token: '$MONITORING_TOKEN'
    
  # Application General Metrics
  - job_name: 'askproai-app'
    static_configs:
      - targets: ['$APP_DOMAIN']
    scheme: https
    metrics_path: /api/metrics
    scrape_interval: 30s
    scrape_timeout: 10s
    bearer_token: '$MONITORING_TOKEN'
    
  # Database Metrics
  - job_name: 'askproai-database'
    static_configs:
      - targets: ['localhost:9104']
    scrape_interval: 30s
    scrape_timeout: 10s
    
  # Redis Metrics
  - job_name: 'askproai-redis'
    static_configs:
      - targets: ['localhost:9121']
    scrape_interval: 30s
    scrape_timeout: 10s
    
  # System Metrics
  - job_name: 'node-exporter'
    static_configs:
      - targets: ['localhost:9100']
    scrape_interval: 30s
    scrape_timeout: 10s
EOF

echo "Prometheus configuration generated: $PROMETHEUS_CONFIG_FILE"

# Validate configuration if prometheus is available
if command -v promtool &> /dev/null; then
    if promtool check config "$PROMETHEUS_CONFIG_FILE"; then
        echo "Prometheus configuration is valid"
    else
        echo "WARNING: Prometheus configuration validation failed"
    fi
else
    echo "promtool not available, skipping validation"
fi

# Generate systemd service file for Prometheus
cat > "/tmp/prometheus-askproai.service" << EOF
[Unit]
Description=Prometheus Server for AskProAI MCP Monitoring
Documentation=https://prometheus.io/docs/introduction/overview/
After=network-online.target

[Service]
Type=simple
User=prometheus
Group=prometheus
ExecReload=/bin/kill -HUP \$MAINPID
ExecStart=/usr/local/bin/prometheus \\
  --config.file=$PROMETHEUS_CONFIG_FILE \\
  --storage.tsdb.path=/var/lib/prometheus/ \\
  --web.console.templates=/etc/prometheus/consoles \\
  --web.console.libraries=/etc/prometheus/console_libraries \\
  --web.listen-address=0.0.0.0:9090 \\
  --web.external-url= \\
  --storage.tsdb.retention.time=30d

SendSIGKILL=no
Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
EOF

echo "Systemd service file generated: /tmp/prometheus-askproai.service"
echo "To install: sudo cp /tmp/prometheus-askproai.service /etc/systemd/system/"

# Generate docker-compose configuration for Prometheus stack
cat > "/tmp/docker-compose.monitoring.yml" << EOF
version: '3.8'

services:
  prometheus:
    image: prom/prometheus:latest
    container_name: askproai-prometheus
    ports:
      - "9090:9090"
    volumes:
      - $CONFIG_DIR:/etc/prometheus
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus-mcp.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--web.console.templates=/etc/prometheus/consoles'
      - '--web.console.libraries=/etc/prometheus/console_libraries'
      - '--web.enable-lifecycle'
      - '--storage.tsdb.retention.time=30d'
    restart: unless-stopped
    networks:
      - monitoring

  grafana:
    image: grafana/grafana:latest
    container_name: askproai-grafana
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin123
      - GF_USERS_ALLOW_SIGN_UP=false
      - GF_DASHBOARDS_DEFAULT_HOME_DASHBOARD_PATH=/etc/grafana/provisioning/dashboards/mcp-dashboard.json
    volumes:
      - grafana_data:/var/lib/grafana
      - $CONFIG_DIR:/etc/grafana/provisioning/dashboards:ro
    restart: unless-stopped
    networks:
      - monitoring
    depends_on:
      - prometheus

  alertmanager:
    image: prom/alertmanager:latest
    container_name: askproai-alertmanager
    ports:
      - "9093:9093"
    volumes:
      - $CONFIG_DIR:/etc/alertmanager
      - alertmanager_data:/alertmanager
    command:
      - '--config.file=/etc/alertmanager/alertmanager.yml'
      - '--storage.path=/alertmanager'
      - '--web.external-url=http://localhost:9093'
      - '--cluster.advertise-address=0.0.0.0:9093'
    restart: unless-stopped
    networks:
      - monitoring

  node-exporter:
    image: prom/node-exporter:latest
    container_name: askproai-node-exporter
    ports:
      - "9100:9100"
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.sysfs=/host/sys'
      - '--collector.filesystem.mount-points-exclude=^/(sys|proc|dev|host|etc)(\$|/)'
    restart: unless-stopped
    networks:
      - monitoring

volumes:
  prometheus_data:
  grafana_data:
  alertmanager_data:

networks:
  monitoring:
    driver: bridge
EOF

echo "Docker Compose configuration generated: /tmp/docker-compose.monitoring.yml"
echo "To start monitoring stack: docker-compose -f /tmp/docker-compose.monitoring.yml up -d"

# Generate AlertManager configuration
cat > "$CONFIG_DIR/alertmanager.yml" << EOF
# AlertManager configuration for AskProAI MCP
global:
  smtp_smarthost: 'localhost:587'
  smtp_from: 'alerts@askproai.de'
  smtp_auth_username: 'alerts@askproai.de'
  smtp_auth_password: '${SMTP_PASSWORD:-}'

route:
  group_by: ['alertname', 'service']
  group_wait: 10s
  group_interval: 10s
  repeat_interval: 1h
  receiver: 'web.hook'
  routes:
    - match:
        severity: critical
      receiver: 'critical-alerts'
    - match:
        service: mcp
      receiver: 'mcp-alerts'

receivers:
  - name: 'web.hook'
    webhook_configs:
      - url: 'http://127.0.0.1:5001/webhook'
        
  - name: 'critical-alerts'
    email_configs:
      - to: 'admin@askproai.de'
        subject: 'CRITICAL: {{ .GroupLabels.alertname }}'
        body: |
          Alert: {{ .GroupLabels.alertname }}
          Summary: {{ range .Alerts }}{{ .Annotations.summary }}{{ end }}
          Description: {{ range .Alerts }}{{ .Annotations.description }}{{ end }}
    webhook_configs:
      - url: '${SLACK_ALERT_WEBHOOK_URL:-}'
        title: 'CRITICAL: {{ .GroupLabels.alertname }}'
        text: '{{ range .Alerts }}{{ .Annotations.summary }}{{ end }}'
        
  - name: 'mcp-alerts'
    webhook_configs:
      - url: '$APP_URL/api/alerts/mcp'
        http_config:
          bearer_token: '$MONITORING_TOKEN'

inhibit_rules:
  - source_match:
      severity: 'critical'
    target_match:
      severity: 'warning'
    equal: ['alertname', 'service']
EOF

echo "AlertManager configuration generated: $CONFIG_DIR/alertmanager.yml"

echo
echo "Configuration Generation Complete!"
echo "Files generated:"
echo "  - Prometheus config: $PROMETHEUS_CONFIG_FILE"
echo "  - Alert rules: $ALERT_RULES_FILE"
echo "  - AlertManager config: $CONFIG_DIR/alertmanager.yml"
echo "  - Grafana dashboard: $CONFIG_DIR/grafana-mcp-dashboard.json"
echo "  - Docker Compose: /tmp/docker-compose.monitoring.yml"
echo "  - Systemd service: /tmp/prometheus-askproai.service"
echo
echo "Next steps:"
echo "1. Review and customize configurations as needed"
echo "2. Deploy monitoring stack using Docker Compose or systemd"
echo "3. Import Grafana dashboard"
echo "4. Configure alert destinations"
echo "5. Test alerting with: curl -X POST $APP_URL/api/test-alert"
echo