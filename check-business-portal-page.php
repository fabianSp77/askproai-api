<?php

echo "=== CHECKING Business Portal Page Rendering ===\n\n";

// 1. Check what route serves /business/calls/:id/v2
echo "1. Route Configuration:\n";
$routes = require base_path('routes/business-portal.php');
echo "   Business Portal routes loaded\n";

// 2. Check if it's a React SPA or Blade template
$viewPath = resource_path('views/portal/calls/show.blade.php');
$viewV2Path = resource_path('views/portal/calls/show-v2.blade.php');
$reactIndexPath = resource_path('views/portal/calls/react-index.blade.php');

echo "\n2. View Files:\n";
echo "   show.blade.php: " . (file_exists($viewPath) ? "EXISTS" : "NOT FOUND") . "\n";
echo "   show-v2.blade.php: " . (file_exists($viewV2Path) ? "EXISTS" : "NOT FOUND") . "\n";
echo "   react-index.blade.php: " . (file_exists($reactIndexPath) ? "EXISTS" : "NOT FOUND") . "\n";

// 3. Check what the controller returns
echo "\n3. Controller Analysis:\n";
$controllerPath = app_path('Http/Controllers/Portal/CallController.php');
if (file_exists($controllerPath)) {
    $controllerContent = file_get_contents($controllerPath);
    
    // Look for v2 method
    if (preg_match('/public function showV2.*?\{(.*?)\}/s', $controllerContent, $matches)) {
        echo "   showV2 method found\n";
        if (str_contains($matches[1], 'react-index')) {
            echo "   ✅ Returns React view\n";
        } elseif (str_contains($matches[1], 'inertia')) {
            echo "   ✅ Uses Inertia.js\n";
        } else {
            echo "   Returns: " . substr(trim($matches[1]), 0, 100) . "...\n";
        }
    } else {
        echo "   showV2 method NOT FOUND\n";
    }
}

// 4. Check if it's a full React SPA
echo "\n4. React SPA Configuration:\n";
$routesWebPath = base_path('routes/web.php');
$routesContent = file_get_contents($routesWebPath);
if (str_contains($routesContent, '{any}') || str_contains($routesContent, 'catch-all')) {
    echo "   ✅ Catch-all route found (React SPA)\n";
} else {
    echo "   Standard Laravel routing\n";
}

// 5. Check main portal layout
echo "\n5. Portal Layout Check:\n";
$layoutPath = resource_path('views/portal/layouts/app.blade.php');
if (file_exists($layoutPath)) {
    $layoutContent = file_get_contents($layoutPath);
    
    // Check for CSRF token
    if (str_contains($layoutContent, 'csrf-token')) {
        echo "   ✅ CSRF token in layout\n";
        
        // Check how it's rendered
        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $layoutContent, $matches);
        if (isset($matches[1])) {
            echo "   Token template: " . $matches[1] . "\n";
        }
    }
    
    // Check if it yields content or is a React root
    if (str_contains($layoutContent, '@yield') || str_contains($layoutContent, '@section')) {
        echo "   ✅ Traditional Blade layout\n";
    }
    if (str_contains($layoutContent, 'id="app"') || str_contains($layoutContent, 'id="root"')) {
        echo "   ✅ Has React root element\n";
    }
}

// 6. Check how calls/v2 route is handled
echo "\n6. Business Portal Routing:\n";
exec("php artisan route:list --name=business.calls | grep v2", $output);
foreach ($output as $line) {
    echo "   $line\n";
}

echo "\n=== CONCLUSION ===\n";
echo "The Business Portal appears to be a ";
if (str_contains($routesContent ?? '', '{any}')) {
    echo "React SPA with client-side routing\n";
    echo "CSRF token should be available in window.Laravel.csrfToken\n";
} else {
    echo "traditional Laravel app with server-side routing\n";
    echo "CSRF token should be in meta tag\n";
}