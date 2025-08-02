#!/bin/bash
# =============================================================================
# AskProAI Security Hardening Deployment Script
# =============================================================================
# CRITICAL: Automated deployment of server security hardening
# Usage: chmod +x deploy-security-hardening.sh && sudo ./deploy-security-hardening.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔐 AskProAI Security Hardening Deployment${NC}"
echo -e "${BLUE}===========================================${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}❌ This script must be run as root (use sudo)${NC}"
    exit 1
fi

# Create timestamp for backups
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="/var/backups/askproai-security-$TIMESTAMP"

echo -e "${YELLOW}📋 Creating backup directory: $BACKUP_DIR${NC}"
mkdir -p $BACKUP_DIR

# -----------------------------------------------------------------------------
# Phase 1: Backup Existing Configurations
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}📦 Phase 1: Backing up existing configurations${NC}"

# Backup Nginx
if [ -f "/etc/nginx/sites-available/api.askproai.de" ]; then
    cp /etc/nginx/sites-available/api.askproai.de $BACKUP_DIR/nginx-api.askproai.de.backup
    echo -e "${GREEN}✅ Nginx config backed up${NC}"
fi

# Backup PHP
if [ -f "/etc/php/8.3/fpm/php.ini" ]; then
    cp /etc/php/8.3/fpm/php.ini $BACKUP_DIR/php.ini.backup
    echo -e "${GREEN}✅ PHP config backed up${NC}"
fi

# Backup Redis
if [ -f "/etc/redis/redis.conf" ]; then
    cp /etc/redis/redis.conf $BACKUP_DIR/redis.conf.backup
    echo -e "${GREEN}✅ Redis config backed up${NC}"
fi

# Backup UFW rules
ufw --dry-run status > $BACKUP_DIR/ufw-rules.backup 2>/dev/null || true

echo -e "${GREEN}✅ Phase 1 completed - Backups created${NC}"

# -----------------------------------------------------------------------------
# Phase 2: Install Required Packages
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🔧 Phase 2: Installing required packages${NC}"

# Update package list
apt update

# Install fail2ban if not present
if ! command -v fail2ban-client &> /dev/null; then
    echo -e "${YELLOW}📦 Installing fail2ban...${NC}"
    apt install -y fail2ban
    echo -e "${GREEN}✅ Fail2ban installed${NC}"
else
    echo -e "${GREEN}✅ Fail2ban already installed${NC}"
fi

# Install iptables-persistent for rule persistence
if ! dpkg -l | grep -q iptables-persistent; then
    echo -e "${YELLOW}📦 Installing iptables-persistent...${NC}"
    DEBIAN_FRONTEND=noninteractive apt install -y iptables-persistent
    echo -e "${GREEN}✅ iptables-persistent installed${NC}"
fi

echo -e "${GREEN}✅ Phase 2 completed - Required packages installed${NC}"

# -----------------------------------------------------------------------------
# Phase 3: Apply Nginx Security Configuration
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🌐 Phase 3: Applying Nginx security configuration${NC}"

# Copy security configuration
cp /var/www/api-gateway/security/nginx-security.conf /etc/nginx/conf.d/askproai-security.conf

# Test nginx configuration
if nginx -t; then
    echo -e "${GREEN}✅ Nginx configuration valid${NC}"
    systemctl reload nginx
    echo -e "${GREEN}✅ Nginx reloaded with security configuration${NC}"
else
    echo -e "${RED}❌ Nginx configuration invalid - rolling back${NC}"
    rm /etc/nginx/conf.d/askproai-security.conf
    exit 1
fi

echo -e "${GREEN}✅ Phase 3 completed - Nginx hardened${NC}"

# -----------------------------------------------------------------------------
# Phase 4: Apply PHP Security Configuration
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🐘 Phase 4: Applying PHP security configuration${NC}"

# Copy PHP security settings
cp /var/www/api-gateway/security/php-security.ini /etc/php/8.3/fpm/conf.d/99-askproai-security.ini

# Create PHP upload directory with secure permissions
mkdir -p /tmp/php_uploads
chown www-data:www-data /tmp/php_uploads
chmod 750 /tmp/php_uploads

# Test PHP configuration
if php-fpm8.3 -t; then
    echo -e "${GREEN}✅ PHP configuration valid${NC}"
    systemctl restart php8.3-fpm
    echo -e "${GREEN}✅ PHP-FPM restarted with security configuration${NC}"
