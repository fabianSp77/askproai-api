<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\PhoneNumber;
use Illuminate\Support\Str;

echo "🚀 Erstelle Demo-Companies...\n\n";

// 1. Demo Zahnarztpraxis
echo "📁 Erstelle Demo Zahnarztpraxis...\n";

$company1 = Company::firstOrCreate(
    ['slug' => 'demo-zahnarztpraxis'],
    [
    'name' => 'Demo Zahnarztpraxis',
    'email' => 'demo-zahnarzt@askproai.de',
    'phone' => '+493012345678',
    'subscription_status' => 'trial',
    'timezone' => 'Europe/Berlin',
    'retell_api_key' => encrypt(config('services.retell.api_key')),
    'calcom_api_key' => encrypt(config('services.calcom.api_key')),
]);

$branch1 = Branch::create([
    'company_id' => $company1->id,
    'name' => 'Praxis Berlin-Mitte',
    'address' => 'Friedrichstraße 123, 10117 Berlin',
    'phone' => '+493012345678',
    'email' => 'mitte@demo-zahnarzt.de',
    'timezone' => 'Europe/Berlin',
    'working_hours' => [
        'monday' => ['start' => '08:00', 'end' => '18:00'],
        'tuesday' => ['start' => '08:00', 'end' => '18:00'],
        'wednesday' => ['start' => '08:00', 'end' => '18:00'],
        'thursday' => ['start' => '08:00', 'end' => '20:00'],
        'friday' => ['start' => '08:00', 'end' => '16:00'],
    ],
]);

// Services
$services1 = [
    ['name' => 'Kontrolluntersuchung', 'duration' => 30, 'price' => 50],
    ['name' => 'Professionelle Zahnreinigung', 'duration' => 60, 'price' => 80],
    ['name' => 'Füllungstherapie', 'duration' => 45, 'price' => 120],
];

foreach ($services1 as $serviceData) {
    Service::create([
        'company_id' => $company1->id,
        'branch_id' => $branch1->id,
        'name' => $serviceData['name'],
        'duration' => $serviceData['duration'],
        'price' => $serviceData['price'],
        'active' => true,
    ]);
}

// Staff
$staff1 = [
    ['name' => 'Dr. Maria Schmidt', 'email' => 'dr.schmidt@demo-zahnarzt.de', 'role' => 'Zahnärztin'],
    ['name' => 'Dr. Thomas Weber', 'email' => 'dr.weber@demo-zahnarzt.de', 'role' => 'Zahnarzt'],
    ['name' => 'Jana Müller', 'email' => 'j.mueller@demo-zahnarzt.de', 'role' => 'Prophylaxe'],
];

foreach ($staff1 as $staffData) {
    Staff::create([
        'company_id' => $company1->id,
        'branch_id' => $branch1->id,
        'name' => $staffData['name'],
        'email' => $staffData['email'],
        'role' => $staffData['role'],
        'is_available' => true,
    ]);
}

// Phone Number
PhoneNumber::create([
    'company_id' => $company1->id,
    'branch_id' => $branch1->id,
    'number' => '+493012345678',
    'is_active' => true,
    'is_primary' => true,
]);

echo "✅ Demo Zahnarztpraxis erstellt!\n\n";

// 2. Demo Friseursalon
echo "📁 Erstelle Demo Friseursalon...\n";

$company2 = Company::create([
    'name' => 'Demo Friseursalon',
    'email' => 'demo-friseur@askproai.de',
    'phone' => '+493087654321',
    'subscription_status' => 'trial',
    'timezone' => 'Europe/Berlin',
    'retell_api_key' => encrypt(config('services.retell.api_key')),
]);

$branch2 = Branch::create([
    'company_id' => $company2->id,
    'name' => 'Hair Style Berlin',
    'address' => 'Kurfürstendamm 100, 10711 Berlin',
    'phone' => '+493087654321',
    'email' => 'info@demo-friseur.de',
    'timezone' => 'Europe/Berlin',
    'working_hours' => [
        'monday' => ['start' => '09:00', 'end' => '18:00'],
        'tuesday' => ['start' => '09:00', 'end' => '18:00'],
        'wednesday' => ['start' => '09:00', 'end' => '18:00'],
        'thursday' => ['start' => '09:00', 'end' => '20:00'],
        'friday' => ['start' => '09:00', 'end' => '20:00'],
        'saturday' => ['start' => '09:00', 'end' => '16:00'],
    ],
]);

// Services
$services2 = [
    ['name' => 'Herrenhaarschnitt', 'duration' => 30, 'price' => 35],
    ['name' => 'Damenhaarschnitt', 'duration' => 45, 'price' => 55],
    ['name' => 'Färben & Schneiden', 'duration' => 120, 'price' => 120],
    ['name' => 'Bart trimmen', 'duration' => 15, 'price' => 15],
];

