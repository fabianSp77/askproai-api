<?php

/**
 * REAL E2E TEST: Visit EVERY admin page and check for errors
 * This actually makes HTTP requests to pages (not just instantiates classes)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== E2E ADMIN PANEL TEST ===\n";
echo "Visiting EVERY actual admin page\n\n";

// Login as admin
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
if (!$user) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

// Create authenticated session
auth()->login($user);
$token = $user->createToken('test')->plainTextToken;

echo "✅ Logged in as: {$user->email}\n\n";

$results = [
    'pages' => [],
    'resources' => [],
    'errors' => [],
];

// ============================================
// PART 1: Test Custom Pages
// ============================================
echo "=== PART 1: TESTING CUSTOM PAGES (HTTP Requests) ===\n\n";

$customPages = [
    'Dashboard' => '/admin',
    'Appointments' => '/admin/appointments',
    'Calls' => '/admin/calls',
    'Customers' => '/admin/customers',
    'Staff' => '/admin/staff',
    'Services' => '/admin/services',
    'Branches' => '/admin/branches',
    'Companies' => '/admin/companies',
    'Users' => '/admin/users',
    'PhoneNumbers' => '/admin/phone-numbers',
    'CallbackRequests' => '/admin/callback-requests',
    'Policies' => '/admin/policy-configurations',
    'RetellCallSessions' => '/admin/retell-call-sessions',
    'CalcomEventMaps' => '/admin/calcom-event-maps',
    'ProfitDashboard' => '/admin/profit-dashboard',
    'SystemAdministration' => '/admin/system-administration',
    'SystemTestingDashboard' => '/admin/system-testing-dashboard',
];

foreach ($customPages as $name => $url) {
    echo str_pad($name, 40, ' ');

    try {
        // Make actual HTTP request
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Cookie' => 'askpro_ai_gateway_session=' . session()->getId(),
        ])->get('http://localhost' . $url);

        if ($response->successful()) {
            echo "✅ OK (HTTP {$response->status()})\n";
            $results['pages'][$name] = ['status' => 'ok', 'url' => $url];
        } else {
            echo "❌ HTTP {$response->status()}\n";
            $results['pages'][$name] = ['status' => 'http_error', 'code' => $response->status(), 'url' => $url];
            $results['errors'][] = "{$name}: HTTP {$response->status()} at {$url}";
        }

    } catch (\Exception $e) {
        $errorMsg = $e->getMessage();

        if (str_contains($errorMsg, "Column not found") || str_contains($errorMsg, "Unknown column")) {
            preg_match("/Unknown column '(.*?)'/", $errorMsg, $matches);
            $column = $matches[1] ?? 'unknown';
            echo "❌ MISSING COLUMN: {$column}\n";
            $results['pages'][$name] = ['status' => 'missing_column', 'column' => $column, 'url' => $url];
            $results['errors'][] = "{$name}: Missing column '{$column}' at {$url}";
        } elseif (str_contains($errorMsg, "Table") && str_contains($errorMsg, "doesn't exist")) {
            preg_match("/Table '.*?\\.(\\w+)' doesn't exist/", $errorMsg, $matches);
            $table = $matches[1] ?? 'unknown';
            echo "❌ MISSING TABLE: {$table}\n";
            $results['pages'][$name] = ['status' => 'missing_table', 'table' => $table, 'url' => $url];
            $results['errors'][] = "{$name}: Missing table '{$table}' at {$url}";
        } else {
            echo "❌ ERROR\n";
            echo "   " . substr($errorMsg, 0, 100) . "\n";
            $results['pages'][$name] = ['status' => 'error', 'error' => substr($errorMsg, 0, 200), 'url' => $url];
            $results['errors'][] = "{$name}: " . substr($errorMsg, 0, 100);
        }
    }
}

// ============================================
// SUMMARY
// ============================================
echo "\n=== SUMMARY ===\n\n";

$pagesOk = count(array_filter($results['pages'], fn($r) => $r['status'] === 'ok'));
$pagesFail = count($results['pages']) - $pagesOk;

echo "PAGES TESTED:\n";
echo "  ✅ Working: {$pagesOk}/" . count($customPages) . "\n";
echo "  ❌ Failed: {$pagesFail}\n\n";

if (!empty($results['errors'])) {
    echo "=== ERRORS FOUND ===\n";
    foreach ($results['errors'] as $error) {
        echo "• {$error}\n";
    }
    echo "\n";
}

// Save results
file_put_contents('e2e_admin_test_results.json', json_encode($results, JSON_PRETTY_PRINT));
echo "Detailed results saved to: e2e_admin_test_results.json\n";

exit(count($results['errors']) > 0 ? 1 : 0);
