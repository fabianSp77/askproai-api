#!/usr/bin/env php
<?php

use App\Models\Branch;
use App\Models\User;
use App\Filament\Admin\Resources\BranchResource;
use Illuminate\Support\Facades\Auth;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Testing Branch Page Rendering\n";
echo "==============================\n\n";

// Find an admin user
$adminUser = User::where('email', 'admin@askproai.de')->first();
if (!$adminUser) {
    $adminUser = User::first();
}

if (!$adminUser) {
    echo "No admin user found\n";
    exit(1);
}

// Login as admin
Auth::login($adminUser);

// Test the branch page
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';
$branch = Branch::find($branchId);

if (!$branch) {
    echo "Branch not found: $branchId\n";
    exit(1);
}

echo "Branch found: {$branch->name}\n";
echo "Company: " . ($branch->company ? $branch->company->name : 'None') . "\n\n";

// Try to access the view page
$url = BranchResource::getUrl('view', ['record' => $branchId]);
echo "View URL: $url\n\n";

// Create a request to the page
$request = \Illuminate\Http\Request::create($url, 'GET');
$request->setUserResolver(function () use ($adminUser) {
    return $adminUser;
});

try {
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n\n";
    
    if ($response->getStatusCode() === 200) {
        $content = $response->getContent();
        
        // Check for CSS files
        echo "CSS Files Referenced:\n";
        preg_match_all('/<link[^>]*href="([^"]*\.css[^"]*)"[^>]*>/', $content, $cssMatches);
        foreach ($cssMatches[1] as $css) {
            echo "  - $css\n";
        }
        echo "\n";
        
        // Check for key design elements
        echo "Design Elements Check:\n";
        
        $designChecks = [
            'fi-layout' => 'Layout wrapper',
            'fi-sidebar' => 'Sidebar navigation',
            'fi-main' => 'Main content area',
            'fi-section' => 'Content sections',
            'fi-header' => 'Page header',
            'fi-infolist' => 'Info list component',
            'rounded-' => 'Rounded corners (Tailwind)',
            'bg-white' => 'White backgrounds',
            'shadow-' => 'Box shadows',
            'text-gray' => 'Gray text colors',
            'bg-sky' => 'Sky theme colors',
            'bg-gradient' => 'Gradient backgrounds',
        ];
        
        foreach ($designChecks as $class => $description) {
            $count = substr_count($content, $class);
            $status = $count > 0 ? "✓" : "✗";
            echo "  $status $description (class: $class) - Found: $count times\n";
        }
        
        // Check for potential CSS issues
        echo "\nPotential CSS Issues:\n";
        
        // Check for inline styles (which might override theme)
        preg_match_all('/style="[^"]*"/', $content, $inlineStyles);
        echo "  - Inline styles found: " . count($inlineStyles[0]) . "\n";
        
        // Check for missing Tailwind classes
        if (strpos($content, 'tailwind') === false && strpos($content, 'tailwindcss') === false) {
            echo "  ⚠ Tailwind might not be loaded properly\n";
        }
        
        // Check for theme.css
        if (strpos($content, 'theme.css') === false) {
            echo "  ⚠ Custom theme.css might not be loaded\n";
        }
        
        // Check viewport meta tag
        if (strpos($content, 'viewport') === false) {
            echo "  ⚠ Missing viewport meta tag (responsive issues)\n";
        }
        
        // Output a sample of the HTML structure
        echo "\nHTML Structure Sample:\n";
        echo "----------------------\n";
        
        // Extract body classes
        preg_match('/<body[^>]*class="([^"]*)"/', $content, $bodyClasses);
        if (isset($bodyClasses[1])) {
            echo "Body classes: " . $bodyClasses[1] . "\n";
        }
        
        // Check for dark mode
        if (strpos($content, 'dark:') !== false) {
            echo "✓ Dark mode classes detected\n";
        }
        
    } else {
        echo "Page returned error: " . $response->getStatusCode() . "\n";
        if ($response->getStatusCode() === 500) {
            echo "Content: " . substr($response->getContent(), 0, 500) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

$kernel->terminate($request, $response);

echo "\n==============================\n";
echo "Test completed.\n";