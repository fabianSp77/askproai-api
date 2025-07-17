<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\CallActivity;
use App\Models\PrepaidBalance;
use App\Models\BalanceTopup;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🎯 Füge mehr realistische Demo-Daten hinzu...\n";
echo "==========================================\n\n";

// Get companies
$companies = Company::whereIn('name', [
    'Zahnarztpraxis Dr. Schmidt',
    'Physiotherapie Bewegung Plus', 
    'Autohaus Müller GmbH'
])->with(['branches', 'prepaidBalance'])->get();

if ($companies->count() !== 3) {
    echo "❌ Nicht alle Demo-Companies gefunden!\n";
    exit(1);
}

// Verschiedene Anruf-Szenarien
$callScenarios = [
    // Dr. Schmidt - Medizinische Anfragen
    [
        'company' => 'Zahnarztpraxis Dr. Schmidt',
        'scenarios' => [
            [
                'customer_name' => 'Maria Weber',
                'phone' => '+49' . rand(170, 179) . rand(1000000, 9999999),
                'transcript' => "AI: Praxis Dr. Schmidt, guten Tag. Wie kann ich Ihnen helfen?\n\nAnrufer: Hallo, ich habe seit drei Tagen starke Kopfschmerzen.\n\nAI: Das tut mir leid zu hören. Ich kann Ihnen gerne einen Termin bei Dr. Schmidt vereinbaren. Wann würde es Ihnen passen?\n\nAnrufer: Am liebsten noch diese Woche.\n\nAI: Ich habe Donnerstag um 14:30 Uhr oder Freitag um 9:00 Uhr frei. Was passt Ihnen besser?\n\nAnrufer: Donnerstag wäre perfekt.\n\nAI: Sehr gut. Ich habe Sie für Donnerstag, 14:30 Uhr eingetragen. Bitte bringen Sie Ihre Versichertenkarte mit.",
                'duration' => 95,
                'appointment_created' => true,
                'time_offset' => -2 // Stunden
            ],
            [
                'customer_name' => 'Thomas Becker',
                'phone' => '+49' . rand(170, 179) . rand(1000000, 9999999),
                'transcript' => "AI: Praxis Dr. Schmidt, guten Tag.\n\nAnrufer: Ich brauche ein Rezept für meine Blutdruckmedikamente.\n\nAI: Gerne. Können Sie mir Ihren Namen nennen?\n\nAnrufer: Thomas Becker.\n\nAI: Vielen Dank. Ich leite das an Dr. Schmidt weiter. Das Rezept liegt morgen ab 10 Uhr zur Abholung bereit.",
                'duration' => 48,
                'appointment_created' => false,
                'time_offset' => -3
            ],
            [
                'customer_name' => 'Sabine Hoffmann',
                'phone' => '+49' . rand(170, 179) . rand(1000000, 9999999),
                'transcript' => "AI: Praxis Dr. Schmidt, einen schönen guten Morgen.\n\nAnrufer: Meine Tochter hat Fieber. Können wir heute noch vorbeikommen?\n\nAI: Natürlich, das ist wichtig. Wie hoch ist das Fieber?\n\nAnrufer: 39,2 Grad.\n\nAI: Kommen Sie bitte sofort vorbei. Ich informiere Dr. Schmidt. Wie heißt Ihre Tochter?\n\nAnrufer: Emma Hoffmann, sie ist 6 Jahre alt.\n\nAI: Alles klar. Dr. Schmidt erwartet Sie.",
                'duration' => 72,
                'appointment_created' => true,
                'time_offset' => -4
            ]
        ]
    ],
    // Physiotherapie - Gesundheitsanfragen
    [
        'company' => 'Physiotherapie Bewegung Plus',
        'scenarios' => [
            [
                'customer_name' => 'Klaus Richter',
                'phone' => '+49' . rand(170, 179) . rand(1000000, 9999999),
                'transcript' => "AI: Physiotherapie Bewegung Plus, guten Tag.\n\nAnrufer: Ich habe starke Rückenschmerzen und brauche dringend einen Termin.\n\nAI: Das tut mir leid zu hören. Wo genau haben Sie die Schmerzen?\n\nAnrufer: Im unteren Rücken, besonders beim Bücken.\n\nAI: Ich verstehe. Haben Sie ein Rezept vom Arzt?\n\nAnrufer: Ja, für 6x Krankengymnastik.\n\nAI: Perfekt. Morgen um 11 Uhr hätten wir noch einen Termin frei. Passt das?\n\nAnrufer: Ja, das wäre super.\n\nAI: Gut. Bringen Sie bitte das Rezept und bequeme Kleidung mit.",
                'duration' => 124,
                'appointment_created' => true,
                'time_offset' => -1
            ],
            [
                'customer_name' => 'Andrea Schulz',
                'phone' => '+49' . rand(170, 179) . rand(1000000, 9999999),
                'transcript' => "AI: Physiotherapie Bewegung Plus, wie kann ich Ihnen helfen?\n\nAnrufer: Mein Knie tut weh nach einer OP.\n\nAI: Wann war denn die Operation?\n\nAnrufer: Vor zwei Wochen, Kreuzbandriss.\n\nAI: Ah, da brauchen Sie spezielle Nachbehandlung. Haben Sie ein Rezept?\n\nAnrufer: Ja, vom Chirurgen.\n\nAI: Gut. Unser Sporttherapeut ist darauf spezialisiert. Mittwoch 10 Uhr?\n\nAnrufer: Passt perfekt.\n\nAI: Super. Die erste Einheit dauert etwa 45 Minuten für die Anamnese.",
                'duration' => 86,
                'appointment_created' => true,
                'time_offset' => -5
            ]
        ]
    ],
    // Autohaus Müller - Service Termine
    [
        'company' => 'Autohaus Müller GmbH',
        'scenarios' => [
            [
                'customer_name' => 'Jennifer Meyer',
                'phone' => '+49' . rand(170, 179) . rand(1000000, 9999999),
                'transcript' => "AI: Autohaus Müller, schönen guten Tag.\n\nAnrufer: Ich muss zur Inspektion mit meinem Golf.\n\nAI: Gerne. Welches Baujahr ist Ihr Golf?\n\nAnrufer: 2019, die 30.000er Inspektion steht an.\n\nAI: Verstehe. Die große Inspektion. Wann würde es Ihnen passen?\n\nAnrufer: Samstag früh wäre ideal.\n\nAI: Samstag 8 Uhr können Sie direkt vorfahren. Die Inspektion dauert etwa 2 Stunden.\n\nAnrufer: Perfekt, dann komme ich.\n\nAI: Bringen Sie bitte Fahrzeugschein und Serviceheft mit.",
                'duration' => 93,
                'appointment_created' => true,
                'time_offset' => -2
            ],
            [
                'customer_name' => 'Michaela Wagner',
                'phone' => '+49' . rand(170, 179) . rand(1000000, 9999999),
                'transcript' => "AI: Autohaus Müller, guten Tag.\n\nAnrufer: Meine Motorkontrollleuchte ist an.\n\nAI: Oh, das sollten wir schnell prüfen. Welches Fahrzeug fahren Sie?\n\nAnrufer: Einen Passat, Baujahr 2020.\n\nAI: Können Sie noch fahren oder gibt es Probleme?\n\nAnrufer: Läuft noch, aber ich bin beunruhigt.\n\nAI: Verständlich. Kommen Sie morgen 16:30 Uhr zur Diagnose?\n\nAnrufer: Ja, das geht.\n\nAI: Gut. Die Fehlerauslesung dauert etwa 30 Minuten und kostet 49 Euro.",
                'duration' => 108,
                'appointment_created' => true,
                'time_offset' => -6
            ]
        ]
    ]
];

