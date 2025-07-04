<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

echo "=== Filament CSS Fix Script ===\n\n";

// 1. Clear all caches
echo "1. Clearing all caches...\n";
Artisan::call('optimize:clear');
echo "✓ Caches cleared\n\n";

// 2. Re-publish Filament assets
echo "2. Publishing Filament assets...\n";
Artisan::call('vendor:publish', [
    '--provider' => 'Filament\\FilamentServiceProvider',
    '--force' => true
]);
echo "✓ Filament assets published\n\n";

// 3. Check if core CSS exists
$coreCssPath = public_path('css/filament/filament/app.css');
if (File::exists($coreCssPath)) {
    echo "3. Core CSS exists at: $coreCssPath\n";
    echo "   Size: " . number_format(filesize($coreCssPath)) . " bytes\n\n";
} else {
    echo "❌ ERROR: Core CSS not found!\n";
}

// 4. Create a hotfix CSS file that ensures basic layout
echo "4. Creating hotfix CSS file...\n";
$hotfixCss = <<<'CSS'
/* Filament Hotfix - Ensure basic layout */
.fi-layout {
    display: flex;
    min-height: 100vh;
}

.fi-sidebar {
    position: fixed;
    inset-block-start: 0;
    inset-inline-start: 0;
    z-index: 20;
    display: flex;
    height: 100vh;
    width: 20rem;
    flex-direction: column;
    background-color: #fff;
    border-inline-end: 1px solid #e5e7eb;
}

.dark .fi-sidebar {
    background-color: #111827;
    border-inline-end-color: #374151;
}

.fi-sidebar-header {
    padding: 1rem;
    background-color: rgb(245 158 11);
    color: white;
}

.fi-sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem;
}

.fi-main {
    margin-inline-start: 20rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.fi-topbar {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #fff;
    border-bottom: 1px solid #e5e7eb;
    padding: 1rem;
}

.dark .fi-topbar {
    background-color: #111827;
    border-bottom-color: #374151;
}

.fi-page {
    flex: 1;
    padding: 2rem;
}

/* Ensure forms and inputs are visible */
.fi-form input,
.fi-form select,
.fi-form textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
}

/* Mobile responsiveness */
@media (max-width: 1024px) {
    .fi-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s;
    }
    
    .fi-sidebar.fi-sidebar-open {
        transform: translateX(0);
    }
    
    .fi-main {
        margin-inline-start: 0;
    }
}
CSS;

File::put(public_path('css/filament-hotfix.css'), $hotfixCss);
echo "✓ Hotfix CSS created\n\n";

// 5. Update AdminPanelProvider to include all necessary CSS
echo "5. Checking AdminPanelProvider configuration...\n";
$providerPath = app_path('Providers/Filament/AdminPanelProvider.php');
$providerContent = File::get($providerPath);

// Check if vendor CSS is being loaded
if (!str_contains($providerContent, 'vendor/filament')) {
    echo "⚠ WARNING: Vendor CSS might not be loading correctly\n";
    echo "  Consider adding FilamentAsset::register() calls in boot() method\n";
} else {
    echo "✓ Vendor CSS loading appears configured\n";
}

// 6. Rebuild assets
echo "\n6. Rebuilding assets with npm...\n";
exec('cd ' . base_path() . ' && npm run build 2>&1', $output, $returnCode);
if ($returnCode === 0) {
    echo "✓ Assets rebuilt successfully\n";
} else {
    echo "❌ Failed to rebuild assets\n";
    echo "Output: " . implode("\n", $output) . "\n";
}

// 7. Create diagnostic page
echo "\n7. Creating diagnostic page...\n";
$diagnosticHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filament CSS Diagnostic</title>
    <link rel="stylesheet" href="/css/filament/filament/app.css">
    <link rel="stylesheet" href="/css/filament-hotfix.css">
    <link rel="stylesheet" href="/build/assets/theme-7pLFGx1V.css">
</head>
<body>
    <div class="fi-layout">
        <aside class="fi-sidebar">
            <div class="fi-sidebar-header">
                <h1>Filament Diagnostic</h1>
            </div>
            <nav class="fi-sidebar-nav">
                <ul>
                    <li><a href="#">Menu Item 1</a></li>
                    <li><a href="#">Menu Item 2</a></li>
                    <li><a href="#">Menu Item 3</a></li>
                </ul>
            </nav>
        </aside>
        <div class="fi-main">
            <header class="fi-topbar">
                <h2>Top Bar</h2>
            </header>
            <main class="fi-page">
                <h1>CSS Loading Test</h1>
                <p>If you can see this layout with sidebar on the left, CSS is working.</p>
                
                <h2>Loaded Stylesheets:</h2>
                <ul id="stylesheets"></ul>
                
                <script>
                    const sheets = Array.from(document.styleSheets);
                    const list = document.getElementById('stylesheets');
                    sheets.forEach(sheet => {
                        if (sheet.href) {
                            const li = document.createElement('li');
                            li.textContent = sheet.href;
                            list.appendChild(li);
                        }
                    });
                </script>
            </main>
        </div>
    </div>
</body>
</html>
HTML;

File::put(public_path('filament-diagnostic.html'), $diagnosticHtml);
echo "✓ Diagnostic page created at: /filament-diagnostic.html\n";

echo "\n=== Fix Complete ===\n";
echo "Next steps:\n";
echo "1. Visit https://api.askproai.de/filament-diagnostic.html to test CSS loading\n";
echo "2. Clear browser cache (Ctrl+Shift+R)\n";
echo "3. If still broken, check browser console for CSS loading errors\n";