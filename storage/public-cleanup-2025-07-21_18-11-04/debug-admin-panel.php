<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Services\MCP\DebugMCPServer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

$debugServer = new DebugMCPServer();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel Debug</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Admin Panel Debug Analysis</h1>

        <?php
        // 1. Debug Auth State
        echo '<div class="bg-white rounded-lg shadow p-6 mb-6">';
        echo '<h2 class="text-xl font-semibold mb-4">1. Authentication State</h2>';
        try {
            $authState = $debugServer->executeTool('debug_auth_state', ['verbose' => false]);
            echo '<pre class="bg-gray-100 p-4 rounded overflow-x-auto">';
            echo htmlspecialchars(json_encode($authState, JSON_PRETTY_PRINT));
            echo '</pre>';
        } catch (Exception $e) {
            echo '<div class="bg-red-100 text-red-800 p-4 rounded">Error: ' . $e->getMessage() . '</div>';
        }
        echo '</div>';

        // 2. Check Admin Routes
        echo '<div class="bg-white rounded-lg shadow p-6 mb-6">';
        echo '<h2 class="text-xl font-semibold mb-4">2. Admin Routes Analysis</h2>';
        try {
            $adminRoutes = $debugServer->executeTool('list_routes', [
                'filter' => 'admin',
                'method' => 'GET'
            ]);
            
            echo '<div class="overflow-x-auto">';
            echo '<table class="min-w-full divide-y divide-gray-200">';
            echo '<thead class="bg-gray-50">';
            echo '<tr>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URI</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Middleware</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody class="bg-white divide-y divide-gray-200">';
            
            foreach ($adminRoutes['routes'] as $route) {
                echo '<tr>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm">' . htmlspecialchars($route['uri']) . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm">' . htmlspecialchars($route['name'] ?? '-') . '</td>';
                echo '<td class="px-6 py-4 text-sm">' . htmlspecialchars($route['action']) . '</td>';
                echo '<td class="px-6 py-4 text-sm">' . implode(', ', array_slice($route['middleware'], 0, 3)) . '...</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '<p class="mt-2 text-sm text-gray-600">Total admin routes: ' . $adminRoutes['total'] . '</p>';
        } catch (Exception $e) {
            echo '<div class="bg-red-100 text-red-800 p-4 rounded">Error: ' . $e->getMessage() . '</div>';
        }
        echo '</div>';

        // 3. Trace Admin Request
        echo '<div class="bg-white rounded-lg shadow p-6 mb-6">';
        echo '<h2 class="text-xl font-semibold mb-4">3. Admin Request Trace</h2>';
        try {
            $trace = $debugServer->executeTool('trace_request_flow', [
                'method' => 'GET',
                'uri' => '/admin'
            ]);
            
            if (isset($trace['route'])) {
                echo '<div class="mb-4">';
                echo '<h3 class="font-semibold">Route Information:</h3>';
                echo '<ul class="list-disc list-inside mt-2">';
                echo '<li>URI: ' . htmlspecialchars($trace['route']['uri']) . '</li>';
                echo '<li>Name: ' . htmlspecialchars($trace['route']['name'] ?? 'unnamed') . '</li>';
                echo '<li>Controller: ' . htmlspecialchars($trace['route']['controller'] ?? 'unknown') . '</li>';
                echo '<li>Method: ' . htmlspecialchars($trace['route']['method'] ?? 'unknown') . '</li>';
                echo '</ul>';
                echo '</div>';
                
                echo '<div class="mb-4">';
                echo '<h3 class="font-semibold">Middleware Stack:</h3>';
                echo '<ol class="list-decimal list-inside mt-2">';
                foreach ($trace['middleware'] as $m) {
                    echo '<li>' . htmlspecialchars($m['class']) . '</li>';
                }
                echo '</ol>';
                echo '</div>';
            }
            
            if (!empty($trace['errors'])) {
                echo '<div class="bg-red-100 p-4 rounded">';
                echo '<h3 class="font-semibold text-red-800">Errors:</h3>';
                echo '<ul class="list-disc list-inside mt-2">';
                foreach ($trace['errors'] as $error) {
                    echo '<li>' . htmlspecialchars($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="bg-red-100 text-red-800 p-4 rounded">Error: ' . $e->getMessage() . '</div>';
        }
        echo '</div>';

        // 4. Check Filament Configuration
        echo '<div class="bg-white rounded-lg shadow p-6 mb-6">';
        echo '<h2 class="text-xl font-semibold mb-4">4. Filament Admin Panel Check</h2>';
        try {
            $filamentPath = base_path('app/Providers/Filament/AdminPanelProvider.php');
            if (file_exists($filamentPath)) {
                echo '<p class="text-green-600">✓ AdminPanelProvider exists</p>';
                
                // Check if we can access Filament
                if (class_exists('\Filament\Panel')) {
                    echo '<p class="text-green-600">✓ Filament Panel class loaded</p>';
                } else {
                    echo '<p class="text-red-600">✗ Filament Panel class not found</p>';
                }
                
                // Check admin user
                $adminUser = \App\Models\User::where('email', 'admin@askproai.de')->first();
                if ($adminUser) {
                    echo '<p class="text-green-600">✓ Admin user exists (ID: ' . $adminUser->id . ')</p>';
                } else {
                    echo '<p class="text-red-600">✗ Admin user not found</p>';
                }
            } else {
                echo '<p class="text-red-600">✗ AdminPanelProvider not found</p>';
            }
        } catch (Exception $e) {
            echo '<div class="bg-red-100 text-red-800 p-4 rounded">Error: ' . $e->getMessage() . '</div>';
        }
        echo '</div>';

        // 5. Test Direct Access
        echo '<div class="bg-white rounded-lg shadow p-6 mb-6">';
        echo '<h2 class="text-xl font-semibold mb-4">5. Direct Access Test</h2>';
        echo '<div class="space-y-2">';
        echo '<a href="/admin" class="inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Test /admin</a>';
        echo '<a href="/admin/login" class="inline-block px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 ml-2">Test /admin/login</a>';
        echo '<a href="/admin-working.php" class="inline-block px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 ml-2">Test Working Admin</a>';
        echo '</div>';
        echo '</div>';

        // 6. Session Debug
        echo '<div class="bg-white rounded-lg shadow p-6 mb-6">';
        echo '<h2 class="text-xl font-semibold mb-4">6. Session Information</h2>';
        try {
            $sessionDebug = $debugServer->executeTool('debug_session', [
                'guard' => 'web'
            ]);
            echo '<pre class="bg-gray-100 p-4 rounded overflow-x-auto text-sm">';
            echo htmlspecialchars(json_encode($sessionDebug, JSON_PRETTY_PRINT));
            echo '</pre>';
        } catch (Exception $e) {
            echo '<div class="bg-red-100 text-red-800 p-4 rounded">Error: ' . $e->getMessage() . '</div>';
        }
        echo '</div>';
        ?>
    </div>
</body>
</html>