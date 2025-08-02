#!/bin/bash

echo "🔧 Fixing remaining withoutGlobalScopes() patterns..."
echo ""

# Fix ServiceController
echo "Fixing ServiceController..."
sed -i 's/Service::withoutGlobalScopes()/Service::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/ServiceController.php

# Fix BillingController - more complex, need to check if Company table has company_id
echo "Fixing BillingController..."
# For Company model - doesn't need company_id filter
sed -i 's/Company::withoutGlobalScopes()/Company::query()/g' app/Http/Controllers/Admin/Api/BillingController.php
# For Invoice model
sed -i 's/Invoice::withoutGlobalScopes()/Invoice::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/BillingController.php
# For PrepaidBalance
sed -i 's/PrepaidBalance::withoutGlobalScopes()/PrepaidBalance::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/BillingController.php
# For BalanceTopup  
sed -i 's/BalanceTopup::withoutGlobalScopes()/BalanceTopup::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/BillingController.php

# Fix relationship withoutGlobalScopes in CustomerController
echo "Fixing CustomerController relationship queries..."
# This is trickier - need to replace relationship queries
sed -i 's/->withoutGlobalScopes()$/->where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/CustomerController.php

# Fix SimpleAuthTestController
echo "Fixing SimpleAuthTestController..."
sed -i 's/PortalUser::withoutGlobalScopes()/PortalUser::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/SimpleAuthTestController.php

# Fix StaffController if exists
if [ -f "app/Http/Controllers/Admin/Api/StaffController.php" ]; then
    echo "Fixing StaffController..."
    sed -i 's/Staff::withoutGlobalScopes()/Staff::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/StaffController.php
fi

# Fix CompanyController if exists
if [ -f "app/Http/Controllers/Admin/Api/CompanyController.php" ]; then
    echo "Fixing CompanyController..."
    # Company model doesn't have company_id, so just remove withoutGlobalScopes
    sed -i 's/Company::withoutGlobalScopes()/Company::query()/g' app/Http/Controllers/Admin/Api/CompanyController.php
fi

# Fix CalcomController if exists
if [ -f "app/Http/Controllers/Admin/Api/CalcomController.php" ]; then
    echo "Fixing CalcomController..."
    sed -i 's/CalcomEventType::withoutGlobalScopes()/CalcomEventType::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/CalcomController.php
fi

# Fix RetellController if exists
if [ -f "app/Http/Controllers/Admin/Api/RetellController.php" ]; then
    echo "Fixing RetellController..."
    sed -i 's/RetellAgent::withoutGlobalScopes()/RetellAgent::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/RetellController.php
fi

# Fix WebhookController if exists
if [ -f "app/Http/Controllers/Admin/Api/WebhookController.php" ]; then
    echo "Fixing WebhookController..."
    sed -i 's/WebhookEvent::withoutGlobalScopes()/WebhookEvent::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/WebhookController.php
fi

echo ""
echo "🔍 Checking remaining patterns..."

# Count different types of withoutGlobalScope usage
TOTAL_WITHOUT=$(grep -r "withoutGlobalScope" app/Http/Controllers/ --include="*.php" | wc -l)
PLURAL_WITHOUT=$(grep -r "withoutGlobalScopes()" app/Http/Controllers/ --include="*.php" | wc -l)
TENANT_WITHOUT=$(grep -r "withoutGlobalScope(TenantScope::class)" app/Http/Controllers/ --include="*.php" | wc -l)

echo "📊 RESULTS:"
echo "- Total withoutGlobalScope calls: $TOTAL_WITHOUT"
echo "- withoutGlobalScopes() (plural): $PLURAL_WITHOUT"
echo "- withoutGlobalScope(TenantScope::class): $TENANT_WITHOUT"

# Show some remaining patterns for manual review
echo ""
echo "📋 Sample remaining patterns for manual review:"
grep -r "withoutGlobalScope" app/Http/Controllers/ --include="*.php" | head -10

echo ""
echo "✅ Script complete!"
echo ""
echo "⚠️  IMPORTANT NOTES:"
echo "1. Relationship queries in closures may need manual review"
echo "2. Company model doesn't have company_id - uses query() instead"
echo "3. Some models might not have company_id field - verify table structure"
echo "4. Test all affected endpoints after these changes"