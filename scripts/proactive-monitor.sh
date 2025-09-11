#!/bin/bash
#━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# Proactive Monitoring System for Laravel Application
# Automatische Fehlererkennung ohne Browser-Tools
#━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

# Konfiguration
APP_URL="https://api.askproai.de"
ADMIN_URL="${APP_URL}/admin"
LOG_FILE="/var/www/api-gateway/storage/logs/monitoring.log"
ERROR_LOG="/var/www/api-gateway/storage/logs/laravel.log"
ALERT_FILE="/var/www/api-gateway/storage/logs/alerts.json"
WORKING_DIR="/var/www/api-gateway"

# Farben für Terminal-Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Timestamp für Logs
timestamp() {
    date '+%Y-%m-%d %H:%M:%S'
}

# Log-Funktion
log_message() {
    echo "[$(timestamp)] $1" >> "$LOG_FILE"
    echo -e "${2:-$NC}$1${NC}"
}

# JSON Alert schreiben
write_alert() {
    local severity="$1"
    local message="$2"
    local details="$3"
    
    if [ ! -f "$ALERT_FILE" ]; then
        echo "[]" > "$ALERT_FILE"
    fi
    
    jq ". += [{
        \"timestamp\": \"$(timestamp)\",
        \"severity\": \"$severity\",
        \"message\": \"$message\",
        \"details\": \"$details\",
        \"resolved\": false
    }]" "$ALERT_FILE" > "$ALERT_FILE.tmp" && mv "$ALERT_FILE.tmp" "$ALERT_FILE"
}

#━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# HEALTH CHECK FUNKTIONEN
#━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

# HTTP Status Check
check_http_status() {
    local url="$1"
    local expected="$2"
    local name="$3"
    
    log_message "🔍 Checking $name at $url..."
    
    # Mehrere Versuche mit Timeout
    local status=""
    for i in {1..3}; do
        status=$(curl -s -o /dev/null -w "%{http_code}" -L --max-time 10 "$url" 2>/dev/null)
        if [ "$status" == "$expected" ]; then
            break
        fi
        sleep 2
    done
    
    if [ "$status" == "$expected" ]; then
        log_message "✅ $name OK (HTTP $status)" "$GREEN"
        return 0
    else
        log_message "❌ $name FAILED (HTTP $status, expected $expected)" "$RED"
        write_alert "ERROR" "$name returned HTTP $status" "URL: $url, Expected: $expected"
        return 1
    fi
}

# View Cache Health Check
check_view_cache() {
    log_message "🔍 Checking view cache integrity..."
    
    cd "$WORKING_DIR" || exit 1
    
    # Prüfe ob View-Cache-Verzeichnis existiert
    if [ ! -d "storage/framework/views" ]; then
        log_message "❌ View cache directory missing!" "$RED"
        write_alert "CRITICAL" "View cache directory missing" "Path: storage/framework/views"
        return 1
    fi
    
    # Prüfe auf filemtime Fehler in den letzten 5 Minuten
    local recent_errors=$(tail -1000 "$ERROR_LOG" 2>/dev/null | grep -c "filemtime(): stat failed")
    
    if [ "$recent_errors" -gt 0 ]; then
        log_message "⚠️  Found $recent_errors view cache errors" "$YELLOW"
        write_alert "WARNING" "View cache errors detected" "Count: $recent_errors"
        return 1
    fi
    
    # Prüfe Permissions
    local perm_issues=$(find storage/framework/views -type f ! -perm -644 2>/dev/null | wc -l)
    if [ "$perm_issues" -gt 0 ]; then
        log_message "⚠️  Permission issues on $perm_issues files" "$YELLOW"
        return 1
    fi
    
    log_message "✅ View cache healthy" "$GREEN"
    return 0
}

# Laravel Error Log Check
check_error_logs() {
    log_message "🔍 Checking Laravel error logs..."
    
    if [ ! -f "$ERROR_LOG" ]; then
        log_message "ℹ️  No error log file found (might be fresh install)" "$YELLOW"
        return 0
    fi
    
    # Prüfe auf kritische Fehler in den letzten 5 Minuten
    local five_min_ago=$(date -d '5 minutes ago' '+%Y-%m-%d %H:%M')
    local critical_errors=$(tail -500 "$ERROR_LOG" | grep -E "ERROR|CRITICAL|EMERGENCY" | grep -c "$five_min_ago")
    
    if [ "$critical_errors" -gt 0 ]; then
        log_message "❌ Found $critical_errors critical errors in last 5 minutes" "$RED"
        
        # Extrahiere die letzten Fehler für Details
        local last_error=$(tail -100 "$ERROR_LOG" | grep -E "ERROR|CRITICAL" | tail -1)
        write_alert "ERROR" "Critical errors in application log" "$last_error"
        return 1
    fi
    
    log_message "✅ No recent critical errors" "$GREEN"
    return 0
}

