<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;

// Force login
$user = PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();
if ($user) {
    Auth::guard('portal')->login($user);
    session(['portal_authenticated' => true]);
    session(['portal_user_id' => $user->id]);
    session()->regenerate();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>AskProAI - Business Portal</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/build/assets/app-CxN0OuGD.css">
    
    <script>
        // CRITICAL: Set auth before React loads
        window.__PRELOADED_STATE__ = {
            auth: {
                isAuthenticated: true,
                user: {
                    id: <?= $user->id ?>,
                    name: "<?= $user->name ?>",
                    email: "<?= $user->email ?>",
                    company_id: <?= $user->company_id ?>,
                    role: "admin"
                }
            }
        };
        
        // Override fetch to always return authenticated
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            if (url.includes('/api/user') || url.includes('/business/api/user')) {
                return Promise.resolve({
                    ok: true,
                    status: 200,
                    json: () => Promise.resolve(window.__PRELOADED_STATE__.auth.user),
                    headers: new Headers({'content-type': 'application/json'})
                });
            }
            return originalFetch(url, options);
        };
    </script>
</head>
<body>
    <div id="app" 
         data-auth='<?= json_encode(['user' => [
             'id' => $user->id,
             'name' => $user->name,
             'email' => $user->email,
             'company_id' => $user->company_id,
             'role' => 'admin'
         ]]) ?>'
         data-api-url="/api"
         data-csrf="<?= csrf_token() ?>"
         data-initial-route="/">
        <div style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
            <p>Lade Business Portal...</p>
        </div>
    </div>
    
    <script>
        window.Laravel = { csrfToken: '<?= csrf_token() ?>' };
    </script>
    
    <!-- Load React with PortalApp bundle -->
    <script type="module">
        import '/build/assets/PortalApp-BbJO6cPj.js';
        console.log('PortalApp loaded');
    </script>
</body>
</html>