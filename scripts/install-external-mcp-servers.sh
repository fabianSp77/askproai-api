#!/bin/bash

# Install External MCP Servers for AskProAI
# This script installs the requested MCP servers for enhanced Claude capabilities

echo "======================================"
echo "External MCP Server Installation"
echo "======================================"

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js 18+ first."
    echo "   Visit: https://nodejs.org/"
    exit 1
fi

# Check Node.js version
NODE_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 18 ]; then
    echo "❌ Node.js version is too old. Please install Node.js 18+ (current: $(node -v))"
    exit 1
fi

echo "✓ Node.js $(node -v) detected"
echo ""

# Function to install MCP server
install_mcp_server() {
    local package_name=$1
    local display_name=$2
    
    echo "Installing $display_name..."
    
    # Check if package exists
    if npm search "$package_name" --json 2>/dev/null | grep -q "\"name\":\"$package_name\""; then
        npm install -g "$package_name"
        if [ $? -eq 0 ]; then
            echo "✓ $display_name installed successfully"
        else
            echo "❌ Failed to install $display_name"
        fi
    else {
        echo "⚠️  $display_name package not found in npm registry"
        echo "   Package name: $package_name"
    }
    fi
    echo ""
}

# Install sequential-thinking
echo "1. Sequential-Thinking MCP Server"
echo "   Purpose: Step-by-step reasoning and problem-solving"
install_mcp_server "@modelcontextprotocol/server-sequential-thinking" "Sequential-Thinking"

# Install postgres (note: for MySQL/MariaDB compatibility)
echo "2. PostgreSQL MCP Server (will be used with MySQL/MariaDB)"
echo "   Purpose: Direct database access and management"
install_mcp_server "@modelcontextprotocol/server-postgres" "PostgreSQL MCP"

# Try to find effect-docs
echo "3. Effect-Docs MCP Server"
echo "   Purpose: Documentation generation and effect tracking"
install_mcp_server "@modelcontextprotocol/server-effect-docs" "Effect-Docs"

# Try to find taskmaster-ai
echo "4. Taskmaster-AI MCP Server"
echo "   Purpose: Advanced task management and automation"
install_mcp_server "@modelcontextprotocol/server-taskmaster-ai" "Taskmaster-AI"

echo ""
echo "======================================"
echo "Installation Summary"
echo "======================================"

# Check installed packages
echo "Checking installed MCP servers..."
echo ""

if command -v sequential-thinking &> /dev/null || npm list -g @modelcontextprotocol/server-sequential-thinking &> /dev/null; then
    echo "✓ Sequential-Thinking: Installed"
else
    echo "✗ Sequential-Thinking: Not installed"
fi

if command -v postgres-mcp &> /dev/null || npm list -g @modelcontextprotocol/server-postgres &> /dev/null; then
    echo "✓ PostgreSQL MCP: Installed"
else
    echo "✗ PostgreSQL MCP: Not installed"
fi

echo ""
echo "======================================"
echo "Next Steps"
echo "======================================"
echo ""
echo "1. Check server status:"
echo "   php artisan mcp:external status"
echo ""
echo "2. Start all enabled servers:"
echo "   php artisan mcp:external start"
echo ""
echo "3. Configure Claude Desktop:"
echo "   Add the configuration from EXTERNAL_MCP_INTEGRATION_GUIDE.md"
echo "   to your Claude Desktop config.json file"
echo ""
echo "4. For MySQL/MariaDB support:"
echo "   Consider installing a MySQL-specific MCP server"
echo "   as the PostgreSQL MCP expects PostgreSQL syntax"
echo ""

# Make script executable
chmod +x "$0"