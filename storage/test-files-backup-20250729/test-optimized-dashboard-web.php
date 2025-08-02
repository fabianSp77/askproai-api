<?php
// Web test for OptimizedDashboard
session_start();

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    // Try to login using cookies/session
    $sessionName = config('session.cookie');
    if (isset($_COOKIE[$sessionName])) {
        session_id($_COOKIE[$sessionName]);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Optimized Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f4f4f4; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Testing Optimized Dashboard</h1>
    
    <?php
    // Check auth
    $user = auth()->user();
    if (!$user) {
        echo '<p class="error">Not authenticated. Please <a href="/admin/login">login</a> first.</p>';
        exit;
    }
    
    echo '<p class="success">Authenticated as: ' . $user->email . '</p>';
    
    // Check the page class
    try {
        $pageClass = 'App\\Filament\\Admin\\Pages\\OptimizedDashboard';
        $page = new $pageClass();
        
        echo '<h2>Page Instance Check</h2>';
        echo '<pre>';
        echo "Class: " . get_class($page) . "\n";
        echo "View: " . (property_exists($page, 'view') ? 'Set' : 'Not set') . "\n";
        echo '</pre>';
        
        // Try to mount the page
        echo '<h2>Mount Test</h2>';
        try {
            $page->mount();
            echo '<p class="success">Mount successful</p>';
            
            // Check stats
            echo '<h3>Stats loaded:</h3>';
            echo '<pre>';
            print_r($page->stats);
            echo '</pre>';
        } catch (Exception $e) {
            echo '<p class="error">Mount failed: ' . $e->getMessage() . '</p>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        }
        
        // Test database queries directly
        echo '<h2>Direct Query Test</h2>';
        try {
            // Check appointments table structure
            $columns = \DB::select("SHOW COLUMNS FROM appointments");
            echo '<h3>Appointments table columns:</h3>';
            echo '<pre>';
            foreach ($columns as $col) {
                echo $col->Field . ' (' . $col->Type . ")\n";
            }
            echo '</pre>';
            
            // Test the problematic query
            echo '<h3>Testing pending appointments query:</h3>';
            $query = \App\Models\Appointment::where('status', 'scheduled');
            
            // Find the right date column
            $dateColumns = ['starts_at', 'appointment_date', 'scheduled_at', 'date', 'datetime'];
            $dateColumn = null;
            
            foreach ($dateColumns as $col) {
                if (in_array($col, array_column($columns, 'Field'))) {
                    $dateColumn = $col;
                    break;
                }
            }
            
            if ($dateColumn) {
                echo '<p>Using date column: <strong>' . $dateColumn . '</strong></p>';
                $count = $query->whereDate($dateColumn, '>=', today())->count();
                echo '<p class="success">Query successful! Count: ' . $count . '</p>';
            } else {
                echo '<p class="error">No suitable date column found!</p>';
            }
            
        } catch (Exception $e) {
            echo '<p class="error">Query failed: ' . $e->getMessage() . '</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">Error: ' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
    ?>
    
    <hr>
    <p><a href="/admin/optimized-dashboard">Try to access the actual page</a></p>
</body>
</html>