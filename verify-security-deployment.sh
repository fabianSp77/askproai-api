#!/bin/bash

echo "🔍 Verifying Security Deployment..."
echo "================================"

ERRORS=0

# Check BelongsToCompany trait
echo -n "BelongsToCompany secure version: "
if grep -q "getCurrentCompanyId" app/Traits/BelongsToCompany.php && \
   grep -q "setTrustedCompanyContext" app/Traits/BelongsToCompany.php; then
    echo "✅ Deployed"
else
    echo "❌ Not deployed"
    ERRORS=$((ERRORS + 1))
fi

# Check CompanyScope
echo -n "CompanyScope secure version: "
if grep -q "SECURITY: This method is critical" app/Models/Scopes/CompanyScope.php || \
   grep -q "trusted_job" app/Models/Scopes/CompanyScope.php; then
    echo "✅ Deployed"
else
    echo "❌ Not deployed"
    ERRORS=$((ERRORS + 1))
fi

# Check WebhookCompanyResolver
echo -n "WebhookCompanyResolver secure version: "
if grep -q "SECURITY: This method MUST correctly" app/Services/Webhook/WebhookCompanyResolver.php || \
   grep -q "NO FALLBACK TO RANDOM COMPANY" app/Services/Webhook/WebhookCompanyResolver.php; then
    echo "✅ Deployed"
else
    echo "❌ Not deployed"
    ERRORS=$((ERRORS + 1))
fi

# Check encrypted models
echo -n "Tenant encrypted model: "
if grep -q "getApiKeyAttribute\|encryptApiKey" app/Models/Tenant.php; then
    echo "✅ Deployed"
else
    echo "❌ Not deployed"
    ERRORS=$((ERRORS + 1))
fi

echo -n "RetellConfiguration encrypted model: "
if grep -q "getWebhookSecretAttribute\|verifyWebhookSignature" app/Models/RetellConfiguration.php; then
    echo "✅ Deployed"
else
    echo "❌ Not deployed"
    ERRORS=$((ERRORS + 1))
fi

echo -n "CustomerAuth encrypted model: "
if grep -q "getPortalAccessTokenAttribute\|generatePortalToken" app/Models/CustomerAuth.php; then
    echo "✅ Deployed"
else
    echo "❌ Not deployed"
    ERRORS=$((ERRORS + 1))
fi

# Check migrations
echo -n "Encryption migrations: "
if php artisan migrate:status | grep -E "2025_06_27_120000.*encrypt_tenant_api_keys.*Ran" | grep -q "Ran" && \
   php artisan migrate:status | grep -E "2025_06_27_121000.*encrypt_all_sensitive_fields.*Ran" | grep -q "Ran"; then
    echo "✅ Completed"
else
    echo "❌ Not completed"
    ERRORS=$((ERRORS + 1))
fi

echo ""
echo "================================"
if [ $ERRORS -eq 0 ]; then
    echo "✅ All security fixes are deployed!"
else
    echo "❌ $ERRORS components are not deployed correctly"
    exit 1
fi