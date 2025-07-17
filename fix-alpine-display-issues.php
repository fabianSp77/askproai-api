#!/usr/bin/env php
<?php

/**
 * Fix Alpine.js Display Issues in AskProAI
 * 
 * This script identifies and fixes common Alpine.js initialization problems
 * that cause elements not to display properly.
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

echo "\n🔍 Analyzing Alpine.js Display Issues...\n\n";

$issues = [];
$fixes = [];

// 1. Check for malformed x-data attributes in Blade files
echo "1. Checking for malformed x-data attributes...\n";
$bladeFiles = File::glob(resource_path('views/**/*.blade.php'));
$malformedCount = 0;

foreach ($bladeFiles as $file) {
    $content = File::get($file);
    
    // Check for common x-data issues
    $patterns = [
        // Empty x-data
        '/x-data\s*=\s*["\']?\s*["\']?(?=\s|>)/' => 'Empty x-data attribute',
        // x-data without quotes
        '/x-data\s*=\s*(?!["\'])([^>\s]+)/' => 'x-data without quotes',
        // x-data with undefined functions
        '/x-data\s*=\s*["\'](\w+)\(\)["\']/' => 'Possible undefined Alpine component',
    ];
    
    foreach ($patterns as $pattern => $description) {
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $issues[] = [
                    'file' => str_replace(base_path() . '/', '', $file),
                    'line' => $line,
                    'issue' => $description,
                    'code' => trim($match[0])
                ];
                $malformedCount++;
            }
        }
    }
}

echo "   Found $malformedCount potential issues\n";

// 2. Check JavaScript files for Alpine component definitions
echo "\n2. Checking Alpine component definitions...\n";
$jsFiles = array_merge(
    File::glob(resource_path('js/**/*.js')),
    File::glob(public_path('js/**/*.js'))
);

$definedComponents = [];
$referencedComponents = [];

foreach ($jsFiles as $file) {
    $content = File::get($file);
    
    // Find Alpine.data definitions
    if (preg_match_all('/Alpine\.data\s*\(\s*["\'](\w+)["\']/', $content, $matches)) {
        foreach ($matches[1] as $component) {
            $definedComponents[$component] = str_replace(base_path() . '/', '', $file);
        }
    }
}

// Check which components are referenced but not defined
foreach ($bladeFiles as $file) {
    $content = File::get($file);
    
    if (preg_match_all('/x-data\s*=\s*["\']\s*(\w+)\s*\(\)/', $content, $matches)) {
        foreach ($matches[1] as $component) {
            if (!isset($definedComponents[$component])) {
                $referencedComponents[$component][] = str_replace(base_path() . '/', '', $file);
            }
        }
    }
}

echo "   Defined components: " . count($definedComponents) . "\n";
echo "   Undefined components referenced: " . count($referencedComponents) . "\n";

// 3. Check for Alpine initialization timing issues
echo "\n3. Checking Alpine initialization order...\n";
$appJs = resource_path('js/app.js');
if (File::exists($appJs)) {
    $content = File::get($appJs);
    
    // Check if Alpine.start() is called
    if (!Str::contains($content, 'Alpine.start()')) {
        $issues[] = [
            'file' => 'resources/js/app.js',
            'issue' => 'Alpine.start() not found',
            'severity' => 'high'
        ];
    }
    
    // Check if Alpine is assigned to window
    if (!Str::contains($content, 'window.Alpine')) {
        $issues[] = [
            'file' => 'resources/js/app.js',
            'issue' => 'Alpine not assigned to window',
            'severity' => 'high'
        ];
    }
}

// 4. Generate fixes
echo "\n4. Generating fixes...\n";

// Fix 1: Create missing Alpine component stubs
foreach ($referencedComponents as $component => $files) {
    $fixes[] = [
        'type' => 'missing_component',
        'component' => $component,
        'files' => $files,
        'fix' => "Alpine.data('$component', () => ({ /* Add component logic here */ }))"
    ];
}

// Fix 2: Update app.js with proper initialization
if (!empty($issues)) {
    $fixes[] = [
        'type' => 'initialization',
        'description' => 'Ensure proper Alpine initialization',
        'code' => <<<'JS'
// Ensure Alpine is properly initialized
document.addEventListener('alpine:init', () => {
    console.log('Alpine initializing...');
});

// Add error handler
window.addEventListener('error', (e) => {
    if (e.message.includes('Alpine')) {
        console.error('Alpine error:', e);
    }
});

// Ensure Alpine starts after DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.start();
    });
} else {
    Alpine.start();
}
JS
    ];
}

// 5. Output report
echo "\n" . str_repeat('=', 60) . "\n";
echo "ALPINE.JS DISPLAY ISSUES REPORT\n";
echo str_repeat('=', 60) . "\n\n";

