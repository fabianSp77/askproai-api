#!/bin/bash
################################################################################
# Migration Validation Script
# Purpose: Validate individual migration execution
# Usage: ./validate-migration.sh <table_name>
# Exit Codes: 0=success, 1=validation failed
################################################################################

set -euo pipefail

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="/var/www/api-gateway/storage/logs/deployment"
DB_NAME="askproai_db"

# Table specifications (expected structure)
declare -A TABLE_SPECS

# policy_configurations expected structure
TABLE_SPECS[policy_configurations_columns]="id,company_id,branch_id,service_id,staff_id,config_type,callback_url,auto_callback_enabled,callback_delay_minutes,callback_business_hours_only,callback_retry_attempts,callback_retry_delay_minutes,effective_from,effective_until,priority,is_active,metadata,created_at,updated_at,deleted_at"
TABLE_SPECS[policy_configurations_indexes]="policy_configurations_company_id_foreign,policy_configurations_branch_id_foreign,policy_configurations_service_id_foreign,policy_configurations_staff_id_foreign,policy_configurations_config_type_index,policy_configurations_is_active_index,policy_configurations_effective_from_index,policy_configurations_effective_until_index,policy_configurations_priority_index"

# callback_requests expected structure
TABLE_SPECS[callback_requests_columns]="id,company_id,branch_id,service_id,staff_id,customer_id,appointment_id,policy_configuration_id,callback_phone,preferred_callback_time,callback_reason,priority,status,scheduled_at,attempted_at,completed_at,failed_at,retry_count,last_retry_at,failure_reason,agent_notes,metadata,created_at,updated_at,deleted_at"
TABLE_SPECS[callback_requests_indexes]="callback_requests_company_id_foreign,callback_requests_branch_id_foreign,callback_requests_service_id_foreign,callback_requests_staff_id_foreign,callback_requests_customer_id_foreign,callback_requests_appointment_id_foreign,callback_requests_policy_configuration_id_foreign,callback_requests_status_index,callback_requests_priority_index,callback_requests_scheduled_at_index,callback_requests_callback_phone_index"

# Counters
CHECKS_PASSED=0
CHECKS_FAILED=0

################################################################################
# Logging Functions
################################################################################

setup_logging() {
    local table_name=$1
    local log_file="${LOG_DIR}/validate-${table_name}-$(date +%Y%m%d-%H%M%S).log"
    mkdir -p "$LOG_DIR"
    echo "$log_file"
}

log() {
    local log_file=$1
    shift
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$log_file"
}

log_success() {
    local log_file=$1
    shift
    echo -e "${GREEN}✅ $*${NC}" | tee -a "$log_file"
    ((CHECKS_PASSED++))
}

log_error() {
    local log_file=$1
    shift
    echo -e "${RED}❌ $*${NC}" | tee -a "$log_file"
    ((CHECKS_FAILED++))
}

log_info() {
    local log_file=$1
    shift
    echo -e "${BLUE}ℹ️  $*${NC}" | tee -a "$log_file"
}

################################################################################
# Validation Functions
################################################################################

check_table_exists() {
    local table_name=$1
    local log_file=$2

    log_info "$log_file" "Checking if table '$table_name' exists..."

    local exists=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        echo DB::getSchemaBuilder()->hasTable('$table_name') ? 'yes' : 'no';
    " 2>/dev/null)

    if [[ "$exists" == "yes" ]]; then
        log_success "$log_file" "Table '$table_name' exists"
        return 0
    else
        log_error "$log_file" "Table '$table_name' does not exist"
        return 1
    fi
}