else
    echo -e "${RED}❌ PHP configuration invalid - rolling back${NC}"
    rm /etc/php/8.3/fpm/conf.d/99-askproai-security.ini
    exit 1
fi

echo -e "${GREEN}✅ Phase 4 completed - PHP hardened${NC}"

# -----------------------------------------------------------------------------
# Phase 5: Configure Fail2ban
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🚫 Phase 5: Configuring Fail2ban${NC}"

# Copy main jail configuration
cp /var/www/api-gateway/security/fail2ban-security.conf /etc/fail2ban/jail.local

# Create custom filter directories
mkdir -p /etc/fail2ban/filter.d

# Create AskProAI auth filter
cat > /etc/fail2ban/filter.d/askproai-auth.conf << 'EOF'
[Definition]
failregex = ^<HOST> .* "POST /api/(login|auth|verify) .* 401
            ^<HOST> .* "POST /api/(login|auth|verify) .* 403
            ^<HOST> .* "POST /api/(login|auth|verify) .* 422
ignoreregex =
EOF

# Create AskProAI admin filter
cat > /etc/fail2ban/filter.d/askproai-admin.conf << 'EOF'
[Definition]
failregex = ^<HOST> .* "GET /admin .* 401
            ^<HOST> .* "GET /admin .* 403
            ^<HOST> .* "POST /admin .* 401
            ^<HOST> .* "POST /admin .* 403
ignoreregex =
EOF

# Create webhook abuse filter
cat > /etc/fail2ban/filter.d/askproai-webhook-abuse.conf << 'EOF'
[Definition]
failregex = ^<HOST> .* "POST /api/(retell|calcom|stripe)/webhook .* 403
            ^<HOST> .* "POST /api/(retell|calcom|stripe)/webhook .* 400
            ^<HOST> .* "POST /api/(retell|calcom|stripe)/webhook .* 401
ignoreregex =
EOF

# Start and enable fail2ban
systemctl enable fail2ban
systemctl restart fail2ban

# Wait for fail2ban to start
sleep 5

# Check fail2ban status
if systemctl is-active --quiet fail2ban; then
    echo -e "${GREEN}✅ Fail2ban active${NC}"
    fail2ban-client status
else
    echo -e "${RED}❌ Fail2ban failed to start${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Phase 5 completed - Fail2ban configured${NC}"

# -----------------------------------------------------------------------------
# Phase 6: Configure Redis Security
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🔴 Phase 6: Configuring Redis security${NC}"

# Generate strong Redis password
REDIS_PASSWORD=$(openssl rand -base64 32)

# Copy Redis configuration and set password
cp /var/www/api-gateway/security/redis-security.conf /etc/redis/redis.conf
sed -i "s/your_very_strong_redis_password_here_change_this_immediately/$REDIS_PASSWORD/g" /etc/redis/redis.conf

# Update Laravel .env with Redis password
if grep -q "REDIS_PASSWORD=" /var/www/api-gateway/.env; then
    sed -i "s/REDIS_PASSWORD=.*/REDIS_PASSWORD=$REDIS_PASSWORD/" /var/www/api-gateway/.env
else
    echo "REDIS_PASSWORD=$REDIS_PASSWORD" >> /var/www/api-gateway/.env
fi

# Save Redis password to secure file for reference
echo "Redis Password: $REDIS_PASSWORD" > $BACKUP_DIR/redis-password.txt
chmod 600 $BACKUP_DIR/redis-password.txt

# Restart Redis
systemctl restart redis-server

# Test Redis connection
if redis-cli -a $REDIS_PASSWORD ping | grep -q PONG; then
    echo -e "${GREEN}✅ Redis security configured and tested${NC}"
else
    echo -e "${RED}❌ Redis configuration failed${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Phase 6 completed - Redis secured${NC}"

# -----------------------------------------------------------------------------
# Phase 7: Configure Firewall (CAREFUL!)
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🔥 Phase 7: Configuring firewall${NC}"
echo -e "${YELLOW}⚠️  This will modify firewall rules - ensure you have alternative access!${NC}"

read -p "Continue with firewall configuration? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}⏭️  Skipping firewall configuration${NC}"
else
    # Run firewall configuration script
    /var/www/api-gateway/security/firewall-rules.sh
    echo -e "${GREEN}✅ Firewall configured${NC}"
