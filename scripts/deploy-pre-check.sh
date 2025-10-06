#!/bin/bash
################################################################################
# Pre-Deployment Validation Script
# Purpose: Comprehensive environment checks before production migration
# Exit Codes: 0=success, 1=critical failure, 2=warning
################################################################################

set -euo pipefail

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="/var/www/api-gateway/storage/logs/deployment"
LOG_FILE="${LOG_DIR}/pre-check-$(date +%Y%m%d-%H%M%S).log"
DB_NAME="askproai_db"
BACKUP_DIR="/var/www/api-gateway/storage/backups"
MIN_DISK_SPACE_GB=10
MIN_MYSQL_VERSION="8.0"

# Required parent tables
REQUIRED_TABLES=(
    "companies"
    "branches"
    "services"
    "staff"
    "appointments"
    "customers"
)

# Tables that will be created (check they don't exist)
NEW_TABLES=(
    "policy_configurations"
    "callback_requests"
)

# Counters
CHECKS_PASSED=0
CHECKS_FAILED=0
CHECKS_WARNING=0

################################################################################
# Logging Functions
################################################################################

setup_logging() {
    mkdir -p "$LOG_DIR"
    touch "$LOG_FILE"
    echo "==================================" | tee -a "$LOG_FILE"
    echo "Pre-Deployment Check - $(date)" | tee -a "$LOG_FILE"
    echo "==================================" | tee -a "$LOG_FILE"
}

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}✅ $*${NC}" | tee -a "$LOG_FILE"
    ((CHECKS_PASSED++))
}

log_error() {
    echo -e "${RED}❌ $*${NC}" | tee -a "$LOG_FILE"
    ((CHECKS_FAILED++))
}

log_warning() {
    echo -e "${YELLOW}⚠️  $*${NC}" | tee -a "$LOG_FILE"
    ((CHECKS_WARNING++))
}

log_info() {
    echo -e "${BLUE}ℹ️  $*${NC}" | tee -a "$LOG_FILE"
}

################################################################################
# Check Functions
################################################################################

