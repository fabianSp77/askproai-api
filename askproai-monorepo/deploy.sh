#!/bin/bash

# AskProAI React Deployment Script
# Usage: ./deploy.sh [admin|business|all] [staging|production]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="/var/www/api-gateway/askproai-monorepo"
DEPLOY_ROOT="/var/www"
NGINX_SITES="/etc/nginx/sites-available"

# Parse arguments
TARGET=${1:-all}
ENVIRONMENT=${2:-staging}

echo -e "${GREEN}🚀 Starting deployment...${NC}"
echo "Target: $TARGET"
echo "Environment: $ENVIRONMENT"

# Function to deploy an app
deploy_app() {
    local APP_NAME=$1
    local APP_DOMAIN=$2
    local APP_PORT=$3
    
    echo -e "${YELLOW}📦 Deploying $APP_NAME to $APP_DOMAIN...${NC}"
    
    # Build the app
    cd "$PROJECT_ROOT/apps/$APP_NAME"
    
    # Copy appropriate env file
    cp ".env.$ENVIRONMENT" .env.production.local
    
    # Install dependencies and build
    npm install --force
    npm run build
    
    # Create deployment directory
    DEPLOY_DIR="$DEPLOY_ROOT/$APP_DOMAIN"
    sudo mkdir -p "$DEPLOY_DIR"
    
    # Copy build files
    sudo cp -r .next/standalone/* "$DEPLOY_DIR/"
    sudo cp -r .next/static "$DEPLOY_DIR/.next/"
    sudo cp -r public "$DEPLOY_DIR/"
    
    # Create PM2 ecosystem file
    cat > "$DEPLOY_DIR/ecosystem.config.js" << EOF
module.exports = {
  apps: [{
    name: '$APP_NAME-$ENVIRONMENT',
    script: 'server.js',
    instances: 2,
    exec_mode: 'cluster',
    env: {
      PORT: $APP_PORT,
      NODE_ENV: 'production'
    },
    error_file: '/var/log/pm2/$APP_NAME-error.log',
    out_file: '/var/log/pm2/$APP_NAME-out.log',
    log_file: '/var/log/pm2/$APP_NAME-combined.log',
    time: true
  }]
}
EOF
    
    # Set permissions
    sudo chown -R www-data:www-data "$DEPLOY_DIR"
    
    # Create Nginx configuration
    sudo tee "$NGINX_SITES/$APP_DOMAIN" > /dev/null << EOF
server {
    listen 80;
    server_name $APP_DOMAIN;
    
    # Redirect to HTTPS
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name $APP_DOMAIN;
    
    # SSL configuration will be added by certbot
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self' https://api.askproai.de; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;" always;
    
    # Proxy to Next.js
    location / {
        proxy_pass http://localhost:$APP_PORT;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
    
    # Static file caching
    location /_next/static {
        alias $DEPLOY_DIR/.next/static;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    location /static {
        alias $DEPLOY_DIR/public/static;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF
    
    # Enable Nginx site
    sudo ln -sf "$NGINX_SITES/$APP_DOMAIN" "/etc/nginx/sites-enabled/"
    
    # Start/restart with PM2
    cd "$DEPLOY_DIR"
    pm2 delete "$APP_NAME-$ENVIRONMENT" || true
    pm2 start ecosystem.config.js
    pm2 save
    
    echo -e "${GREEN}✅ $APP_NAME deployed successfully!${NC}"
}

# Deploy based on target
case $TARGET in
    admin)
        if [ "$ENVIRONMENT" == "production" ]; then
            deploy_app "admin" "admin.askproai.de" "3001"
        else
            deploy_app "admin" "admin-staging.askproai.de" "4001"
        fi
        ;;
    business)
        if [ "$ENVIRONMENT" == "production" ]; then
            deploy_app "business" "portal.askproai.de" "3002"
        else
            deploy_app "business" "portal-staging.askproai.de" "4002"
        fi
        ;;
    all)
        if [ "$ENVIRONMENT" == "production" ]; then
            deploy_app "admin" "admin.askproai.de" "3001"
            deploy_app "business" "portal.askproai.de" "3002"
        else
            deploy_app "admin" "admin-staging.askproai.de" "4001"
            deploy_app "business" "portal-staging.askproai.de" "4002"
        fi
        ;;
    *)
        echo -e "${RED}Invalid target. Use: admin, business, or all${NC}"
        exit 1
        ;;
esac

# Test Nginx configuration
echo -e "${YELLOW}🔧 Testing Nginx configuration...${NC}"
sudo nginx -t

# Reload Nginx
echo -e "${YELLOW}🔄 Reloading Nginx...${NC}"
sudo systemctl reload nginx

# Setup SSL if needed
if [ "$ENVIRONMENT" == "production" ]; then
    echo -e "${YELLOW}🔒 Setting up SSL certificates...${NC}"
    
    if [ "$TARGET" == "admin" ] || [ "$TARGET" == "all" ]; then
        sudo certbot --nginx -d admin.askproai.de --non-interactive --agree-tos --email admin@askproai.de || true
    fi
    
    if [ "$TARGET" == "business" ] || [ "$TARGET" == "all" ]; then
        sudo certbot --nginx -d portal.askproai.de --non-interactive --agree-tos --email admin@askproai.de || true
    fi
else
    echo -e "${YELLOW}🔒 Setting up SSL for staging...${NC}"
    
    if [ "$TARGET" == "admin" ] || [ "$TARGET" == "all" ]; then
        sudo certbot --nginx -d admin-staging.askproai.de --non-interactive --agree-tos --email admin@askproai.de || true
    fi
    
    if [ "$TARGET" == "business" ] || [ "$TARGET" == "all" ]; then
        sudo certbot --nginx -d portal-staging.askproai.de --non-interactive --agree-tos --email admin@askproai.de || true
    fi
fi

echo -e "${GREEN}🎉 Deployment complete!${NC}"
echo ""
echo "URLs:"
if [ "$ENVIRONMENT" == "production" ]; then
    [ "$TARGET" == "admin" ] || [ "$TARGET" == "all" ] && echo "Admin Portal: https://admin.askproai.de"
    [ "$TARGET" == "business" ] || [ "$TARGET" == "all" ] && echo "Business Portal: https://portal.askproai.de"
else
    [ "$TARGET" == "admin" ] || [ "$TARGET" == "all" ] && echo "Admin Staging: https://admin-staging.askproai.de"
    [ "$TARGET" == "business" ] || [ "$TARGET" == "all" ] && echo "Business Staging: https://portal-staging.askproai.de"
fi

# Show PM2 status
echo ""
echo -e "${YELLOW}📊 PM2 Status:${NC}"
pm2 list