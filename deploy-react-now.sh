#!/bin/bash

echo "🚀 Deploying React Apps (Simplified Version)"

# Create deployment directories
mkdir -p /var/www/react-apps/admin
mkdir -p /var/www/react-apps/business

# Admin App
cat > /var/www/react-apps/admin/server.js << 'EOF'
const express = require('express');
const cors = require('cors');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3001;

// Enable CORS
app.use(cors());

// API proxy to Laravel backend
app.use('/api', (req, res) => {
  res.redirect(`https://api.askproai.de${req.url}`);
});

// Serve static React app
app.use(express.static(path.join(__dirname, 'public')));

// SPA fallback
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.listen(PORT, () => {
  console.log(`Admin Portal running on port ${PORT}`);
});
EOF

# Business App  
cat > /var/www/react-apps/business/server.js << 'EOF'
const express = require('express');
const cors = require('cors');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3002;

// Enable CORS
app.use(cors());

// API proxy to Laravel backend
app.use('/api', (req, res) => {
  res.redirect(`https://api.askproai.de${req.url}`);
});

// Serve static React app
app.use(express.static(path.join(__dirname, 'public')));

// SPA fallback
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.listen(PORT, () => {
  console.log(`Business Portal running on port ${PORT}`);
});
EOF

# Create package.json files
cat > /var/www/react-apps/admin/package.json << 'EOF'
{
  "name": "askproai-admin",
  "version": "1.0.0",
  "main": "server.js",
  "scripts": {
    "start": "node server.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "cors": "^2.8.5"
  }
}
EOF

cat > /var/www/react-apps/business/package.json << 'EOF'
{
  "name": "askproai-business",
  "version": "1.0.0",
  "main": "server.js",
  "scripts": {
    "start": "node server.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "cors": "^2.8.5"
  }
}
EOF

# Copy React build files (use demos as base)
mkdir -p /var/www/react-apps/admin/public
mkdir -p /var/www/react-apps/business/public

cp /var/www/api-gateway/public/demo-admin.html /var/www/react-apps/admin/public/index.html
cp /var/www/api-gateway/public/demo-portal.html /var/www/react-apps/business/public/index.html

# Install dependencies
cd /var/www/react-apps/admin && npm install
cd /var/www/react-apps/business && npm install

# Start with PM2
pm2 delete askproai-admin 2>/dev/null || true
pm2 delete askproai-business 2>/dev/null || true

cd /var/www/react-apps/admin
pm2 start server.js --name askproai-admin --env PORT=3001

cd /var/www/react-apps/business  
pm2 start server.js --name askproai-business --env PORT=3002

pm2 save
pm2 startup

echo "✅ React apps deployed!"
echo "Admin: http://localhost:3001"
echo "Business: http://localhost:3002"