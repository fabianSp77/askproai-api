#!/bin/bash

# Fix all the syntax errors in the React files

# Find all files with the incorrect template literal syntax
files=$(find /var/www/api-gateway/resources/js -name "*.jsx" -type f)

for file in $files; do
    # Fix the template literal syntax errors where we have '/path/${var}' with extra });
    sed -i "s/await axiosInstance\.get('\/\([^']*\)\${[^}]*}')/await axiosInstance\.get(\`\/\1\`)/g" "$file"
    
    # Fix the extra }); after the template literals
    sed -i '/await axiosInstance.*`.*`$/{N;s/\n.*});/\n/;}' "$file"
    
    # Fix any remaining if (false) conditions
    sed -i 's/if (false)/if (!response.data)/g' "$file"
done

echo "Syntax errors fixed!"