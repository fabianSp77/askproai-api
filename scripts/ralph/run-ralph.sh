#!/bin/bash
# Ralph Runner - Executes Ralph as www-data user (not root)
# Usage: ./run-ralph.sh [max_iterations]
#
# This wrapper is needed because Claude Code doesn't allow
# --dangerously-skip-permissions when running as root.

MAX_ITERATIONS=${1:-25}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="/var/www/api-gateway"

echo "╔════════════════════════════════════════════════════════════╗"
echo "║         Ralph Runner - Starting as www-data                ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Ensure www-data owns the ralph directory
chown -R www-data:www-data "$SCRIPT_DIR"

# Run Ralph as www-data with proper environment
cd "$PROJECT_DIR"
su - www-data -s /bin/bash -c "
  cd $PROJECT_DIR

  # Export necessary environment
  export HOME=/var/www
  export PATH=/usr/local/bin:/usr/bin:/bin

  # Run Ralph
  $SCRIPT_DIR/ralph.sh $MAX_ITERATIONS
"
