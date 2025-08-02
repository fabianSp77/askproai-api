#!/bin/bash

# Cleanup script for test/debug controllers
# Archives them to a backup directory before removal

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="storage/archived-test-controllers-$TIMESTAMP"

echo "🧹 Cleaning up test/debug controllers..."
echo "📁 Creating backup directory: $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"

# List of test/debug controllers to remove
TEST_CONTROLLERS=(
    "app/Http/Controllers/Portal/Auth/DebugLoginController.php"
    "app/Http/Controllers/Portal/Auth/TestLoginController.php"
    "app/Http/Controllers/Portal/Api/SessionDebugController.php"
    "app/Http/Controllers/Portal/Api/DebugCallsApiController.php"
    "app/Http/Controllers/Portal/Api/DebugBillingController.php"
    "app/Http/Controllers/Portal/Api/TestAuthController.php"
    "app/Http/Controllers/Portal/Api/DebugSessionController.php"
    "app/Http/Controllers/Portal/Api/TestSessionController.php"
    "app/Http/Controllers/Portal/Api/TestCallsController.php"
    "app/Http/Controllers/Portal/Api/SimpleSessionTestController.php"
    "app/Http/Controllers/Portal/Api/DebugAuthController.php"
    "app/Http/Controllers/Portal/Api/SimpleAuthTestController.php"
    "app/Http/Controllers/Portal/Api/DebugCallsController.php"
    "app/Http/Controllers/Portal/Api/AuthDebugController.php"
    "app/Http/Controllers/Portal/SimpleDashboardController.php"
    "app/Http/Controllers/Portal/DemoController.php"
    "app/Http/Controllers/Portal/DebugDashboardController.php"
    "app/Http/Controllers/Portal/SimpleLoginController.php"
    "app/Http/Controllers/Portal/TestLoginController.php"
    "app/Http/Controllers/Portal/BypassLoginController.php"
    "app/Http/Controllers/Portal/DirectAccessController.php"
    "app/Http/Controllers/Portal/ReactTestController.php"
)

# Archive and remove each controller
for controller in "${TEST_CONTROLLERS[@]}"; do
    if [ -f "$controller" ]; then
        echo "📄 Archiving: $controller"
        cp "$controller" "$BACKUP_DIR/"
        rm "$controller"
        echo "   ✅ Archived and removed"
    else
        echo "   ⚠️  Not found: $controller"
    fi
done

# Also check for test routes that reference these controllers
echo ""
echo "🔍 Checking for routes that reference test controllers..."

# Create a report of routes to review
ROUTES_REPORT="$BACKUP_DIR/routes-to-review.txt"
echo "Routes that may reference test/debug controllers:" > "$ROUTES_REPORT"
echo "================================================" >> "$ROUTES_REPORT"

grep -r "TestLogin\|DebugLogin\|SimpleLogin\|BypassLogin\|DirectAccess\|TestAuth\|DebugAuth\|DebugSession\|TestSession\|DebugCalls\|TestCalls\|DebugBilling\|DemoController\|ReactTest" routes/ >> "$ROUTES_REPORT" 2>/dev/null

if [ -s "$ROUTES_REPORT" ]; then
    echo "⚠️  Found routes that reference test controllers!"
    echo "   Please review: $ROUTES_REPORT"
else
    echo "✅ No routes found referencing test controllers"
fi

# Summary
echo ""
echo "✨ Cleanup completed!"
echo "📁 Backup location: $BACKUP_DIR"
echo ""
echo "⚠️  IMPORTANT: Please also:"
echo "   1. Review and update routes files"
echo "   2. Clear route cache: php artisan route:clear && php artisan route:cache"
echo "   3. Test the application to ensure no broken references"

# Make report easier to read
if [ -s "$ROUTES_REPORT" ]; then
    echo ""
    echo "📋 Routes that need attention:"
    cat "$ROUTES_REPORT"
fi