if (empty($issues) && empty($referencedComponents)) {
    echo "✅ No critical Alpine.js issues found!\n";
} else {
    echo "⚠️  Found " . count($issues) . " issues and " . count($referencedComponents) . " undefined components\n\n";
    
    echo "ISSUES:\n";
    foreach ($issues as $issue) {
        echo "- {$issue['file']}";
        if (isset($issue['line'])) {
            echo ":{$issue['line']}";
        }
        echo " - {$issue['issue']}";
        if (isset($issue['code'])) {
            echo " ({$issue['code']})";
        }
        echo "\n";
    }
    
    if (!empty($referencedComponents)) {
        echo "\nUNDEFINED COMPONENTS:\n";
        foreach ($referencedComponents as $component => $files) {
            echo "- $component (used in: " . implode(', ', array_slice($files, 0, 3));
            if (count($files) > 3) {
                echo " and " . (count($files) - 3) . " more";
            }
            echo ")\n";
        }
    }
}

// 6. Generate fix file
echo "\n" . str_repeat('=', 60) . "\n";
echo "RECOMMENDED FIXES\n";
echo str_repeat('=', 60) . "\n\n";

$fixContent = "// Alpine.js Fixes - Generated " . date('Y-m-d H:i:s') . "\n\n";

// Add missing component definitions
if (!empty($referencedComponents)) {
    $fixContent .= "// Define missing Alpine components\n";
    $fixContent .= "document.addEventListener('alpine:init', () => {\n";
    foreach ($referencedComponents as $component => $files) {
        $fixContent .= "    // Used in: " . implode(', ', array_slice($files, 0, 2)) . "\n";
        $fixContent .= "    Alpine.data('$component', () => ({\n";
        $fixContent .= "        init() {\n";
        $fixContent .= "            console.log('$component initialized');\n";
        $fixContent .= "        },\n";
        $fixContent .= "        // Add your component properties and methods here\n";
        $fixContent .= "    }));\n\n";
    }
    $fixContent .= "});\n\n";
}

// Add initialization fixes
$fixContent .= <<<'JS'
// Ensure Alpine components are initialized for dynamically loaded content
document.addEventListener('livewire:load', () => {
    Livewire.hook('message.processed', (message, component) => {
        // Reinitialize Alpine components after Livewire updates
        queueMicrotask(() => {
            const uninitializedElements = component.el.querySelectorAll('[x-data]:not([x-id])');
            uninitializedElements.forEach(el => {
                if (window.Alpine) {
                    window.Alpine.initTree(el);
                }
            });
        });
    });
});

// Fix for x-show/x-if not working
document.addEventListener('alpine:initialized', () => {
    // Force re-evaluation of x-show directives
    document.querySelectorAll('[x-show], [x-if]').forEach(el => {
        el._x_forceUpdate && el._x_forceUpdate();
    });
});

JS;

// Save fix file
$fixFile = resource_path('js/alpine-fixes-generated.js');
File::put($fixFile, $fixContent);

echo "1. A fix file has been generated at:\n";
echo "   $fixFile\n\n";

echo "2. Add this to your resources/js/app.js:\n";
echo "   import './alpine-fixes-generated';\n\n";

echo "3. Run: npm run build\n\n";

echo "4. Clear browser cache and reload the page\n\n";

// Check specific transcript viewer issue
echo "TRANSCRIPT VIEWER SPECIFIC CHECK:\n";
$transcriptViewerFile = resource_path('views/filament/infolists/transcript-viewer-enterprise.blade.php');
if (File::exists($transcriptViewerFile)) {
    $content = File::get($transcriptViewerFile);
    
    if (preg_match('/x-data="transcriptViewerEnterprise/', $content)) {
        echo "✅ Transcript viewer Alpine component found\n";
        
        // Check if the component is defined in JS
        $componentDefined = false;
        foreach ($jsFiles as $file) {
            if (Str::contains(File::get($file), 'transcriptViewerEnterprise')) {
                $componentDefined = true;
                break;
            }
        }
        
        if (!$componentDefined) {
            echo "⚠️  But the JavaScript function is defined inline in the Blade file\n";
            echo "   This can cause issues with Alpine initialization timing\n";
            echo "   Consider moving it to a separate JS file\n";
        }
    }
}

echo "\n✅ Analysis complete!\n\n";

// Output diagnostic command
echo "To run live diagnostics in your browser console:\n";
echo "window.runAlpineDiagnostics()\n\n";

echo "To manually fix a specific component:\n";
echo "window.fixAlpineComponent('transcriptViewerEnterprise')\n\n";