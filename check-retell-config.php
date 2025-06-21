<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;

$company = Company::find(1);
echo "Company: " . $company->name . PHP_EOL;
echo "Has Retell API Key: " . ($company->retell_api_key ? 'Yes' : 'No') . PHP_EOL;
echo "Retell Agent ID: " . ($company->retell_agent_id ?? 'Not set') . PHP_EOL;

// Check config
echo "\nConfig values:\n";
echo "services.retell.api_key: " . (config('services.retell.api_key') ? 'Set' : 'Not set') . PHP_EOL;
echo "services.retell.token: " . (config('services.retell.token') ? 'Set' : 'Not set') . PHP_EOL;
echo "services.retell.base_url: " . config('services.retell.base_url') . PHP_EOL;

// Check ENV
echo "\nENV values:\n";
echo "RETELL_TOKEN: " . (env('RETELL_TOKEN') ? 'Set' : 'Not set') . PHP_EOL;
echo "RETELL_API_KEY: " . (env('RETELL_API_KEY') ? 'Set' : 'Not set') . PHP_EOL;
echo "DEFAULT_RETELL_API_KEY: " . (env('DEFAULT_RETELL_API_KEY') ? 'Set' : 'Not set') . PHP_EOL;