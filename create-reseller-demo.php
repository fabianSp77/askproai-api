<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\PortalUser;
use App\Models\Branch;
use App\Models\PrepaidBalance;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 Erstelle Reseller Demo-Szenario...\n\n";

DB::beginTransaction();

try {
    // 1. Create Reseller Company
    echo "1️⃣ Erstelle Reseller Firma...\n";
    $reseller = Company::create([
        'name' => 'TechPartner GmbH',
        'slug' => 'techpartner-gmbh',
        'company_type' => 'reseller',
        'is_white_label' => true,
        'commission_rate' => 20.00,
        'email' => 'info@techpartner.de',
        'phone' => '+49 30 123456',
        'address' => 'Kurfürstendamm 123',
        'city' => 'Berlin',
        'postal_code' => '10719',
        'country' => 'DE',
        'timezone' => 'Europe/Berlin',
        'currency' => 'EUR',
        'is_active' => true,
        'white_label_settings' => [
            'brand_name' => 'TechPartner AI Solutions',
            'primary_color' => '#1E40AF',
            'logo_url' => null,
            'custom_domain' => null,
            'hide_askpro_branding' => true,
        ],
    ]);

    // Create reseller portal user
    $resellerAdmin = PortalUser::create([
        'company_id' => $reseller->id,
        'name' => 'Max Mustermann',
        'email' => 'max@techpartner.de',
        'password' => Hash::make('demo123'),
        'role' => 'owner',
        'is_active' => true,
        'can_access_child_companies' => true,
    ]);

    // Create prepaid balance for reseller
    PrepaidBalance::create([
        'company_id' => $reseller->id,
        'balance' => 5000.00,
        'reserved_balance' => 0.00,
        'low_balance_threshold' => 500.00,
    ]);

    echo "✅ Reseller erstellt: {$reseller->name}\n\n";

    // 2. Create Client Companies
    $industries = [
        [
            'name' => 'Zahnarztpraxis Dr. Schmidt',
            'industry' => 'Zahnarzt',
            'email' => 'praxis@dr-schmidt.de',
            'phone' => '+49 89 234567',
            'city' => 'München',
            'services' => ['Kontrolluntersuchung', 'Zahnreinigung', 'Bleaching', 'Notfalltermin'],
        ],
        [
            'name' => 'Physiotherapie Bewegung Plus',
            'industry' => 'Physiotherapie',
            'email' => 'info@bewegung-plus.de',
            'phone' => '+49 40 345678',
            'city' => 'Hamburg',
            'services' => ['Krankengymnastik', 'Manuelle Therapie', 'Massage', 'Lymphdrainage'],
        ],
        [
            'name' => 'Autohaus Müller GmbH',
            'industry' => 'Autohaus',
            'email' => 'service@autohaus-mueller.de',
            'phone' => '+49 711 456789',
            'city' => 'Stuttgart',
            'services' => ['Inspektion', 'HU/AU', 'Reifenwechsel', 'Ölwechsel'],
        ],
        [
            'name' => 'Friseursalon Style & Beauty',
            'industry' => 'Friseur',
            'email' => 'termine@style-beauty.de',
            'phone' => '+49 221 567890',
            'city' => 'Köln',
            'services' => ['Haarschnitt Damen', 'Haarschnitt Herren', 'Färben', 'Styling'],
        ],
        [
            'name' => 'Rechtsanwälte Meier & Partner',
            'industry' => 'Rechtsanwalt',
            'email' => 'kanzlei@meier-partner.de',
            'phone' => '+49 69 678901',
            'city' => 'Frankfurt',
            'services' => ['Erstberatung', 'Vertragsrecht', 'Arbeitsrecht', 'Familienrecht'],
        ],
    ];

    $count = count($industries);
    echo "2️⃣ Erstelle {$count} Kunden-Firmen...\n";

    foreach ($industries as $i => $data) {
        $client = Company::create([
            'name' => $data['name'],
            'slug' => \Str::slug($data['name']),
            'parent_company_id' => $reseller->id,
            'company_type' => 'client',
            'industry' => $data['industry'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'city' => $data['city'],
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
            'is_active' => true,
            'trial_ends_at' => null, // No trial for reseller clients
        ]);

        // Create branch
        $branch = Branch::create([
            'company_id' => $client->id,
            'name' => 'Hauptstandort',
            'phone' => $data['phone'],
            'email' => $data['email'],
            'city' => $data['city'],
            'country' => 'DE',
            'active' => true,
        ]);

        // Create services
        foreach ($data['services'] as $j => $serviceName) {
            Service::create([
                'company_id' => $client->id,
                'branch_id' => $branch->id,
                'name' => $serviceName,
                'duration' => rand(30, 90),
                'price' => rand(30, 150),
                'is_active' => true,
            ]);
        }

        // Create staff
        $staffNames = ['Anna Weber', 'Thomas Klein', 'Julia Hoffmann'];
        foreach (array_slice($staffNames, 0, rand(1, 3)) as $staffName) {
            Staff::create([
                'company_id' => $client->id,
                'branch_id' => $branch->id,
                'name' => $staffName,
                'email' => \Str::slug($staffName) . '@' . parse_url($data['email'], PHP_URL_HOST),
                'role' => 'employee',
                'is_active' => true,
            ]);
        }

        // Create portal user
        PortalUser::create([
            'company_id' => $client->id,
            'name' => 'Admin ' . $data['name'],
            'email' => 'admin@' . parse_url($data['email'], PHP_URL_HOST),
            'password' => Hash::make('demo123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create prepaid balance
        $balance = PrepaidBalance::create([
            'company_id' => $client->id,
            'balance' => rand(100, 500),
            'reserved_balance' => 0.00,
            'low_balance_threshold' => 50.00,
        ]);

        // Create some demo data
        $numCalls = rand(5, 15);
        $numAppointments = rand(3, 8);
        $numCustomers = rand(10, 25);

        // Create customers
        for ($j = 0; $j < $numCustomers; $j++) {
            Customer::create([
                'company_id' => $client->id,
                'branch_id' => $branch->id,
                'name' => fake()->name(),
                'phone' => '+49' . rand(150, 179) . rand(1000000, 9999999),
                'email' => fake()->email(),
                'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
            ]);
        }

        // Create calls (last 7 days)
        for ($j = 0; $j < $numCalls; $j++) {
            $startTime = fake()->dateTimeBetween('-7 days', 'now');
            $duration = rand(60, 300);
            Call::create([
                'company_id' => $client->id,
                'branch_id' => $branch->id,
                'call_id' => 'demo_' . uniqid(),
                'phone_number' => '+49' . rand(150, 179) . rand(1000000, 9999999),
                'from_number' => $data['phone'],
                'direction' => 'inbound',
                'status' => 'completed',
                'duration_sec' => $duration,
                'started_at' => $startTime,
                'ended_at' => clone($startTime)->modify("+{$duration} seconds"),
                'metadata' => [
                    'customer_name' => fake()->name(),
                    'reason' => fake()->randomElement(['Terminbuchung', 'Anfrage', 'Stornierung']),
                ],
            ]);
        }

        // Create appointments (next 14 days)
        $customers = Customer::where('company_id', $client->id)->get();
        $staff = Staff::where('company_id', $client->id)->get();
        $services = Service::where('company_id', $client->id)->get();

        for ($j = 0; $j < $numAppointments; $j++) {
            Appointment::create([
                'company_id' => $client->id,
                'branch_id' => $branch->id,
                'customer_id' => $customers->random()->id,
                'staff_id' => $staff->random()->id,
                'service_id' => $services->random()->id,
                'scheduled_at' => fake()->dateTimeBetween('now', '+14 days'),
                'duration_minutes' => rand(30, 90),
                'status' => fake()->randomElement(['scheduled', 'confirmed']),
                'notes' => fake()->optional()->sentence(),
            ]);
        }

        echo "  ✅ {$client->name} erstellt mit {$numCalls} Anrufen, {$numAppointments} Terminen\n";
    }

    echo "\n3️⃣ Erstelle Super Admin Zugang für Demo...\n";
    
    // Make sure Super Admin role exists
    $superAdminRole = Role::firstOrCreate(
        ['name' => 'Super Admin', 'guard_name' => 'web'],
        ['name' => 'Super Admin', 'guard_name' => 'web']
    );
    
    // Make sure admin user exists with known password
    $adminUser = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if (!$adminUser) {
        $adminUser = \App\Models\User::create([
            'name' => 'Demo Admin',
            'email' => 'demo@askproai.de',
            'password' => Hash::make('demo123'),
            'email_verified_at' => now(),
        ]);
    } else {
        $adminUser->update(['password' => Hash::make('demo123')]);
    }
    
    // Assign Super Admin role
    if (!$adminUser->hasRole('Super Admin')) {
        $adminUser->assignRole('Super Admin');
    }

    DB::commit();
    
    echo "\n✅ Demo-Szenario erfolgreich erstellt!\n\n";
    echo "📋 Zugangsdaten:\n";
    echo "================================\n";
    echo "🔐 Super Admin (Admin Portal):\n";
    echo "   URL: https://api.askproai.de/admin\n";
    echo "   Email: demo@askproai.de\n";
    echo "   Passwort: demo123\n\n";
    
    echo "🏢 Reseller Portal:\n";
    echo "   URL: https://api.askproai.de/business\n";
    echo "   Email: max@techpartner.de\n";
    echo "   Passwort: demo123\n\n";
    
    echo "👥 Kunde (Beispiel):\n";
    echo "   URL: https://api.askproai.de/business\n";
    echo "   Email: admin@dr-schmidt.de\n";
    echo "   Passwort: demo123\n";
    echo "================================\n\n";
    
    echo "🎯 Demo-Ablauf:\n";
    echo "1. Als Super Admin einloggen\n";
    echo "2. Zu 'Kundenverwaltung' navigieren\n";
    echo "3. TechPartner GmbH und deren Kunden zeigen\n";
    echo "4. In verschiedene Portale wechseln\n";
    echo "5. White-Label Einstellungen demonstrieren\n\n";

} catch (\Exception $e) {
    DB::rollback();
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "✨ Fertig!\n";