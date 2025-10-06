<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create request
$request = Illuminate\Http\Request::create(
    'https://api.askproai.de/admin/branches/34c4d48e-4753-4715-9c30-c55843a943e8/edit',
    'GET'
);

// Login as first user
$user = App\Models\User::first();
Auth::login($user);

echo "Testing as user: " . $user->email . "\n\n";

try {
    // Test branch retrieval
    $branch = App\Models\Branch::find('34c4d48e-4753-4715-9c30-c55843a943e8');
    if ($branch) {
        echo "✅ Branch found: " . $branch->name . "\n";
        echo "   ID: " . $branch->id . "\n";
        echo "   Type: " . gettype($branch->id) . "\n\n";
    } else {
        echo "❌ Branch not found\n\n";
    }

    // Test resource access
    echo "Testing BranchResource access...\n";

    $resource = new App\Filament\Resources\BranchResource();

    // Try to get the edit page
    $page = App\Filament\Resources\BranchResource\Pages\EditBranch::class;

    echo "✅ BranchResource loaded\n";
    echo "✅ EditBranch page class exists\n";

    // Simulate request
    $response = $kernel->handle($request);
    echo "\nResponse Status: " . $response->getStatusCode() . "\n";

    if ($response->getStatusCode() == 500) {
        echo "\n❌ 500 ERROR DETECTED!\n";
        echo "Content snippet:\n";
        echo substr($response->getContent(), 0, 500) . "\n";
    }

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . substr($e->getTraceAsString(), 0, 1000) . "\n";
}