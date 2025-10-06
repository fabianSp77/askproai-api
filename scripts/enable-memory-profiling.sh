#!/bin/bash

# Enable Memory Profiling Script
# Usage: ./scripts/enable-memory-profiling.sh [mode]
# Modes: light (default), aggressive, targeted

set -e

MODE="${1:-light}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_ROOT/.env"

echo "🔍 Enabling memory profiling (mode: $MODE)"

# Backup .env
cp "$ENV_FILE" "$ENV_FILE.backup.$(date +%s)"

# Function to set or update env variable
set_env() {
    local key="$1"
    local value="$2"

    if grep -q "^${key}=" "$ENV_FILE"; then
        # Update existing
        sed -i "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
    else
        # Add new
        echo "${key}=${value}" >> "$ENV_FILE"
    fi
}

case "$MODE" in
    light)
        echo "📊 Light profiling: 1% sampling, checkpoints only"
        set_env "MEMORY_PROFILING_ENABLED" "true"
        set_env "MEMORY_PROFILING_SAMPLE_RATE" "0.01"
        set_env "MEMORY_PROFILE_CHECKPOINTS" "true"
        set_env "MEMORY_PROFILE_MODELS" "false"
        set_env "MEMORY_PROFILE_QUERIES" "false"
        set_env "MEMORY_PROFILE_SCOPES" "false"
        ;;

    aggressive)
        echo "🔥 Aggressive profiling: 100% sampling, all features"
        set_env "MEMORY_PROFILING_ENABLED" "true"
        set_env "MEMORY_PROFILING_SAMPLE_RATE" "1.0"
        set_env "MEMORY_PROFILE_CHECKPOINTS" "true"
        set_env "MEMORY_PROFILE_MODELS" "true"
        set_env "MEMORY_PROFILE_QUERIES" "true"
        set_env "MEMORY_PROFILE_SCOPES" "true"
        set_env "MEMORY_PROFILE_FILAMENT" "true"
        set_env "MEMORY_USE_TICK_MONITORING" "true"
        ;;

    targeted)
        echo "🎯 Targeted profiling: Header-based, full features"
        set_env "MEMORY_PROFILING_ENABLED" "true"
        set_env "MEMORY_PROFILING_SAMPLE_RATE" "0"
        set_env "MEMORY_PROFILE_CHECKPOINTS" "true"
        set_env "MEMORY_PROFILE_MODELS" "true"
        set_env "MEMORY_PROFILE_QUERIES" "true"
        set_env "MEMORY_PROFILE_SCOPES" "true"
        echo ""
        echo "💡 Use header: X-Force-Memory-Profile: true"
        ;;

    *)
        echo "❌ Unknown mode: $MODE"
        echo "Valid modes: light, aggressive, targeted"
        exit 1
        ;;
esac

# Common settings
set_env "MEMORY_WARNING_THRESHOLD" "1536"
set_env "MEMORY_CRITICAL_THRESHOLD" "1792"
set_env "MEMORY_DUMP_THRESHOLD" "1900"
set_env "MEMORY_SAVE_DUMPS" "true"

echo ""
echo "✅ Memory profiling enabled"
echo ""
echo "📝 Next steps:"
echo "  1. Register middleware in bootstrap/app.php or Kernel.php"
echo "  2. Register service providers in config/app.php"
echo "  3. Clear config cache: php artisan config:clear"
echo "  4. Monitor logs: tail -f storage/logs/laravel.log"
echo "  5. Check dumps: ls -lh storage/logs/memory-dumps/"
echo ""
echo "🛑 To disable: ./scripts/disable-memory-profiling.sh"
echo "💾 .env backup: ${ENV_FILE}.backup.*"
