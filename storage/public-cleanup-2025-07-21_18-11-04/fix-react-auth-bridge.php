<?php
/**
 * React Auth Bridge - Verbindet PHP Session mit React
 * 
 * Problem: React App erkennt die PHP Session nicht
 * Lösung: Wir setzen die nötigen localStorage Werte
 */

// Laravel Bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Check if user is authenticated
$user = \Illuminate\Support\Facades\Auth::guard('portal')->user();

if (!$user) {
    // If not authenticated, try to login demo user
    $user = \App\Models\PortalUser::withoutGlobalScopes()
        ->where('email', 'demo@askproai.de')
        ->first();
    
    if ($user) {
        \Illuminate\Support\Facades\Auth::guard('portal')->login($user, true);
        session(['portal_authenticated' => true]);
        session(['portal_user_id' => $user->id]);
        session(['portal_company_id' => $user->company_id]);
        session()->regenerate();
        session()->save();
    }
}

$sessionId = session()->getId();
$csrfToken = csrf_token();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🔧 React Auth Bridge - Fixing Authentication</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 600px;
        }
        h1 {
            font-size: 32px;
            margin-bottom: 20px;
        }
        .status {
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-family: monospace;
        }
        .success {
            background: rgba(46, 213, 115, 0.3);
            border: 2px solid #2ed573;
        }
        .btn {
            display: inline-block;
            background: #2ed573;
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            margin: 10px;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(46, 213, 115, 0.4);
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 React Auth Bridge</h1>
        
        <div class="status">
            <p><strong>PHP Session Status:</strong></p>
            <p>✅ Session ID: <?php echo substr($sessionId, 0, 20); ?>...</p>
            <p>✅ User: <?php echo $user ? $user->email : 'Not authenticated'; ?></p>
            <p>✅ User ID: <?php echo $user ? $user->id : 'N/A'; ?></p>
            <p>✅ Company ID: <?php echo $user ? $user->company_id : 'N/A'; ?></p>
        </div>
        
        <div id="react-status" class="status">
            <p><span class="loading"></span> Bereite React Auth vor...</p>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="#" id="go-to-business" class="btn" style="display: none;">
                🚀 Zum Business Portal
            </a>
        </div>
        
        <p style="margin-top: 20px; font-size: 14px; opacity: 0.8;">
            Diese Seite verbindet deine PHP Session mit der React App
        </p>
    </div>
    
    <script>
        <?php if ($user): ?>
        // User data from PHP
        const userData = {
            id: <?php echo $user->id; ?>,
            name: "<?php echo addslashes($user->name ?? 'User'); ?>",
            email: "<?php echo addslashes($user->email); ?>",
            company_id: <?php echo $user->company_id; ?>,
            role: "<?php echo addslashes($user->role ?? 'user'); ?>"
        };
        
        // Session data
        const sessionData = {
            sessionId: "<?php echo $sessionId; ?>",
            csrfToken: "<?php echo $csrfToken; ?>",
            timestamp: Date.now()
        };
        
        console.log('🔧 Setting up React Auth Bridge...');
        
        // 1. Clear old data
        localStorage.removeItem('demo_mode');
        delete window.__DEMO_MODE__;
        
        // 2. Set user data for React
        localStorage.setItem('portal_user', JSON.stringify(userData));
        localStorage.setItem('portal_session', JSON.stringify(sessionData));
        localStorage.setItem('auth_token', 'session-bridge-' + sessionData.sessionId);
        
        // 3. Set cookies if possible (might be httpOnly)
        document.cookie = `portal_user_id=${userData.id}; path=/; SameSite=Lax`;
        document.cookie = `portal_authenticated=true; path=/; SameSite=Lax`;
        
        // 4. Update status
        document.getElementById('react-status').innerHTML = `
            <p style="color: #2ed573;">✅ React Auth vorbereitet!</p>
            <p>User: ${userData.email}</p>
            <p>Session Bridge aktiv</p>
        `;
        document.getElementById('react-status').classList.add('success');
        
        // 5. Show button
        document.getElementById('go-to-business').style.display = 'inline-block';
        
        // 6. Auto redirect after 3 seconds
        setTimeout(() => {
            console.log('🚀 Redirecting to Business Portal...');
            window.location.href = '/business';
        }, 3000);
        
        // Button click handler
        document.getElementById('go-to-business').onclick = function(e) {
            e.preventDefault();
            window.location.href = '/business';
        };
        
        console.log('✅ Bridge setup complete!', {
            user: userData,
            session: sessionData,
            localStorage: {
                portal_user: localStorage.getItem('portal_user'),
                auth_token: localStorage.getItem('auth_token')
            }
        });
        
        <?php else: ?>
        // No user authenticated
        document.getElementById('react-status').innerHTML = `
            <p style="color: #ff6b6b;">❌ Keine aktive Session gefunden</p>
            <p>Bitte erst einloggen</p>
        `;
        <?php endif; ?>
    </script>
</body>
</html>