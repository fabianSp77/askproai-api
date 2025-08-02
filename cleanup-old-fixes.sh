#!/bin/bash

echo "=== Cleaning up old fix scripts ==="
echo "Date: $(date)"
echo ""

# Create list of files to remove
cat > /tmp/files-to-remove.txt << 'EOF'
public/js/admin-portal-fixes.js
public/js/alpine-error-fix.js
public/js/alpine-stores-fix.js
public/js/app/filament-safe-fixes.js
public/js/app/wizard-dropdown-fix.js
public/js/askproai-ui-tester.js
public/js/comprehensive-ui-fix.js
public/js/dashboard-fixes.js
public/js/dashboard-visual-fixes.js
public/js/debug-alpine-livewire.js
public/js/debug-dropdowns.js
public/js/debug-loading-sequence.js
public/js/debug-wizard-form.js
public/js/document-write-fix.js
public/js/dropdown-close-fix.js
public/js/dropdown-debug-helper.js
public/js/dropdown-fix-direct.js
public/js/emergency-business-portal-fix.js
public/js/emergency-button-fix.js
public/js/filament-alpine-fix.js
public/js/filament-dropdown-global-fix.js
public/js/filament-search-fix.js
public/js/filament-select-fix.js
public/js/filament-toggle-buttons-fix.js
public/js/final-wizard-fix.js
public/js/fix-alpine-livewire-init.js
public/js/fix-csrf-popup.js
public/js/fix-filament-dropdowns.js
public/js/fix-wizard-toggle.js
public/js/force-livewire-alpine-load.js
public/js/force-wizard-reactivity.js
public/js/livewire-404-fix.js
public/js/livewire-config-fix.js
public/js/livewire-debug.js
public/js/livewire-dropdown-fix.js
public/js/livewire-fix.js
public/js/livewire-modal-fix.js
public/js/livewire-mount-fix.js
public/js/livewire-reactive-fix.js
public/js/minimal-ui-fix.js
public/js/modal-fix.js
public/js/operations-center-dropdown-fix.js
public/js/portal-auth-fix.js
public/js/responsive-zoom-handler-fixed.js
public/js/test-minimal-setup.js
public/js/unified-ui-fix.js
public/js/unified-ui-fix-v2.js
public/js/wizard-form-handler.js
public/js/remove-error-overlay.js
public/js/button-click-handler.js
public/js/clean-table-layout.js
public/unregister-sw.js
EOF

# Keep only clean-livewire-fix.js
echo "Files to keep:"
echo "- public/js/clean-livewire-fix.js"
echo ""

echo "Removing old fix scripts..."
while IFS= read -r file; do
    if [ -f "$file" ]; then
        echo "Removing: $file"
        rm -f "$file"
    fi
done < /tmp/files-to-remove.txt

echo ""
echo "Cleaning empty directories..."
find public/js -type d -empty -delete

echo ""
echo "=== Cleanup complete ==="
echo "Kept only: public/js/clean-livewire-fix.js"