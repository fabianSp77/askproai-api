#!/bin/bash

# Deploy Security Fixes Script
# This script safely deploys API encryption and multi-tenancy security fixes

set -e

echo "🔒 Deploying Security Fixes..."
echo "================================"

# 1. Create backup directory
BACKUP_DIR="storage/backups/security-deploy-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "📦 Creating backups in $BACKUP_DIR..."

# 2. Backup current files
cp app/Traits/BelongsToCompany.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Models/Scopes/CompanyScope.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Services/Webhook/WebhookCompanyResolver.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Models/Tenant.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Models/RetellConfiguration.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Models/CustomerAuth.php "$BACKUP_DIR/" 2>/dev/null || true

echo "✅ Backups created"

# 3. Deploy multi-tenancy security fixes
echo ""
echo "🛡️  Deploying Multi-Tenancy Security Fixes..."

cp app/Traits/BelongsToCompany_SECURE.php app/Traits/BelongsToCompany.php
cp app/Models/Scopes/CompanyScope_SECURE.php app/Models/Scopes/CompanyScope.php
cp app/Services/Webhook/WebhookCompanyResolver_SECURE.php app/Services/Webhook/WebhookCompanyResolver.php

echo "✅ Multi-tenancy security deployed"

# 4. Deploy API encryption models
echo ""
echo "🔐 Deploying API Encryption Models..."

cp app/Models/Tenant_ENCRYPTED.php app/Models/Tenant.php
cp app/Models/RetellConfiguration_ENCRYPTED.php app/Models/RetellConfiguration.php
cp app/Models/CustomerAuth_ENCRYPTED.php app/Models/CustomerAuth.php

echo "✅ Encrypted models deployed"

# 5. Clear all caches
echo ""
echo "🧹 Clearing caches..."

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "✅ Caches cleared"

# 6. Run migrations
echo ""
echo "🗄️  Running encryption migrations..."

# Check if migrations already ran
if php artisan migrate:status | grep -q "2025_06_27_120000.*encrypt_tenant_api_keys.*Ran"; then
    echo "⚠️  Encryption migrations already ran"
else
    php artisan migrate --force
    echo "✅ Migrations completed"
fi

# 7. Verify deployment
echo ""
echo "🔍 Verifying deployment..."

# Check that files were replaced
if grep -q "SECURITY: This trait ensures" app/Traits/BelongsToCompany.php; then
    echo "✅ BelongsToCompany secure version deployed"
else
    echo "❌ BelongsToCompany deployment failed!"
    exit 1
fi

if grep -q "SECURITY: This method is critical" app/Models/Scopes/CompanyScope.php; then
    echo "✅ CompanyScope secure version deployed"
else
    echo "❌ CompanyScope deployment failed!"
    exit 1
fi

if grep -q "SECURITY: This method MUST correctly" app/Services/Webhook/WebhookCompanyResolver.php; then
    echo "✅ WebhookCompanyResolver secure version deployed"
else
    echo "❌ WebhookCompanyResolver deployment failed!"
    exit 1
fi

# 8. Test basic functionality
echo ""
echo "🧪 Running basic tests..."

# Test that application still loads
if php artisan tinker --execute="echo 'Application loads OK';" >/dev/null 2>&1; then
    echo "✅ Application loads successfully"
else
    echo "❌ Application failed to load!"
    exit 1
fi

# 9. Log deployment
echo ""
echo "📝 Logging deployment..."

cat >> storage/logs/deployments.log << EOF
=== Security Deployment ===
Date: $(date)
User: $(whoami)
Deployed:
- Multi-tenancy security fixes
- API key encryption
- Database migrations
Backup: $BACKUP_DIR
===========================

EOF

echo ""
echo "🎉 Security fixes deployed successfully!"
echo ""
echo "⚠️  IMPORTANT POST-DEPLOYMENT STEPS:"
echo "1. Monitor logs for security violations:"
echo "   tail -f storage/logs/laravel.log | grep 'SECURITY\\|WARNING\\|CRITICAL'"
echo ""
echo "2. Test webhook processing for each company"
echo ""
echo "3. Verify that all features work correctly"
echo ""
echo "4. If issues occur, rollback with:"
echo "   ./rollback-security-fixes.sh $BACKUP_DIR"
echo ""
echo "5. Restart queue workers:"
echo "   php artisan horizon:terminate"
echo ""

# Create rollback script
cat > rollback-security-fixes.sh << 'ROLLBACK'
#!/bin/bash
# Rollback Security Fixes

if [ -z "$1" ]; then
    echo "Usage: $0 <backup-directory>"
    exit 1
fi

BACKUP_DIR="$1"

if [ ! -d "$BACKUP_DIR" ]; then
    echo "Backup directory not found: $BACKUP_DIR"
    exit 1
fi

echo "Rolling back from $BACKUP_DIR..."

# Restore files
cp "$BACKUP_DIR/BelongsToCompany.php" app/Traits/ 2>/dev/null || true
cp "$BACKUP_DIR/CompanyScope.php" app/Models/Scopes/ 2>/dev/null || true
cp "$BACKUP_DIR/WebhookCompanyResolver.php" app/Services/Webhook/ 2>/dev/null || true
cp "$BACKUP_DIR/Tenant.php" app/Models/ 2>/dev/null || true
cp "$BACKUP_DIR/RetellConfiguration.php" app/Models/ 2>/dev/null || true
cp "$BACKUP_DIR/CustomerAuth.php" app/Models/ 2>/dev/null || true

# Clear caches
php artisan cache:clear
php artisan config:clear

echo "✅ Rollback completed"
ROLLBACK

chmod +x rollback-security-fixes.sh

echo "✅ Rollback script created: rollback-security-fixes.sh"