<?php

// Script to check if pages display actual content

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║           CHECKING PAGE CONTENT RENDERING                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// URLs to test with their expected content
$testUrls = [
    [
        'url' => '/admin/tenants/1',
        'name' => 'Tenant #1',
        'expected' => ['AskProAI', 'Tenant Information', 'Active'],
    ],
    [
        'url' => '/admin/companies/1',
        'name' => 'Company #1',
        'expected' => ['Krückeberg', 'Company Information'],
    ],
    [
        'url' => '/admin/branches/34c4d48e-4753-4715-9c30-c55843a943e8',
        'name' => 'Branch',
        'expected' => ['Branch Information', 'Zentrale'],
    ],
    [
        'url' => '/admin/calls/3',
        'name' => 'Call #3',
        'expected' => ['Call Information', 'Duration'],
    ],
    [
        'url' => '/admin/phone-numbers/03513893-d962-4db0-858c-ea5b0e227e9a',
        'name' => 'Phone Number',
        'expected' => ['Phone Number Information', '+49'],
    ],
    [
        'url' => '/admin/retell-agents/135',
        'name' => 'Retell Agent #135',
        'expected' => ['Agent Information', 'Online'],
    ],
    [
        'url' => '/admin/services/1',
        'name' => 'Service #1',
        'expected' => ['Service Information', 'Test Service'],
    ],
    [
        'url' => '/admin/staff/9f47fda1-977c-47aa-a87a-0e8cbeaeb119',
        'name' => 'Staff Member',
        'expected' => ['Staff Information', 'Fabian'],
    ],
    [
        'url' => '/admin/users/5',
        'name' => 'User #5',
        'expected' => ['User Information', 'admin'],
    ],
];

// Login as admin
$admin = User::where('email', 'admin@askproai.de')->first();
if (!$admin) {
    echo "❌ Admin user not found\n";
    exit(1);
}

// Start session
Session::start();
Auth::login($admin);
$token = csrf_token();

$results = [];

foreach ($testUrls as $test) {
    echo "Testing: {$test['name']}\n";
    echo "URL: {$test['url']}\n";
    
    try {
        // Create request with authentication
        $request = Illuminate\Http\Request::create(
            $test['url'],
            'GET',
            [],
            [
                'laravel_session' => Session::getId(),
            ],
            [],
            [
                'HTTP_X_CSRF_TOKEN' => $token,
                'HTTP_ACCEPT' => 'text/html',
            ]
        );
        
        // Set the authenticated user
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });
        
        // Handle the request
        $response = $kernel->handle($request);
        $statusCode = $response->getStatusCode();
        
        echo "  Status: $statusCode\n";
        
        if ($statusCode === 200) {
            $content = $response->getContent();
            
            // Check for expected content
            $foundContent = [];
            $missingContent = [];
            
            foreach ($test['expected'] as $expected) {
                if (stripos($content, $expected) !== false) {
                    $foundContent[] = $expected;
                } else {
                    $missingContent[] = $expected;
                }
            }
            
            if (count($foundContent) > 0) {
                echo "  ✅ Found content: " . implode(', ', $foundContent) . "\n";
            }
            
            if (count($missingContent) > 0) {
                echo "  ❌ Missing content: " . implode(', ', $missingContent) . "\n";
                $results['missing'][] = $test['name'] . ": " . implode(', ', $missingContent);
            }
            
            // Check if page has actual data
            if (strpos($content, 'infolist') !== false || strpos($content, 'fi-in') !== false) {
                echo "  ✅ Infolist components detected\n";
            } else {
                echo "  ⚠️  No infolist components found\n";
                $results['warnings'][] = $test['name'] . ": No infolist components";
            }
            
            // Check for empty page indicators
            if (strpos($content, 'No content') !== false || 
                strpos($content, 'Empty') !== false ||
                preg_match('/<main[^>]*>\s*<\/main>/i', $content)) {
                echo "  ⚠️  Page might be empty\n";
                $results['empty'][] = $test['name'];
            }
            
        } elseif ($statusCode === 302) {
            echo "  ⚠️  Redirect (not authenticated properly)\n";
            $results['redirects'][] = $test['name'];
        } else {
            echo "  ❌ Error response\n";
            $results['errors'][] = $test['name'] . " (Status: $statusCode)";
        }
        
    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . "\n";
        $results['exceptions'][] = $test['name'] . ": " . $e->getMessage();
    }
    
    echo "\n";
}

// Summary
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                         SUMMARY                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if (isset($results['errors']) || isset($results['exceptions'])) {
    echo "❌ ERRORS:\n";
    if (isset($results['errors'])) {
        foreach ($results['errors'] as $error) {
            echo "  • $error\n";
        }
    }
    if (isset($results['exceptions'])) {
        foreach ($results['exceptions'] as $exception) {
            echo "  • $exception\n";
        }
    }
    echo "\n";
}

if (isset($results['missing'])) {
    echo "❌ MISSING CONTENT:\n";
    foreach ($results['missing'] as $missing) {
        echo "  • $missing\n";
    }
    echo "\n";
}

if (isset($results['empty'])) {
    echo "⚠️  POSSIBLY EMPTY PAGES:\n";
    foreach ($results['empty'] as $empty) {
        echo "  • $empty\n";
    }
    echo "\n";
}

if (isset($results['redirects'])) {
    echo "⚠️  AUTHENTICATION ISSUES:\n";
    foreach ($results['redirects'] as $redirect) {
        echo "  • $redirect\n";
    }
    echo "\n";
}

if (empty($results)) {
    echo "✅ All pages appear to be rendering content correctly!\n";
} else {
    echo "🔧 Issues detected. Pages may need further investigation.\n";
}