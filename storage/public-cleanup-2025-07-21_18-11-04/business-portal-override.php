<?php
/**
 * Business Portal Override - Lädt das echte Portal mit Session
 */

// Laravel Bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Get or create session
$user = \Illuminate\Support\Facades\Auth::guard('portal')->user();
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

// Get the React dashboard view
$view = view('portal.react-dashboard');
$content = $view->render();

// Inject session bridge script BEFORE React loads
$sessionBridge = '
<script>
// Session Bridge - Must run BEFORE React
(function() {
    const userData = ' . json_encode([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'company_id' => $user->company_id,
        'role' => 'user'
    ]) . ';
    
    // Set localStorage before React checks
    localStorage.setItem("portal_user", JSON.stringify(userData));
    localStorage.setItem("auth_token", "session-' . session()->getId() . '");
    localStorage.setItem("portal_session_id", "' . session()->getId() . '");
    
    // Remove demo mode
    localStorage.removeItem("demo_mode");
    delete window.__DEMO_MODE__;
    
    // Override auth check in React
    window.__AUTH_OVERRIDE__ = true;
    window.__AUTH_USER__ = userData;
    
    console.log("✅ Session Bridge Active", userData);
})();
</script>
';

// Insert the script right after <head>
$content = str_replace('</head>', $sessionBridge . '</head>', $content);

// Output the modified content
echo $content;
?>