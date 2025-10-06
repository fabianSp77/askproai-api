#!/bin/bash
################################################################################
# Post-Deployment Smoke Test Script
# Purpose: Quick validation of critical functionality after deployment
# Exit Codes: 0=GREEN (all pass), 1=YELLOW (warnings), 2=RED (critical failure)
################################################################################

set -euo pipefail

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="/var/www/api-gateway/storage/logs/deployment"
LOG_FILE="${LOG_DIR}/smoke-test-$(date +%Y%m%d-%H%M%S).log"

# Test results
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_WARNING=0

################################################################################
# Logging Functions
################################################################################

setup_logging() {
    mkdir -p "$LOG_DIR"
    touch "$LOG_FILE"
    echo "==================================" | tee -a "$LOG_FILE"
    echo "Smoke Test - $(date)" | tee -a "$LOG_FILE"
    echo "==================================" | tee -a "$LOG_FILE"
}

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}✅ $*${NC}" | tee -a "$LOG_FILE"
    ((TESTS_PASSED++))
}

log_error() {
    echo -e "${RED}❌ $*${NC}" | tee -a "$LOG_FILE"
    ((TESTS_FAILED++))
}

log_warning() {
    echo -e "${YELLOW}⚠️  $*${NC}" | tee -a "$LOG_FILE"
    ((TESTS_WARNING++))
}

log_info() {
    echo -e "${BLUE}ℹ️  $*${NC}" | tee -a "$LOG_FILE"
}

################################################################################
# Test Functions
################################################################################