check_database_connectivity() {
    log_info "Checking database connectivity..."

    if php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        try {
            DB::connection()->getPdo();
            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " >/dev/null 2>&1; then
        log_success "Database connection successful"
        return 0
    else
        log_error "Database connection failed"
        return 1
    fi
}

check_mysql_version() {
    log_info "Checking MySQL version..."

    local version=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        echo DB::select('SELECT VERSION() as version')[0]->version;
    " 2>/dev/null)

    if [[ -n "$version" ]]; then
        log_success "MySQL version: $version"

        # Extract major.minor version
        local major_minor=$(echo "$version" | grep -oP '^\d+\.\d+')

        if [[ $(echo "$major_minor >= $MIN_MYSQL_VERSION" | bc -l) -eq 1 ]]; then
            log_success "Version meets minimum requirement ($MIN_MYSQL_VERSION)"
            return 0
        else
            log_error "Version $major_minor is below minimum $MIN_MYSQL_VERSION"
            return 1
        fi
    else
        log_error "Could not determine MySQL version"
        return 1
    fi
}

check_disk_space() {
    log_info "Checking disk space..."

    local available_gb=$(df -BG /var/www/api-gateway | awk 'NR==2 {print $4}' | sed 's/G//')

    log_info "Available disk space: ${available_gb}GB"

    if [[ $available_gb -ge $MIN_DISK_SPACE_GB ]]; then
        log_success "Sufficient disk space available (${available_gb}GB >= ${MIN_DISK_SPACE_GB}GB)"
        return 0
    else
        log_error "Insufficient disk space (${available_gb}GB < ${MIN_DISK_SPACE_GB}GB)"
        return 1
    fi
}

check_required_tables() {
    log_info "Checking required parent tables exist..."

    local all_exist=true

    for table in "${REQUIRED_TABLES[@]}"; do
        local exists=$(php -r "
            require '/var/www/api-gateway/vendor/autoload.php';
            \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
            \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
            echo DB::getSchemaBuilder()->hasTable('$table') ? 'yes' : 'no';
        " 2>/dev/null)

        if [[ "$exists" == "yes" ]]; then
            log_success "Table exists: $table"
        else
            log_error "Required table missing: $table"
            all_exist=false
        fi
    done

    if $all_exist; then
        return 0
    else
        return 1
    fi
}

check_table_conflicts() {
    log_info "Checking for table name conflicts..."

    local conflicts=false

    for table in "${NEW_TABLES[@]}"; do
        local exists=$(php -r "
            require '/var/www/api-gateway/vendor/autoload.php';
            \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
            \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
            echo DB::getSchemaBuilder()->hasTable('$table') ? 'yes' : 'no';
        " 2>/dev/null)

        if [[ "$exists" == "yes" ]]; then
            log_error "Table already exists (conflict): $table"
            conflicts=true
        else
            log_success "No conflict for table: $table"
        fi
    done

    if ! $conflicts; then
        return 0
    else
        return 1
    fi
}

check_backup_directory() {
    log_info "Checking backup directory..."

    if [[ ! -d "$BACKUP_DIR" ]]; then
        log_info "Creating backup directory: $BACKUP_DIR"
        mkdir -p "$BACKUP_DIR"
    fi

    if [[ -w "$BACKUP_DIR" ]]; then
        log_success "Backup directory writable: $BACKUP_DIR"
        return 0
    else
        log_error "Backup directory not writable: $BACKUP_DIR"
        return 1
    fi
}

check_php_extensions() {
    log_info "Checking required PHP extensions..."

    local required_extensions=("pdo_mysql" "redis" "mbstring" "json")
    local all_loaded=true

    for ext in "${required_extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            log_success "PHP extension loaded: $ext"
        else
            log_error "PHP extension missing: $ext"
            all_loaded=false
        fi
    done

    if $all_loaded; then
        return 0
    else
        return 1
    fi
}

check_artisan_access() {
    log_info "Checking artisan command access..."

    if cd /var/www/api-gateway && php artisan --version >/dev/null 2>&1; then
        log_success "Artisan command accessible"
        return 0
    else
        log_error "Cannot execute artisan commands"
        return 1
    fi
}

check_migration_files() {
    log_info "Checking migration files exist..."

    local migration_dir="/var/www/api-gateway/database/migrations"
    local required_migrations=(
        "create_policy_configurations_table"
        "create_callback_requests_table"
    )

    local all_found=true

    for migration in "${required_migrations[@]}"; do
        if find "$migration_dir" -name "*${migration}.php" | grep -q .; then
            log_success "Migration file found: $migration"
        else
            log_error "Migration file missing: $migration"
            all_found=false
        fi
    done

    if $all_found; then
        return 0
    else
        return 1
    fi
}

check_database_locks() {
    log_info "Checking for database locks..."

    local locks=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        \$locks = DB::select('SELECT COUNT(*) as count FROM information_schema.INNODB_LOCKS');
        echo \$locks[0]->count ?? 0;
    " 2>/dev/null)

    if [[ "$locks" == "0" ]]; then
        log_success "No database locks detected"
        return 0
    else
        log_warning "Database locks detected: $locks (may impact migration)"
        return 0
    fi
}

check_redis_connection() {
    log_info "Checking Redis connection..."

    if php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        try {
            Redis::connection()->ping();
            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAILED';
            exit(1);
        }
    " >/dev/null 2>&1; then
        log_success "Redis connection successful"
        return 0
    else
        log_warning "Redis connection failed (cache may not work)"
        return 0
    fi
}

################################################################################
# Summary Report
################################################################################

print_summary() {
    echo "" | tee -a "$LOG_FILE"
    echo "==================================" | tee -a "$LOG_FILE"
    echo "Pre-Deployment Check Summary" | tee -a "$LOG_FILE"
    echo "==================================" | tee -a "$LOG_FILE"
    log_success "Checks passed: $CHECKS_PASSED"
    log_warning "Warnings: $CHECKS_WARNING"
    log_error "Checks failed: $CHECKS_FAILED"
    echo "==================================" | tee -a "$LOG_FILE"
    echo "Log file: $LOG_FILE" | tee -a "$LOG_FILE"
    echo "" | tee -a "$LOG_FILE"
}

################################################################################
# Main Execution
################################################################################

main() {
    setup_logging

    log_info "Starting pre-deployment validation..."
    echo ""

    # Critical checks
    check_database_connectivity || exit 1
    check_mysql_version || exit 1
    check_disk_space || exit 1
    check_required_tables || exit 1
    check_table_conflicts || exit 1
    check_backup_directory || exit 1
    check_php_extensions || exit 1
    check_artisan_access || exit 1
    check_migration_files || exit 1

    # Warning checks (don't fail deployment)
    check_database_locks
    check_redis_connection

    echo ""
    print_summary

    if [[ $CHECKS_FAILED -eq 0 ]]; then
        log_success "All critical checks passed - safe to deploy"
        exit 0
    else
        log_error "Pre-deployment checks failed - DO NOT DEPLOY"
        exit 1
    fi
}

main "$@"
