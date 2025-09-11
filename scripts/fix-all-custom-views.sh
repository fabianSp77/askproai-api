#!/bin/bash

echo "Removing all custom view declarations from Filament pages..."
echo ""

# Counter for tracking changes
count=0
files_processed=""

# Function to process a single file
process_file() {
    local file="$1"
    
    # Check if file has custom view declaration
    if grep -q "protected static string \\\$view = " "$file" 2>/dev/null; then
        echo "Processing: $file"
        
        # Comment out the custom view line
        sed -i 's/^    protected static string \$view = /    \/\/ protected static string \$view = /' "$file"
        
        ((count++))
        files_processed="$files_processed$file\n"
    fi
}

# Process all PHP files in Filament Admin Resources
echo "Scanning for files with custom view declarations..."
echo ""

# Find all PHP files in the Resources directory
for file in $(find app/Filament/Admin/Resources -name "*.php" -type f); do
    process_file "$file"
done

echo ""
echo "✅ Fixed $count files with custom view declarations"
echo ""

if [ $count -gt 0 ]; then
    echo "Files processed:"
    echo -e "$files_processed"
    echo ""
fi

echo "Clearing all caches..."
php artisan optimize:clear
php artisan filament:clear-cached-components

echo ""
echo "Restarting PHP-FPM to clear OPcache..."
sudo service php8.3-fpm restart

echo ""
echo "✅ All custom views have been disabled and caches cleared!"
echo "Filament will now use its default views for all pages."