<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;

echo "=== Portal Users ===\n\n";

$users = PortalUser::with('company')->orderBy('created_at', 'desc')->limit(10)->get();

foreach ($users as $user) {
    echo "ID: {$user->id}\n";
    echo "Email: {$user->email}\n";
    echo "Name: {$user->name}\n";
    echo "Company: " . ($user->company ? $user->company->name : 'None') . "\n";
    echo "Created: {$user->created_at}\n";
    echo "---\n";
}

echo "\n=== Companies ===\n\n";
$companies = Company::orderBy('created_at', 'desc')->limit(5)->get();
foreach ($companies as $company) {
    echo "ID: {$company->id}\n";
    echo "Name: {$company->name}\n";
    echo "---\n";
}