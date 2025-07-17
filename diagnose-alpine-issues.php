#!/usr/bin/env php
<?php

echo "\n🔍 Diagnosing Alpine.js Display Issues in AskProAI...\n\n";

$basePath = __DIR__;
$viewsPath = $basePath . '/resources/views';
$jsPath = $basePath . '/resources/js';

// 1. Check for x-data issues in Blade files
echo "1. Checking for x-data issues in Blade files...\n";
$issues = [];

// Find all blade files with x-data
$bladeFiles = shell_exec("find $viewsPath -name '*.blade.php' -type f -exec grep -l 'x-data' {} \;");
$files = array_filter(explode("\n", $bladeFiles));

foreach ($files as $file) {
    if (empty($file)) continue;
    
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'x-data') !== false) {
            // Check for empty x-data
            if (preg_match('/x-data\s*=\s*["\']?\s*["\']?(?=\s|>)/', $line)) {
                $issues[] = [
                    'file' => str_replace($basePath . '/', '', $file),
                    'line' => $lineNum + 1,
                    'issue' => 'Empty x-data',
                    'code' => trim($line)
                ];
            }
            // Check for function calls in x-data
            elseif (preg_match('/x-data\s*=\s*["\'](\w+)\(\)/', $line, $matches)) {
                $componentName = $matches[1];
                // Store for later checking
                $referencedComponents[$componentName][] = [
                    'file' => str_replace($basePath . '/', '', $file),
                    'line' => $lineNum + 1
                ];
            }
        }
    }
}

echo "   Found " . count($issues) . " potential issues\n";

// 2. Check for Alpine component definitions
echo "\n2. Checking Alpine component definitions...\n";
$definedComponents = [];

// Check in blade files for inline definitions
$transcriptViewer = $basePath . '/resources/views/filament/infolists/transcript-viewer-enterprise.blade.php';
if (file_exists($transcriptViewer)) {
    $content = file_get_contents($transcriptViewer);
    if (strpos($content, 'function transcriptViewerEnterprise') !== false) {
        $definedComponents['transcriptViewerEnterprise'] = 'inline in blade file';
        echo "   ✅ Found transcriptViewerEnterprise (defined inline)\n";
    }
}

// Check JS files
$jsFiles = shell_exec("find $jsPath -name '*.js' -type f");
$jsFileList = array_filter(explode("\n", $jsFiles));

foreach ($jsFileList as $file) {
    if (empty($file)) continue;
    $content = file_get_contents($file);
    
    // Look for Alpine.data definitions
    if (preg_match_all('/Alpine\.data\s*\(\s*["\'](\w+)["\']/', $content, $matches)) {
        foreach ($matches[1] as $component) {
            $definedComponents[$component] = str_replace($basePath . '/', '', $file);
        }
    }
}

echo "   Found " . count($definedComponents) . " defined components\n";

// 3. Check app.js for proper Alpine setup
echo "\n3. Checking Alpine initialization...\n";
$appJs = $basePath . '/resources/js/app.js';
if (file_exists($appJs)) {
    $content = file_get_contents($appJs);
    
    $checks = [
        'Alpine imported' => strpos($content, "import Alpine from 'alpinejs'") !== false,
        'window.Alpine set' => strpos($content, 'window.Alpine') !== false,
        'Alpine.start() called' => strpos($content, 'Alpine.start()') !== false,
        'Plugins loaded' => strpos($content, 'Alpine.plugin') !== false
    ];
    
    foreach ($checks as $check => $result) {
        echo "   " . ($result ? "✅" : "❌") . " $check\n";
    }
}

// 4. Output specific findings
echo "\n" . str_repeat('=', 60) . "\n";
echo "ALPINE.JS DIAGNOSTIC REPORT\n";
echo str_repeat('=', 60) . "\n\n";

if (!empty($issues)) {
    echo "⚠️  ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "- {$issue['file']}:{$issue['line']} - {$issue['issue']}\n";
        echo "  Code: {$issue['code']}\n";
    }
}

// 5. Provide specific fixes
echo "\n🔧 RECOMMENDED FIXES:\n\n";

echo "1. Add this to the beginning of your blade files with Alpine components:\n";
echo <<<'BLADE'
@push('scripts')
<script>
    // Ensure Alpine is loaded
    if (typeof Alpine === 'undefined') {
        console.error('Alpine.js is not loaded!');
    }
</script>
@endpush

BLADE;

echo "\n2. For the transcript viewer, ensure the component is properly initialized:\n";
echo <<<'JS'
// Add this after your component definition in the blade file:
document.addEventListener('alpine:init', () => {
    // Register the component if it's not already registered
    if (typeof Alpine.data === 'function' && !Alpine.data.transcriptViewerEnterprise) {
        console.log('Registering transcriptViewerEnterprise component');
    }
});

JS;

echo "\n3. Debug in browser console:\n";
echo "   - Open browser console and type: Alpine\n";
echo "   - Check if Alpine is defined and started\n";
echo "   - Look for any JavaScript errors\n";
echo "   - Run: document.querySelectorAll('[x-data]')\n";
echo "   - This shows all Alpine components on the page\n";

echo "\n4. Force re-initialization (browser console):\n";
echo <<<'JS'
// Find all x-data elements
document.querySelectorAll('[x-data]').forEach(el => {
    console.log('Component:', el.getAttribute('x-data'));
    // Try to reinitialize
    if (window.Alpine && window.Alpine.initTree) {
        window.Alpine.initTree(el);
    }
});

JS;

echo "\n5. Check browser console for errors when loading the page\n";
echo "   Common errors:\n";
echo "   - 'Alpine is not defined' - Alpine not loaded\n";
echo "   - 'Cannot read property' - Component not initialized\n";
echo "   - 'Illegal invocation' - Function binding issue\n";

echo "\n✅ Diagnostic complete!\n";