<?php

// Quick test to verify the Company Integration Portal UI fix

echo "🧪 Testing Company Integration Portal UI Fix\n";
echo "==========================================\n\n";

// Check if the fixed view exists
$viewPath = __DIR__ . '/resources/views/filament/admin/pages/company-integration-portal-fixed-v2.blade.php';
if (file_exists($viewPath)) {
    echo "✅ Fixed view exists\n";
    
    // Check for problematic inline styles
    $content = file_get_contents($viewPath);
    if (strpos($content, 'style=') !== false) {
        echo "⚠️  WARNING: Found inline styles in the view\n";
    } else {
        echo "✅ No inline styles found (good!)\n";
    }
    
    // Check for Filament components
    if (strpos($content, 'x-filament') !== false) {
        echo "✅ Using Filament components\n";
    }
    
    // Check for proper wire:key attributes
    if (strpos($content, 'wire:key=') !== false) {
        echo "✅ Has wire:key attributes for Livewire\n";
    }
} else {
    echo "❌ Fixed view not found!\n";
}

// Check if CSS is compiled
$manifestPath = __DIR__ . '/public/build/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (isset($manifest['resources/css/filament/admin/company-integration-portal-clean.css'])) {
        echo "✅ Clean CSS is compiled\n";
    } else {
        echo "❌ Clean CSS not found in manifest\n";
    }
}

echo "\n📋 Checklist for manual verification:\n";
echo "1. [ ] Clear browser cache (Cmd+Shift+R or Ctrl+Shift+R)\n";
echo "2. [ ] Visit /admin/company-integration-portal\n";
echo "3. [ ] Company cards should be in a proper grid\n";
echo "4. [ ] Text should be fully visible\n";
echo "5. [ ] Buttons should look like buttons (not code)\n";
echo "6. [ ] Everything should be clickable\n";
echo "7. [ ] Page should be responsive on mobile\n";

echo "\n🔍 If still broken, check:\n";
echo "- Browser console for errors (F12)\n";
echo "- Network tab for 404s\n";
echo "- Try incognito mode\n";
echo "- Disable browser extensions\n";