<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Try multiple users
$emails = ['testuser@askproai.de', 'admin+1@askproai.de', 'demo@askproai.de'];
$user = null;

foreach ($emails as $email) {
    $user = PortalUser::where('email', $email)->where('is_active', true)->first();
    if ($user) {
        break;
    }
}

if (!$user) {
    // Create a user on the fly
    $user = PortalUser::create([
        'email' => 'quicktest@askproai.de',
        'password' => \Illuminate\Support\Facades\Hash::make('test123'),
        'name' => 'Quick Test User',
        'company_id' => 1,
        'is_active' => true,
        'role' => 'admin',
        'permissions' => json_encode(['calls.view_all' => true, 'billing.view' => true])
    ]);
}

// Force login
Auth::guard('portal')->login($user);

// Set session
Session::put('portal_user_id', $user->id);
Session::save();

// Debug info
echo "<h2>Login Successful!</h2>";
echo "<p>User: {$user->email}</p>";
echo "<p>Company: {$user->company->name}</p>";
echo "<p>Redirecting to dashboard in 2 seconds...</p>";
echo "<script>setTimeout(function() { window.location.href = '/business/dashboard'; }, 2000);</script>";

// Also provide direct links
echo "<hr>";
echo "<p>Or click here:</p>";
echo "<ul>";
echo "<li><a href='/business/dashboard'>Dashboard</a></li>";
echo "<li><a href='/business/calls'>Calls (Test Features Here)</a></li>";
echo "<li><a href='/business/billing'>Billing (Test Stripe)</a></li>";
echo "</ul>";