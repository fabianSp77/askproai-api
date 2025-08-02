<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/test', 'GET');
$response = $kernel->handle($request);
$kernel->bootstrap();

echo "=== FIXING LOGIN SCOPE ISSUE ===\n\n";

// 1. Test with scope
echo "1. WITH SCOPE:\n";
$userWithScope = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
echo "User found: " . ($userWithScope ? "YES (ID: {$userWithScope->id})" : "NO") . "\n\n";

// 2. Test without scope
echo "2. WITHOUT SCOPE:\n";
$userWithoutScope = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->first();
echo "User found: " . ($userWithoutScope ? "YES (ID: {$userWithoutScope->id})" : "NO") . "\n\n";

// 3. Check what scopes are applied
echo "3. GLOBAL SCOPES:\n";
$scopes = (new \App\Models\PortalUser)->getGlobalScopes();
foreach ($scopes as $name => $scope) {
    echo "  - $name: " . get_class($scope) . "\n";
}
echo "\n";

// 4. Solution - update login route to bypass scope
echo "4. SOLUTION:\n";
echo "The issue is that PortalUser has a company scope applied.\n";
echo "When accessed from a public PHP file, there's no company context.\n";
echo "The login endpoint needs to bypass this scope.\n\n";

// 5. Test auth without scope
echo "5. TEST AUTH WITHOUT SCOPE:\n";
$user = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->first();

if ($user) {
    echo "User found: {$user->email}\n";
    echo "Password check: " . (\Illuminate\Support\Facades\Hash::check('demo123', $user->password) ? 'VALID' : 'INVALID') . "\n";
    
    // Force login
    \Illuminate\Support\Facades\Auth::guard('portal')->login($user);
    echo "Login forced: " . (\Illuminate\Support\Facades\Auth::guard('portal')->check() ? 'SUCCESS' : 'FAILED') . "\n";
}

$kernel->terminate($request, $response);