check_columns() {
    local table_name=$1
    local log_file=$2
    local expected_columns_key="${table_name}_columns"

    if [[ -z "${TABLE_SPECS[$expected_columns_key]:-}" ]]; then
        log_info "$log_file" "No column specification for '$table_name', skipping column check"
        return 0
    fi

    log_info "$log_file" "Checking columns for table '$table_name'..."

    local expected_columns="${TABLE_SPECS[$expected_columns_key]}"
    IFS=',' read -ra EXPECTED_COLS <<< "$expected_columns"

    local actual_columns=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        \$columns = DB::getSchemaBuilder()->getColumnListing('$table_name');
        echo implode(',', \$columns);
    " 2>/dev/null)

    IFS=',' read -ra ACTUAL_COLS <<< "$actual_columns"

    local all_present=true

    for col in "${EXPECTED_COLS[@]}"; do
        if [[ " ${ACTUAL_COLS[*]} " =~ " ${col} " ]]; then
            log_success "$log_file" "Column exists: $col"
        else
            log_error "$log_file" "Column missing: $col"
            all_present=false
        fi
    done

    if $all_present; then
        log_success "$log_file" "All expected columns present (${#EXPECTED_COLS[@]} columns)"
        return 0
    else
        log_error "$log_file" "Column validation failed"
        return 1
    fi
}

check_indexes() {
    local table_name=$1
    local log_file=$2
    local expected_indexes_key="${table_name}_indexes"

    if [[ -z "${TABLE_SPECS[$expected_indexes_key]:-}" ]]; then
        log_info "$log_file" "No index specification for '$table_name', skipping index check"
        return 0
    fi

    log_info "$log_file" "Checking indexes for table '$table_name'..."

    local expected_indexes="${TABLE_SPECS[$expected_indexes_key]}"
    IFS=',' read -ra EXPECTED_IDX <<< "$expected_indexes"

    local actual_indexes=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        \$indexes = DB::select('SHOW INDEX FROM $table_name');
        \$names = array_unique(array_column(\$indexes, 'Key_name'));
        echo implode(',', \$names);
    " 2>/dev/null)

    IFS=',' read -ra ACTUAL_IDX <<< "$actual_indexes"

    local all_present=true

    for idx in "${EXPECTED_IDX[@]}"; do
        if [[ " ${ACTUAL_IDX[*]} " =~ " ${idx} " ]]; then
            log_success "$log_file" "Index exists: $idx"
        else
            log_error "$log_file" "Index missing: $idx"
            all_present=false
        fi
    done

    if $all_present; then
        log_success "$log_file" "All expected indexes present (${#EXPECTED_IDX[@]} indexes)"
        return 0
    else
        log_error "$log_file" "Index validation failed"
        return 1
    fi
}

