#!/bin/bash

echo "=== AskProAI Public Directory Security Audit ==="
echo "Date: $(date)"
echo ""

# Check for .htaccess protection in critical directories
echo "Checking .htaccess protection status:"
echo "------------------------------------"

directories=(
    "/var/www/api-gateway/public/documentation"
    "/var/www/api-gateway/public/docs"
    "/var/www/api-gateway/public/mkdocs.backup.20250623161417"
    "/var/www/api-gateway/public/admin_old"
    "/var/www/api-gateway/public/api-client"
    "/var/www/api-gateway/public/dashboard"
)

for dir in "${directories[@]}"; do
    if [ -f "$dir/.htaccess" ]; then
        if grep -q "AuthType Basic" "$dir/.htaccess"; then
            echo "✅ PROTECTED: $dir"
        else
            echo "⚠️  PARTIAL: $dir (has .htaccess but no Basic Auth)"
        fi
    else
        echo "❌ UNPROTECTED: $dir"
    fi
done

echo ""
echo "Checking for sensitive files in public directories:"
echo "------------------------------------------------"

# Look for potentially sensitive files
find /var/www/api-gateway/public -type f \( -name "*.env*" -o -name "*config*.php" -o -name "*database*.json" -o -name "*credential*" -o -name "*secret*" \) 2>/dev/null | while read file; do
    echo "⚠️  Found: $file"
done

echo ""
echo "Checking for backup files:"
echo "------------------------"
find /var/www/api-gateway/public -type f \( -name "*.bak" -o -name "*.backup" -o -name "*.old" -o -name "*.save" \) 2>/dev/null | head -10

echo ""
echo "=== Audit Complete ==="