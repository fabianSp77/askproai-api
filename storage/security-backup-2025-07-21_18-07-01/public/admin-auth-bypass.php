<?php
// Bypass authentication for testing admin pages
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a custom request with session ID
$request = Illuminate\Http\Request::create('/admin/calls', 'GET');
$request->headers->set('Cookie', 'askproai_session=JOEXlBmJRdX2bjveR0H4tHbXWyKpKFSGBNNWtsTW');

// Process the request
$response = $kernel->handle($request);

// Force authentication bypass
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();
if ($user) {
    Auth::login($user);
    
    // Set the session data directly
    session()->setId('JOEXlBmJRdX2bjveR0H4tHbXWyKpKFSGBNNWtsTW');
    session()->put('login.web', $user->id);
    session()->put('password_hash_web', $user->getAuthPassword());
    session()->save();
}

// Now handle the actual admin request
$adminRequest = Request::create('/admin/calls', 'GET');
$adminRequest->setLaravelSession(session());

// Process through Filament
$response = $kernel->handle($adminRequest);

// Output the response
echo $response->getContent();
?>