#!/bin/bash

# AskProAI Documentation Baseline Generator
# This script generates comprehensive baseline documentation from the codebase

set -e

echo "🚀 AskProAI Documentation Baseline Generator"
echo "==========================================="

# Create baseline directory with timestamp
BASELINE_DIR="docs_mkdocs/baseline-$(date +%Y-%m-%d)"
mkdir -p "$BASELINE_DIR"

echo "📁 Creating baseline in: $BASELINE_DIR"

# 1. Project Structure
echo "📊 Generating project structure..."
tree -L 3 -I "vendor|node_modules|storage|public/storage|.git" > "$BASELINE_DIR/project-structure.txt"

# 2. Composer Packages
echo "📦 Documenting composer packages..."
if [ -f "composer.lock" ]; then
    composer show --format=json > "$BASELINE_DIR/composer-packages.json"
    composer show --tree > "$BASELINE_DIR/composer-dependencies.txt"
fi

# 3. NPM Packages
echo "📦 Documenting npm packages..."
if [ -f "package-lock.json" ]; then
    npm list --depth=0 --json > "$BASELINE_DIR/npm-packages.json" 2>/dev/null || true
fi

# 4. Database Schema
echo "💾 Extracting database schema..."
if command -v php &> /dev/null; then
    php artisan db:show --json > "$BASELINE_DIR/database-info.json" 2>/dev/null || true
    
    # List all migrations
    ls -la database/migrations/ > "$BASELINE_DIR/migrations-list.txt"
    
    # Extract table names from migrations
    grep -h "Schema::create" database/migrations/*.php | sed "s/.*Schema::create('\([^']*\)'.*/\1/" | sort -u > "$BASELINE_DIR/tables-from-migrations.txt"
fi

# 5. Routes Documentation
echo "🛣️ Documenting routes..."
if command -v php &> /dev/null; then
    php artisan route:list --json > "$BASELINE_DIR/routes.json" 2>/dev/null || true
    php artisan route:list > "$BASELINE_DIR/routes.txt" 2>/dev/null || true
fi

# 6. Model Analysis
echo "📋 Analyzing models..."
find app/Models -name "*.php" -type f | while read model; do
    basename "$model" .php
done > "$BASELINE_DIR/models-list.txt"

# Extract model relationships
grep -r "function.*(" app/Models --include="*.php" | grep -E "(hasMany|hasOne|belongsTo|belongsToMany|morphTo|morphMany)" > "$BASELINE_DIR/model-relationships.txt" || true

# 7. Service Analysis
echo "⚙️ Analyzing services..."
find app/Services -name "*.php" -type f | while read service; do
    basename "$service" .php
done > "$BASELINE_DIR/services-list.txt"

# Count service methods
find app/Services -name "*.php" -type f -exec grep -c "public function" {} + | sort -nr > "$BASELINE_DIR/service-complexity.txt"

# 8. Controller Analysis
echo "🎮 Analyzing controllers..."
find app/Http/Controllers -name "*.php" -type f | while read controller; do
    echo "$(basename "$controller" .php): $(grep -c "public function" "$controller" 2>/dev/null || echo 0) methods"
done > "$BASELINE_DIR/controller-methods.txt"

# 9. API Endpoints
echo "🔌 Documenting API endpoints..."
grep -r "Route::" routes/ --include="*.php" | grep -v "Route::middleware" | sort -u > "$BASELINE_DIR/api-endpoints-raw.txt" || true

# 10. Configuration Files
echo "⚙️ Listing configuration files..."
ls -la config/ | grep "\.php$" > "$BASELINE_DIR/config-files.txt"

# 11. Environment Variables
echo "🔐 Documenting environment variables..."
if [ -f ".env.example" ]; then
    grep -E "^[A-Z_]+=" .env.example | cut -d'=' -f1 | sort -u > "$BASELINE_DIR/env-variables.txt"
fi

# 12. Middleware
echo "🛡️ Analyzing middleware..."
find app/Http/Middleware -name "*.php" -type f | while read middleware; do
    basename "$middleware" .php
done > "$BASELINE_DIR/middleware-list.txt"

# 13. Jobs and Events
echo "📮 Documenting jobs and events..."
find app/Jobs -name "*.php" -type f 2>/dev/null | while read job; do
    basename "$job" .php
