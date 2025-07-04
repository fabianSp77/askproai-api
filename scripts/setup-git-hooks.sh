#!/bin/bash
#
# Setup script for Git hooks
# Configures Git to use our custom hooks directory
#

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Setting up Git hooks for AskProAI...${NC}"
echo ""

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo -e "${RED}Error: Not in a git repository!${NC}"
    exit 1
fi

# Check if .githooks directory exists
if [ ! -d ".githooks" ]; then
    echo -e "${RED}Error: .githooks directory not found!${NC}"
    exit 1
fi

# Configure git to use our hooks directory
echo -n "Configuring Git to use .githooks directory... "
git config core.hooksPath .githooks

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    exit 1
fi

# Make all hooks executable
echo -n "Making hooks executable... "
chmod +x .githooks/*
echo -e "${GREEN}✓${NC}"

# List available hooks
echo ""
echo -e "${BLUE}Available hooks:${NC}"
for hook in .githooks/*; do
    if [ -f "$hook" ]; then
        hook_name=$(basename "$hook")
        echo "  • $hook_name"
    fi
done

# Check for required dependencies
echo ""
echo -e "${BLUE}Checking dependencies:${NC}"

# PHP
echo -n "  • PHP... "
if command -v php >/dev/null 2>&1; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2)
    echo -e "${GREEN}✓${NC} (v$PHP_VERSION)"
else
    echo -e "${RED}✗ Not found${NC}"
fi

# Composer
echo -n "  • Composer... "
if [ -f "composer.json" ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗ composer.json not found${NC}"
fi

# Laravel Pint
echo -n "  • Laravel Pint... "
if [ -f "vendor/bin/pint" ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠️  Not installed - run 'composer install'${NC}"
fi

# PHPStan
echo -n "  • PHPStan... "
if [ -f "vendor/bin/phpstan" ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠️  Not installed - run 'composer install'${NC}"
fi

# Provide instructions
echo ""
echo -e "${GREEN}✅ Git hooks setup complete!${NC}"
echo ""
echo -e "${BLUE}What the hooks do:${NC}"
echo "  • pre-commit    - Runs code quality checks before commits"
echo "  • commit-msg    - Enforces conventional commit format"
echo "  • post-commit   - Checks if documentation needs updating"
echo "  • pre-push      - Runs tests and final checks before push"
echo ""
echo -e "${BLUE}Usage:${NC}"
echo "  Just use git normally - hooks will run automatically!"
echo ""
echo -e "${BLUE}To bypass hooks (use sparingly):${NC}"
echo "  git commit --no-verify"
echo "  git push --no-verify"
echo ""
echo -e "${YELLOW}⚠️  Note:${NC} First time setup may require running 'composer install'"
echo "to install all development dependencies."
echo ""

# Create a .gitignore entry if needed
if ! grep -q "^.githooks" .gitignore 2>/dev/null; then
    echo -e "${BLUE}Note:${NC} .githooks directory is tracked in git (this is intentional)"
fi

echo -e "${GREEN}Happy coding! 🚀${NC}"