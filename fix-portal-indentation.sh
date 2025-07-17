#!/bin/bash

# Fix indentation in portal entry points

echo "Fixing indentation in portal entry points..."

files=(
    "resources/js/portal-customers.jsx"
    "resources/js/portal-settings.jsx"
    "resources/js/portal-team.jsx"
    "resources/js/portal-analytics.jsx"
    "resources/js/portal-appointments.jsx"
)

for file in "${files[@]}"; do
    echo "Fixing $file..."
    # Fix the indentation for ThemeProvider
    sed -i 's/^        <ThemeProvider defaultTheme="system">$/        <ThemeProvider defaultTheme="system">/' "$file"
    sed -i 's/^        <AuthProvider csrfToken={csrfToken}>$/            <AuthProvider csrfToken={csrfToken}>/' "$file"
    sed -i '/^            <[A-Z].*\/>$/s/^/    /' "$file"
    sed -i 's/^        <\/AuthProvider>$/            <\/AuthProvider>/' "$file"
    sed -i 's/^        <\/ThemeProvider>$/        <\/ThemeProvider>/' "$file"
done

echo "Done!"