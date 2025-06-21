#!/bin/bash

# AskProAI Monitoring Setup Script
# Version: 1.0
# Date: 2025-06-18

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
MONITORING_DIR="/var/www/api-gateway/deploy/monitoring"
NGINX_CONF="/etc/nginx/sites-available/askproai-monitoring"

echo -e "${BLUE}Setting up AskProAI Monitoring Stack...${NC}"

# 1. Check prerequisites
echo -e "${BLUE}Checking prerequisites...${NC}"

if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker not installed. Please install Docker first.${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Docker Compose not installed. Please install Docker Compose first.${NC}"
    exit 1
fi

# 2. Create MySQL exporter user
echo -e "${BLUE}Creating MySQL exporter user...${NC}"

mysql -u root -p -e "
CREATE USER IF NOT EXISTS 'exporter'@'localhost' IDENTIFIED BY '${MYSQL_EXPORTER_PASSWORD:-exporter_password}';
GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'localhost';
FLUSH PRIVILEGES;
" || echo -e "${YELLOW}MySQL exporter user might already exist${NC}"

# 3. Create environment file
echo -e "${BLUE}Creating environment configuration...${NC}"

cat > "$MONITORING_DIR/.env" << EOF
# SMTP Configuration
SMTP_USER=${SMTP_USER:-}
SMTP_PASSWORD=${SMTP_PASSWORD:-}

***REMOVED***
MYSQL_EXPORTER_PASSWORD=${MYSQL_EXPORTER_PASSWORD:-exporter_password}
REDIS_PASSWORD=${REDIS_PASSWORD:-}

# Alerting
SLACK_WEBHOOK_CRITICAL=${SLACK_WEBHOOK_CRITICAL:-}
EOF

# 4. Start monitoring stack
echo -e "${BLUE}Starting monitoring stack...${NC}"

cd "$MONITORING_DIR"
docker-compose up -d

# 5. Wait for services to start
echo -e "${BLUE}Waiting for services to start...${NC}"
sleep 30

# 6. Check service health
echo -e "${BLUE}Checking service health...${NC}"

services=("prometheus:9090" "grafana:3000" "alertmanager:9093")
for service in "${services[@]}"; do
    IFS=':' read -r name port <<< "$service"
    if curl -s -o /dev/null -w "%{http_code}" "http://localhost:$port" | grep -q "200\|302"; then
        echo -e "${GREEN}✓ $name is running on port $port${NC}"
    else
        echo -e "${RED}✗ $name failed to start on port $port${NC}"
    fi
done

# 7. Configure Nginx reverse proxy
echo -e "${BLUE}Configuring Nginx reverse proxy...${NC}"

cat > "$NGINX_CONF" << 'EOF'
# Grafana
server {
    listen 80;
    server_name monitoring.askproai.de;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name monitoring.askproai.de;

    ssl_certificate /etc/letsencrypt/live/askproai.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/askproai.de/privkey.pem;

    location / {
        proxy_pass http://localhost:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# Prometheus (internal only)
server {
    listen 127.0.0.1:80;
    server_name prometheus.internal;

    location / {
        proxy_pass http://localhost:9090;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
EOF

# Enable site
ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# 8. Import dashboard
echo -e "${BLUE}Importing Grafana dashboard...${NC}"

# Wait for Grafana to be fully ready
sleep 10

# Get admin password
GRAFANA_PASS="askproai-secure-password"

# Import dashboard via API
curl -X POST \
  -H "Content-Type: application/json" \
  -u "admin:$GRAFANA_PASS" \
  -d @grafana-dashboard.json \
  http://localhost:3000/api/dashboards/db

# 9. Test metrics endpoint
echo -e "${BLUE}Testing metrics collection...${NC}"

if curl -s http://localhost:9090/api/v1/targets | grep -q "askproai"; then
    echo -e "${GREEN}✓ AskProAI metrics are being collected${NC}"
else
    echo -e "${YELLOW}⚠ AskProAI metrics not yet available${NC}"
fi

# 10. Create systemd service
echo -e "${BLUE}Creating systemd service...${NC}"

cat > /etc/systemd/system/askproai-monitoring.service << EOF
[Unit]
Description=AskProAI Monitoring Stack
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=$MONITORING_DIR
ExecStart=/usr/local/bin/docker-compose up -d
ExecStop=/usr/local/bin/docker-compose down
TimeoutStartSec=0

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable askproai-monitoring

echo -e "${GREEN}================================================${NC}"
echo -e "${GREEN}Monitoring setup completed!${NC}"
echo -e "${GREEN}================================================${NC}"
echo ""
echo "Access points:"
echo -e "- Grafana: ${BLUE}https://monitoring.askproai.de${NC}"
echo -e "  Username: admin"
echo -e "  Password: askproai-secure-password"
echo -e "- Prometheus: ${BLUE}http://localhost:9090${NC} (internal only)"
echo -e "- Alertmanager: ${BLUE}http://localhost:9093${NC} (internal only)"
echo ""
echo "Next steps:"
echo "1. Update Grafana admin password"
echo "2. Configure alert notification channels"
echo "3. Customize dashboards as needed"
echo "4. Set up SSL certificate for monitoring.askproai.de"