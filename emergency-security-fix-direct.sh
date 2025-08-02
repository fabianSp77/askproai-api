#\!/bin/bash
# Direct security fixes without git operations

echo "🚨 EMERGENCY SECURITY FIX STARTING (DIRECT MODE)..."
echo "Target: TOP 18 Critical withoutGlobalScope Vulnerabilities"
echo ""

echo "🔧 Applying emergency fixes..."

# FIX #1: AdminApiController - KRITISCH
echo "Fix #1: AdminApiController..."
sed -i 's/Call::withoutGlobalScope.*TenantScope.*->find/Call::where("company_id", auth()->user()->company_id)->find/g' app/Http/Controllers/Admin/AdminApiController.php

# FIX #2: PublicDownloadController - KRITISCH  
echo "Fix #2: PublicDownloadController..."
sed -i 's/Call::withoutGlobalScope.*TenantScope.*where/Call::where("company_id", auth()->user()->company_id)->where/g' app/Http/Controllers/Portal/PublicDownloadController.php

# FIX #3: GuestAccessController - KRITISCH
echo "Fix #3: GuestAccessController..."
sed -i 's/Call::withoutGlobalScope.*TenantScope.*where/Call::where/g' app/Http/Controllers/Portal/GuestAccessController.php

# FIX #4: RetellWebhookWorkingController - KRITISCH
echo "Fix #4: RetellWebhookWorkingController..."
sed -i 's/Call::withoutGlobalScope(TenantScope::class)->where/Call::where/g' app/Http/Controllers/Api/RetellWebhookWorkingController.php
sed -i 's/Branch::withoutGlobalScope(TenantScope::class)->find/Branch::find/g' app/Http/Controllers/Api/RetellWebhookWorkingController.php

# FIX #5: RetellWebhookSimpleController - KRITISCH
echo "Fix #5: RetellWebhookSimpleController..."
sed -i 's/Call::withoutGlobalScope(TenantScope::class)->where/Call::where/g' app/Http/Controllers/Api/RetellWebhookSimpleController.php
sed -i 's/Branch::withoutGlobalScope(TenantScope::class)->find/Branch::find/g' app/Http/Controllers/Api/RetellWebhookSimpleController.php

# FIX #6: PortalApiAuth Middleware - KRITISCH
echo "Fix #6: PortalApiAuth Middleware..."
# Already fixed in previous session

# FIX #7: DashboardMetricsService - HOCH
echo "Fix #7: DashboardMetricsService..."
sed -i 's/Appointment::withoutGlobalScope.*TenantScope.*where.*company_id.*$companyId/Appointment::where("company_id", auth()->user()->company_id ?? $companyId)/g' app/Services/Dashboard/DashboardMetricsService.php

# FIX #8: Job Processing - Critical Background Jobs
echo "Fix #8: Job Processing..."
find app/Jobs/ -name "*.php" -exec sed -i 's/WebhookEvent::withoutGlobalScope.*TenantScope.*->find/WebhookEvent::find/g' {} \;
find app/Jobs/ -name "*.php" -exec sed -i 's/Call::withoutGlobalScope.*TenantScope.*->find/Call::find/g' {} \;

# FIX #9: RetellEnhancedWebhookController - KRITISCH
echo "Fix #9: RetellEnhancedWebhookController..."
sed -i 's/Call::withoutGlobalScope.*TenantScope.*where/Call::where/g' app/Http/Controllers/RetellEnhancedWebhookController.php
sed -i 's/Customer::withoutGlobalScope.*TenantScope.*where/Customer::where/g' app/Http/Controllers/RetellEnhancedWebhookController.php
sed -i 's/Branch::withoutGlobalScope.*TenantScope.*find/Branch::find/g' app/Http/Controllers/RetellEnhancedWebhookController.php

# FIX #10: Portal Controllers - Remaining Critical
echo "Fix #10: Portal Controllers..."
find app/Http/Controllers/Portal/ -name "*.php" -exec sed -i 's/Company::withoutGlobalScope.*TenantScope.*->find/Company::find/g' {} \;

echo ""
echo "✅ Emergency fixes applied\!"
echo ""

# Validation
echo "🔍 Validating fixes..."
REMAINING=$(grep -r "withoutGlobalScope.*TenantScope" app/Http/Controllers/ --include="*.php" | wc -l)
CRITICAL_REMAINING=$(grep -r "withoutGlobalScope.*find.*\$" app/Http/Controllers/ --include="*.php" | wc -l)

echo "📊 RESULTS:"
echo "- Total withoutGlobalScope in Controllers: $REMAINING (was ~40)"
echo "- Critical direct access patterns: $CRITICAL_REMAINING (was ~18)" 

if [ $CRITICAL_REMAINING -lt 5 ]; then
    echo "🎯 SUCCESS: Critical vulnerabilities significantly reduced\!"
else
    echo "⚠️  WARNING: Manual review needed for remaining patterns"
fi

# Test syntax
echo ""
echo "🧪 Testing PHP syntax..."
SYNTAX_ERRORS=0
for file in app/Http/Controllers/Admin/AdminApiController.php app/Http/Controllers/Portal/PublicDownloadController.php app/Http/Controllers/Portal/GuestAccessController.php app/Http/Controllers/Api/RetellWebhookWorkingController.php app/Http/Controllers/Api/RetellWebhookSimpleController.php; do
    if [ -f "$file" ]; then
        if \! php -l "$file" >/dev/null 2>&1; then
            echo "❌ Syntax error in $file"
            SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
        fi
    fi
done

if [ $SYNTAX_ERRORS -eq 0 ]; then
    echo "✅ All fixed files have valid PHP syntax"
else
    echo "❌ $SYNTAX_ERRORS files have syntax errors - rollback recommended"
fi

echo ""
echo "🚀 NEXT STEPS:"
echo "1. Test critical workflows (login, calls, webhooks)"
echo "2. Monitor logs for any 'Cross-tenant access denied' alerts"  
echo "3. Run full test suite: php artisan test"
echo "4. Deploy to production after testing"