fi

echo -e "${GREEN}✅ Phase 7 completed - Firewall status checked${NC}"

# -----------------------------------------------------------------------------
# Phase 8: Setup Security Monitoring
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}📊 Phase 8: Setting up security monitoring${NC}"

# Create log directory
mkdir -p /var/log/askproai-security
chown root:adm /var/log/askproai-security
chmod 750 /var/log/askproai-security

# Setup logrotate for security logs
cat > /etc/logrotate.d/askproai-security << 'EOF'
/var/log/askproai-security/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
    create 640 root adm
}
EOF

# Add monitoring to cron (every 15 minutes)
CRON_LINE="*/15 * * * * /var/www/api-gateway/security/security-monitoring.sh"
if ! crontab -l 2>/dev/null | grep -q "/var/www/api-gateway/security/security-monitoring.sh"; then
    (crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -
    echo -e "${GREEN}✅ Security monitoring cron job added${NC}"
else
    echo -e "${GREEN}✅ Security monitoring cron job already exists${NC}"
fi

# Run initial security check
/var/www/api-gateway/security/security-monitoring.sh

echo -e "${GREEN}✅ Phase 8 completed - Security monitoring active${NC}"

# -----------------------------------------------------------------------------
# Phase 9: Final Verification
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🔍 Phase 9: Final verification${NC}"

# Test all services
SERVICES=("nginx" "php8.3-fpm" "mysql" "redis-server" "fail2ban")
ALL_SERVICES_OK=true

for service in "${SERVICES[@]}"; do
    if systemctl is-active --quiet $service; then
        echo -e "${GREEN}✅ $service is running${NC}"
    else
        echo -e "${RED}❌ $service is not running${NC}"
        ALL_SERVICES_OK=false
    fi
done

# Test web connectivity
if curl -f -s -o /dev/null https://api.askproai.de; then
    echo -e "${GREEN}✅ Website is accessible${NC}"
else
    echo -e "${YELLOW}⚠️  Website accessibility test failed (might be normal if SSL not configured)${NC}"
fi

# Test Laravel application
cd /var/www/api-gateway
if sudo -u www-data php artisan config:cache; then
    echo -e "${GREEN}✅ Laravel configuration cached successfully${NC}"
else
    echo -e "${RED}❌ Laravel configuration cache failed${NC}"
    ALL_SERVICES_OK=false
fi

echo -e "${GREEN}✅ Phase 9 completed - System verification done${NC}"

# -----------------------------------------------------------------------------
# Final Summary
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}📋 DEPLOYMENT SUMMARY${NC}"
echo -e "${BLUE}=====================${NC}"

echo -e "\n${GREEN}✅ SUCCESSFULLY DEPLOYED:${NC}"
echo -e "   🌐 Nginx security configuration with rate limiting"
echo -e "   🐘 PHP security hardening with resource limits"
echo -e "   🚫 Fail2ban protection against brute-force attacks"
echo -e "   🔴 Redis authentication and command restrictions"
echo -e "   📊 Security monitoring and alerting system"

echo -e "\n${YELLOW}📁 BACKUP LOCATION:${NC} $BACKUP_DIR"
echo -e "${YELLOW}🔑 Redis Password:${NC} Saved in $BACKUP_DIR/redis-password.txt"

echo -e "\n${BLUE}🔧 NEXT STEPS:${NC}"
echo -e "   1. Test your application thoroughly"
echo -e "   2. Monitor security logs: tail -f /var/log/askproai-security/alerts.log"
echo -e "   3. Check fail2ban status: sudo fail2ban-client status"
echo -e "   4. Verify firewall rules: sudo ufw status numbered"
echo -e "   5. Run SSL test: https://www.ssllabs.com/ssltest/"

if [ "$ALL_SERVICES_OK" = true ]; then
    echo -e "\n${GREEN}🎉 SECURITY HARDENING COMPLETED SUCCESSFULLY!${NC}"
    echo -e "${GREEN}Your AskProAI infrastructure is now significantly more secure.${NC}"
else
    echo -e "\n${RED}⚠️  SOME ISSUES DETECTED!${NC}"
    echo -e "${RED}Please review the output above and fix any failed services.${NC}"
fi

echo -e "\n${BLUE}📞 Need help? Check the SERVER_HARDENING_CHECKLIST.md${NC}"