test_policy_configuration_create() {
    log_info "Testing PolicyConfiguration CREATE..."

    local result=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            DB::beginTransaction();

            // Get first company
            \$company = DB::table('companies')->first();
            if (!\$company) throw new Exception('No company found');

            // Create policy configuration
            \$id = DB::table('policy_configurations')->insertGetId([
                'company_id' => \$company->id,
                'config_type' => 'callback',
                'callback_url' => 'https://example.com/callback',
                'auto_callback_enabled' => true,
                'callback_delay_minutes' => 5,
                'callback_business_hours_only' => false,
                'callback_retry_attempts' => 3,
                'callback_retry_delay_minutes' => 10,
                'priority' => 100,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify created
            \$policy = DB::table('policy_configurations')->find(\$id);
            if (!\$policy) throw new Exception('Policy not found after insert');
            if (\$policy->callback_url !== 'https://example.com/callback') {
                throw new Exception('Data mismatch');
            }

            DB::rollBack();
            echo 'OK';
        } catch (Exception \$e) {
            DB::rollBack();
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [[ "$result" == "OK" ]]; then
        log_success "PolicyConfiguration CREATE test passed"
        return 0
    else
        log_error "PolicyConfiguration CREATE test failed: $result"
        return 1
    fi
}

test_policy_configuration_update() {
    log_info "Testing PolicyConfiguration UPDATE..."

    local result=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            DB::beginTransaction();

            // Get first company
            \$company = DB::table('companies')->first();
            if (!\$company) throw new Exception('No company found');

            // Create policy
            \$id = DB::table('policy_configurations')->insertGetId([
                'company_id' => \$company->id,
                'config_type' => 'callback',
                'priority' => 100,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update policy
            DB::table('policy_configurations')
                ->where('id', \$id)
                ->update([
                    'priority' => 200,
                    'callback_delay_minutes' => 15,
                    'updated_at' => now(),
                ]);

            // Verify update
            \$policy = DB::table('policy_configurations')->find(\$id);
            if (\$policy->priority !== 200) throw new Exception('Update failed');
            if (\$policy->callback_delay_minutes !== 15) throw new Exception('Update incomplete');

            DB::rollBack();
            echo 'OK';
        } catch (Exception \$e) {
            DB::rollBack();
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [[ "$result" == "OK" ]]; then
        log_success "PolicyConfiguration UPDATE test passed"
        return 0
    else
        log_error "PolicyConfiguration UPDATE test failed: $result"
        return 1
    fi
}

test_policy_configuration_delete() {
    log_info "Testing PolicyConfiguration SOFT DELETE..."

    local result=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            DB::beginTransaction();

            // Get first company
            \$company = DB::table('companies')->first();
            if (!\$company) throw new Exception('No company found');

            // Create policy
            \$id = DB::table('policy_configurations')->insertGetId([
                'company_id' => \$company->id,
                'config_type' => 'callback',
                'priority' => 100,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Soft delete
            DB::table('policy_configurations')
                ->where('id', \$id)
                ->update(['deleted_at' => now()]);

            // Verify soft delete
            \$policy = DB::table('policy_configurations')->find(\$id);
            if (!\$policy->deleted_at) throw new Exception('Soft delete failed');

            DB::rollBack();
            echo 'OK';
        } catch (Exception \$e) {
            DB::rollBack();
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [[ "$result" == "OK" ]]; then
        log_success "PolicyConfiguration SOFT DELETE test passed"
        return 0
    else
        log_error "PolicyConfiguration SOFT DELETE test failed: $result"
        return 1
    fi
}

test_callback_request_create() {
    log_info "Testing CallbackRequest CREATE..."

    local result=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            DB::beginTransaction();

            // Get required entities
            \$company = DB::table('companies')->first();
            \$customer = DB::table('customers')->first();
            if (!\$company || !\$customer) throw new Exception('Missing company or customer');

            // Create callback request
            \$id = DB::table('callback_requests')->insertGetId([
                'company_id' => \$company->id,
                'customer_id' => \$customer->id,
                'callback_phone' => '+1234567890',
                'callback_reason' => 'Test callback request',
                'priority' => 'high',
                'status' => 'pending',
                'retry_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify created
            \$callback = DB::table('callback_requests')->find(\$id);
            if (!\$callback) throw new Exception('Callback not found after insert');
            if (\$callback->status !== 'pending') throw new Exception('Data mismatch');

            DB::rollBack();
            echo 'OK';
        } catch (Exception \$e) {
            DB::rollBack();
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [[ "$result" == "OK" ]]; then
        log_success "CallbackRequest CREATE test passed"
        return 0
    else
        log_error "CallbackRequest CREATE test failed: $result"
        return 1
    fi
}

test_callback_request_status_update() {
    log_info "Testing CallbackRequest STATUS transitions..."

    local result=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            DB::beginTransaction();

            // Get required entities
            \$company = DB::table('companies')->first();
            \$customer = DB::table('customers')->first();
            if (!\$company || !\$customer) throw new Exception('Missing company or customer');

            // Create callback
            \$id = DB::table('callback_requests')->insertGetId([
                'company_id' => \$company->id,
                'customer_id' => \$customer->id,
                'callback_phone' => '+1234567890',
                'priority' => 'normal',
                'status' => 'pending',
                'retry_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Test status transitions
            $statuses = ['scheduled', 'in_progress', 'completed'];
            foreach ($statuses as $status) {
                DB::table('callback_requests')
                    ->where('id', \$id)
                    ->update(['status' => \$status, 'updated_at' => now()]);

                \$cb = DB::table('callback_requests')->find(\$id);
                if (\$cb->status !== \$status) {
                    throw new Exception(\"Status transition to \$status failed\");
                }
            }

            DB::rollBack();
            echo 'OK';
        } catch (Exception \$e) {
            DB::rollBack();
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [[ "$result" == "OK" ]]; then
        log_success "CallbackRequest STATUS transitions test passed"
        return 0
    else
        log_error "CallbackRequest STATUS transitions test failed: $result"
        return 1
    fi
}

test_foreign_key_integrity() {
    log_info "Testing FOREIGN KEY integrity enforcement..."

    local result=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            DB::beginTransaction();

            // Try inserting with invalid company_id
            try {
                DB::table('policy_configurations')->insert([
                    'company_id' => 999999999,
                    'config_type' => 'callback',
                    'priority' => 100,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                throw new Exception('FK constraint not enforced on policy_configurations');
            } catch (Exception \$e) {
                if (strpos(\$e->getMessage(), 'foreign key') === false &&
                    strpos(\$e->getMessage(), 'Integrity constraint') === false) {
                    throw \$e;
                }
            }

            // Try inserting with invalid customer_id
            try {
                \$company = DB::table('companies')->first();
                DB::table('callback_requests')->insert([
                    'company_id' => \$company->id,
                    'customer_id' => 999999999,
                    'callback_phone' => '+1234567890',
                    'status' => 'pending',
                    'priority' => 'normal',
                    'retry_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                throw new Exception('FK constraint not enforced on callback_requests');
            } catch (Exception \$e) {
                if (strpos(\$e->getMessage(), 'foreign key') === false &&
                    strpos(\$e->getMessage(), 'Integrity constraint') === false) {
                    throw \$e;
                }
            }

            DB::rollBack();
            echo 'OK';
        } catch (Exception \$e) {
            DB::rollBack();
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [[ "$result" == "OK" ]]; then
        log_success "Foreign key integrity test passed"
        return 0
    else
        log_error "Foreign key integrity test failed: $result"
        return 1
    fi
}

test_cache_operations() {
    log_info "Testing CACHE operations..."

    local result=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            // Test cache set/get
            Cache::put('smoke_test_key', 'smoke_test_value', 60);
            \$value = Cache::get('smoke_test_key');

            if (\$value !== 'smoke_test_value') {
                throw new Exception('Cache get/set failed');
            }

            // Test cache delete
            Cache::forget('smoke_test_key');
            if (Cache::has('smoke_test_key')) {
                throw new Exception('Cache delete failed');
            }

            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [[ "$result" == "OK" ]]; then
        log_success "Cache operations test passed"
        return 0
    else
        log_warning "Cache operations test failed: $result (non-critical)"
        return 0
    fi
}

test_index_performance() {
    log_info "Testing INDEX performance..."

    local result=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            // Test indexed query on policy_configurations
            \$start = microtime(true);
            \$policies = DB::table('policy_configurations')
                ->where('is_active', true)
                ->where('config_type', 'callback')
                ->limit(10)
                ->get();
            \$duration1 = microtime(true) - \$start;

            // Test indexed query on callback_requests
            \$start = microtime(true);
            \$callbacks = DB::table('callback_requests')
                ->where('status', 'pending')
                ->orderBy('scheduled_at')
                ->limit(10)
                ->get();
            \$duration2 = microtime(true) - \$start;

            if (\$duration1 > 1.0 || \$duration2 > 1.0) {
                echo 'WARNING: Queries slower than expected';
            } else {
                echo 'OK';
            }
        } catch (Exception \$e) {
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [[ "$result" == "OK" ]]; then
        log_success "Index performance test passed"
        return 0
    else
        log_warning "Index performance test: $result"
        return 0
    fi
}

test_effective_policy_query() {
    log_info "Testing getEffectivePolicyConfig() query simulation..."

    local result=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            \$company = DB::table('companies')->first();
            if (!\$company) throw new Exception('No company found');

            // Simulate getEffectivePolicyConfig query
            \$policy = DB::table('policy_configurations')
                ->where('company_id', \$company->id)
                ->where('is_active', true)
                ->where(function(\$query) {
                    \$query->whereNull('effective_from')
                          ->orWhere('effective_from', '<=', now());
                })
                ->where(function(\$query) {
                    \$query->whereNull('effective_until')
                          ->orWhere('effective_until', '>=', now());
                })
                ->orderBy('priority', 'desc')
                ->first();

            // Query should work even with no results
            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [[ "$result" == "OK" ]]; then
        log_success "Effective policy config query test passed"
        return 0
    else
        log_error "Effective policy config query test failed: $result"
        return 1
    fi
}

################################################################################
# Status Determination
################################################################################

determine_status() {
    local status=""
    local exit_code=0

    if [[ $TESTS_FAILED -eq 0 ]]; then
        if [[ $TESTS_WARNING -eq 0 ]]; then
            status="${GREEN}${BOLD}🟢 GREEN - All systems operational${NC}"
            exit_code=0
        else
            status="${YELLOW}${BOLD}🟡 YELLOW - Operational with warnings${NC}"
            exit_code=1
        fi
    else
        status="${RED}${BOLD}🔴 RED - Critical failures detected${NC}"
        exit_code=2
    fi

    echo ""
    echo "==================================" | tee -a "$LOG_FILE"
    echo -e "$status" | tee -a "$LOG_FILE"
    echo "==================================" | tee -a "$LOG_FILE"
    log_success "Tests passed: $TESTS_PASSED"
    log_warning "Warnings: $TESTS_WARNING"
    log_error "Tests failed: $TESTS_FAILED"
    echo "==================================" | tee -a "$LOG_FILE"
    echo "Log file: $LOG_FILE" | tee -a "$LOG_FILE"
    echo ""

    return $exit_code
}

################################################################################
# Main Execution
################################################################################

main() {
    setup_logging

    log_info "Starting smoke tests..."
    echo ""

    # Run all smoke tests
    test_policy_configuration_create
    test_policy_configuration_update
    test_policy_configuration_delete
    test_callback_request_create
    test_callback_request_status_update
    test_foreign_key_integrity
    test_effective_policy_query
    test_cache_operations
    test_index_performance

    echo ""
    determine_status
    exit $?
}

main "$@"
