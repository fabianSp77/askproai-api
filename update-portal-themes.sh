#!/bin/bash

# Update all portal entry points to include ThemeProvider

echo "Updating portal entry points with ThemeProvider..."

# List of portal entry files
files=(
    "resources/js/portal-calls.jsx"
    "resources/js/portal-appointments.jsx"
    "resources/js/portal-customers.jsx"
    "resources/js/portal-team.jsx"
    "resources/js/portal-analytics.jsx"
    "resources/js/portal-settings.jsx"
)

for file in "${files[@]}"; do
    echo "Updating $file..."
    
    # Add ThemeProvider import after AuthProvider import
    sed -i "/import { AuthProvider }/a import { ThemeProvider } from './contexts/ThemeContext';" "$file"
    
    # Wrap AuthProvider with ThemeProvider
    sed -i '/<AuthProvider csrfToken={csrfToken}>/i\        <ThemeProvider defaultTheme="system">' "$file"
    sed -i '/<\/AuthProvider>/a\        </ThemeProvider>' "$file"
done

echo "Done! All portal entry points updated."