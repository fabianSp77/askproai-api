<?php
// Direct login fix
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Clear all session errors and old input
session()->forget('errors');
session()->forget('_old_input');
session()->forget('_flash');
session()->forget('url.intended');

// Clear flash data
if (session()->has('_flash')) {
    $flash = session()->get('_flash');
    if (isset($flash['old'])) {
        foreach ($flash['old'] as $key) {
            session()->forget($key);
        }
    }
}

// Attempt login with demo credentials
$user = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->where('is_active', 1)
    ->first();

if ($user) {
    // Login the user
    auth()->guard('portal')->login($user);
    session()->save();
    
    // Set success message
    session()->flash('success', 'Successfully logged in!');
    
    // Redirect to dashboard
    header('Location: /business/dashboard');
    exit;
} else {
    echo "Demo user not found or inactive.";
}

$kernel->terminate($request, $response);