foreach ($services2 as $serviceData) {
    Service::create([
        'company_id' => $company2->id,
        'branch_id' => $branch2->id,
        'name' => $serviceData['name'],
        'duration' => $serviceData['duration'],
        'price' => $serviceData['price'],
        'active' => true,
    ]);
}

// Staff
$staff2 = [
    ['name' => 'Marco Rossi', 'email' => 'marco@demo-friseur.de', 'role' => 'Master Stylist'],
    ['name' => 'Sophie Klein', 'email' => 'sophie@demo-friseur.de', 'role' => 'Stylistin'],
    ['name' => 'Tom Fischer', 'email' => 'tom@demo-friseur.de', 'role' => 'Barber'],
];

foreach ($staff2 as $staffData) {
    Staff::create([
        'company_id' => $company2->id,
        'branch_id' => $branch2->id,
        'name' => $staffData['name'],
        'email' => $staffData['email'],
        'role' => $staffData['role'],
        'is_available' => true,
    ]);
}

// Phone Number
PhoneNumber::create([
    'company_id' => $company2->id,
    'branch_id' => $branch2->id,
    'number' => '+493087654321',
    'is_active' => true,
    'is_primary' => true,
]);

echo "✅ Demo Friseursalon erstellt!\n\n";

// 3. Demo Anwaltskanzlei
echo "📁 Erstelle Demo Anwaltskanzlei...\n";

$company3 = Company::create([
    'name' => 'Demo Anwaltskanzlei',
    'email' => 'demo-anwalt@askproai.de',
    'phone' => '+493055555555',
    'subscription_status' => 'trial',
    'timezone' => 'Europe/Berlin',
    'retell_api_key' => encrypt(config('services.retell.api_key')),
]);

$branch3 = Branch::create([
    'company_id' => $company3->id,
    'name' => 'Rechtsanwälte Berlin',
    'address' => 'Unter den Linden 50, 10117 Berlin',
    'phone' => '+493055555555',
    'email' => 'info@demo-anwalt.de',
    'timezone' => 'Europe/Berlin',
    'working_hours' => [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '15:00'],
    ],
]);

// Services
$services3 = [
    ['name' => 'Erstberatung', 'duration' => 60, 'price' => 150],
    ['name' => 'Vertragsberatung', 'duration' => 90, 'price' => 250],
    ['name' => 'Arbeitsrecht Beratung', 'duration' => 60, 'price' => 200],
];

foreach ($services3 as $serviceData) {
    Service::create([
        'company_id' => $company3->id,
        'branch_id' => $branch3->id,
        'name' => $serviceData['name'],
        'duration' => $serviceData['duration'],
        'price' => $serviceData['price'],
        'active' => true,
    ]);
}

// Staff
$staff3 = [
    ['name' => 'Dr. jur. Anna Wagner', 'email' => 'wagner@demo-anwalt.de', 'role' => 'Rechtsanwältin'],
    ['name' => 'RA Michael Bauer', 'email' => 'bauer@demo-anwalt.de', 'role' => 'Rechtsanwalt'],
];

foreach ($staff3 as $staffData) {
    Staff::create([
        'company_id' => $company3->id,
        'branch_id' => $branch3->id,
        'name' => $staffData['name'],
        'email' => $staffData['email'],
        'role' => $staffData['role'],
        'is_available' => true,
    ]);
}

// Phone Number
PhoneNumber::create([
    'company_id' => $company3->id,
    'branch_id' => $branch3->id,
    'number' => '+493055555555',
    'is_active' => true,
    'is_primary' => true,
]);

echo "✅ Demo Anwaltskanzlei erstellt!\n\n";

echo "🎉 Alle Demo-Companies wurden erfolgreich erstellt!\n\n";

// Zeige Zusammenfassung
echo "📊 ZUSAMMENFASSUNG:\n";
echo "==================\n\n";

$companies = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->with(['branches' => function ($query) {
        $query->withoutGlobalScope(\App\Scopes\TenantScope::class);
    }])
    ->whereIn('name', ['Demo Zahnarztpraxis', 'Demo Friseursalon', 'Demo Anwaltskanzlei'])
    ->get();

foreach ($companies as $company) {
    echo "📁 {$company->name}\n";
    echo "   Email: {$company->email}\n";
    echo "   Status: {$company->subscription_status}\n";
    
    foreach ($company->branches as $branch) {
        $staffCount = Staff::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('branch_id', $branch->id)->count();
        $serviceCount = Service::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('branch_id', $branch->id)->count();
        
        echo "   └── {$branch->name}\n";
        echo "       👥 {$staffCount} Mitarbeiter\n";
        echo "       🛠️  {$serviceCount} Services\n";
    }
    echo "\n";
}

echo "\n✅ Das System ist jetzt mit vollständig konfigurierten Demo-Companies bereit!\n";