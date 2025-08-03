<?php
/**
 * Test Unified Authentication System
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$results = [
    'timestamp' => now()->toDateTimeString(),
    'status' => 'success',
    'checks' => [],
];

// 1. Check demo user
$demoUser = User::where('email', 'demo@askproai.de')->first();
if ($demoUser) {
    $results['checks']['demo_user'] = [
        'exists' => true,
        'id' => $demoUser->id,
        'is_active' => $demoUser->is_active,
        'portal_role' => $demoUser->portal_role,
        'roles' => $demoUser->getRoleNames()->toArray(),
        'requires_2fa' => $demoUser->requires2FA(),
        'two_factor_enforced' => $demoUser->two_factor_enforced,
        'can_access_business' => $demoUser->canAccessPanel(app('filament')->getPanel('business')),
        'default_url' => $demoUser->getFilamentDefaultUrl(),
    ];
} else {
    $results['checks']['demo_user'] = ['exists' => false];
    $results['status'] = 'error';
}

// 2. Check migrated users count
$results['checks']['migrated_users'] = [
    'total' => User::whereNotNull('portal_role')->count(),
    'by_role' => User::whereNotNull('portal_role')
        ->selectRaw('portal_role, COUNT(*) as count')
        ->groupBy('portal_role')
        ->pluck('count', 'portal_role')
        ->toArray(),
];

// 3. Check roles exist
$requiredRoles = ['company_owner', 'company_admin', 'company_manager', 'company_staff'];
$existingRoles = \Spatie\Permission\Models\Role::whereIn('name', $requiredRoles)->pluck('name')->toArray();
$results['checks']['roles'] = [
    'required' => $requiredRoles,
    'existing' => $existingRoles,
    'missing' => array_diff($requiredRoles, $existingRoles),
];

// 4. Check routes
$routes = [];
foreach (Route::getRoutes() as $route) {
    $name = $route->getName();
    if (in_array($name, ['login', 'logout', 'business.login', 'admin.login'])) {
        $routes[$name] = [
            'uri' => $route->uri(),
            'methods' => $route->methods(),
        ];
    }
}
$results['checks']['routes'] = $routes;

// 5. Test login simulation (without actually logging in)
$results['checks']['login_test'] = [
    'demo_password_valid' => \Hash::check('P4$$w0rd!', $demoUser->password ?? ''),
    'auth_guards' => array_keys(config('auth.guards')),
    'default_guard' => config('auth.defaults.guard'),
];

// Output results
?>
<!DOCTYPE html>
<html>
<head>
    <title>Unified Auth Test</title>
    <style>
        body {
            font-family: 'SF Mono', Monaco, monospace;
            background: #1a1a1a;
            color: #00ff00;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 {
            color: #00ff00;
            text-shadow: 0 0 10px #00ff00;
        }
        .status-success {
            color: #00ff00;
            font-weight: bold;
        }
        .status-error {
            color: #ff4444;
            font-weight: bold;
        }
        .check-section {
            background: #2a2a2a;
            border: 1px solid #00ff00;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .check-title {
            color: #ffff00;
            font-weight: bold;
            margin-bottom: 10px;
        }
        pre {
            background: #0a0a0a;
            padding: 10px;
            overflow: auto;
            border: 1px solid #333;
        }
        .action-buttons {
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #00ff00;
            color: #000;
            text-decoration: none;
            border-radius: 3px;
            margin: 5px;
            font-weight: bold;
        }
        .btn:hover {
            background: #00cc00;
            box-shadow: 0 0 10px #00ff00;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Unified Authentication System Test</h1>
        
        <div class="status-<?php echo $results['status']; ?>">
            Overall Status: <?php echo strtoupper($results['status']); ?>
        </div>
        
        <?php foreach ($results['checks'] as $checkName => $checkData): ?>
        <div class="check-section">
            <div class="check-title"><?php echo ucwords(str_replace('_', ' ', $checkName)); ?></div>
            <pre><?php echo json_encode($checkData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
        </div>
        <?php endforeach; ?>
        
        <div class="action-buttons">
            <a href="/login" class="btn">🔑 Go to Unified Login</a>
            <a href="/admin" class="btn">👨‍💼 Admin Panel</a>
            <a href="/business" class="btn">💼 Business Panel</a>
        </div>
        
        <div class="check-section">
            <div class="check-title">Next Steps</div>
            <ol>
                <li>Click "Go to Unified Login" to test the login page</li>
                <li>Login with: demo@askproai.de / P4$$w0rd!</li>
                <li>You should be redirected to /business panel</li>
                <li>Super admins will be redirected to /admin panel</li>
            </ol>
        </div>
    </div>
</body>
</html>