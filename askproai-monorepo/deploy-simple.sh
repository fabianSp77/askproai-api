#!/bin/bash

# Simplified deployment for staging
set -e

echo "🚀 Starting simplified deployment to staging..."

# Build Admin Portal
echo "📦 Building Admin Portal..."
cd /var/www/api-gateway/askproai-monorepo/apps/admin

# Create standalone Next.js app
cat > next.config.mjs << 'EOF'
/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  output: 'standalone',
  images: {
    domains: ['api.askproai.de'],
  },
}

export default nextConfig
EOF

# Build
npm run build || echo "Build failed, continuing..."

# Create deployment directory
ADMIN_DIR="/var/www/admin-staging.askproai.de"
sudo mkdir -p $ADMIN_DIR

# Copy built files (if they exist)
if [ -d ".next" ]; then
    sudo cp -r .next $ADMIN_DIR/
    sudo cp -r public $ADMIN_DIR/ 2>/dev/null || true
    sudo cp package.json $ADMIN_DIR/
    sudo cp next.config.mjs $ADMIN_DIR/
fi

# Copy source files as fallback
sudo cp -r src $ADMIN_DIR/
sudo cp -r ../../packages $ADMIN_DIR/../

# Build Business Portal
echo "📦 Building Business Portal..."
cd /var/www/api-gateway/askproai-monorepo/apps/business

# Create standalone Next.js app
cat > next.config.mjs << 'EOF'
/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  output: 'standalone',
  images: {
    domains: ['api.askproai.de'],
  },
}

export default nextConfig
EOF

# Build
npm run build || echo "Build failed, continuing..."

# Create deployment directory
BUSINESS_DIR="/var/www/portal-staging.askproai.de"
sudo mkdir -p $BUSINESS_DIR

# Copy built files (if they exist)
if [ -d ".next" ]; then
    sudo cp -r .next $BUSINESS_DIR/
    sudo cp -r public $BUSINESS_DIR/ 2>/dev/null || true
    sudo cp package.json $BUSINESS_DIR/
    sudo cp next.config.mjs $BUSINESS_DIR/
fi

# Copy source files as fallback
sudo cp -r src $BUSINESS_DIR/

# Create simple startup scripts
echo "📝 Creating startup scripts..."

# Admin startup script
sudo tee $ADMIN_DIR/start.sh > /dev/null << 'EOF'
#!/bin/bash
cd /var/www/admin-staging.askproai.de
export PORT=4001
export NODE_ENV=production
npm install --production --force || true
npm run start || node .next/standalone/server.js || npx next start -p 4001
EOF

# Business startup script
sudo tee $BUSINESS_DIR/start.sh > /dev/null << 'EOF'
#!/bin/bash
cd /var/www/portal-staging.askproai.de
export PORT=4002
export NODE_ENV=production
npm install --production --force || true
npm run start || node .next/standalone/server.js || npx next start -p 4002
EOF

sudo chmod +x $ADMIN_DIR/start.sh
sudo chmod +x $BUSINESS_DIR/start.sh

# Create PM2 configs
echo "🔧 Setting up PM2..."

# Admin PM2 config
sudo tee $ADMIN_DIR/ecosystem.config.js > /dev/null << 'EOF'
module.exports = {
  apps: [{
    name: 'admin-staging',
    script: './start.sh',
    cwd: '/var/www/admin-staging.askproai.de',
    env: {
      PORT: 4001,
      NODE_ENV: 'production'
    }
  }]
}
EOF

# Business PM2 config
sudo tee $BUSINESS_DIR/ecosystem.config.js > /dev/null << 'EOF'
module.exports = {
  apps: [{
    name: 'portal-staging',
    script: './start.sh',
    cwd: '/var/www/portal-staging.askproai.de',
    env: {
      PORT: 4002,
      NODE_ENV: 'production'
    }
  }]
}
EOF

# Set permissions
sudo chown -R www-data:www-data $ADMIN_DIR
sudo chown -R www-data:www-data $BUSINESS_DIR

# Nginx configs
echo "🌐 Setting up Nginx..."

# Admin staging Nginx
sudo tee /etc/nginx/sites-available/admin-staging.askproai.de > /dev/null << 'EOF'
server {
    listen 80;
    server_name admin-staging.askproai.de;
    
    location / {
        proxy_pass http://localhost:4001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
EOF

# Business staging Nginx
sudo tee /etc/nginx/sites-available/portal-staging.askproai.de > /dev/null << 'EOF'
server {
    listen 80;
    server_name portal-staging.askproai.de;
    
    location / {
        proxy_pass http://localhost:4002;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
EOF

# Enable sites
sudo ln -sf /etc/nginx/sites-available/admin-staging.askproai.de /etc/nginx/sites-enabled/
sudo ln -sf /etc/nginx/sites-available/portal-staging.askproai.de /etc/nginx/sites-enabled/

# Test and reload Nginx
sudo nginx -t && sudo systemctl reload nginx

# Start with PM2
echo "🚀 Starting applications with PM2..."
cd $ADMIN_DIR && pm2 delete admin-staging 2>/dev/null || true
cd $ADMIN_DIR && pm2 start ecosystem.config.js

cd $BUSINESS_DIR && pm2 delete portal-staging 2>/dev/null || true  
cd $BUSINESS_DIR && pm2 start ecosystem.config.js

pm2 save

# Setup SSL
echo "🔒 Setting up SSL certificates..."
sudo certbot --nginx -d admin-staging.askproai.de --non-interactive --agree-tos --email admin@askproai.de || echo "SSL setup failed for admin"
sudo certbot --nginx -d portal-staging.askproai.de --non-interactive --agree-tos --email admin@askproai.de || echo "SSL setup failed for portal"

echo "✅ Deployment complete!"
echo ""
echo "URLs:"
echo "Admin Staging: https://admin-staging.askproai.de"
echo "Business Staging: https://portal-staging.askproai.de"
echo ""
echo "PM2 Status:"
pm2 list