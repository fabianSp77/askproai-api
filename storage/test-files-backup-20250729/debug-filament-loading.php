<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Process the request through middleware
$response = $kernel->handle($request);

// Check if user is authenticated
if (!auth()->check()) {
    header('Location: /admin/login');
    exit;
}

$user = auth()->user();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Filament Loading Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .test-result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .test-pass { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .test-fail { background-color: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Filament Resources Loading Debug</h1>
        
        <div class="test-result test-pass">
            <h3>Authentication Status</h3>
            <p>✅ Authenticated as: <?= htmlspecialchars($user->email) ?></p>
            <p>User ID: <?= $user->id ?></p>
            <p>Company ID: <?= $user->company_id ?? 'NULL' ?></p>
        </div>

        <div class="test-result <?= app()->has('current_company_id') ? 'test-pass' : 'test-fail' ?>">
            <h3>Company Context (Before Manual Fix)</h3>
            <?php if (app()->has('current_company_id')): ?>
                <p class="success">✅ Company context is set</p>
                <p>current_company_id: <?= app('current_company_id') ?></p>
                <p>company_context_source: <?= app('company_context_source') ?></p>
            <?php else: ?>
                <p class="error">❌ Company context NOT set!</p>
                <p>This is why your pages don't load!</p>
                
                <?php 
                // Apply ForceCompanyContext middleware to fix it
                $forceMiddleware = new \App\Http\Middleware\ForceCompanyContext();
                $forceMiddleware->handle($request, function($req) { return response('OK'); });
                ?>
                
                <p class="warning">⚠️ Applied ForceCompanyContext middleware manually</p>
                <?php if (app()->has('current_company_id')): ?>
                    <p class="success">✅ Company context now set after ForceCompanyContext</p>
                    <p>current_company_id: <?= app('current_company_id') ?></p>
                    <p>company_context_source: <?= app('company_context_source') ?></p>
                <?php else: ?>
                    <p class="error">❌ Still not set even after ForceCompanyContext!</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <h2>Testing Each Resource</h2>
        
        <?php
        // Run EnsureCompanyContext middleware manually if not already set
        if (!app()->has('current_company_id')) {
            $middleware = new \App\Http\Middleware\EnsureCompanyContext();
            $middleware->handle($request, function($req) { return response('OK'); });
        }
        
        $resources = [
            'Calls' => [
                'model' => \App\Models\Call::class,
                'resource' => \App\Filament\Admin\Resources\CallResource::class,
                'url' => '/admin/calls'
            ],
            'Appointments' => [
                'model' => \App\Models\Appointment::class,
                'resource' => \App\Filament\Admin\Resources\AppointmentResource::class,
                'url' => '/admin/appointments'
            ],
            'Customers' => [
                'model' => \App\Models\Customer::class,
                'resource' => \App\Filament\Admin\Resources\CustomerResource::class,
                'url' => '/admin/customers'
            ],
            'Branches' => [
                'model' => \App\Models\Branch::class,
                'resource' => \App\Filament\Admin\Resources\BranchResource::class,
                'url' => '/admin/branches'
            ],
        ];
        
        foreach ($resources as $name => $config):
            $modelClass = $config['model'];
            $resourceClass = $config['resource'];
            $url = $config['url'];
            
            try {
                $count = $modelClass::count();
                $query = $modelClass::query();
                $sql = $query->toSql();
                $bindings = $query->getBindings();
                
                $canView = class_exists($resourceClass) ? $resourceClass::canViewAny() : false;
                
                $hasCompanyFilter = strpos($sql, 'company_id') !== false;
                $isBlocked = strpos($sql, '0 = 1') !== false;
                
                $status = $isBlocked ? 'fail' : ($count > 0 ? 'pass' : 'warning');
                ?>
                
                <div class="test-result test-<?= $status ?>">
                    <h3><?= $name ?></h3>
                    <table>
                        <tr>
                            <th>Check</th>
                            <th>Result</th>
                            <th>Details</th>
                        </tr>
                        <tr>
                            <td>Record Count</td>
                            <td><?= $count > 0 ? '✅' : '⚠️' ?></td>
                            <td><?= $count ?> records</td>
                        </tr>
                        <tr>
                            <td>Can View Permission</td>
                            <td><?= $canView ? '✅' : '❌' ?></td>
                            <td><?= $canView ? 'Allowed' : 'Denied' ?></td>
                        </tr>
                        <tr>
                            <td>Company Filter</td>
                            <td><?= $hasCompanyFilter ? '✅' : ($isBlocked ? '❌' : '⚠️') ?></td>
                            <td><?= $isBlocked ? 'BLOCKED (0 = 1)' : ($hasCompanyFilter ? 'Filtered by company_id' : 'No filter') ?></td>
                        </tr>
                        <tr>
                            <td>SQL Query</td>
                            <td colspan="2" class="code"><?= htmlspecialchars($sql) ?></td>
                        </tr>
                        <?php if (!empty($bindings)): ?>
                        <tr>
                            <td>Bindings</td>
                            <td colspan="2" class="code"><?= htmlspecialchars(json_encode($bindings)) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    <p><a href="<?= $url ?>" target="_blank">Open <?= $name ?> page →</a></p>
                </div>
                
                <?php
            } catch (Exception $e) {
                ?>
                <div class="test-result test-fail">
                    <h3><?= $name ?></h3>
                    <p class="error">❌ Error: <?= htmlspecialchars($e->getMessage()) ?></p>
                </div>
                <?php
            }
        endforeach;
        ?>

        <h2>Session & Cookie Information</h2>
        <div class="code">
            <h4>Session Data:</h4>
            <pre><?= htmlspecialchars(print_r($_SESSION, true)) ?></pre>
            
            <h4>Cookies:</h4>
            <pre><?= htmlspecialchars(print_r($_COOKIE, true)) ?></pre>
        </div>

        <h2>Middleware Stack</h2>
        <?php
        $adminProvider = app(\App\Providers\Filament\AdminPanelProvider::class);
        ?>
        <div class="code">
            <p>The AdminPanelProvider middleware configuration should include EnsureCompanyContext.</p>
        </div>

        <h2>Quick Actions</h2>
        <div style="margin-top: 20px;">
            <a href="/admin" class="button">Go to Admin Dashboard</a>
            <a href="/admin/optimized-dashboard" class="button">Try Optimized Dashboard</a>
            <a href="javascript:location.reload()" class="button">Refresh This Page</a>
        </div>
    </div>
</body>
</html>