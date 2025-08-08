<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Authenticate
$user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
auth()->login($user);

echo "<h1>Livewire Table Render Test</h1>";

// Get a sample call
$call = \App\Models\Call::where('company_id', $user->company_id ?? 1)->first();

if (!$call) {
    die("No calls found");
}

echo "<h2>Sample Call Data:</h2>";
echo "<pre>";
echo "ID: " . $call->id . "\n";
echo "Created: " . $call->created_at . "\n";
echo "Status: " . $call->status . "\n";
echo "Duration: " . $call->duration_sec . "\n";
echo "From: " . $call->from_phone . "\n";
echo "To: " . $call->to_phone . "\n";
echo "</pre>";

// Try to render a Filament TextColumn
use Filament\Tables\Columns\TextColumn;

echo "<h2>TextColumn Render Test:</h2>";

$columns = [
    'id' => TextColumn::make('id'),
    'created_at' => TextColumn::make('created_at'),
    'status' => TextColumn::make('status'),
];

foreach ($columns as $name => $column) {
    echo "<h3>Column: $name</h3>";
    
    // Set the record
    $column->record($call);
    
    // Try to get the state
    try {
        $state = $column->getState();
        echo "State: " . var_export($state, true) . "<br>";
    } catch (\Exception $e) {
        echo "Error getting state: " . $e->getMessage() . "<br>";
    }
    
    // Check column HTML
    try {
        $html = $column->toHtml();
        echo "HTML: <pre>" . htmlspecialchars($html) . "</pre>";
    } catch (\Exception $e) {
        echo "Error rendering: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
}

// Test the actual Livewire component
echo "<h2>Livewire Component Test:</h2>";
echo "<pre>";

try {
    $componentClass = \App\Filament\Admin\Resources\CallResource\Pages\ListCalls::class;
    
    if (class_exists($componentClass)) {
        echo "ListCalls component exists\n";
        
        // Check if it's a Livewire component
        $reflection = new ReflectionClass($componentClass);
        $parent = $reflection->getParentClass();
        echo "Parent class: " . ($parent ? $parent->getName() : 'none') . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Add link to force reload
echo "<hr>";
echo "<h2>Actions:</h2>";
echo "<a href='/admin/calls?_t=" . time() . "' target='_blank'>Open Calls Page (with cache buster)</a>";