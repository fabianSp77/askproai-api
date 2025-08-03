<?php
/**
 * Business Portal Authentication Fix
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

// Action handling
$action = $_REQUEST['action'] ?? '';
$result = ['status' => 'none', 'message' => ''];

switch ($action) {
    case 'logout_all':
        Auth::guard('web')->logout();
        Auth::guard('portal')->logout();
        Session::flush();
        Session::regenerate();
        $result = ['status' => 'success', 'message' => 'Alle Sessions wurden beendet.'];
        break;
        
    case 'login_demo':
        // Logout from web guard first
        Auth::guard('web')->logout();
        
        $user = PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('email', 'demo@askproai.de')
            ->first();
            
        if ($user && $user->is_active) {
            Auth::guard('portal')->login($user);
            Session::regenerate();
            $result = ['status' => 'success', 'message' => 'Erfolgreich angemeldet!'];
        } else {
            $result = ['status' => 'error', 'message' => 'Demo-User nicht gefunden.'];
        }
        break;
}

// Current status
$status = [
    'web' => [
        'authenticated' => Auth::guard('web')->check(),
        'user' => Auth::guard('web')->user() ? [
            'id' => Auth::guard('web')->id(),
            'email' => Auth::guard('web')->user()->email
        ] : null
    ],
    'portal' => [
        'authenticated' => Auth::guard('portal')->check(),
        'user' => Auth::guard('portal')->user() ? [
            'id' => Auth::guard('portal')->id(),
            'email' => Auth::guard('portal')->user()->email
        ] : null
    ],
    'session' => [
        'id' => session()->getId(),
        'keys' => array_keys(session()->all())
    ]
];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Authentication Fix</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header h1 {
            font-size: 2.5rem;
            margin: 0;
            background: linear-gradient(to right, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .card h2 {
            margin: 0 0 1rem 0;
            font-size: 1.25rem;
            color: #f1f5f9;
        }
        .status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .status-indicator.active {
            background: #10b981;
            box-shadow: 0 0 8px #10b981;
        }
        .status-indicator.inactive {
            background: #ef4444;
            box-shadow: 0 0 8px #ef4444;
        }
        .info {
            font-size: 0.875rem;
            color: #94a3b8;
            margin-left: 1.25rem;
        }
        .actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid;
        }
        .alert-success {
            background: #065f46;
            border-color: #10b981;
            color: #d1fae5;
        }
        .alert-error {
            background: #7f1d1d;
            border-color: #ef4444;
            color: #fee2e2;
        }
        .code-block {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 0.5rem;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.875rem;
            overflow-x: auto;
            margin-top: 1rem;
        }
        .highlight {
            color: #60a5fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Portal Authentication Manager</h1>
            <p style="color: #94a3b8;">Manage your authentication sessions</p>
        </div>
        
        <?php if ($result['message']): ?>
            <div class="alert alert-<?php echo $result['status'] === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($result['message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid">
            <div class="card">
                <h2>🏢 Admin Portal (Web Guard)</h2>
                <div class="status">
                    <span class="status-indicator <?php echo $status['web']['authenticated'] ? 'active' : 'inactive'; ?>"></span>
                    <span><?php echo $status['web']['authenticated'] ? 'Authenticated' : 'Not Authenticated'; ?></span>
                </div>
                <?php if ($status['web']['user']): ?>
                    <div class="info">
                        User: <?php echo htmlspecialchars($status['web']['user']['email']); ?><br>
                        ID: <?php echo $status['web']['user']['id']; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>💼 Business Portal (Portal Guard)</h2>
                <div class="status">
                    <span class="status-indicator <?php echo $status['portal']['authenticated'] ? 'active' : 'inactive'; ?>"></span>
                    <span><?php echo $status['portal']['authenticated'] ? 'Authenticated' : 'Not Authenticated'; ?></span>
                </div>
                <?php if ($status['portal']['user']): ?>
                    <div class="info">
                        User: <?php echo htmlspecialchars($status['portal']['user']['email']); ?><br>
                        ID: <?php echo $status['portal']['user']['id']; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="actions">
            <?php if (!$status['portal']['authenticated']): ?>
                <a href="?action=login_demo" class="btn btn-success">
                    ✅ Login as demo@askproai.de
                </a>
            <?php else: ?>
                <a href="/business/dashboard" class="btn btn-success">
                    📊 Go to Dashboard
                </a>
            <?php endif; ?>
            
            <a href="?action=logout_all" class="btn btn-danger">
                🚪 Logout All Sessions
            </a>
            
            <a href="/business/login" class="btn btn-primary">
                🔑 Business Login Page
            </a>
        </div>
        
        <div class="card" style="margin-top: 2rem;">
            <h2>📋 Session Information</h2>
            <div class="code-block">
                <div>Session ID: <span class="highlight"><?php echo substr($status['session']['id'], 0, 32); ?>...</span></div>
                <div>Session Keys: <span class="highlight"><?php echo implode(', ', array_slice($status['session']['keys'], 0, 5)); ?>...</span></div>
                <div>Portal Cookie: <span class="highlight"><?php echo isset($_COOKIE['askproai_portal_session']) ? 'Set' : 'Not Set'; ?></span></div>
            </div>
        </div>
        
        <div class="card" style="margin-top: 1rem;">
            <h2>💡 Quick Help</h2>
            <ul style="color: #94a3b8; margin: 0; padding-left: 1.5rem;">
                <li>You cannot be logged into both portals simultaneously</li>
                <li>To access Business Portal, logout from Admin Portal first</li>
                <li>Use "Logout All Sessions" to start fresh</li>
                <li>Demo credentials: demo@askproai.de / password</li>
            </ul>
        </div>
    </div>
</body>
</html>