$totalCalls = 0;
$totalAppointments = 0;

foreach ($callScenarios as $companyScenarios) {
    $company = $companies->where('name', $companyScenarios['company'])->first();
    
    echo "📞 Erstelle Anrufe für {$company->name}:\n";
    
    foreach ($companyScenarios['scenarios'] as $scenario) {
        // Create or find customer
        $customer = Customer::firstOrCreate(
            ['phone' => $scenario['phone']],
            [
                'name' => $scenario['customer_name'],
                'email' => strtolower(str_replace(' ', '.', $scenario['customer_name'])) . '@example.com',
                'company_id' => $company->id,
                'created_at' => now()->subHours($scenario['time_offset'])
            ]
        );
        
        // Create call
        $call = Call::create([
            'retell_call_id' => 'call_demo_' . uniqid(),
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'from_number' => $scenario['phone'],
            'to_number' => $company->phone,
            'status' => 'ended',
            'direction' => 'inbound',
            'call_type' => 'AI',
            'duration_sec' => $scenario['duration'],
            'transcript_raw' => $scenario['transcript'],
            'transcript_formatted' => $scenario['transcript'],
            'started_at' => now()->subHours($scenario['time_offset'])->subMinutes(2),
            'ended_at' => now()->subHours($scenario['time_offset']),
            'created_at' => now()->subHours($scenario['time_offset'])
        ]);
        
        // Add call activities
        CallActivity::create([
            'call_id' => $call->id,
            'company_id' => $company->id,
            'activity_type' => 'customer_identified',
            'title' => 'Kunde identifiziert',
            'description' => "Kunde identifiziert: {$scenario['customer_name']}",
            'is_system' => true,
            'created_at' => now()->subHours($scenario['time_offset'])->subMinutes(1)
        ]);
        
        // Create appointment if applicable
        if ($scenario['appointment_created']) {
            $appointmentDate = now()->addDays(rand(1, 7));
            
            $branchId = $company->branches->count() > 0 ? $company->branches->first()->id : null;
            
            $appointment = Appointment::create([
                'company_id' => $company->id,
                'branch_id' => $branchId,
                'customer_id' => $customer->id,
                'call_id' => $call->id,
                'appointment_datetime' => $appointmentDate,
                'duration_minutes' => rand(30, 90),
                'status' => 'scheduled',
                'notes' => "Termin vereinbart während Anruf",
                'created_at' => now()->subHours($scenario['time_offset'])
            ]);
            
            CallActivity::create([
                'call_id' => $call->id,
                'company_id' => $company->id,
                'activity_type' => 'appointment_scheduled',
                'title' => 'Termin vereinbart',
                'description' => "Termin vereinbart für " . $appointmentDate->format('d.m.Y H:i'),
                'is_system' => true,
                'created_at' => now()->subHours($scenario['time_offset'])
            ]);
            
            $totalAppointments++;
        }
        
        $totalCalls++;
        echo "   ✅ {$scenario['customer_name']} - " . ($scenario['appointment_created'] ? 'Mit Termin' : 'Ohne Termin') . "\n";
    }
    
    echo "\n";
}

