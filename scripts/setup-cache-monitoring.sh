#!/bin/bash

# Setup script for automated cache monitoring and recovery
# This sets up both cron jobs and supervisor processes

echo "🔧 Setting up automated cache monitoring..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PROJECT_PATH="/var/www/api-gateway"

# Function to check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        echo -e "${RED}This script must be run as root${NC}"
        exit 1
    fi
}

# Function to setup cron jobs
setup_cron() {
    echo -e "${YELLOW}Setting up cron jobs...${NC}"
    
    # Create cron file for www-data user
    cat > /etc/cron.d/laravel-cache-monitor << 'EOF'
# Laravel View Cache Monitoring and Maintenance
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# Run cache monitor every 5 minutes
*/5 * * * * www-data cd /var/www/api-gateway && php artisan cache:monitor --fix >> /var/www/api-gateway/storage/logs/cache-monitor.log 2>&1

# Warm cache every hour
0 * * * * www-data cd /var/www/api-gateway && php artisan cache:warm --all >> /var/www/api-gateway/storage/logs/cache-warm.log 2>&1

# Clean stale cache files daily at 3 AM
0 3 * * * www-data cd /var/www/api-gateway && find storage/framework/views -type f -mtime +1 -delete >> /var/www/api-gateway/storage/logs/cache-clean.log 2>&1

# Weekly full cache rebuild on Sunday at 4 AM
0 4 * * 0 www-data cd /var/www/api-gateway && php artisan cache:monitor --fix >> /var/www/api-gateway/storage/logs/cache-rebuild.log 2>&1
EOF
    
    # Set proper permissions
    chmod 0644 /etc/cron.d/laravel-cache-monitor
    
    echo -e "${GREEN}✓ Cron jobs configured${NC}"
}

# Function to setup supervisor for continuous monitoring
setup_supervisor() {
    echo -e "${YELLOW}Setting up supervisor process...${NC}"
    
    # Check if supervisor is installed
    if ! command -v supervisord &> /dev/null; then
        echo -e "${YELLOW}Installing supervisor...${NC}"
        apt-get update && apt-get install -y supervisor
    fi
    
    # Create supervisor configuration
    cat > /etc/supervisor/conf.d/laravel-cache-monitor.conf << 'EOF'
[program:laravel-cache-monitor]
process_name=%(program_name)s
command=/usr/bin/php /var/www/api-gateway/artisan cache:monitor --continuous --fix --interval=300
autostart=true
autorestart=true
startsecs=10
stopwaitsecs=600
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/api-gateway/storage/logs/cache-monitor-supervisor.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
environment=PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
EOF
    
    # Reload supervisor configuration
    supervisorctl reread
    supervisorctl update
    supervisorctl start laravel-cache-monitor
    
    echo -e "${GREEN}✓ Supervisor process configured and started${NC}"
}

# Function to setup systemd service (alternative to supervisor)
setup_systemd() {
    echo -e "${YELLOW}Setting up systemd service...${NC}"
    
    # Create systemd service file
    cat > /etc/systemd/system/laravel-cache-monitor.service << 'EOF'
[Unit]
Description=Laravel Cache Monitor
After=network.target redis.service mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/api-gateway
ExecStart=/usr/bin/php /var/www/api-gateway/artisan cache:monitor --continuous --fix --interval=300
Restart=always
RestartSec=10
StandardOutput=append:/var/www/api-gateway/storage/logs/cache-monitor-systemd.log
StandardError=append:/var/www/api-gateway/storage/logs/cache-monitor-systemd.log

[Install]
WantedBy=multi-user.target
EOF
    
    # Reload systemd and enable service
    systemctl daemon-reload
    systemctl enable laravel-cache-monitor.service
    systemctl start laravel-cache-monitor.service
    
    echo -e "${GREEN}✓ Systemd service configured and started${NC}"
}

