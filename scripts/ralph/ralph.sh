#!/bin/bash
# Ralph Wiggum - Autonomous AI Agent Loop for AskPro Gateway
# Usage: ./ralph.sh [max_iterations]
#
# This script runs Claude Code in a loop until all PRD items are complete.
# Each iteration is a fresh context window. Memory persists via:
# - Git commits
# - progress.txt (learnings)
# - prd.json (task status)

set -e

MAX_ITERATIONS=${1:-10}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PRD_FILE="$SCRIPT_DIR/prd.json"
PROGRESS_FILE="$SCRIPT_DIR/progress.txt"
ARCHIVE_DIR="$SCRIPT_DIR/archive"
LAST_BRANCH_FILE="$SCRIPT_DIR/.last-branch"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║           Ralph Wiggum - AskPro Gateway                    ║${NC}"
echo -e "${BLUE}║           Autonomous AI Agent Loop                         ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check prerequisites
if [ ! -f "$PRD_FILE" ]; then
    echo -e "${RED}Error: prd.json not found at $PRD_FILE${NC}"
    echo "Create a PRD first using: Load the prd-laravel skill"
    exit 1
fi

if ! command -v jq &> /dev/null; then
    echo -e "${RED}Error: jq is required but not installed${NC}"
    echo "Install with: apt-get install jq"
    exit 1
fi

if ! command -v claude &> /dev/null; then
    echo -e "${RED}Error: claude CLI is required but not installed${NC}"
    echo "Install Claude Code first"
    exit 1
fi

# Archive previous run if branch changed
if [ -f "$PRD_FILE" ] && [ -f "$LAST_BRANCH_FILE" ]; then
    CURRENT_BRANCH=$(jq -r '.branchName // empty' "$PRD_FILE" 2>/dev/null || echo "")
    LAST_BRANCH=$(cat "$LAST_BRANCH_FILE" 2>/dev/null || echo "")

    if [ -n "$CURRENT_BRANCH" ] && [ -n "$LAST_BRANCH" ] && [ "$CURRENT_BRANCH" != "$LAST_BRANCH" ]; then
        DATE=$(date +%Y-%m-%d)
        FOLDER_NAME=$(echo "$LAST_BRANCH" | sed 's|^ralph/||')
        ARCHIVE_FOLDER="$ARCHIVE_DIR/$DATE-$FOLDER_NAME"

        echo -e "${YELLOW}Archiving previous run: $LAST_BRANCH${NC}"
        mkdir -p "$ARCHIVE_FOLDER"
        [ -f "$PRD_FILE" ] && cp "$PRD_FILE" "$ARCHIVE_FOLDER/"
        [ -f "$PROGRESS_FILE" ] && cp "$PROGRESS_FILE" "$ARCHIVE_FOLDER/"
        echo -e "${GREEN}Archived to: $ARCHIVE_FOLDER${NC}"

        # Reset progress file for new run
        echo "# Ralph Progress Log - AskPro Gateway" > "$PROGRESS_FILE"
        echo "Started: $(date)" >> "$PROGRESS_FILE"
        echo "---" >> "$PROGRESS_FILE"
    fi
fi

# Track current branch
if [ -f "$PRD_FILE" ]; then
    CURRENT_BRANCH=$(jq -r '.branchName // empty' "$PRD_FILE" 2>/dev/null || echo "")
    if [ -n "$CURRENT_BRANCH" ]; then
        echo "$CURRENT_BRANCH" > "$LAST_BRANCH_FILE"
    fi
fi

# Initialize progress file if it doesn't exist
if [ ! -f "$PROGRESS_FILE" ]; then
    echo "# Ralph Progress Log - AskPro Gateway" > "$PROGRESS_FILE"
    echo "Started: $(date)" >> "$PROGRESS_FILE"
    echo "---" >> "$PROGRESS_FILE"
fi

# Show current status
echo -e "${BLUE}PRD File:${NC} $PRD_FILE"
echo -e "${BLUE}Branch:${NC} $(jq -r '.branchName' "$PRD_FILE")"
echo -e "${BLUE}Description:${NC} $(jq -r '.description' "$PRD_FILE")"
echo ""

# Count stories
TOTAL_STORIES=$(jq '.userStories | length' "$PRD_FILE")
DONE_STORIES=$(jq '[.userStories[] | select(.passes == true)] | length' "$PRD_FILE")
echo -e "${BLUE}Stories:${NC} $DONE_STORIES / $TOTAL_STORIES complete"
echo ""

echo -e "${GREEN}Starting Ralph - Max iterations: $MAX_ITERATIONS${NC}"
echo ""

for i in $(seq 1 $MAX_ITERATIONS); do
    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  Ralph Iteration $i of $MAX_ITERATIONS${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
    echo ""

    # Show remaining stories
    REMAINING=$(jq '[.userStories[] | select(.passes == false)] | length' "$PRD_FILE")
    echo -e "${YELLOW}Remaining stories: $REMAINING${NC}"

    # Check if all stories are done BEFORE running Claude
    if [ "$REMAINING" -eq 0 ]; then
        echo ""
        echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║           All stories completed!                           ║${NC}"
        echo -e "${GREEN}║           Finished at iteration $i of $MAX_ITERATIONS                 ║${NC}"
        echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
        echo ""
        echo -e "${BLUE}Final Status:${NC}"
        jq '.userStories[] | {id, title, passes}' "$PRD_FILE"
        exit 0
    fi

    # Run Claude Code with the ralph prompt
    TEMP_OUTPUT="$SCRIPT_DIR/.iteration-output.txt"

    # Read the prompt
    PROMPT_CONTENT=$(cat "$SCRIPT_DIR/prompt.md")

    # Change to project directory
    cd /var/www/api-gateway

    # Use claude with -p flag and --dangerously-skip-permissions
    # This works when NOT running as root (use run-ralph.sh wrapper)
    /usr/local/bin/claude -p "$PROMPT_CONTENT" --dangerously-skip-permissions > "$TEMP_OUTPUT" 2>&1 || true
    OUTPUT=$(cat "$TEMP_OUTPUT")

    # Show last 50 lines of output
    echo -e "${BLUE}--- Claude Output (last 50 lines) ---${NC}"
    tail -50 "$TEMP_OUTPUT"
    echo -e "${BLUE}--- End Output ---${NC}"

    # Check for completion signal
    if echo "$OUTPUT" | grep -q "ALL_STORIES_DONE"; then
        echo ""
        echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║           Ralph completed all tasks!                       ║${NC}"
        echo -e "${GREEN}║           Completed at iteration $i of $MAX_ITERATIONS              ║${NC}"
        echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"

        # Final summary
        echo ""
        echo -e "${BLUE}Final Status:${NC}"
        jq '.userStories[] | {id, title, passes}' "$PRD_FILE"

        exit 0
    fi

    echo -e "${YELLOW}Iteration $i complete. Continuing...${NC}"
    sleep 2
done

echo ""
echo -e "${RED}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${RED}║  Ralph reached max iterations ($MAX_ITERATIONS) without completing   ║${NC}"
echo -e "${RED}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "Check $PROGRESS_FILE for status."
echo ""
echo -e "${BLUE}Current story status:${NC}"
jq '.userStories[] | {id, title, passes}' "$PRD_FILE"

exit 1