# Disk Space Check
check_disk_space() {
    log_message "🔍 Checking disk space..."
    
    local usage=$(df -h /var/www | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$usage" -gt 90 ]; then
        log_message "❌ Disk usage critical: ${usage}%" "$RED"
        write_alert "CRITICAL" "Disk space critical" "Usage: ${usage}%"
        return 1
    elif [ "$usage" -gt 80 ]; then
        log_message "⚠️  Disk usage warning: ${usage}%" "$YELLOW"
        write_alert "WARNING" "Disk space warning" "Usage: ${usage}%"
        return 1
    fi
    
    log_message "✅ Disk usage OK: ${usage}%" "$GREEN"
    return 0
}

# PHP Process Check
check_php_processes() {
    log_message "🔍 Checking PHP processes..."
    
    local php_procs=$(ps aux | grep -c "[p]hp-fpm\|[p]hp.*artisan")
    
    if [ "$php_procs" -lt 1 ]; then
        log_message "❌ No PHP processes running!" "$RED"
        write_alert "CRITICAL" "PHP processes not running" "Count: $php_procs"
        return 1
    fi
    
    log_message "✅ PHP processes running: $php_procs" "$GREEN"
    return 0
}

# Database Connection Check
check_database() {
    log_message "🔍 Checking database connection..."
    
    cd "$WORKING_DIR" || exit 1
    
    # Versuche eine einfache Datenbankabfrage
    local db_check=$(php artisan tinker --execute="try { DB::select('SELECT 1'); echo 'OK'; } catch (Exception \$e) { echo 'FAIL'; }" 2>/dev/null)
    
    if [[ "$db_check" == *"OK"* ]]; then
        log_message "✅ Database connection OK" "$GREEN"
        return 0
    else
        log_message "❌ Database connection failed!" "$RED"
        write_alert "CRITICAL" "Database connection failed" "Unable to connect to database"
        return 1
    fi
}

#━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# SELF-HEALING FUNKTIONEN
#━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

# Auto-Fix View Cache
auto_fix_view_cache() {
    log_message "🔧 Attempting to auto-fix view cache..." "$YELLOW"
    
    cd "$WORKING_DIR" || exit 1
    
    # Führe Auto-Fix-Script aus
    if [ -f "scripts/auto-fix-cache.sh" ]; then
        bash scripts/auto-fix-cache.sh >> "$LOG_FILE" 2>&1
        
        # Warte kurz und prüfe erneut
        sleep 3
        
        if check_http_status "$ADMIN_URL" "200" "Admin Panel After Fix"; then
            log_message "✅ Auto-fix successful!" "$GREEN"
            write_alert "INFO" "Auto-fix applied successfully" "View cache repaired"
            return 0
        fi
    fi
    
    # Fallback: Aggressivere Reparatur
    log_message "🔧 Applying aggressive fix..." "$YELLOW"
    
    rm -rf storage/framework/views/*
    php artisan view:clear
    php artisan cache:clear
    php artisan config:clear
    php artisan optimize
    
    sleep 3
    
    if check_http_status "$ADMIN_URL" "200" "Admin Panel After Aggressive Fix"; then
        log_message "✅ Aggressive fix successful!" "$GREEN"
        write_alert "INFO" "Aggressive fix applied successfully" "All caches rebuilt"
        return 0
    fi
    
    log_message "❌ Auto-fix failed, manual intervention required" "$RED"
    write_alert "CRITICAL" "Auto-fix failed" "Manual intervention required"
    return 1
}

# Restart Services
restart_services() {
    log_message "🔧 Restarting services..." "$YELLOW"
    
    # PHP OPCache reset
    php -r "opcache_reset();" 2>/dev/null
    
    # Versuche PHP-FPM Neustart (verschiedene Methoden)
    systemctl restart php8.3-fpm 2>/dev/null || \
    systemctl restart php-fpm 2>/dev/null || \
    service php8.3-fpm restart 2>/dev/null || \
    service php-fpm restart 2>/dev/null || \
    log_message "⚠️  Could not restart PHP-FPM (may not be needed)" "$YELLOW"
    
    log_message "✅ Services restarted" "$GREEN"
}

#━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# MAIN MONITORING LOOP
#━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

run_health_checks() {
    local issues=0
    
    log_message "═══════════════════════════════════════════════════════"
    log_message "🚀 Starting Health Check at $(timestamp)"
    log_message "═══════════════════════════════════════════════════════"
    
    # Führe alle Checks durch
    check_disk_space || ((issues++))
    check_php_processes || ((issues++))
    check_database || ((issues++))
    check_view_cache || ((issues++))
    check_error_logs || ((issues++))
    check_http_status "$ADMIN_URL" "200" "Admin Panel" || ((issues++))
    check_http_status "$APP_URL/up" "200" "Health Endpoint" || ((issues++))
    
    # Bewertung
    if [ "$issues" -eq 0 ]; then
        log_message "═══════════════════════════════════════════════════════"
        log_message "✅ ALL SYSTEMS OPERATIONAL" "$GREEN"
        log_message "═══════════════════════════════════════════════════════"
        return 0
    elif [ "$issues" -le 2 ]; then
        log_message "═══════════════════════════════════════════════════════"
        log_message "⚠️  MINOR ISSUES DETECTED ($issues)" "$YELLOW"
        log_message "═══════════════════════════════════════════════════════"
        
        # Versuche Auto-Fix
        auto_fix_view_cache
        return 1
    else
        log_message "═══════════════════════════════════════════════════════"
        log_message "❌ CRITICAL ISSUES DETECTED ($issues)" "$RED"
        log_message "═══════════════════════════════════════════════════════"
        
        # Aggressive Reparatur
        auto_fix_view_cache
        restart_services
        return 2
    fi
}

# Parse Argumente
case "${1:-}" in
    --once)
        # Einmaliger Check
        run_health_checks
        exit $?
        ;;
    --continuous)
        # Kontinuierliches Monitoring
        log_message "🔄 Starting continuous monitoring (Ctrl+C to stop)..."
        while true; do
            run_health_checks
            sleep "${2:-60}" # Standard: alle 60 Sekunden
        done
        ;;
    --fix)
        # Nur Reparatur ausführen
        auto_fix_view_cache
        exit $?
        ;;
    *)
        echo "Usage: $0 [--once|--continuous [interval]|--fix]"
        echo "  --once       Run health check once"
        echo "  --continuous Run continuously (default: 60s interval)"
        echo "  --fix        Run auto-fix immediately"
        exit 1
        ;;
esac