# Function to setup log rotation
setup_logrotate() {
    echo -e "${YELLOW}Setting up log rotation...${NC}"
    
    cat > /etc/logrotate.d/laravel-cache-monitor << 'EOF'
/var/www/api-gateway/storage/logs/cache-*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload php8.3-fpm > /dev/null 2>&1 || true
    endscript
}
EOF
    
    echo -e "${GREEN}✓ Log rotation configured${NC}"
}

# Function to create monitoring script
create_monitoring_script() {
    echo -e "${YELLOW}Creating monitoring helper script...${NC}"
    
    cat > ${PROJECT_PATH}/scripts/check-cache-health.sh << 'EOF'
#!/bin/bash

# Quick health check script for cache system

echo "🔍 Checking cache system health..."
echo ""

# Check if monitor is running
if pgrep -f "cache:monitor --continuous" > /dev/null; then
    echo "✅ Cache monitor is running"
else
    echo "❌ Cache monitor is NOT running"
fi

# Check Redis
if redis-cli ping > /dev/null 2>&1; then
    echo "✅ Redis is responsive"
else
    echo "❌ Redis is NOT responsive"
fi

# Check view directory
VIEW_DIR="/var/www/api-gateway/storage/framework/views"
if [ -w "$VIEW_DIR" ]; then
    echo "✅ View directory is writable"
    FILE_COUNT=$(find $VIEW_DIR -type f -name "*.php" | wc -l)
    STALE_COUNT=$(find $VIEW_DIR -type f -name "*.php" -mtime +1 | wc -l)
    echo "   📊 Total view files: $FILE_COUNT"
    echo "   📊 Stale files (>24h): $STALE_COUNT"
else
    echo "❌ View directory is NOT writable"
fi

# Check recent errors
ERROR_COUNT=$(grep -c "ERROR" /var/www/api-gateway/storage/logs/cache-monitor.log 2>/dev/null || echo "0")
echo ""
echo "📋 Recent errors in log: $ERROR_COUNT"

# Run artisan health check
echo ""
echo "🏥 Running artisan health check..."
cd /var/www/api-gateway && php artisan cache:monitor
EOF
    
    chmod +x ${PROJECT_PATH}/scripts/check-cache-health.sh
    
    echo -e "${GREEN}✓ Health check script created${NC}"
}

# Main setup flow
main() {
    check_root
    
    echo "========================================="
    echo "  Laravel Cache Monitoring Setup"
    echo "========================================="
    echo ""
    
    setup_cron
    
    # Ask user for supervisor vs systemd
    echo ""
    echo "Choose monitoring service type:"
    echo "1) Supervisor (recommended)"
    echo "2) Systemd service"
    echo "3) Both"
    echo "4) Skip service setup"
    read -p "Enter choice [1-4]: " choice
    
    case $choice in
        1)
            setup_supervisor
            ;;
        2)
            setup_systemd
            ;;
        3)
            setup_supervisor
            setup_systemd
            ;;
        4)
            echo -e "${YELLOW}Skipping service setup${NC}"
            ;;
        *)
            echo -e "${RED}Invalid choice. Defaulting to supervisor.${NC}"
            setup_supervisor
            ;;
    esac
    
    setup_logrotate
    create_monitoring_script
    
    echo ""
    echo "========================================="
    echo -e "${GREEN}✅ Setup Complete!${NC}"
    echo "========================================="
    echo ""
    echo "Monitoring is now active with:"
    echo "  • Cron jobs for periodic checks"
    echo "  • Continuous monitoring process"
    echo "  • Automatic error recovery"
    echo "  • Log rotation"
    echo ""
    echo "Useful commands:"
    echo "  Check health: ${PROJECT_PATH}/scripts/check-cache-health.sh"
    echo "  View logs:    tail -f ${PROJECT_PATH}/storage/logs/cache-monitor.log"
    echo "  Manual fix:   php artisan cache:monitor --fix"
    echo "  Warm cache:   php artisan cache:warm --all"
    echo ""
}

# Run main function
main