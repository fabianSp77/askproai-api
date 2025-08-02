<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;
use App\Models\User;

echo "=== Middleware Execution Debug ===\n\n";

// Find admin user
$adminUser = User::where('email', 'fabian@askproai.de')->first();
if (!$adminUser) {
    $adminUser = User::whereNotNull('company_id')->first();
}

echo "User: {$adminUser->email} (Company ID: {$adminUser->company_id})\n\n";

// Manually login
Auth::login($adminUser);

// Test 1: Direct middleware call
echo "=== Test 1: Direct Middleware Call ===\n";
$request = Illuminate\Http\Request::create('/admin/calls', 'GET');
app()->forgetInstance('current_company_id');
app()->forgetInstance('company_context_source');

$middleware = new \App\Http\Middleware\ForceCompanyContext();
$response = $middleware->handle($request, function($req) {
    echo "Inside next closure:\n";
    echo "- current_company_id: " . (app()->has('current_company_id') ? app('current_company_id') : 'NOT SET') . "\n";
    echo "- company_context_source: " . (app()->has('company_context_source') ? app('company_context_source') : 'NOT SET') . "\n";
    return new \Illuminate\Http\Response('OK');
});

echo "\nAfter middleware:\n";
echo "- current_company_id: " . (app()->has('current_company_id') ? app('current_company_id') : 'NOT SET') . "\n";
echo "- company_context_source: " . (app()->has('company_context_source') ? app('company_context_source') : 'NOT SET') . "\n";

// Test 2: Full request cycle
echo "\n\n=== Test 2: Full Request Cycle ===\n";
app()->forgetInstance('current_company_id');
app()->forgetInstance('company_context_source');

// Create a test request with session
$request = Illuminate\Http\Request::create('/admin/calls', 'GET');
$request->setLaravelSession(app('session.store'));
$request->setUserResolver(function() use ($adminUser) {
    return $adminUser;
});

// Manually set auth
Auth::guard('web')->setUser($adminUser);

echo "Before kernel handle:\n";
echo "- Auth check: " . (Auth::check() ? 'YES' : 'NO') . "\n";
echo "- Auth user: " . (Auth::user() ? Auth::user()->email : 'NULL') . "\n";
echo "- current_company_id: " . (app()->has('current_company_id') ? app('current_company_id') : 'NOT SET') . "\n";

$response = $kernel->handle($request);

echo "\nAfter kernel handle:\n";
echo "- current_company_id: " . (app()->has('current_company_id') ? app('current_company_id') : 'NOT SET') . "\n";
echo "- company_context_source: " . (app()->has('company_context_source') ? app('company_context_source') : 'NOT SET') . "\n";

// Test 3: Check middleware order
echo "\n\n=== Test 3: Middleware Stack ===\n";
$router = app('router');
$route = $router->getRoutes()->match($request);
$middleware = $route->gatherMiddleware();
echo "Middleware for /admin/calls:\n";
foreach ($middleware as $m) {
    echo "- $m\n";
}

echo "\n=== Done ===\n";