#!/bin/bash

# Disable Memory Profiling Script

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_ROOT/.env"

echo "🛑 Disabling memory profiling"

# Function to set or update env variable
set_env() {
    local key="$1"
    local value="$2"

    if grep -q "^${key}=" "$ENV_FILE"; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
    else
        echo "${key}=${value}" >> "$ENV_FILE"
    fi
}

set_env "MEMORY_PROFILING_ENABLED" "false"

echo "✅ Memory profiling disabled"
echo "🔄 Clear config cache: php artisan config:clear"
