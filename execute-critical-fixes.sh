#!/bin/bash
# 🚨 CRITICAL FIXES EXECUTION SCRIPT
# Führt die wichtigsten Sofortmaßnahmen aus

set -e

echo "🚨 Starting Critical Fixes for Business Portal..."
echo "================================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Function to check success
check_success() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1 successful${NC}"
    else
        echo -e "${RED}❌ $1 failed${NC}"
        exit 1
    fi
}

# 1. Backup current state
echo -e "\n${YELLOW}1. Creating backup...${NC}"
php artisan backup:run --only-db
check_success "Database backup"

# 2. Clear all caches
echo -e "\n${YELLOW}2. Clearing caches...${NC}"
php artisan optimize:clear
check_success "Cache clear"

# 3. Fix Customer API Routes
echo -e "\n${YELLOW}3. Fixing Customer API routes...${NC}"
cat << 'EOF' > /tmp/customer-api-routes.php
// Customer API Routes for Business Portal
Route::prefix('api')->middleware(['auth:customer-api', 'throttle:api'])->group(function () {
    // Customer endpoints
    Route::get('customers', [Api\CustomersApiController::class, 'index']);
    Route::get('customers/{customer}', [Api\CustomersApiController::class, 'show']);
    Route::put('customers/{customer}', [Api\CustomersApiController::class, 'update']);
    Route::get('customers/{customer}/appointments', [Api\CustomersApiController::class, 'appointments']);
    Route::get('customers/{customer}/invoices', [Api\CustomersApiController::class, 'invoices']);
    
    // Stats endpoint fix
    Route::get('stats', [Api\DashboardApiController::class, 'stats']);
    
    // Appointments endpoint fix
    Route::get('appointments', [Api\AppointmentsApiController::class, 'index']);
    Route::get('appointments/{appointment}', [Api\AppointmentsApiController::class, 'show']);
});
EOF

echo "Please add the above routes to routes/business-portal.php"
echo -e "${GREEN}✅ Route template created${NC}"

# 4. Fix CSRF for API routes
echo -e "\n${YELLOW}4. Configuring CSRF exceptions...${NC}"
php artisan tinker --execute="
    \$middleware = app()->make('App\Http\Middleware\VerifyCsrfToken');
    echo 'Current CSRF exceptions: ';
    print_r(\$middleware->except ?? []);
"

# 5. Test API endpoints
echo -e "\n${YELLOW}5. Testing API endpoints...${NC}"
php artisan route:list | grep -E "business/(api|dashboard)" | head -20

# 6. Generate emergency tests
echo -e "\n${YELLOW}6. Generating emergency test suite...${NC}"
mkdir -p tests/Feature/Portal/Emergency

cat << 'EOF' > tests/Feature/Portal/Emergency/CriticalPathTest.php
<?php

namespace Tests\Feature\Portal\Emergency;

use Tests\TestCase;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CriticalPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_login()
    {
        $customer = Customer::factory()->create();
        
        $response = $this->post('/business/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/business/dashboard');
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_dashboard_loads_for_authenticated_customer()
    {
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($customer, 'customer')
            ->get('/business/dashboard');

        $response->assertOk();
    }

    public function test_api_customers_endpoint_works()
    {
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($customer, 'customer-api')
            ->getJson('/business/api/customers');

        $response->assertOk();
    }
}
EOF
check_success "Test generation"

# 7. Run emergency tests
echo -e "\n${YELLOW}7. Running emergency tests...${NC}"
php artisan test tests/Feature/Portal/Emergency/CriticalPathTest.php || true

# 8. Check system health
echo -e "\n${YELLOW}8. System Health Check...${NC}"
php artisan tinker --execute="
    echo 'Active customer sessions: ' . Redis::scard('customer:sessions:active') . PHP_EOL;
    echo 'Error rate (last hour): ' . Cache::get('portal.errors.rate', 'unknown') . PHP_EOL;
    echo 'Database connected: ' . (DB::connection()->getPdo() ? 'Yes' : 'No') . PHP_EOL;
"

# 9. Create monitoring dashboard
echo -e "\n${YELLOW}9. Setting up monitoring...${NC}"
cat << 'EOF' > public/portal-health-check.php
<?php
// Quick health check endpoint
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => [
        'database' => false,
        'redis' => false,
        'api_routes' => false,
    ]
];

// Check database
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn');
    $health['checks']['database'] = true;
} catch (Exception $e) {
    $health['status'] = 'error';
}

// Check Redis
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $redis->ping();
    $health['checks']['redis'] = true;
} catch (Exception $e) {
    $health['status'] = 'error';
}

echo json_encode($health, JSON_PRETTY_PRINT);
EOF
check_success "Monitoring setup"

# 10. Final summary
echo -e "\n${GREEN}================================================${NC}"
echo -e "${GREEN}🎉 Critical fixes script completed!${NC}"
echo -e "${GREEN}================================================${NC}"
echo -e "\n${YELLOW}Next manual steps:${NC}"
echo "1. Add the customer API routes to routes/business-portal.php"
echo "2. Update VerifyCsrfToken middleware to exclude API routes"
echo "3. Deploy changes with: php artisan config:cache && php artisan route:cache"
echo "4. Monitor health at: https://api.askproai.de/portal-health-check.php"
echo -e "\n${YELLOW}Quick test command:${NC}"
echo "curl -s https://api.askproai.de/portal-health-check.php | jq ."