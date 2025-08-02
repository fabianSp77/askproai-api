<?php
// Direct Filament resource test
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Login demo user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();
if (!$user) {
    die("Demo user not found!");
}

Auth::login($user);

// Try to access Filament resources directly
try {
    // Get the Filament panel
    $panel = \Filament\Facades\Filament::getPanel('admin');
    
    echo "<h1>Filament Admin Panel Test</h1>";
    echo "<p><strong>User:</strong> " . $user->email . "</p>";
    echo "<p><strong>Authenticated:</strong> " . (Auth::check() ? '✅ Yes' : '❌ No') . "</p>";
    echo "<p><strong>Panel ID:</strong> " . $panel->getId() . "</p>";
    echo "<hr>";
    
    // List available resources
    echo "<h2>Available Resources:</h2>";
    echo "<ul>";
    
    $resources = [
        \App\Filament\Admin\Resources\CallResource::class,
        \App\Filament\Admin\Resources\AppointmentResource::class,
        \App\Filament\Admin\Resources\CustomerResource::class,
        \App\Filament\Admin\Resources\CompanyResource::class,
    ];
    
    foreach ($resources as $resourceClass) {
        if (class_exists($resourceClass)) {
            $name = class_basename($resourceClass);
            $url = $resourceClass::getUrl();
            echo "<li>$name - <a href='$url'>$url</a></li>";
        }
    }
    
    echo "</ul>";
    
    // Try to get Call data
    echo "<h2>Recent Calls (Direct Query):</h2>";
    $calls = \App\Models\Call::query()
        ->where('company_id', $user->company_id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    if ($calls->count() > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Phone</th><th>Status</th><th>Duration</th><th>Created</th></tr>";
        foreach ($calls as $call) {
            echo "<tr>";
            echo "<td>{$call->id}</td>";
            echo "<td>{$call->from_number}</td>";
            echo "<td>{$call->status}</td>";
            echo "<td>{$call->duration_sec}s</td>";
            echo "<td>{$call->created_at}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No calls found for this company.</p>";
    }
    
} catch (\Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

$kernel->terminate($request, $response);
?>