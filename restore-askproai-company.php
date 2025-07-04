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
    // Create AskProAI company
    $company = Company::create([
        'name' => 'AskProAI',
        'email' => 'info@askproai.de',
        'phone' => '+49 89 987654321',
        'address' => 'Innovationsstraße 42, 80333 München',
        'contact_person' => 'Dr. Anna Schmidt',
        'billing_contact_email' => 'billing@askproai.de',
        'billing_contact_phone' => '+49 89 987654322',
        'is_active' => true,
        'active' => true,
        'prepaid_billing_enabled' => true,
        'billing_status' => 'active',
        'billing_type' => 'prepaid',
        'industry' => 'technology',
        'country' => 'DE',
        'currency' => 'EUR',
        'timezone' => 'Europe/Berlin',
        'payment_terms' => 'prepaid',
        'settings' => [
            'needs_appointment_booking' => false,
            'service_type' => 'ai_telephony',
            'business_type' => 'b2b_saas',
            'prepaid_enabled' => true
        ],
    ]);
    
    echo "Created company: {$company->name} (ID: {$company->id})\n";
    
    // Create prepaid balance
    $balance = PrepaidBalance::create([
        'company_id' => $company->id,
        'balance' => 250.00, // Start with 250€
        'reserved_balance' => 0.00,
        'low_balance_threshold' => 50.00, // 20% of 250€
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
    
    // Create single branch in Munich
    $branch = Branch::create([
        'id' => Str::uuid(),
        'company_id' => $company->id,
        'name' => 'AskProAI Hauptsitz München',
        'address' => 'Innovationsstraße 42, 80333 München',
        'phone' => '+49 89 987654321',
        'email' => 'muenchen@askproai.de',
        'is_active' => true,
        'is_primary' => true,
        'city' => 'München',
        'country' => 'DE',
        'timezone' => 'Europe/Berlin',
        'business_hours' => [
            'monday' => ['08:00', '20:00'],
            'tuesday' => ['08:00', '20:00'],
            'wednesday' => ['08:00', '20:00'],
            'thursday' => ['08:00', '20:00'],
            'friday' => ['08:00', '18:00'],
            'saturday' => ['10:00', '14:00'],
            'sunday' => ['closed']
        ],
    ]);
    
    echo "Created branch: {$branch->name} in {$branch->city}\n";
    
    // Create portal admin user
    $portalUser = PortalUser::create([
        'company_id' => $company->id,
        'name' => 'Admin AskProAI',
        'email' => 'admin@askproai.de',
        'password' => bcrypt('AskProAI2024!'),
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
    echo "Password: AskProAI2024!\n";
    
    DB::commit();
    
    echo "\n✅ Successfully restored AskProAI company!\n";
    echo "Company ID: {$company->id}\n";
    echo "Balance: {$balance->balance}€\n";
    echo "Rate: {$billingRate->rate_per_minute}€/min\n";
    echo "\nWe now have 2 prepaid companies:\n";
    echo "1. AskProAI (ID: {$company->id}) - 1 branch in München\n";
    echo "2. TeleService Pro GmbH (ID: 14) - 9 branches across Germany\n";
    
} catch (\Exception $e) {
    DB::rollback();
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}