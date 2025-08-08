#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\MCP\HairSalonMCPServer;
use App\Models\Company;
use App\Models\Service;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Hair Salon MCP Services\n";
echo "================================\n\n";

// Check company
$company = Company::find(1);
echo "Company: " . ($company ? $company->name : "NOT FOUND") . "\n";

// Check services
$services = Service::where('company_id', 1)->where('active', 1)->get();
echo "Services in DB: " . $services->count() . "\n";
foreach ($services as $service) {
    echo "  - {$service->name}: {$service->price}€\n";
}

// Test MCP Server
echo "\nTesting MCP Server:\n";
$mcpServer = new HairSalonMCPServer();
$mcpServer->setSalonCompany($company);

$result = $mcpServer->getServices(['company_id' => 1]);
echo "MCP Result:\n";
echo "  Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
if ($result['success']) {
    echo "  Services count: " . count($result['services']) . "\n";
    foreach ($result['services'] as $service) {
        echo "    - {$service['name']}: {$service['price']}€\n";
    }
} else {
    echo "  Error: " . $result['error'] . "\n";
}