<?php
// Direct Laravel bootstrap to test session handling
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

// Create kernel and handle request
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$response = $kernel->handle($request);

// Get action parameter
$action = $_GET['action'] ?? 'info';

header('Content-Type: application/json');

switch ($action) {
    case 'info':
        // Show session configuration
        $sessionConfig = config('session');
        $sessionFiles = [];
        
        if ($sessionConfig['driver'] === 'file') {
            $sessionPath = storage_path('framework/sessions');
            if (is_dir($sessionPath)) {
                $files = scandir($sessionPath);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $fullPath = $sessionPath . '/' . $file;
                        $sessionFiles[] = [
                            'file' => $file,
                            'size' => filesize($fullPath),
                            'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
                            'readable' => is_readable($fullPath) ? 'YES' : 'NO',
                            'owner' => posix_getpwuid(fileowner($fullPath))['name'] ?? 'unknown'
                        ];
                    }
                }
            }
        }
        
        echo json_encode([
            'session_driver' => $sessionConfig['driver'],
            'session_lifetime' => $sessionConfig['lifetime'],
            'session_path' => $sessionConfig['path'],
            'session_domain' => $sessionConfig['domain'],
            'session_secure' => $sessionConfig['secure'],
            'session_same_site' => $sessionConfig['same_site'],
            'session_cookie' => $sessionConfig['cookie'],
            'session_http_only' => $sessionConfig['http_only'],
            'session_encrypt' => $sessionConfig['encrypt'],
            'session_table' => $sessionConfig['table'] ?? null,
            'session_id' => session_id(),
            'session_save_path' => ini_get('session.save_path'),
            'session_gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
            'session_cookie_lifetime' => ini_get('session.cookie_lifetime'),
            'session_files' => $sessionFiles,
            'storage_permissions' => [
                'sessions_dir' => is_writable(storage_path('framework/sessions')) ? 'WRITABLE' : 'NOT WRITABLE',
                'sessions_owner' => posix_getpwuid(fileowner(storage_path('framework/sessions')))['name'] ?? 'unknown'
            ]
        ], JSON_PRETTY_PRINT);
        break;
        
    case 'test-write':
        // Test writing to session
        $testKey = 'test_' . time();
        $testValue = 'value_' . uniqid();
        
        session([$testKey => $testValue]);
        $request->session()->save();
        
        // Verify it was written
        $retrieved = session($testKey);
        
        echo json_encode([
            'write_test' => [
                'key' => $testKey,
                'value_written' => $testValue,
                'value_retrieved' => $retrieved,
                'success' => $retrieved === $testValue
            ],
            'session_id' => session()->getId(),
            'all_keys' => array_keys(session()->all())
        ], JSON_PRETTY_PRINT);
        break;
        
    case 'test-auth':
        // Test authentication persistence
        $user = PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();
        
        if ($user) {
            // Clear any existing auth
            Auth::guard('portal')->logout();
            session()->flush();
            session()->regenerate();
            
            // Login user
            Auth::guard('portal')->login($user);
            
            // Set session keys
            session(['portal_user_id' => $user->id]);
            $portalSessionKey = 'login_portal_' . sha1('Illuminate\\Auth\\SessionGuard.portal');
            session([$portalSessionKey => $user->id]);
            
            // Force save
            session()->save();
            
            echo json_encode([
                'auth_test' => [
                    'user_id' => $user->id,
                    'auth_check_immediate' => Auth::guard('portal')->check(),
                    'session_id' => session()->getId(),
                    'portal_user_id_in_session' => session('portal_user_id'),
                    'auth_key_in_session' => session($portalSessionKey),
                    'all_session_keys' => array_keys(session()->all())
                ]
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['error' => 'Demo user not found']);
        }
        break;
        
    case 'check-auth':
        // Check if auth persisted from previous request
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\\Auth\\SessionGuard.portal');
        
        echo json_encode([
            'auth_check' => [
                'auth_guard_check' => Auth::guard('portal')->check(),
                'user' => Auth::guard('portal')->user() ? [
                    'id' => Auth::guard('portal')->user()->id,
                    'email' => Auth::guard('portal')->user()->email
                ] : null,
                'session_id' => session()->getId(),
                'portal_user_id' => session('portal_user_id'),
                'auth_key' => session($portalSessionKey),
                'all_session_keys' => array_keys(session()->all()),
                'cookie_data' => $_COOKIE
            ]
        ], JSON_PRETTY_PRINT);
        break;
        
    default:
        echo json_encode(['error' => 'Unknown action']);
}

// Terminate the kernel
$kernel->terminate($request, $response);