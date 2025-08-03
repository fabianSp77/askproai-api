<?php
/**
 * Fix Demo User 2FA Issue
 * Problem: Demo user has admin role which requires 2FA, but 2FA routes are missing
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\PortalUser;
use Illuminate\Support\Facades\DB;

$action = $_GET['action'] ?? '';
$result = '';

// Get demo user
$user = PortalUser::where('email', 'demo@askproai.de')->first();

if (!$user) {
    die('Demo user not found!');
}

// Handle actions
switch ($action) {
    case 'change_role':
        $user->role = 'staff'; // Change from admin to staff to bypass 2FA requirement
        $user->save();
        $result = "✅ Changed demo user role from '{$user->getOriginal('role')}' to 'staff'";
        break;
        
    case 'disable_2fa':
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_enforced = false;
        $user->save();
        $result = "✅ Disabled 2FA for demo user";
        break;
        
    case 'both':
        $oldRole = $user->role;
        $user->role = 'staff';
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_enforced = false;
        $user->save();
        $result = "✅ Changed role from '$oldRole' to 'staff' AND disabled 2FA";
        break;
}

// Current status
$currentStatus = [
    'email' => $user->email,
    'role' => $user->role,
    'requires_2fa' => $user->requires2FA() ? 'YES' : 'NO',
    'has_2fa_secret' => $user->two_factor_secret ? 'YES' : 'NO',
    'two_factor_enforced' => $user->two_factor_enforced ? 'YES' : 'NO',
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Demo User 2FA Issue</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { color: #333; margin-top: 0; }
        .status {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-family: monospace;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.2s;
        }
        .btn:hover { background: #2563eb; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        .result {
            padding: 15px;
            background: #d1fae5;
            color: #065f46;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .problem {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { font-weight: 600; color: #6b7280; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔧 Fix Demo User 2FA Issue</h1>
        
        <div class="problem">
            <strong>Problem:</strong> Der Demo-User (demo@askproai.de) hat die Rolle "admin", welche automatisch 2FA erfordert. 
            Die benötigten 2FA-Setup-Routen existieren jedoch nicht, was zu einem Redirect-Loop führt.
        </div>
        
        <?php if ($result): ?>
            <div class="result"><?php echo $result; ?></div>
        <?php endif; ?>
        
        <div class="status">
            <h3>Current Status:</h3>
            <table>
                <?php foreach ($currentStatus as $key => $value): ?>
                <tr>
                    <th><?php echo str_replace('_', ' ', ucfirst($key)); ?>:</th>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <h3>Quick Fix Options:</h3>
        <div style="margin: 20px 0;">
            <a href="?action=change_role" class="btn">
                👤 Change Role to Staff (Bypass 2FA)
            </a>
            <a href="?action=disable_2fa" class="btn btn-danger">
                🔓 Force Disable 2FA
            </a>
            <a href="?action=both" class="btn btn-success">
                ✅ Both: Change Role + Disable 2FA
            </a>
        </div>
        
        <h3>Test Login:</h3>
        <div style="margin: 20px 0;">
            <a href="/business/login" class="btn" style="background: #8b5cf6;">
                🔑 Go to Business Login
            </a>
            <a href="/portal-auth-fix.php" class="btn" style="background: #6366f1;">
                🔐 Portal Auth Manager
            </a>
        </div>
    </div>
    
    <div class="card">
        <h2>Alternative Solutions:</h2>
        <ol>
            <li><strong>Fix in Code:</strong> Modify LoginController to skip 2FA for demo@askproai.de</li>
            <li><strong>Add Routes:</strong> Implement the missing 2FA setup/challenge routes</li>
            <li><strong>Change Logic:</strong> Modify requires2FA() method to exclude demo user</li>
        </ol>
    </div>
</body>
</html>