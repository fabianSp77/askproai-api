<?php
// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$action = $_GET['action'] ?? 'test';

switch ($action) {
    case 'login':
        // Create a proper request
        $request = Request::create('/session-test-login', 'POST');
        
        // Handle the request through the kernel
        $response = $kernel->handle($request);
        
        // Clear any existing auth
        Auth::guard('portal')->logout();
        $request->session()->invalidate();
        $request->session()->regenerate();
        
        // Get demo user
        $user = PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();
        
        if (!$user) {
            $response = new Response(json_encode(['error' => 'User not found']), 404);
            $response->headers->set('Content-Type', 'application/json');
            $response->send();
            $kernel->terminate($request, $response);
            exit;
        }
        
        // Login user
        Auth::guard('portal')->login($user);
        
        // Set session data
        $request->session()->put('portal_user_id', $user->id);
        $portalKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        $request->session()->put($portalKey, $user->id);
        
        // Create response
        $responseData = [
            'login_success' => true,
            'session_id' => $request->session()->getId(),
            'auth_check' => Auth::guard('portal')->check(),
            'portal_user_id' => $request->session()->get('portal_user_id'),
            'cookie_will_be_set' => $request->session()->getName()
        ];
        
        $response = new Response(json_encode($responseData), 200);
        $response->headers->set('Content-Type', 'application/json');
        
        // Send response (this should set the session cookie)
        $response->send();
        
        // Terminate properly
        $kernel->terminate($request, $response);
        exit;
        
    case 'check':
        // Create request
        $request = Request::create('/session-test-check', 'GET');
        
        // Copy cookies from current request
        foreach ($_COOKIE as $name => $value) {
            $request->cookies->set($name, $value);
        }
        
        // Handle request
        $response = $kernel->handle($request);
        
        $portalKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        
        $responseData = [
            'session_id' => $request->session()->getId(),
            'auth_check' => Auth::guard('portal')->check(),
            'portal_user_id' => $request->session()->get('portal_user_id'),
            'auth_key' => $request->session()->get($portalKey),
            'user' => Auth::guard('portal')->user() ? [
                'id' => Auth::guard('portal')->user()->id,
                'email' => Auth::guard('portal')->user()->email
            ] : null,
            'all_session_keys' => array_keys($request->session()->all()),
            'cookie_received' => isset($_COOKIE[$request->session()->getName()]) ? 'YES' : 'NO'
        ];
        
        $response = new Response(json_encode($responseData), 200);
        $response->headers->set('Content-Type', 'application/json');
        $response->send();
        
        $kernel->terminate($request, $response);
        exit;
        
    default:
        echo "Usage: ?action=login or ?action=check";
}