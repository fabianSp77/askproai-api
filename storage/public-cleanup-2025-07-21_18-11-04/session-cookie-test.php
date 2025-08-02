<?php
// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$response = $kernel->handle($request);

$action = $_GET['action'] ?? 'info';

header('Content-Type: application/json');

switch ($action) {
    case 'set-test-session':
        // Set a test value in session
        session(['test_value' => 'Hello from ' . time()]);
        session()->save();
        
        echo json_encode([
            'action' => 'set',
            'session_id' => session()->getId(),
            'test_value' => session('test_value'),
            'session_name' => session()->getName(),
            'cookie_sent' => isset($_COOKIE[session()->getName()]) ? 'YES' : 'NO',
            'headers' => headers_list()
        ]);
        break;
        
    case 'get-test-session':
        echo json_encode([
            'action' => 'get',
            'session_id' => session()->getId(),
            'test_value' => session('test_value'),
            'session_name' => session()->getName(),
            'cookie_received' => isset($_COOKIE[session()->getName()]) ? 'YES' : 'NO',
            'cookie_value' => $_COOKIE[session()->getName()] ?? null
        ]);
        break;
        
    case 'login-with-response':
        // Clear session
        Auth::guard('portal')->logout();
        session()->flush();
        
        // Get demo user
        $user = PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();
        
        if (!$user) {
            echo json_encode(['error' => 'User not found']);
            break;
        }
        
        // Login user
        Auth::guard('portal')->login($user);
        
        // Set session data
        session(['portal_user_id' => $user->id]);
        $portalKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        session([$portalKey => $user->id]);
        
        // Create response that will send cookies
        $jsonResponse = response()->json([
            'login_success' => true,
            'session_id' => session()->getId(),
            'auth_check' => Auth::guard('portal')->check(),
            'portal_user_id' => session('portal_user_id'),
            'session_name' => session()->getName(),
            'headers_before_send' => headers_list()
        ]);
        
        // Send the response (this should set cookies)
        $jsonResponse->send();
        
        // Terminate
        $kernel->terminate($request, $jsonResponse);
        exit;
        
    case 'raw-session':
        // Check raw PHP session
        session_start();
        echo json_encode([
            'php_session_id' => session_id(),
            'php_session_name' => session_name(),
            'php_session_data' => $_SESSION,
            'laravel_session_id' => session()->getId(),
            'laravel_session_data' => session()->all()
        ]);
        break;
}

$kernel->terminate($request, $response);