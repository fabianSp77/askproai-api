#!/bin/bash
# Safe Laravel Cache Manager
# This script ensures all cache operations maintain correct ownership
# Can be safely run by any user - will always fix ownership

set -e  # Exit on error

# Configuration
PROJECT_DIR="/var/www/api-gateway"
WEB_USER="www-data"
WEB_GROUP="www-data"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Function to run command as www-data
run_as_webuser() {
    if [ "$EUID" -eq 0 ]; then
        sudo -u $WEB_USER "$@"
    else
        "$@"
    fi
}

# Function to verify ownership
verify_ownership() {
    local dir=$1
    local root_count=$(find "$dir" -type f -user root 2>/dev/null | wc -l)
    
    if [ "$root_count" -gt 0 ]; then
        log_warn "Found $root_count root-owned files in $dir"
        return 1
    else
        log_info "All files in $dir correctly owned by $WEB_USER"
        return 0
    fi
}

# Function to fix ownership
fix_ownership() {
    local dir=$1
    
    if [ "$EUID" -eq 0 ]; then
        log_info "Fixing ownership of $dir..."
        chown -R $WEB_USER:$WEB_GROUP "$dir"
        chmod -R 775 "$dir"
        log_info "Ownership fixed for $dir"
    else
        log_warn "Not running as root, cannot fix ownership"
    fi
}

# Main cache management function
manage_cache() {
    local action=$1
    
    cd "$PROJECT_DIR"
    
    case "$action" in
        clear)
            log_info "Clearing all caches..."
            run_as_webuser php artisan cache:clear
            run_as_webuser php artisan view:clear
            run_as_webuser php artisan config:clear
            run_as_webuser php artisan route:clear
            log_info "All caches cleared"
            ;;
        
        optimize)
            log_info "Optimizing caches..."
            run_as_webuser php artisan config:cache
            run_as_webuser php artisan route:cache
            run_as_webuser php artisan view:cache
            log_info "Caches optimized"
            ;;
        
        reset)
            log_info "Performing full cache reset..."
            
            # Clear physical files first
            run_as_webuser rm -rf storage/framework/views/*
            run_as_webuser rm -rf storage/framework/cache/*
            run_as_webuser rm -rf storage/framework/sessions/*
            run_as_webuser rm -rf bootstrap/cache/*
            
            # Clear via artisan
            run_as_webuser php artisan optimize:clear
            
            # Recreate .gitignore files
            run_as_webuser bash -c 'echo -e "*\n!.gitignore" > storage/framework/views/.gitignore'
            run_as_webuser bash -c 'echo -e "*\n!.gitignore" > storage/framework/cache/.gitignore'
            run_as_webuser bash -c 'echo -e "*\n!.gitignore" > storage/framework/sessions/.gitignore'
            
            log_info "Cache reset complete"
            ;;
        
        monitor)
            log_info "Monitoring cache health..."
            
            # Check disk space
            local disk_usage=$(df -h "$PROJECT_DIR" | awk 'NR==2 {print $5}' | sed 's/%//')
            if [ "$disk_usage" -gt 80 ]; then
                log_warn "Disk usage is high: ${disk_usage}%"
            else
                log_info "Disk usage: ${disk_usage}%"
            fi
            
            # Check inode usage
            local inode_usage=$(df -i "$PROJECT_DIR" | awk 'NR==2 {print $5}' | sed 's/%//')
            if [ "$inode_usage" -gt 80 ]; then
                log_warn "Inode usage is high: ${inode_usage}%"
            else
                log_info "Inode usage: ${inode_usage}%"
            fi
            
            # Check ownership
            verify_ownership "storage/framework/views" || fix_ownership "storage/framework/views"
            verify_ownership "storage/framework/cache" || fix_ownership "storage/framework/cache"
            verify_ownership "bootstrap/cache" || fix_ownership "bootstrap/cache"
            
            # Count cache files
            local view_count=$(find storage/framework/views -type f -name "*.php" 2>/dev/null | wc -l)
            log_info "View cache files: $view_count"
            ;;
        
        *)
            log_error "Unknown action: $action"
            echo "Usage: $0 {clear|optimize|reset|monitor}"
            exit 1
            ;;
    esac
    
    # Always verify and fix ownership at the end
    if [ "$EUID" -eq 0 ]; then
        fix_ownership "storage"
        fix_ownership "bootstrap/cache"
    fi
}

# Parse command line arguments
if [ $# -eq 0 ]; then
    log_error "No action specified"
    echo "Usage: $0 {clear|optimize|reset|monitor}"
    exit 1
fi

# Execute main function
manage_cache "$1"

# Final ownership check
if ! verify_ownership "storage/framework/views"; then
    log_error "Ownership issues detected after operation!"
    if [ "$EUID" -eq 0 ]; then
        log_info "Attempting automatic fix..."
        fix_ownership "storage/framework/views"
    else
        log_error "Run as root to fix ownership issues"
        exit 1
    fi
fi

log_info "✅ Cache management completed successfully"