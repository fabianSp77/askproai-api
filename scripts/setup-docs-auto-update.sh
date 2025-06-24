#!/bin/bash

#################################################################
# Setup Script for Documentation Auto-Update System
# 
# This script sets up:
# 1. Cron jobs for periodic updates
# 2. Git hooks for commit-triggered updates
# 3. Required directories and permissions
# 4. Log rotation
#################################################################

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== AskProAI Documentation Auto-Update Setup ===${NC}"

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root or with sudo${NC}"
    exit 1
fi

PROJECT_ROOT="/var/www/api-gateway"
cd "$PROJECT_ROOT" || exit 1

# Step 1: Create required directories
echo -e "${YELLOW}Creating directories...${NC}"
mkdir -p /var/log/askproai
chown www-data:www-data /var/log/askproai

# Step 2: Set up log rotation
echo -e "${YELLOW}Setting up log rotation...${NC}"
cat > /etc/logrotate.d/askproai-docs << EOF
/var/log/askproai/docs-update.log
/var/log/askproai/docs-cron.log
{
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
}
EOF

# Step 3: Install cron job
echo -e "${YELLOW}Installing cron job...${NC}"
if [ -f "$PROJECT_ROOT/config/cron/documentation-update" ]; then
    ln -sf "$PROJECT_ROOT/config/cron/documentation-update" /etc/cron.d/askproai-docs-update
    echo -e "${GREEN}✓ Cron job installed${NC}"
else
    echo -e "${RED}✗ Cron configuration not found${NC}"
fi

# Step 4: Install git hook
echo -e "${YELLOW}Installing git hook...${NC}"
if [ -f "$PROJECT_ROOT/scripts/git-hooks/post-commit" ]; then
    ln -sf "$PROJECT_ROOT/scripts/git-hooks/post-commit" "$PROJECT_ROOT/.git/hooks/post-commit"
    echo -e "${GREEN}✓ Git hook installed${NC}"
else
    echo -e "${RED}✗ Git hook not found${NC}"
fi

# Step 5: Install Python dependencies for MkDocs
echo -e "${YELLOW}Installing MkDocs dependencies...${NC}"
pip install --upgrade pip
pip install mkdocs-material==9.5.3 \
    mkdocs-mermaid2-plugin==1.1.1 \
    mkdocs-git-revision-date-localized-plugin==1.2.2 \
    mkdocs-minify-plugin==0.8.0 \
    pymdown-extensions==10.7

# Step 6: Create systemd timer (alternative to cron)
echo -e "${YELLOW}Creating systemd timer...${NC}"
cat > /etc/systemd/system/askproai-docs-update.service << EOF
[Unit]
Description=AskProAI Documentation Update
After=network.target

[Service]
Type=oneshot
User=www-data
WorkingDirectory=$PROJECT_ROOT
ExecStart=$PROJECT_ROOT/scripts/auto-update-docs.sh
StandardOutput=append:/var/log/askproai/docs-update.log
StandardError=append:/var/log/askproai/docs-update.log

[Install]
WantedBy=multi-user.target
EOF

cat > /etc/systemd/system/askproai-docs-update.timer << EOF
[Unit]
Description=Run AskProAI Documentation Update every hour
Requires=askproai-docs-update.service

[Timer]
OnCalendar=hourly
Persistent=true

[Install]
WantedBy=timers.target
EOF

systemctl daemon-reload
systemctl enable askproai-docs-update.timer
systemctl start askproai-docs-update.timer

# Step 7: Set permissions
echo -e "${YELLOW}Setting permissions...${NC}"
chown -R www-data:www-data "$PROJECT_ROOT/docs_mkdocs"
chown -R www-data:www-data "$PROJECT_ROOT/public/mkdocs" 2>/dev/null || true
chmod +x "$PROJECT_ROOT/scripts/auto-update-docs.sh"

# Step 8: Create initial documentation
echo -e "${YELLOW}Creating initial documentation...${NC}"
sudo -u www-data "$PROJECT_ROOT/scripts/auto-update-docs.sh"

# Step 9: Verify installation
echo -e "${YELLOW}Verifying installation...${NC}"
echo -e "Cron job: $([ -f /etc/cron.d/askproai-docs-update ] && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}")"
echo -e "Git hook: $([ -f $PROJECT_ROOT/.git/hooks/post-commit ] && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}")"
echo -e "Systemd timer: $(systemctl is-enabled askproai-docs-update.timer &>/dev/null && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}")"
echo -e "Log directory: $([ -d /var/log/askproai ] && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}")"

echo -e "\n${GREEN}=== Setup Complete ===${NC}"
echo -e "Documentation will be automatically updated:"
echo -e "  • Every hour via cron/systemd"
echo -e "  • After commits that change PHP/MD files"
echo -e "  • Manually: ${YELLOW}$PROJECT_ROOT/scripts/auto-update-docs.sh${NC}"
echo -e "\nView logs: ${YELLOW}tail -f /var/log/askproai/docs-update.log${NC}"
echo -e "View docs: ${YELLOW}https://api.askproai.de/mkdocs/${NC}"