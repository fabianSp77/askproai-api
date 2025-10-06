#!/bin/bash

# ═══════════════════════════════════════════════════════════
# PROFIT SYSTEM COMPREHENSIVE TEST SUITE
# ═══════════════════════════════════════════════════════════

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║     PROFIT SYSTEM - COMPREHENSIVE TEST SUITE              ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test results tracking
TESTS_PASSED=0
TESTS_FAILED=0

# Function to run a test suite
run_test_suite() {
    local suite_name=$1
    local test_command=$2

    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}Running: $suite_name${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    if eval $test_command; then
        echo -e "${GREEN}✅ $suite_name PASSED${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}❌ $suite_name FAILED${NC}"
        ((TESTS_FAILED++))
    fi
    echo ""
}

# 1. Setup Test Database
echo "🔧 Setting up test database..."
php artisan migrate:fresh --env=testing --seed --seeder=ProfitTestSeeder

# 2. Run Unit Tests
run_test_suite "Unit Tests - CostCalculator" \
    "php artisan test --filter=CostCalculatorTest"

# 3. Run Feature Tests
run_test_suite "Feature Tests - Profit Security" \
    "php artisan test --filter=ProfitSecurityTest"

# 4. Run Permission Tests
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}Testing Role-Based Access Control${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Test Super Admin Access
php artisan tinker --execute="
    \$user = App\Models\User::where('email', 'superadmin1@test.com')->first();
    \$user->assignRole('super-admin');
    echo '✅ Super Admin: Can access profit dashboard: ' .
        (App\Filament\Pages\ProfitDashboard::canAccess() ? 'YES' : 'NO') . PHP_EOL;
"

# Test Reseller Admin Access
php artisan tinker --execute="
    \$user = App\Models\User::where('email', 'mandant1@test.com')->first();
    \$user->assignRole('reseller_admin');
    echo '✅ Reseller: Can access profit dashboard: ' .
        (App\Filament\Pages\ProfitDashboard::canAccess() ? 'YES' : 'NO') . PHP_EOL;
"

# Test Customer Access (should be NO)
php artisan tinker --execute="
    \$user = App\Models\User::where('email', 'customer@test.com')->first();
    \$user->assignRole('customer');
    echo '❌ Customer: Can access profit dashboard: ' .
        (App\Filament\Pages\ProfitDashboard::canAccess() ? 'YES' : 'NO') . PHP_EOL;
"

# 5. Test Profit Calculations
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}Testing Profit Calculations${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php artisan tinker --execute="
    \$calculator = new App\Services\CostCalculator();
    \$call = App\Models\Call::first();

    // Test different user perspectives
    \$superAdmin = App\Models\User::role('super-admin')->first();
    \$reseller = App\Models\User::role('reseller_admin')->first();
    \$customer = App\Models\User::role('customer')->first();

    echo 'Call ID: ' . \$call->id . PHP_EOL;
    echo 'Base Cost: €' . number_format(\$call->base_cost / 100, 2) . PHP_EOL;
    echo 'Customer Cost: €' . number_format(\$call->customer_cost / 100, 2) . PHP_EOL;
    echo 'Total Profit: €' . number_format(\$call->total_profit / 100, 2) . PHP_EOL;
    echo PHP_EOL;

    \$superProfit = \$calculator->getDisplayProfit(\$call, \$superAdmin);
    echo '👑 Super Admin sees profit: €' . number_format(\$superProfit['profit'] / 100, 2) .
         ' (Type: ' . \$superProfit['type'] . ')' . PHP_EOL;

    \$resellerProfit = \$calculator->getDisplayProfit(\$call, \$reseller);
    echo '🏢 Reseller sees profit: €' . number_format(\$resellerProfit['profit'] / 100, 2) .
         ' (Type: ' . \$resellerProfit['type'] . ')' . PHP_EOL;

    \$customerProfit = \$calculator->getDisplayProfit(\$call, \$customer);
    echo '👤 Customer sees profit: €' . number_format(\$customerProfit['profit'] / 100, 2) .
         ' (Type: ' . \$customerProfit['type'] . ')' . PHP_EOL;
"

# 6. Test Data Integrity
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}Testing Data Integrity${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php artisan tinker --execute="
    // Check profit consistency
    \$calls = App\Models\Call::whereNotNull('total_profit')->get();
    \$inconsistent = 0;

    foreach (\$calls as \$call) {
        if (\$call->total_profit != (\$call->platform_profit + \$call->reseller_profit)) {
            \$inconsistent++;
        }
    }

    echo 'Total Calls with Profit: ' . \$calls->count() . PHP_EOL;
    echo 'Inconsistent Profit Calculations: ' . \$inconsistent . PHP_EOL;

    if (\$inconsistent == 0) {
        echo '✅ All profit calculations are consistent!' . PHP_EOL;
    } else {
        echo '❌ Found ' . \$inconsistent . ' inconsistent profit calculations!' . PHP_EOL;
    }
"

# 7. Performance Test
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}Performance Test${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php artisan tinker --execute="
    \$start = microtime(true);

    // Load profit dashboard data
    \$calls = App\Models\Call::whereDate('created_at', today())->get();
    \$calculator = new App\Services\CostCalculator();
    \$user = App\Models\User::role('super-admin')->first();

    \$totalProfit = 0;
    foreach (\$calls as \$call) {
        \$profitData = \$calculator->getDisplayProfit(\$call, \$user);
        \$totalProfit += \$profitData['profit'];
    }

    \$end = microtime(true);
    \$time = round(\$end - \$start, 3);

    echo 'Processed ' . \$calls->count() . ' calls in ' . \$time . ' seconds' . PHP_EOL;
    echo 'Average: ' . round(\$time / max(\$calls->count(), 1) * 1000, 2) . 'ms per call' . PHP_EOL;

    if (\$time < 1) {
        echo '✅ Performance is excellent!' . PHP_EOL;
    } elseif (\$time < 3) {
        echo '⚠️ Performance is acceptable' . PHP_EOL;
    } else {
        echo '❌ Performance needs optimization' . PHP_EOL;
    }
"

# 8. Summary
echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║                    TEST SUMMARY                           ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo -e "${GREEN}✅ Tests Passed: $TESTS_PASSED${NC}"
echo -e "${RED}❌ Tests Failed: $TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 ALL TESTS PASSED! The Profit System is secure and functional!${NC}"
    exit 0
else
    echo -e "${RED}⚠️ Some tests failed. Please review the output above.${NC}"
    exit 1
fi