check_foreign_keys() {
    local table_name=$1
    local log_file=$2

    log_info "$log_file" "Checking foreign keys for table '$table_name'..."

    local foreign_keys=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        \$fks = DB::select(\"
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = '$DB_NAME'
            AND TABLE_NAME = '$table_name'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        \");
        echo count(\$fks);
    " 2>/dev/null)

    if [[ $foreign_keys -gt 0 ]]; then
        log_success "$log_file" "Foreign keys created: $foreign_keys constraints"
        return 0
    else
        log_error "$log_file" "No foreign keys found (expected at least 1)"
        return 1
    fi
}

test_basic_insert() {
    local table_name=$1
    local log_file=$2

    log_info "$log_file" "Testing basic INSERT operation..."

    local test_result=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            DB::beginTransaction();

            if ('$table_name' == 'policy_configurations') {
                // Get first company
                \$company = DB::table('companies')->first();
                if (!\$company) throw new Exception('No company found for test');

                DB::table('policy_configurations')->insert([
                    'company_id' => \$company->id,
                    'config_type' => 'callback',
                    'is_active' => true,
                    'priority' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ('$table_name' == 'callback_requests') {
                // Get first company and customer
                \$company = DB::table('companies')->first();
                \$customer = DB::table('customers')->first();
                if (!\$company || !\$customer) throw new Exception('No company/customer found for test');

                DB::table('callback_requests')->insert([
                    'company_id' => \$company->id,
                    'customer_id' => \$customer->id,
                    'callback_phone' => '+1234567890',
                    'status' => 'pending',
                    'priority' => 'normal',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::rollBack();
            echo 'OK';
        } catch (Exception \$e) {
            DB::rollBack();
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [[ "$test_result" == "OK" ]]; then
        log_success "$log_file" "INSERT test passed (transaction rolled back)"
        return 0
    else
        log_error "$log_file" "INSERT test failed: $test_result"
        return 1
    fi
}

test_foreign_key_constraints() {
    local table_name=$1
    local log_file=$2

    log_info "$log_file" "Testing foreign key constraints..."

    local test_result=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            DB::beginTransaction();

            if ('$table_name' == 'policy_configurations') {
                // Try to insert with invalid company_id
                DB::table('policy_configurations')->insert([
                    'company_id' => 999999999,
                    'config_type' => 'callback',
                    'is_active' => true,
                    'priority' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::rollBack();
                echo 'FAILED: FK constraint not enforced';
                exit(1);
            } elseif ('$table_name' == 'callback_requests') {
                // Try to insert with invalid customer_id
                DB::table('callback_requests')->insert([
                    'company_id' => 1,
                    'customer_id' => 999999999,
                    'callback_phone' => '+1234567890',
                    'status' => 'pending',
                    'priority' => 'normal',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::rollBack();
                echo 'FAILED: FK constraint not enforced';
                exit(1);
            }
        } catch (Exception \$e) {
            DB::rollBack();
            if (strpos(\$e->getMessage(), 'foreign key constraint') !== false ||
                strpos(\$e->getMessage(), 'Integrity constraint') !== false) {
                echo 'OK';
            } else {
                echo 'FAILED: ' . \$e->getMessage();
                exit(1);
            }
        }
    " 2>&1)

    if [[ "$test_result" == "OK" ]]; then
        log_success "$log_file" "Foreign key constraints working correctly"
        return 0
    else
        log_error "$log_file" "Foreign key constraint test failed: $test_result"
        return 1
    fi
}

check_soft_deletes() {
    local table_name=$1
    local log_file=$2

    log_info "$log_file" "Checking soft delete support..."

    local has_deleted_at=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        echo DB::getSchemaBuilder()->hasColumn('$table_name', 'deleted_at') ? 'yes' : 'no';
    " 2>/dev/null)

    if [[ "$has_deleted_at" == "yes" ]]; then
        log_success "$log_file" "Soft delete support enabled (deleted_at column exists)"
        return 0
    else
        log_error "$log_file" "Soft delete column (deleted_at) missing"
        return 1
    fi
}

################################################################################
# Summary Report
################################################################################

print_summary() {
    local log_file=$1
    local table_name=$2

    echo "" | tee -a "$log_file"
    echo "==================================" | tee -a "$log_file"
    echo "Validation Summary: $table_name" | tee -a "$log_file"
    echo "==================================" | tee -a "$log_file"
    log_success "$log_file" "Checks passed: $CHECKS_PASSED"
    log_error "$log_file" "Checks failed: $CHECKS_FAILED"
    echo "==================================" | tee -a "$log_file"
    echo "Log file: $log_file" | tee -a "$log_file"
    echo "" | tee -a "$log_file"
}

################################################################################
# Main Execution
################################################################################

main() {
    if [[ $# -lt 1 ]]; then
        echo "Usage: $0 <table_name>"
        echo "Example: $0 policy_configurations"
        exit 1
    fi

    local table_name=$1
    local log_file=$(setup_logging "$table_name")

    log "$log_file" "==================================="
    log "$log_file" "Migration Validation: $table_name"
    log "$log_file" "==================================="
    echo ""

    # Run all validation checks
    check_table_exists "$table_name" "$log_file" || exit 1
    check_columns "$table_name" "$log_file" || exit 1
    check_indexes "$table_name" "$log_file" || exit 1
    check_foreign_keys "$table_name" "$log_file" || exit 1
    check_soft_deletes "$table_name" "$log_file" || exit 1
    test_basic_insert "$table_name" "$log_file" || exit 1
    test_foreign_key_constraints "$table_name" "$log_file" || exit 1

    echo ""
    print_summary "$log_file" "$table_name"

    if [[ $CHECKS_FAILED -eq 0 ]]; then
        log_success "$log_file" "Migration validation PASSED for table: $table_name"
        exit 0
    else
        log_error "$log_file" "Migration validation FAILED for table: $table_name"
        exit 1
    fi
}

main "$@"
