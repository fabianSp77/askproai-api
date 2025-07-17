<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

// Company ID für Demo GmbH
$companyId = 16;

echo "🎯 Erstelle Demo-Daten für Company ID: $companyId\n";
echo "=========================================\n";

// Disable tenant scope for data creation
\App\Models\Call::withoutGlobalScopes()->where('company_id', $companyId)->delete();
\App\Models\Customer::withoutGlobalScopes()->where('company_id', $companyId)->delete();
\App\Models\Appointment::withoutGlobalScopes()->where('company_id', $companyId)->delete();

// Create demo customers
$customers = [];
for ($i = 1; $i <= 5; $i++) {
    $customer = \App\Models\Customer::withoutGlobalScopes()->create([
        'company_id' => $companyId,
        'name' => "Demo Kunde $i",
        'phone' => "+49 151 " . rand(10000000, 99999999),
        'email' => "kunde{$i}@demo.de",
        'notes' => 'Demo-Kunde für Testzwecke'
    ]);
    $customers[] = $customer;
    echo "✅ Kunde erstellt: {$customer->name}\n";
}

// Create demo calls
for ($i = 1; $i <= 10; $i++) {
    $customer = $customers[array_rand($customers)];
    $call = \App\Models\Call::withoutGlobalScopes()->create([
        'company_id' => $companyId,
        'call_id' => 'demo_' . uniqid(),
        'agent_id' => 'demo_agent',
        'phone_number' => $customer->phone,
        'from_number' => $customer->phone,
        'to_number' => '+49 30 12345678',
        'call_type' => 'inbound',
        'direction' => 'inbound',
        'call_status' => 'completed',
        'start_time' => now()->subDays(rand(0, 7))->subHours(rand(0, 23)),
        'end_time' => now()->subDays(rand(0, 7))->subHours(rand(0, 23))->addMinutes(rand(1, 10)),
        'duration' => rand(60, 300),
        'duration_ms' => rand(60000, 300000),
        'cost' => rand(10, 50) / 100,
        'transcript' => "Demo-Anruf $i: Kunde ruft an wegen Terminvereinbarung.",
        'summary' => "Kunde möchte einen Termin vereinbaren",
        'language' => 'de',
        'sentiment' => 'positive',
        'customer_name' => $customer->name,
        'customer_id' => $customer->id,
        'retell_call_id' => 'retell_' . uniqid(),
    ]);
    echo "✅ Anruf erstellt: {$call->call_id}\n";
}

// Create demo appointments
for ($i = 1; $i <= 5; $i++) {
    $customer = $customers[array_rand($customers)];
    $appointment = \App\Models\Appointment::withoutGlobalScopes()->create([
        'company_id' => $companyId,
        'customer_id' => $customer->id,
        'title' => "Beratungstermin mit {$customer->name}",
        'description' => 'Demo-Termin',
        'start_time' => now()->addDays(rand(1, 14))->setHour(rand(9, 17))->setMinute(0),
        'end_time' => now()->addDays(rand(1, 14))->setHour(rand(10, 18))->setMinute(0),
        'status' => 'scheduled',
        'location' => 'Demo GmbH, Berlin',
    ]);
    echo "✅ Termin erstellt: {$appointment->title}\n";
}

echo "\n✅ Demo-Daten erfolgreich erstellt!\n";