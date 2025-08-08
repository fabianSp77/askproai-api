<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\User;
use App\Models\CompanyPricingTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

DB::transaction(function () {
    echo "Creating reseller demo data...\n";
    
    // Create a reseller company
    $reseller = Company::create([
        'name' => 'Premium Telecom Solutions GmbH',
        'slug' => 'premium-telecom',
        'email' => 'kontakt@premium-telecom.de',
        'phone' => '+49 30 12345678',
        'company_type' => 'reseller',
        'is_active' => true,
        'settings' => [
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR'
        ]
    ]);
    
    echo "Created reseller: {$reseller->name}\n";
    
    // Create reseller owner user
    $resellerOwner = User::create([
        'name' => 'Max Vermittler',
        'email' => 'max@premium-telecom.de',
        'password' => Hash::make('password'),
        'company_id' => $reseller->id,
        'email_verified_at' => now()
    ]);
    
    $resellerOwner->assignRole('reseller_owner');
    echo "Created reseller owner: {$resellerOwner->email}\n";
    
    // Create client companies
    $clients = [
        [
            'name' => 'Friseur Schmidt',
            'email' => 'info@friseur-schmidt.de',
            'type' => 'Friseursalon'
        ],
        [
            'name' => 'Dr. Müller Zahnarztpraxis',
            'email' => 'praxis@dr-mueller.de',
            'type' => 'Zahnarztpraxis'
        ],
        [
            'name' => 'Restaurant Bella Vista',
            'email' => 'reservierung@bellavista.de',
            'type' => 'Restaurant'
        ]
    ];
    
    foreach ($clients as $clientData) {
        $client = Company::create([
            'name' => $clientData['name'],
            'slug' => \Str::slug($clientData['name']),
            'email' => $clientData['email'],
            'phone' => '+49 30 ' . rand(10000000, 99999999),
            'company_type' => 'client',
            'parent_company_id' => $reseller->id,
            'is_active' => true,
            'can_make_outbound_calls' => rand(0, 1), // Random outbound capability
            'settings' => [
                'timezone' => 'Europe/Berlin',
                'currency' => 'EUR',
                'business_type' => $clientData['type']
            ]
        ]);
        
        echo "Created client: {$client->name}\n";
        
        // Create client owner
        $clientOwner = User::create([
            'name' => 'Inhaber ' . explode(' ', $clientData['name'])[1],
            'email' => $clientData['email'],
            'password' => Hash::make('password'),
            'company_id' => $client->id,
            'email_verified_at' => now()
        ]);
        
        $clientOwner->assignRole('company_owner');
        
        // Create pricing tiers for this client
        $pricingTypes = ['inbound', 'outbound'];
        
        foreach ($pricingTypes as $type) {
            CompanyPricingTier::create([
                'company_id' => $reseller->id,
                'child_company_id' => $client->id,
                'pricing_type' => $type,
                'cost_price' => $type === 'inbound' ? 0.25 : 0.35, // Reseller pays
                'sell_price' => $type === 'inbound' ? 0.40 : 0.50, // Client pays
                'included_minutes' => $type === 'inbound' ? 500 : 0,
                'overage_rate' => $type === 'inbound' ? 0.40 : 0.50,
                'monthly_fee' => $type === 'inbound' ? 49.00 : 0,
                'is_active' => true
            ]);
        }
        
        echo "Created pricing tiers for {$client->name}\n";
    }
    
    // Create reseller's own pricing (what they pay)
    foreach (['inbound', 'outbound'] as $type) {
        CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => null,
            'pricing_type' => $type,
            'cost_price' => $type === 'inbound' ? 0.015 : 0.025, // Provider cost
            'sell_price' => $type === 'inbound' ? 0.25 : 0.35, // What reseller charges
            'included_minutes' => 0,
            'is_active' => true
        ]);
    }
    
    echo "\nDemo data created successfully!\n";
    echo "Reseller login: max@premium-telecom.de / password\n";
    echo "Client logins: Use the email addresses shown above with password 'password'\n";
});

echo "\nDone!\n";