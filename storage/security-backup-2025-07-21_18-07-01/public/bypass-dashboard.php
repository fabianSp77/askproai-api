<?php

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Get session data
$userId = session('portal_user_id');

if (!$userId) {
    header('Location: /business/login');
    exit;
}

// Get user
$user = \App\Models\PortalUser::withoutGlobalScopes()->find($userId);

if (!$user) {
    header('Location: /business/login');
    exit;
}

// Set company context
app()->instance('current_company_id', $user->company_id);

// Re-authenticate if needed
if (!\Illuminate\Support\Facades\Auth::guard('portal')->check()) {
    \Illuminate\Support\Facades\Auth::guard('portal')->login($user);
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Portal Dashboard</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0;
            padding: 0;
            background: #f5f7fa;
        }
        .header {
            background: white;
            border-bottom: 1px solid #e1e4e8;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .welcome {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #3B82F6;
            margin: 10px 0;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            background: #3B82F6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
        }
        .btn:hover {
            background: #2563EB;
        }
        .debug {
            background: #f5f5f5;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1 style="margin: 0;">Business Portal Dashboard</h1>
            <p style="margin: 5px 0; color: #666;">Welcome back, <?php echo htmlspecialchars($user->name); ?></p>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h2>Dashboard Overview</h2>
            <p>You are successfully logged in to the Business Portal.</p>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Calls</h3>
                    <div class="stat-value">
                        <?php
                        $callCount = \App\Models\Call::withoutGlobalScopes()
                            ->where('company_id', $user->company_id)
                            ->count();
                        echo number_format($callCount);
                        ?>
                    </div>
                    <p>Lifetime calls</p>
                </div>
                
                <div class="stat-card">
                    <h3>Today's Calls</h3>
                    <div class="stat-value">
                        <?php
                        $todayCount = \App\Models\Call::withoutGlobalScopes()
                            ->where('company_id', $user->company_id)
                            ->whereDate('created_at', today())
                            ->count();
                        echo number_format($todayCount);
                        ?>
                    </div>
                    <p>Calls today</p>
                </div>
                
                <div class="stat-card">
                    <h3>Active Staff</h3>
                    <div class="stat-value">
                        <?php
                        $staffCount = \App\Models\Staff::withoutGlobalScopes()
                            ->whereHas('branches', function($q) use ($user) {
                                $q->where('company_id', $user->company_id);
                            })
                            ->count();
                        echo number_format($staffCount);
                        ?>
                    </div>
                    <p>Team members</p>
                </div>
            </div>
            
            <div class="actions">
                <a href="/business/calls" class="btn">View Calls</a>
                <a href="/business/appointments" class="btn">Appointments</a>
                <a href="/business/team" class="btn">Team</a>
                <a href="/business/settings" class="btn">Settings</a>
            </div>
        </div>
        
        <div class="debug">
            <strong>Debug Info:</strong><br>
            User ID: <?php echo $user->id; ?><br>
            Company ID: <?php echo $user->company_id; ?><br>
            Session ID: <?php echo session()->getId(); ?><br>
            Auth Check: <?php echo \Illuminate\Support\Facades\Auth::guard('portal')->check() ? 'Yes' : 'No'; ?>
        </div>
    </div>
</body>
</html>

<?php
$kernel->terminate($request, $response);