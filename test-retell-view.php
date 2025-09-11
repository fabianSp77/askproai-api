<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test RetellAgent Model
$agent = \App\Models\RetellAgent::find(135);

echo "=== RETELL AGENT TEST ===\n\n";
echo "Agent Found: " . ($agent ? 'YES' : 'NO') . "\n";

if ($agent) {
    echo "\n--- Basic Data ---\n";
    echo "ID: " . $agent->id . "\n";
    echo "Name: " . $agent->name . "\n";
    echo "Agent ID: " . $agent->agent_id . "\n";
    echo "Company ID: " . $agent->company_id . "\n";
    echo "Active: " . ($agent->is_active ? 'YES' : 'NO') . "\n";
    echo "Created: " . $agent->created_at . "\n";
    
    echo "\n--- Checking Relationships ---\n";
    echo "Has Company: " . ($agent->company ? 'YES - ' . $agent->company->name : 'NO') . "\n";
    
    echo "\n--- All Attributes ---\n";
    foreach ($agent->getAttributes() as $key => $value) {
        if (is_string($value) && strlen($value) > 100) {
            echo "$key: [" . strlen($value) . " chars]\n";
        } else {
            echo "$key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }
}

// Test Filament Resource
echo "\n\n=== FILAMENT RESOURCE TEST ===\n";
$resource = \App\Filament\Admin\Resources\RetellAgentResource::class;
echo "Resource Class Exists: " . (class_exists($resource) ? 'YES' : 'NO') . "\n";

if (class_exists($resource)) {
    echo "Model Class: " . $resource::getModel() . "\n";
    echo "Has Infolist Method: " . (method_exists($resource, 'infolist') ? 'YES' : 'NO') . "\n";
    
    // Try to get pages
    $pages = $resource::getPages();
    echo "Pages Registered: " . count($pages) . "\n";
    foreach ($pages as $name => $page) {
        echo "  - $name: " . $page::class . "\n";
    }
}

// Test ViewRetellAgent Page
echo "\n=== VIEW PAGE TEST ===\n";
$viewPage = \App\Filament\Admin\Resources\RetellAgentResource\Pages\ViewRetellAgent::class;
echo "ViewRetellAgent Exists: " . (class_exists($viewPage) ? 'YES' : 'NO') . "\n";

if (class_exists($viewPage)) {
    echo "Parent Class: " . get_parent_class($viewPage) . "\n";
    echo "Has infolist Method: " . (method_exists($viewPage, 'infolist') ? 'YES' : 'NO') . "\n";
}