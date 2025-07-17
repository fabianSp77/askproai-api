#!/bin/bash

# Setup Code Coverage for PHPUnit
# This script helps install and configure code coverage drivers

echo "🔧 Setting up Code Coverage for PHPUnit"
echo "======================================"
echo ""

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "✓ PHP Version: $PHP_VERSION"

# Check if coverage driver is already installed
check_coverage_driver() {
    if php -m | grep -q "pcov"; then
        echo "✅ PCOV is already installed"
        return 0
    elif php -m | grep -q "xdebug"; then
        echo "⚠️  Xdebug is installed (slower than PCOV for coverage)"
        return 0
    else
        echo "❌ No coverage driver found"
        return 1
    fi
}

# Install PCOV
install_pcov() {
    echo ""
    echo "📦 Installing PCOV..."
    echo ""
    
    # Check if we have pecl
    if ! command -v pecl &> /dev/null; then
        echo "❌ PECL not found. Please install php-dev package:"
        echo "   sudo apt-get install php${PHP_VERSION}-dev"
        return 1
    fi
    
    # Try to install PCOV
    if pecl install pcov; then
        echo ""
        echo "✅ PCOV installed successfully"
        
        # Find PHP ini directory
        PHP_INI_DIR=$(php -i | grep "Scan this dir for additional .ini files" | cut -d' ' -f7)
        
        if [ -n "$PHP_INI_DIR" ]; then
            echo "extension=pcov.so" > "$PHP_INI_DIR/20-pcov.ini"
            echo "✅ PCOV extension enabled in $PHP_INI_DIR/20-pcov.ini"
        else
            echo "⚠️  Could not find PHP ini directory. Add this to your php.ini:"
            echo "   extension=pcov.so"
        fi
        
        # Configure PCOV
        echo "pcov.enabled=1" >> "$PHP_INI_DIR/20-pcov.ini"
        echo "pcov.directory=$PWD/app" >> "$PHP_INI_DIR/20-pcov.ini"
        
        return 0
    else
        echo "❌ Failed to install PCOV"
        return 1
    fi
}

# Create coverage runner script
create_coverage_runner() {
    cat > coverage-report.sh << 'EOF'
#!/bin/bash

# PHPUnit Coverage Report Runner

echo "🧪 Running PHPUnit with Code Coverage"
echo "===================================="
echo ""

# Check for coverage driver
if ! php -m | grep -qE "(pcov|xdebug)"; then
    echo "❌ No coverage driver available!"
    echo "   Run: ./scripts/setup-coverage.sh"
    exit 1
fi

# Use coverage-specific config if available
if [ -f "phpunit.coverage.xml" ]; then
    CONFIG="phpunit.coverage.xml"
else
    CONFIG="phpunit.xml"
fi

# Clear previous coverage
rm -rf coverage/
mkdir -p coverage

# Run tests with coverage
echo "Running tests with coverage..."
echo ""

if [ "$1" == "--html" ]; then
    # HTML report only
    php artisan test --coverage-html=coverage/html --configuration=$CONFIG
    echo ""
    echo "📊 HTML Coverage Report: coverage/html/index.html"
    
elif [ "$1" == "--full" ]; then
    # Full coverage with all formats
    ./vendor/bin/phpunit --configuration=$CONFIG \
        --coverage-html=coverage/html \
        --coverage-text=coverage/coverage.txt \
        --coverage-clover=coverage/clover.xml \
        --coverage-cobertura=coverage/cobertura.xml \
        --log-junit=coverage/junit.xml \
        --testdox-html=coverage/testdox.html
    
    echo ""
    echo "📊 Coverage Reports Generated:"
    echo "   - HTML: coverage/html/index.html"
    echo "   - Text: coverage/coverage.txt"
    echo "   - Clover: coverage/clover.xml"
    echo "   - Cobertura: coverage/cobertura.xml"
    
else
    # Default: text coverage with 80% minimum
    php artisan test --coverage --min=80 --configuration=$CONFIG
fi

# If we have enhanced analyzer, run it too
if [ -f "scripts/analyze-test-coverage-enhanced.php" ]; then
    echo ""
    echo "Running enhanced coverage analysis..."
    php scripts/analyze-test-coverage-enhanced.php
fi

EOF
    
    chmod +x coverage-report.sh
    echo "✅ Created coverage-report.sh"
}

# Main execution
echo "Checking current coverage setup..."
echo ""

if check_coverage_driver; then
    echo ""
    echo "✅ Coverage driver is available"
else
    echo ""
    read -p "Would you like to install PCOV? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        install_pcov
    fi
fi

# Always create the runner script
create_coverage_runner

echo ""
echo "📋 Next Steps:"
echo "============="
echo ""
echo "1. Run coverage analysis:"
echo "   ./coverage-report.sh           # Text report with 80% minimum"
echo "   ./coverage-report.sh --html    # HTML report"
echo "   ./coverage-report.sh --full    # All report formats"
echo ""
echo "2. View static analysis (no driver needed):"
echo "   php scripts/analyze-test-coverage-enhanced.php"
echo ""
echo "3. View coverage dashboard:"
echo "   cat coverage/TEST_COVERAGE_DASHBOARD.md"
echo ""

# If no driver installed, show alternative
if ! check_coverage_driver > /dev/null 2>&1; then
    echo "⚠️  Without PCOV/Xdebug, you can still use:"
    echo "   - Static analysis: php scripts/analyze-test-coverage-enhanced.php"
    echo "   - View dashboard: coverage/TEST_COVERAGE_DASHBOARD.md"
    echo "   - Count tests: php artisan test --testsuite=Unit --stop-on-failure"
fi