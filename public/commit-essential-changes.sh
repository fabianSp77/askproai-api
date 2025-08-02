#!/bin/bash

# Commit Essential Changes Script
# This script helps commit the most important changes from the portal fixes

echo "🚀 Committing Essential Portal Fixes..."

# Portal Authentication Fixes
echo "📌 Stage 1: Portal Authentication"
git add resources/views/portal/business-integrated.blade.php
git add public/js/portal-auth-fix.js
git add public/js/portal-complete-implementation.js
git add public/portal-working.html
git add resources/js/PortalApp.jsx
git add resources/js/contexts/AuthContext.jsx
git add resources/js/services/axiosInstance.js
git add resources/js/utils/portalAxios.js

echo "✅ Staged portal authentication files"

# Critical Middleware
echo "📌 Stage 2: Critical Middleware"
git add app/Http/Middleware/PortalAuth.php
git add app/Http/Middleware/EnsureTwoFactorEnabled.php
git add app/Http/Middleware/SharePortalSession.php
git add app/Http/Middleware/VerifyCsrfToken.php

echo "✅ Staged middleware files"

# API Controllers
echo "📌 Stage 3: API Controllers"
git add app/Http/Controllers/Portal/Auth/LoginController.php
git add app/Http/Controllers/Portal/DashboardController.php
git add app/Http/Controllers/Controller.php

echo "✅ Staged controller files"

# Routes
echo "📌 Stage 4: Routes"
git add routes/business-portal.php
git add routes/web.php
git add routes/api.php

echo "✅ Staged route files"

# Config files
echo "📌 Stage 5: Configuration"
git add config/session.php
git add bootstrap/app.php

echo "✅ Staged configuration files"

# Show what will be committed
echo ""
echo "📋 Files staged for commit:"
git status --short | grep '^[AM]'

echo ""
echo "📝 Suggested commit message:"
echo "fix: Portal authentication and session handling"
echo ""
echo "- Fixed business portal login flow"
echo "- Added working portal implementation"
echo "- Fixed session persistence issues"
echo "- Updated middleware for proper auth handling"
echo ""
echo "Run: git commit -m \"fix: Portal authentication and session handling\""