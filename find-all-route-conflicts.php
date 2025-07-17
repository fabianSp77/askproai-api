<?php
// Find all route conflicts

echo "🔍 Finding ALL route conflicts...\n\n";

// Get all routes
exec('php artisan route:list --json 2>&1', $output, $returnCode);

if ($returnCode !== 0) {
    // Parse error message to find conflicts
    $errorOutput = implode("\n", $output);
    
    // Extract all conflict messages
    preg_match_all('/Unable to prepare route \[([^\]]+)\].*name \[([^\]]+)\]/', $errorOutput, $matches);
    
    if (!empty($matches[0])) {
        echo "❌ Found route conflicts:\n\n";
        
        $conflicts = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $route = $matches[1][$i];
            $name = $matches[2][$i];
            $conflicts[$name][] = $route;
        }
        
        foreach ($conflicts as $name => $routes) {
            echo "Duplicate name: '$name'\n";
            echo "Routes: " . implode(", ", array_unique($routes)) . "\n\n";
        }
        
        // Find which files contain these routes
        echo "📁 Searching for route definitions...\n\n";
        
        foreach ($conflicts as $name => $routes) {
            $routeName = explode('.', $name);
            $resource = $routeName[count($routeName) - 2] ?? '';
            
            if ($resource) {
                echo "Looking for resource: $resource\n";
                exec("grep -r \"apiResource.*$resource\" routes/ 2>/dev/null", $grepOutput);
                foreach ($grepOutput as $line) {
                    echo "  Found in: $line\n";
                }
                echo "\n";
            }
        }
    }
} else {
    echo "✅ No route conflicts found!\n";
    echo "Route cache should work now.\n";
}

// Check specific known problem routes
echo "\n📋 Checking known problem routes:\n";
$knownProblems = ['staff', 'services', 'branches', 'calls', 'appointments', 'customers'];

foreach ($knownProblems as $resource) {
    exec("grep -r \"apiResource.*$resource\\|Route::resource.*$resource\" routes/ 2>/dev/null | grep -v backup", $resourceOutput);
    if (count($resourceOutput) > 1) {
        echo "\n⚠️  Multiple definitions for '$resource':\n";
        foreach ($resourceOutput as $line) {
            echo "  - $line\n";
        }
    }
}

echo "\n💡 To fix conflicts:\n";
echo "1. Add ->names() to duplicate resources\n";
echo "2. Use different prefixes (e.g., 'admin.', 'business.', 'api.')\n";
echo "3. Or use different resource names\n";