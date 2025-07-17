<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\Call;
use App\Models\Customer;
use App\Models\CallActivity;
use Illuminate\Support\Str;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔴 LIVE Demo-Aktivität Generator\n";
echo "================================\n";
echo "Erstellt alle 30 Sekunden einen neuen Anruf\n";
echo "Drücke Ctrl+C zum Beenden\n\n";

// Get demo companies
$companies = Company::whereIn('name', [
    'Praxis Dr. Schmidt',
    'Kanzlei Müller & Partner',
    'Salon Bella'
])->get();

if ($companies->isEmpty()) {
    echo "❌ Keine Demo-Companies gefunden!\n";
    exit(1);
}

// Live call scenarios
$liveScenarios = [
    [
        'customer_name' => 'Live Demo Anrufer',
        'transcript' => "AI: Guten Tag, wie kann ich Ihnen helfen?\n\nAnrufer: Ich möchte einen Termin vereinbaren.\n\nAI: Sehr gerne. Wann würde es Ihnen passen?\n\nAnrufer: Diese Woche noch wenn möglich.\n\nAI: Ich schaue nach... Ja, ich hätte morgen um 15 Uhr noch einen Termin frei.\n\nAnrufer: Perfekt, den nehme ich.",
        'duration' => rand(45, 90),
        'status' => 'in_progress'
    ],
    [
        'customer_name' => 'Neuer Kunde',
        'transcript' => "AI: Willkommen, wie kann ich behilflich sein?\n\nAnrufer: Ich bin neu in der Stadt und suche einen [Service].\n\nAI: Herzlich willkommen! Ich erkläre Ihnen gerne unser Angebot...",
        'duration' => rand(60, 120),
        'status' => 'in_progress'
    ],
    [
        'customer_name' => 'Stammkunde',
        'transcript' => "AI: Schön Sie wieder zu hören! Wie geht es Ihnen?\n\nAnrufer: Gut, danke. Ich brauche wieder einen Termin.\n\nAI: Natürlich, Ihren üblichen Termin?\n\nAnrufer: Ja, genau.",
        'duration' => rand(30, 60),
        'status' => 'ended'
    ]
];

$callCount = 0;

// Signal handler for clean shutdown
$running = true;
pcntl_signal(SIGINT, function() use (&$running) {
    $running = false;
    echo "\n\n🛑 Beende Live-Demo Generator...\n";
});

echo "🚀 Starte Live-Aktivität...\n\n";

while ($running) {
    pcntl_signal_dispatch();
    
    // Select random company and scenario
    $company = $companies->random();
    $scenario = $liveScenarios[array_rand($liveScenarios)];
    
    // Generate phone number
    $phoneNumber = '+49' . rand(170, 179) . rand(1000000, 9999999);
    
    // Create live call
    $call = Call::create([
        'retell_call_id' => 'call_live_' . Str::uuid(),
        'company_id' => $company->id,
        'from_number' => $phoneNumber,
        'to_number' => $company->phone,
        'status' => $scenario['status'],
        'direction' => 'inbound',
        'call_type' => 'AI',
        'duration_sec' => $scenario['status'] === 'ended' ? $scenario['duration'] : 0,
        'transcript_raw' => $scenario['transcript'],
        'transcript_formatted' => $scenario['transcript'],
        'started_at' => now(),
        'ended_at' => $scenario['status'] === 'ended' ? now()->addSeconds($scenario['duration']) : null,
        'created_at' => now()
    ]);
    
    // Add call activity
    CallActivity::create([
        'call_id' => $call->id,
        'company_id' => $company->id,
        'activity_type' => 'call_started',
        'title' => 'Anruf gestartet',
        'description' => "Neuer Anruf von {$scenario['customer_name']}",
        'is_system' => true,
        'created_at' => now()
    ]);
    
    // Create or find customer
    $customer = Customer::firstOrCreate(
        ['phone' => $phoneNumber],
        [
            'name' => $scenario['customer_name'] . ' ' . $callCount,
            'company_id' => $company->id,
            'created_at' => now()
        ]
    );
    
    // Update call with customer
    $call->update(['customer_id' => $customer->id]);
    
    $callCount++;
    
    // Show activity
    $timestamp = now()->format('H:i:s');
    echo "[$timestamp] 📞 Neuer Anruf bei {$company->name}\n";
    echo "            Von: {$scenario['customer_name']}\n";
    echo "            Status: " . ($scenario['status'] === 'in_progress' ? '🔴 Aktiv' : '✅ Beendet') . "\n";
    echo "            Anrufe heute: " . Call::whereDate('created_at', today())->count() . "\n\n";
    
    // If call should end after some time
    if ($scenario['status'] === 'in_progress' && rand(1, 3) === 1) {
        sleep(5); // Wait 5 seconds
        
        $call->update([
            'status' => 'ended',
            'duration_sec' => rand(45, 120),
            'ended_at' => now()
        ]);
        
        CallActivity::create([
            'call_id' => $call->id,
            'company_id' => $company->id,
            'activity_type' => 'call_ended',
            'title' => 'Anruf beendet',
            'description' => "Anruf beendet - Termin vereinbart",
            'is_system' => true,
            'created_at' => now()
        ]);
        
        echo "[$timestamp] ✅ Anruf beendet nach " . $call->duration_sec . " Sekunden\n\n";
    }
    
    // Wait before next call (20-40 seconds)
    $waitTime = rand(20, 40);
    echo "⏳ Nächster Anruf in {$waitTime} Sekunden...\n";
    echo "----------------------------------------\n";
    
    for ($i = 0; $i < $waitTime; $i++) {
        pcntl_signal_dispatch();
        if (!$running) break;
        sleep(1);
    }
}

echo "\n📊 Live-Demo Statistik:\n";
echo "======================\n";
echo "✅ Erstellte Anrufe: {$callCount}\n";
echo "✅ Anrufe heute gesamt: " . Call::whereDate('created_at', today())->count() . "\n\n";

echo "Demo-Generator beendet.\n";