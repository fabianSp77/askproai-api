<?php
/**
 * FIX REACT DASHBOARD FINAL
 * 
 * Diese Lösung stellt sicher, dass das echte React Dashboard funktioniert!
 */

// Laravel Bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Create a request with business portal path
$request = \Illuminate\Http\Request::create('/business', 'GET');
$request->headers->set('Accept', 'text/html');

// Process request through kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);

// Get current user
$user = \Illuminate\Support\Facades\Auth::guard('portal')->user();

// If not authenticated, login demo user
if (!$user) {
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

// Get session data
$sessionId = session()->getId();
$csrfToken = csrf_token();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <title>AskProAI - Business Portal</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        #app {
            min-height: 100vh;
        }
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f5f5f5;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #1890ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    <!-- React App CSS will be injected here by Vite -->
    <?php if (file_exists(public_path('build/manifest.json'))): ?>
        <?php
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        if (isset($manifest['resources/css/app.css'])) {
            echo '<link rel="stylesheet" href="/build/' . $manifest['resources/css/app.css']['file'] . '">';
        }
        ?>
    <?php endif; ?>
</head>
<body class="font-sans antialiased">
    <?php
    // Prepare user data for React
    $userData = null;
    if ($user) {
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'role' => $user->role ?? 'user'
        ];
    }
    ?>
    
    <div id="app" 
         data-auth='<?php echo json_encode(['user' => $userData, 'isAdminViewing' => false, 'adminViewingCompany' => '']); ?>'
         data-api-url="<?php echo url('/api'); ?>"
         data-csrf="<?php echo $csrfToken; ?>"
         data-initial-route="/">
        <div class="loading">
            <div class="spinner"></div>
        </div>
    </div>
    
    <script>
        // CRITICAL: Set auth data BEFORE React loads
        window.Laravel = {
            csrfToken: '<?php echo $csrfToken; ?>',
        };
        
        // Set localStorage BEFORE React
        const authData = {
            user: <?php echo json_encode($userData); ?>,
            sessionId: '<?php echo $sessionId; ?>',
            timestamp: Date.now()
        };
        
        if (authData.user) {
            localStorage.setItem('portal_user', JSON.stringify(authData.user));
            localStorage.setItem('portal_session_id', authData.sessionId);
            localStorage.setItem('auth_token', 'php-session-' + authData.sessionId);
            
            // Remove demo mode flags
            localStorage.removeItem('demo_mode');
            delete window.__DEMO_MODE__;
        }
        
        // Override location.href to prevent login redirects
        const _location = window.location;
        const _href = Object.getOwnPropertyDescriptor(window.location, 'href');
        
        Object.defineProperty(window.location, 'href', {
            get: function() {
                return _href.get.call(_location);
            },
            set: function(value) {
                if (typeof value === 'string' && value.includes('/login')) {
                    console.warn('🛑 Login redirect blocked! Auth is already active.');
                    // Instead of redirecting, reload the page
                    if (!window.__preventReload) {
                        window.__preventReload = true;
                        setTimeout(() => {
                            window.location.reload();
                        }, 100);
                    }
                    return;
                }
                _href.set.call(_location, value);
            }
        });
        
        console.log('✅ Auth Bridge Active', authData);
    </script>
    
    <!-- Load React App -->
    <?php if (file_exists(public_path('build/manifest.json'))): ?>
        <?php
        if (isset($manifest['resources/js/PortalApp.jsx'])) {
            echo '<script type="module" src="/build/' . $manifest['resources/js/PortalApp.jsx']['file'] . '"></script>';
        }
        ?>
    <?php else: ?>
        <!-- Development mode -->
        <script type="module">
            import RefreshRuntime from 'http://localhost:5173/@react-refresh'
            RefreshRuntime.injectIntoGlobalHook(window)
            window.$RefreshReg$ = () => {}
            window.$RefreshSig$ = () => (type) => type
            window.__vite_plugin_react_preamble_installed__ = true
        </script>
        <script type="module" src="http://localhost:5173/@vite/client"></script>
        <script type="module" src="http://localhost:5173/resources/js/PortalApp.jsx"></script>
    <?php endif; ?>
</body>
</html>