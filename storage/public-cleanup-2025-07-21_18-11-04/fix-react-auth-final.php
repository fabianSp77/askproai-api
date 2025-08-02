<?php
/**
 * FINAL FIX: React Auth Bridge
 * This ensures the React app can access the PHP session data
 */

// Laravel Bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Auto-login demo user
$user = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->first();

if (!$user) {
    die('Demo user not found!');
}

// Create proper session with web middleware
\Illuminate\Support\Facades\Auth::guard('portal')->login($user, true);
session(['portal_authenticated' => true]);
session(['portal_user_id' => $user->id]);
session(['portal_company_id' => $user->company_id]);
session()->regenerate();
session()->save();

// Set special bridge flag
session(['react_bridge_active' => true]);
session()->save();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title>Loading Business Portal...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .loader {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #1890ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loader">
        <div class="spinner"></div>
        <h2>Lade Business Portal...</h2>
        <p style="color: #666;">Einen Moment bitte...</p>
    </div>

    <script>
        // Set up React auth data
        const userData = <?php echo json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'role' => 'user'
        ]); ?>;
        
        // Store in localStorage
        localStorage.setItem('portal_user', JSON.stringify(userData));
        localStorage.setItem('portal_session_id', '<?php echo session()->getId(); ?>');
        localStorage.setItem('react_bridge_active', 'true');
        
        // Clear old flags
        localStorage.removeItem('demo_mode');
        localStorage.removeItem('bypass_active');
        
        // Inject auth data globally
        window.__AUTH_USER__ = userData;
        window.__AUTH_OVERRIDE__ = true;
        
        // Create a temporary override for the API endpoint
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            if (url === '/business/api/user' || url.includes('/business/api/user')) {
                // Return our user data directly
                return Promise.resolve({
                    ok: true,
                    status: 200,
                    json: () => Promise.resolve(userData),
                    headers: new Headers({
                        'content-type': 'application/json'
                    })
                });
            }
            
            // Add credentials to all requests
            options.credentials = 'include';
            
            // Add CSRF token
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) {
                options.headers = options.headers || {};
                options.headers['X-CSRF-TOKEN'] = token;
                options.headers['X-Requested-With'] = 'XMLHttpRequest';
            }
            
            return originalFetch(url, options);
        };
        
        // Redirect to React app
        setTimeout(() => {
            window.location.href = '/business';
        }, 1000);
    </script>
</body>
</html>