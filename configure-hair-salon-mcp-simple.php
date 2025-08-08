#!/usr/bin/env php
<?php
/**
 * Simple Hair Salon MCP Configuration
 * Creates necessary data without migrations
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "================================================================================\n";
echo "                    💇 Hair Salon MCP Simple Setup\n";
echo "================================================================================\n\n";

try {
    // Find or create the hair salon company
    $company = Company::find(1);
    if (!$company) {
        echo "❌ Company ID 1 not found. Please create a company first.\n";
        exit(1);
    }
    
    echo "✅ Using company: {$company->name}\n";
    
    // Update company settings for MCP
    $settings = $company->settings ?? [];
    $settings['mcp_integration'] = [
        'enabled' => true,
        'type' => 'hair_salon',
        'version' => '2.0'
    ];
    $company->settings = $settings;
    $company->save();
    echo "✅ MCP integration enabled for company\n";
    
    // Ensure we have a branch
    $branch = Branch::where('company_id', $company->id)->first();
    if (!$branch) {
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Hauptfiliale',
            'address' => 'Beispielstraße 1, 10115 Berlin',
            'phone' => '+49 30 33081738'
        ]);
        echo "✅ Created branch: {$branch->name}\n";
    } else {
        echo "✅ Using existing branch: {$branch->name}\n";
    }
    
    // Create staff if they don't exist
    $staffData = [
        ['name' => 'Maria', 'email' => 'maria@salon.de', 'specialization' => 'Färbungen und Balayage'],
        ['name' => 'Anna', 'email' => 'anna@salon.de', 'specialization' => 'Hochsteckfrisuren und Styling'],
        ['name' => 'Lisa', 'email' => 'lisa@salon.de', 'specialization' => 'Schnitte und Dauerwellen']
    ];
    
    foreach ($staffData as $data) {
        $staff = Staff::firstOrCreate(
            [
                'email' => $data['email'],
                'company_id' => $company->id
            ],
            [
                'name' => $data['name'],
                'branch_id' => $branch->id,
                'phone' => '+49 30 ' . rand(10000000, 99999999),
                'notes' => $data['specialization']
            ]
        );
        echo "✅ Staff member: {$staff->name} (ID: {$staff->id})\n";
    }
    
    // Create services if they don't exist
    $services = [
        ['name' => 'Herrenhaarschnitt', 'duration' => 30, 'price' => 35.00],
        ['name' => 'Damenhaarschnitt', 'duration' => 45, 'price' => 55.00],
        ['name' => 'Färbung komplett', 'duration' => 120, 'price' => 85.00],
        ['name' => 'Foliensträhnen', 'duration' => 90, 'price' => 95.00],
        ['name' => 'Dauerwelle', 'duration' => 150, 'price' => 120.00],
        ['name' => 'Beratung (telefonisch)', 'duration' => 15, 'price' => 0.00],
        ['name' => 'Hochsteckfrisur', 'duration' => 60, 'price' => 75.00],
        ['name' => 'Balayage', 'duration' => 180, 'price' => 150.00]
    ];
    
    foreach ($services as $serviceData) {
        $service = Service::firstOrCreate(
            [
                'name' => $serviceData['name'],
                'company_id' => $company->id
            ],
            [
                'duration' => $serviceData['duration'],
                'price' => $serviceData['price'],
                'is_active' => true,
                'description' => "Professioneller {$serviceData['name']} Service"
            ]
        );
        echo "✅ Service: {$service->name} ({$service->price}€, {$service->duration} Min)\n";
    }
    
    echo "\n================================================================================\n";
    echo "                           ✅ Setup Complete!\n";
    echo "================================================================================\n";
    echo "\nTest the MCP endpoint:\n";
    echo "curl -X POST https://api.askproai.de/api/v2/hair-salon-mcp/mcp \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -d '{\n";
    echo '    "jsonrpc": "2.0",'."\n";
    echo '    "method": "list_services",'."\n";
    echo '    "params": {"company_id": 1},'."\n";
    echo '    "id": 1'."\n";
    echo "  }'\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}