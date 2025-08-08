<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Authenticate
$user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
auth()->login($user);

use App\Filament\Admin\Resources\CallResource;
use Filament\Tables\Table;

echo "<h1>Exact Render Test</h1>";
echo "<pre>";

// Test 1: Get the actual query being used
echo "Test 1: Query Test\n";
echo "==================\n";
$query = CallResource::getEloquentQuery();
$sql = $query->toSql();
$bindings = $query->getBindings();
echo "SQL: $sql\n";
echo "Bindings: " . json_encode($bindings) . "\n";
$count = $query->count();
echo "Count: $count\n\n";

// Test 2: Get sample data
echo "Test 2: Sample Data\n";
echo "===================\n";
$samples = $query->limit(3)->get();
foreach ($samples as $call) {
    echo "Row Data:\n";
    echo "  id: " . var_export($call->id, true) . "\n";
    echo "  created_at: " . var_export($call->created_at, true) . "\n";
    echo "  status: " . var_export($call->status, true) . "\n";
    echo "  duration_sec: " . var_export($call->duration_sec, true) . "\n";
    echo "  from_phone: " . var_export($call->from_phone, true) . "\n";
    echo "  to_phone: " . var_export($call->to_phone, true) . "\n";
    echo "\n";
}

// Test 3: Test table columns directly
echo "Test 3: Table Column Test\n";
echo "=========================\n";
$table = app(Table::class);
$table = CallResource::table($table);
$columns = $table->getColumns();
echo "Number of columns: " . count($columns) . "\n";
foreach ($columns as $column) {
    $name = $column->getName();
    echo "Column: $name\n";
    
    // Test with sample data
    if ($samples->first()) {
        $state = $samples->first()->$name ?? null;
        echo "  Raw value: " . var_export($state, true) . "\n";
    }
}

echo "</pre>";

// Add JavaScript to check DOM
echo <<<HTML
<hr>
<h2>Check Live Page</h2>
<button onclick="checkLivePage()">Check /admin/calls Now</button>
<div id="result"></div>

<script>
function checkLivePage() {
    fetch('/admin/calls')
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Check for table cells
            const cells = doc.querySelectorAll('td');
            const result = document.getElementById('result');
            
            result.innerHTML = '<h3>Found ' + cells.length + ' cells</h3>';
            
            // Check first 10 cells
            result.innerHTML += '<h4>First 10 cells content:</h4><ol>';
            for (let i = 0; i < Math.min(10, cells.length); i++) {
                const text = cells[i].textContent.trim();
                const html = cells[i].innerHTML;
                result.innerHTML += '<li>Text: "' + text + '" | HTML length: ' + html.length + '</li>';
            }
            result.innerHTML += '</ol>';
        });
}
</script>
HTML;