// Add some missed calls (showing 24/7 benefit)
echo "📵 Füge verpasste Anrufe hinzu (Außerhalb Geschäftszeiten):\n";

$missedCallTimes = [
    ['hour' => 22, 'minute' => 30], // Spät abends
    ['hour' => 6, 'minute' => 45],   // Früh morgens
    ['hour' => 21, 'minute' => 15],  // Abends
    ['hour' => 7, 'minute' => 20],   // Morgens
    ['hour' => 23, 'minute' => 10],  // Nachts
];

foreach ($companies as $company) {
    foreach ($missedCallTimes as $time) {
        $callTime = now()->setTime($time['hour'], $time['minute'])->subDays(rand(1, 3));
        
        $call = Call::create([
            'retell_call_id' => 'call_missed_' . uniqid(),
            'company_id' => $company->id,
            'from_number' => '+49' . rand(170, 179) . rand(1000000, 9999999),
            'to_number' => $company->phone,
            'status' => 'ended',
            'direction' => 'inbound',
            'call_type' => 'AI',
            'duration_sec' => rand(45, 120),
            'transcript_raw' => "AI: {$company->name}, guten Abend. Wie kann ich Ihnen helfen?\n\nAnrufer: Oh, Sie haben noch auf? Ich dachte schon, ich erreiche niemanden mehr.\n\nAI: Wir sind 24/7 für Sie da. Wie kann ich Ihnen helfen?",
            'started_at' => $callTime,
            'ended_at' => $callTime->copy()->addSeconds(rand(45, 120)),
            'created_at' => $callTime
        ]);
        
        $totalCalls++;
    }
    echo "   ✅ 5 After-Hours Calls für {$company->name}\n";
}

// Update balance usage to show activity
echo "\n💰 Aktualisiere Guthaben-Nutzung:\n";

foreach ($companies as $company) {
    $balance = $company->prepaidBalance;
    if ($balance) {
        // Calculate usage based on calls
        $callMinutes = Call::where('company_id', $company->id)
            ->sum(DB::raw('CEIL(duration_sec / 60)'));
        
        $usage = $callMinutes * 0.15; // 0.15€ per minute cost
        
        // Add a recent topup to show activity
        BalanceTopup::create([
            'company_id' => $company->id,
            'amount' => 50.00,
            'payment_method' => 'auto_topup',
            'status' => 'completed',
            'description' => 'Automatische Aufladung',
            'processed_at' => now()->subDays(2),
            'created_at' => now()->subDays(2)
        ]);
        
        echo "   ✅ {$company->name}: {$callMinutes} Minuten = " . number_format($usage, 2) . "€ Kosten\n";
    }
}

// Summary
echo "\n📊 Zusammenfassung:\n";
echo "==================\n";
echo "✅ Neue Anrufe erstellt: {$totalCalls}\n";
echo "✅ Neue Termine erstellt: {$totalAppointments}\n";
echo "✅ After-Hours Calls: " . (count($companies) * count($missedCallTimes)) . "\n";
echo "✅ Verschiedene Szenarien abgedeckt\n\n";

// Calculate impressive stats
$totalCallsAll = Call::count();
$appointmentRate = ($totalAppointments / $totalCalls) * 100;
$afterHoursCalls = Call::whereTime('started_at', '<', '08:00:00')
    ->orWhereTime('started_at', '>', '18:00:00')
    ->count();
$afterHoursPercent = ($afterHoursCalls / $totalCallsAll) * 100;

echo "🎯 Beeindruckende Demo-Statistiken:\n";
echo "====================================\n";
echo "📞 Gesamt-Anrufe: {$totalCallsAll}\n";
echo "📅 Termin-Quote: " . number_format($appointmentRate, 1) . "%\n";
echo "🌙 After-Hours Anrufe: " . number_format($afterHoursPercent, 1) . "% (Ohne AI verpasst!)\n";
echo "💰 Potenzial: " . number_format($afterHoursCalls * 50, 0) . "€ zusätzlicher Umsatz\n\n";

echo "✅ Demo-Daten sind jetzt noch realistischer und überzeugender!\n";