<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\PrepaidBalance;
use App\Models\BillingRate;
use App\Models\PortalUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

DB::beginTransaction();

try {
    // Create the new prepaid company
    $company = Company::create([
        'name' => 'TeleService Pro GmbH',
        'email' => 'kontakt@teleservice-pro.de',
        'phone' => '+49 30 123456789',
        'address' => 'Hauptstraße 123, 10115 Berlin',
        'contact_person' => 'Max Mustermann',
        'billing_contact_email' => 'buchhaltung@teleservice-pro.de',
        'billing_contact_phone' => '+49 30 123456790',
        'is_active' => true,
        'active' => true,
        'prepaid_billing_enabled' => true,
        'billing_status' => 'active',
        'billing_type' => 'prepaid',
        'industry' => 'telecom',
        'country' => 'DE',
        'currency' => 'EUR',
        'timezone' => 'Europe/Berlin',
        'payment_terms' => 'prepaid',
        'settings' => [
            'needs_appointment_booking' => false,
            'service_type' => 'call_center',
            'business_type' => 'b2b_telephony',
            'prepaid_enabled' => true
        ],
    ]);
    
    echo "Created company: {$company->name} (ID: {$company->id})\n";
    
    // Create prepaid balance
    $balance = PrepaidBalance::create([
        'company_id' => $company->id,
        'balance' => 500.00, // Start with 500€
        'reserved_balance' => 0.00,
        'low_balance_threshold' => 100.00, // 20% of 500€
    ]);
    
    echo "Created prepaid balance: {$balance->balance}€\n";
    
    // Create billing rate
    $billingRate = BillingRate::create([
        'company_id' => $company->id,
        'rate_per_minute' => 0.42,
        'billing_increment' => 1, // Per second
        'minimum_duration' => 0,
        'connection_fee' => 0.00,
        'is_active' => true,
    ]);
    
    echo "Created billing rate: {$billingRate->rate_per_minute}€/min\n";
    
    // Create 9 branches across Germany
    $branches = [
        ['name' => 'Zentrale Berlin', 'city' => 'Berlin', 'phone' => '+49 30 123456701'],
        ['name' => 'Filiale Hamburg', 'city' => 'Hamburg', 'phone' => '+49 40 123456702'],
        ['name' => 'Filiale München', 'city' => 'München', 'phone' => '+49 89 123456703'],
        ['name' => 'Filiale Frankfurt', 'city' => 'Frankfurt', 'phone' => '+49 69 123456704'],
        ['name' => 'Filiale Köln', 'city' => 'Köln', 'phone' => '+49 221 123456705'],
        ['name' => 'Filiale Stuttgart', 'city' => 'Stuttgart', 'phone' => '+49 711 123456706'],
        ['name' => 'Filiale Düsseldorf', 'city' => 'Düsseldorf', 'phone' => '+49 211 123456707'],
        ['name' => 'Filiale Leipzig', 'city' => 'Leipzig', 'phone' => '+49 341 123456708'],
        ['name' => 'Filiale Dresden', 'city' => 'Dresden', 'phone' => '+49 351 123456709'],
    ];
    
    foreach ($branches as $index => $branchData) {
        $branch = Branch::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'name' => $branchData['name'],
            'address' => "Beispielstraße " . ($index + 1) . ", " . $branchData['city'],
            'phone' => $branchData['phone'],
            'email' => strtolower(str_replace(' ', '.', $branchData['name'])) . '@teleservice-pro.de',
            'is_active' => true,
            'is_primary' => $index === 0,
            'city' => $branchData['city'],
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'business_hours' => [
                'monday' => ['09:00', '18:00'],
                'tuesday' => ['09:00', '18:00'],
                'wednesday' => ['09:00', '18:00'],
                'thursday' => ['09:00', '18:00'],
                'friday' => ['09:00', '17:00'],
                'saturday' => ['closed'],
                'sunday' => ['closed']
            ],
        ]);
        
        echo "Created branch: {$branch->name} in {$branch->city}\n";
    }
    
    // Create portal admin user
    $portalUser = PortalUser::create([
        'company_id' => $company->id,
        'name' => 'Admin TeleService',
        'email' => 'admin@teleservice-pro.de',
        'password' => bcrypt('TeleService2024!'),
        'role' => 'owner',
        'permissions' => [
            'full_access' => true,
            'billing.view' => true,
            'billing.pay' => true,
            'billing.manage' => true,
            'calls.view_all' => true,
            'calls.export' => true,
            'team.manage' => true,
            'settings.manage' => true,
        ],
        'is_active' => true,
    ]);
    
    echo "\nCreated portal user:\n";
    echo "Email: {$portalUser->email}\n";
    echo "Password: TeleService2024!\n";
    
    DB::commit();
    
    echo "\n✅ Successfully created prepaid company with 9 branches!\n";
    echo "Company ID: {$company->id}\n";
    echo "Balance: {$balance->balance}€\n";
    echo "Rate: {$billingRate->rate_per_minute}€/min\n";
    
} catch (\Exception $e) {
    DB::rollback();
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}