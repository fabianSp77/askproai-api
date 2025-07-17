<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Start session with the known ID
$session = $app->make('session');
$session->setId('C6nQlCHy6lIuZZPGtvoRBaRPu3Qqsuc27HbJZRiZ');
$session->start();

echo "Session Data:\n";
print_r($session->all());

// Check authentication
$portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
$portalUserId = $session->get($portalSessionKey) ?? $session->get('portal_user_id');
$webUserId = $session->get('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d');
$isAdminViewing = $session->get('is_admin_viewing');

echo "\n\nAuthentication Status:\n";
echo "Portal User ID: " . $portalUserId . "\n";
echo "Web User ID: " . $webUserId . "\n";
echo "Is Admin Viewing: " . ($isAdminViewing ? 'Yes' : 'No') . "\n";

// Get the company
if ($portalUserId) {
    $user = \App\Models\PortalUser::find($portalUserId);
    echo "Portal User: " . ($user ? $user->email : 'Not found') . "\n";
    echo "Company: " . ($user && $user->company ? $user->company->name : 'Not found') . "\n";
} elseif ($webUserId && $isAdminViewing) {
    $adminImpersonation = $session->get('admin_impersonation');
    $companyId = $adminImpersonation['company_id'] ?? null;
    if ($companyId) {
        $company = \App\Models\Company::find($companyId);
        echo "Admin viewing company: " . ($company ? $company->name : 'Not found') . "\n";
    }
}

echo "\n\nTesting API endpoints:\n";

// Test billing endpoint
$request = Illuminate\Http\Request::create(
    '/business/api/billing',
    'GET',
    [],
    ['askproai_session' => 'C6nQlCHy6lIuZZPGtvoRBaRPu3Qqsuc27HbJZRiZ'],
    [],
    ['HTTP_ACCEPT' => 'application/json']
);

// Set the session on the request
$request->setLaravelSession($session);

$response = $kernel->handle($request);
echo "Billing API Response: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() !== 200) {
    echo "Response: " . $response->getContent() . "\n";
}
