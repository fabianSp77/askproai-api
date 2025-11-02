#!/bin/bash
# Docs Credentials Management Script
# Manages authentication credentials for the Documentation Hub

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_ROOT/.env"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "========================================="
echo -e "${BLUE}📚 Docs Credentials Manager${NC}"
echo "========================================="
echo ""

# Check if .env exists
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}❌ .env file not found at: $ENV_FILE${NC}"
    exit 1
fi

# Function to get current credentials
get_current_credentials() {
    USERNAME=$(grep "^DOCS_USERNAME=" "$ENV_FILE" | cut -d '=' -f2)
    PASSWORD=$(grep "^DOCS_PASSWORD=" "$ENV_FILE" | cut -d '=' -f2)

    if [ -z "$USERNAME" ]; then
        USERNAME="(not set)"
    fi

    if [ -z "$PASSWORD" ]; then
        PASSWORD="(not set)"
    fi
}

# Function to show current credentials
show_credentials() {
    get_current_credentials

    echo -e "${BLUE}Current Credentials:${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo -e "Username: ${GREEN}$USERNAME${NC}"

    if [ "$PASSWORD" = "(not set)" ] || [ "$PASSWORD" = "changeme_secure_password_here" ]; then
        echo -e "Password: ${RED}$PASSWORD${NC}"
        echo -e "${YELLOW}⚠️  Warning: Password not configured or using default!${NC}"
    else
        echo -e "Password: ${GREEN}********** (${#PASSWORD} characters)${NC}"
    fi
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
}

# Function to update username
update_username() {
    echo -e "${BLUE}Update Username${NC}"
    echo -n "Enter new username: "
    read -r NEW_USERNAME

    if [ -z "$NEW_USERNAME" ]; then
        echo -e "${RED}❌ Username cannot be empty${NC}"
        return 1
    fi

    # Update .env file
    if grep -q "^DOCS_USERNAME=" "$ENV_FILE"; then
        sed -i "s/^DOCS_USERNAME=.*/DOCS_USERNAME=$NEW_USERNAME/" "$ENV_FILE"
    else
        echo "DOCS_USERNAME=$NEW_USERNAME" >> "$ENV_FILE"
    fi

    echo -e "${GREEN}✅ Username updated to: $NEW_USERNAME${NC}"
}

# Function to update password
update_password() {
    echo -e "${BLUE}Update Password${NC}"
    echo -n "Enter new password: "
    read -rs NEW_PASSWORD
    echo ""
    echo -n "Confirm password: "
    read -rs CONFIRM_PASSWORD
    echo ""

    if [ -z "$NEW_PASSWORD" ]; then
        echo -e "${RED}❌ Password cannot be empty${NC}"
        return 1
    fi

    if [ "$NEW_PASSWORD" != "$CONFIRM_PASSWORD" ]; then
        echo -e "${RED}❌ Passwords do not match${NC}"
        return 1
    fi

    # Check password strength (minimum 8 characters)
    if [ ${#NEW_PASSWORD} -lt 8 ]; then
        echo -e "${YELLOW}⚠️  Warning: Password should be at least 8 characters${NC}"
        echo -n "Continue anyway? (y/N): "
        read -r CONFIRM
        if [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
            echo "Cancelled."
            return 1
        fi
    fi

    # Update .env file
    if grep -q "^DOCS_PASSWORD=" "$ENV_FILE"; then
        # Escape special characters for sed
        ESCAPED_PASSWORD=$(printf '%s\n' "$NEW_PASSWORD" | sed 's/[&/\]/\\&/g')
        sed -i "s/^DOCS_PASSWORD=.*/DOCS_PASSWORD=$ESCAPED_PASSWORD/" "$ENV_FILE"
    else
        echo "DOCS_PASSWORD=$NEW_PASSWORD" >> "$ENV_FILE"
    fi

    echo -e "${GREEN}✅ Password updated successfully${NC}"
}

# Function to generate random password
generate_password() {
    echo -e "${BLUE}Generate Random Password${NC}"

    # Generate secure random password (20 characters)
    NEW_PASSWORD=$(openssl rand -base64 20 | tr -d "=+/" | cut -c1-20)

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo -e "${GREEN}Generated Password:${NC}"
    echo "$NEW_PASSWORD"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo -n "Save this password to .env? (y/N): "
    read -r CONFIRM

    if [ "$CONFIRM" = "y" ] || [ "$CONFIRM" = "Y" ]; then
        # Update .env file
        if grep -q "^DOCS_PASSWORD=" "$ENV_FILE"; then
            ESCAPED_PASSWORD=$(printf '%s\n' "$NEW_PASSWORD" | sed 's/[&/\]/\\&/g')
            sed -i "s/^DOCS_PASSWORD=.*/DOCS_PASSWORD=$ESCAPED_PASSWORD/" "$ENV_FILE"
        else
            echo "DOCS_PASSWORD=$NEW_PASSWORD" >> "$ENV_FILE"
        fi

        echo -e "${GREEN}✅ Password saved to .env${NC}"
        echo -e "${YELLOW}⚠️  Important: Save this password securely!${NC}"
    else
        echo "Password not saved."
    fi
}

# Function to clear Laravel config cache
clear_cache() {
    echo -e "${BLUE}Clearing Laravel config cache...${NC}"
    cd "$PROJECT_ROOT"

    if [ -f "artisan" ]; then
        php artisan config:clear > /dev/null 2>&1
        echo -e "${GREEN}✅ Config cache cleared${NC}"
    else
        echo -e "${YELLOW}⚠️  artisan not found, skipping cache clear${NC}"
    fi
}

# Main menu
show_menu() {
    echo ""
    echo -e "${BLUE}What would you like to do?${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "1) Show current credentials"
    echo "2) Update username"
    echo "3) Update password"
    echo "4) Generate random password"
    echo "5) Clear Laravel cache"
    echo "6) Exit"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo -n "Enter choice [1-6]: "
}

# Main loop
while true; do
    show_menu
    read -r choice

    case $choice in
        1)
            echo ""
            show_credentials
            ;;
        2)
            echo ""
            update_username
            clear_cache
            ;;
        3)
            echo ""
            update_password
            clear_cache
            ;;
        4)
            echo ""
            generate_password
            clear_cache
            ;;
        5)
            echo ""
            clear_cache
            ;;
        6)
            echo ""
            echo -e "${GREEN}Goodbye!${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}Invalid choice. Please enter 1-6.${NC}"
            ;;
    esac
done
