<?php
// Instant Business Portal Access
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

// Get view content
$viewContent = view('portal.react-dashboard-production')->render();

// Inject immediate auth state
$authScript = '
<script>
// Immediate auth state injection
window.__INITIAL_AUTH_STATE__ = {
    authenticated: true,
    user: {
        id: 41,
        name: "Demo User",
        email: "demo@askproai.de",
        company_id: 1,
        role: "admin"
    }
};

// Override auth checks
const originalFetch = window.fetch;
window.fetch = function(url, options = {}) {
    if (url.includes("/api/user") || url.includes("/business/api/user")) {
        return Promise.resolve({
            ok: true,
            status: 200,
            json: () => Promise.resolve(window.__INITIAL_AUTH_STATE__.user),
            headers: new Headers({"content-type": "application/json"})
        });
    }
    return originalFetch.apply(this, arguments);
};

// Set localStorage
localStorage.setItem("portal_auth", "true");
localStorage.setItem("portal_user", JSON.stringify(window.__INITIAL_AUTH_STATE__.user));
</script>
';

// Inject script before </head>
$viewContent = str_replace('</head>', $authScript . '</head>', $viewContent);

// Fix React bundle path - use the correct app-react-simple bundle
$viewContent = str_replace('PortalApp-BE0B4vCp.js', 'app-react-simple-DPGvTkrt.js', $viewContent);
$viewContent = str_replace('app-react-simple-C83LNQFJ.js', 'app-react-simple-DPGvTkrt.js', $viewContent);

echo $viewContent;
