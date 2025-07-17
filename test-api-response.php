<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Simulate an authenticated request
$user = \App\Models\PortalUser::first();
if (!$user) {
    $user = \App\Models\User::first();
}

// Set company context to avoid tenant scope issues
if ($user && $user->company_id) {
    app()->instance('company_id', $user->company_id);
    \App\Scopes\TenantScope::$companyId = $user->company_id;
}

auth()->guard('portal')->login($user);

$controller = new \App\Http\Controllers\Portal\Api\CallsApiController();
$request = new \Illuminate\Http\Request();
$response = $controller->show($request, 262);
$data = json_decode($response->content(), true);

echo "Response structure:\n";
print_r(array_keys($data));
echo "\n\nCall data keys:\n";
if (isset($data['call'])) {
    print_r(array_keys($data['call']));
    echo "\n\nSample call data:\n";
    echo "- id: " . $data['call']['id'] . "\n";
    echo "- from_number: " . $data['call']['from_number'] . "\n";
    echo "- status: " . $data['call']['status'] . "\n";
    echo "- duration_sec: " . $data['call']['duration_sec'] . "\n";
}