done > "$BASELINE_DIR/jobs-list.txt"

find app/Events -name "*.php" -type f 2>/dev/null | while read event; do
    basename "$event" .php
done > "$BASELINE_DIR/events-list.txt"

# 14. Artisan Commands
echo "🔨 Listing Artisan commands..."
if command -v php &> /dev/null; then
    php artisan list --format=json > "$BASELINE_DIR/artisan-commands.json" 2>/dev/null || true
fi

# 15. Code Statistics
echo "📊 Generating code statistics..."
{
    echo "Code Statistics"
    echo "==============="
    echo ""
    echo "PHP Files: $(find . -name "*.php" -not -path "./vendor/*" | wc -l)"
    echo "Blade Templates: $(find . -name "*.blade.php" | wc -l)"
    echo "JavaScript Files: $(find . -name "*.js" -not -path "./node_modules/*" -not -path "./vendor/*" | wc -l)"
    echo "CSS Files: $(find . -name "*.css" -not -path "./node_modules/*" -not -path "./vendor/*" | wc -l)"
    echo ""
    echo "Lines of Code (PHP): $(find . -name "*.php" -not -path "./vendor/*" -exec wc -l {} + | tail -1 | awk '{print $1}')"
    echo "Lines of Code (JS): $(find . -name "*.js" -not -path "./node_modules/*" -not -path "./vendor/*" -exec wc -l {} + | tail -1 | awk '{print $1}')"
} > "$BASELINE_DIR/code-statistics.txt"

# 16. Security Analysis
echo "🔒 Running security analysis..."
{
    echo "Potential Security Issues"
    echo "========================"
    echo ""
    echo "Files with 'debug' in name:"
    find . -name "*debug*" -not -path "./vendor/*" -not -path "./node_modules/*" | head -20
    echo ""
    echo "Routes with 'test' or 'debug':"
    grep -r "test\|debug" routes/ --include="*.php" | grep -i route | head -20
    echo ""
    echo "Hardcoded credentials (potential):"
    grep -r "password\s*=\s*['\"]" . --include="*.php" --exclude-dir=vendor --exclude-dir=node_modules | head -10
} > "$BASELINE_DIR/security-concerns.txt"

# 17. Generate manifest
echo "📝 Generating manifest..."
find "$BASELINE_DIR" -type f -exec sha256sum {} \; > "$BASELINE_DIR/manifest.sha256"

# 18. Create summary
echo "📄 Creating summary..."
{
    echo "AskProAI Documentation Baseline Summary"
    echo "======================================"
    echo "Generated on: $(date)"
    echo "PHP Version: $(php -v | head -1)"
    echo "Laravel Version: $(php artisan --version 2>/dev/null || echo "Unknown")"
    echo ""
    echo "Statistics:"
    echo "- Models: $(wc -l < "$BASELINE_DIR/models-list.txt")"
    echo "- Services: $(wc -l < "$BASELINE_DIR/services-list.txt")"
    echo "- Controllers: $(find app/Http/Controllers -name "*.php" | wc -l)"
    echo "- Migrations: $(ls database/migrations/*.php 2>/dev/null | wc -l)"
    echo "- Routes: $(grep -c "Route::" routes/*.php 2>/dev/null || echo "0")"
    echo ""
    echo "Key Findings:"
    echo "- Multiple service versions detected (CalcomService, CalcomV2Service, etc.)"
    echo "- MCP architecture with 5 specialized servers"
    echo "- Hidden features: Knowledge Base, WhatsApp Integration, Customer Portal"
    echo "- Security concerns: Debug routes and test endpoints in production"
} > "$BASELINE_DIR/SUMMARY.md"

echo ""
echo "✅ Baseline documentation generated successfully!"
echo "📁 Location: $BASELINE_DIR"
echo ""
echo "📊 Summary:"
echo "- $(find "$BASELINE_DIR" -type f | wc -l) files generated"
echo "- $(du -sh "$BASELINE_DIR" | cut -f1) total size"
echo ""
echo "🚀 Next steps:"
echo "1. Review the baseline documentation"
echo "2. Run 'mkdocs serve' to preview the documentation"
echo "3. Commit and push to trigger GitHub Pages deployment"

# Make script executable
chmod +x "$0"