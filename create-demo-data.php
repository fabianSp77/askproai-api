<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

// Find demo user
$user = PortalUser::where('email', 'fabianspitzer@icloud.com')->first();
if (!$user) {
    die("❌ Demo-User nicht gefunden!\n");
}

$company = $user->company;
echo "🎯 Erstelle Demo-Daten für: {$company->name}\n";
echo "=====================================\n";

// Set company context for tenant scope
app()->instance('company_id', $company->id);

// Create demo customers
$customers = [];
$demoCustomers = [
    ['name' => 'Max Mustermann', 'phone' => '+49 151 12345678', 'email' => 'max@example.com'],
    ['name' => 'Erika Musterfrau', 'phone' => '+49 171 98765432', 'email' => 'erika@example.com'],
    ['name' => 'Hans Schmidt', 'phone' => '+49 160 11223344', 'email' => 'hans@example.com'],
    ['name' => 'Maria Weber', 'phone' => '+49 172 55667788', 'email' => 'maria@example.com'],
    ['name' => 'Thomas Müller', 'phone' => '+49 151 99887766', 'email' => 'thomas@example.com']
];

foreach ($demoCustomers as $data) {
    $customer = Customer::firstOrCreate(
        [
            'company_id' => $company->id,
            'phone' => $data['phone']
        ],
        [
            'name' => $data['name'],
            'email' => $data['email'],
            'notes' => 'Demo-Kunde für Testzwecke'
        ]
    );
    $customers[] = $customer;
    echo "✅ Kunde erstellt: {$customer->name}\n";
}

// Create demo calls
$callScenarios = [
    [
        'summary' => 'Kunde möchte einen Termin für eine Beratung vereinbaren',
        'transcript' => "Agent: Guten Tag, vielen Dank für Ihren Anruf bei Demo GmbH. Wie kann ich Ihnen helfen?\n\nKunde: Hallo, ich hätte gerne einen Termin für eine Beratung.\n\nAgent: Sehr gerne! Wann würde es Ihnen denn passen?\n\nKunde: Am besten nächste Woche Dienstag oder Mittwoch.\n\nAgent: Perfekt! Ich habe am Dienstag um 14 Uhr oder am Mittwoch um 10 Uhr noch Termine frei. Was passt Ihnen besser?\n\nKunde: Dienstag um 14 Uhr wäre super.\n\nAgent: Wunderbar! Dann habe ich Sie für Dienstag, den 15. um 14 Uhr eingetragen. Sie erhalten gleich eine Bestätigungs-E-Mail.",
        'duration' => 185,
        'call_type' => 'inbound',
        'language' => 'de'
    ],
    [
        'summary' => 'Kunde fragt nach Öffnungszeiten und Standort',
        'transcript' => "Agent: Demo GmbH, guten Tag! Wie kann ich Ihnen weiterhelfen?\n\nKunde: Hallo, ich wollte fragen, wann Sie geöffnet haben und wo ich Sie finde?\n\nAgent: Gerne! Wir haben Montag bis Freitag von 9 bis 18 Uhr geöffnet und Samstag von 10 bis 14 Uhr. Sie finden uns in der Musterstraße 123 in Berlin.\n\nKunde: Ah perfekt, danke! Und kann ich auch ohne Termin vorbeikommen?\n\nAgent: Ja, natürlich! Allerdings empfehle ich Ihnen, einen Termin zu vereinbaren, damit wir uns ausreichend Zeit für Sie nehmen können.\n\nKunde: Okay, dann komme ich erstmal vorbei. Vielen Dank!\n\nAgent: Sehr gerne! Wir freuen uns auf Ihren Besuch.",
        'duration' => 142,
        'call_type' => 'inbound',
        'language' => 'de'
    ],
    [
        'summary' => 'Kunde möchte bestehenden Termin verschieben',
        'transcript' => "Agent: Guten Tag bei Demo GmbH, Sie sprechen mit der KI-Assistentin. Wie kann ich behilflich sein?\n\nKunde: Ja hallo, ich habe morgen einen Termin bei Ihnen, aber ich muss den leider verschieben.\n\nAgent: Das ist kein Problem! Können Sie mir bitte Ihren Namen nennen, damit ich den Termin finde?\n\nKunde: Müller, Thomas Müller.\n\nAgent: Einen Moment... Ja, ich habe Ihren Termin gefunden. Morgen um 15 Uhr, richtig?\n\nKunde: Genau, den meine ich.\n\nAgent: Wann würde es Ihnen denn besser passen?\n\nKunde: Geht es vielleicht nächste Woche zur gleichen Zeit?\n\nAgent: Ja, das passt! Dann verschiebe ich Ihren Termin auf nächste Woche Donnerstag, 15 Uhr. Passt das?\n\nKunde: Perfekt, vielen Dank!\n\nAgent: Sehr gerne! Sie bekommen gleich eine aktualisierte Bestätigung per E-Mail.",
        'duration' => 223,
        'call_type' => 'inbound',
        'language' => 'de'
    ]
];

$callCount = 0;
foreach ($callScenarios as $index => $scenario) {
    $customer = $customers[$index % count($customers)];
    
    $call = Call::create([
        'company_id' => $company->id,
        'call_id' => 'demo_call_' . uniqid(),
        'agent_id' => 'demo_agent_001',
        'phone_number' => $customer->phone,
        'call_type' => $scenario['call_type'],
        'direction' => 'inbound',
        'call_status' => 'completed',
        'start_time' => now()->subDays(rand(1, 7))->subHours(rand(0, 8)),
        'end_time' => now()->subDays(rand(1, 7))->subHours(rand(0, 8))->addSeconds($scenario['duration']),
        'duration' => $scenario['duration'],
        'duration_ms' => $scenario['duration'] * 1000,
        'cost' => $scenario['duration'] * 0.002, // 0.002€ pro Sekunde
        'transcript' => $scenario['transcript'],
        'transcript_json' => json_encode([
            'utterances' => explode("\n\n", $scenario['transcript'])
        ]),
        'summary' => $scenario['summary'],
        'language' => $scenario['language'],
        'sentiment' => 'positive',
        'customer_name' => $customer->name,
        'customer_id' => $customer->id,
        'retell_call_id' => 'demo_retell_' . uniqid(),
        'metadata' => json_encode([
            'demo_data' => true,
            'created_for' => 'fabianspitzer@icloud.com'
        ])
    ]);
    
    $callCount++;
    echo "✅ Demo-Anruf erstellt: {$scenario['summary']}\n";
}

// Add some appointments
$appointmentCount = 0;
for ($i = 0; $i < 3; $i++) {
    $customer = $customers[$i];
    $appointment = Appointment::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'title' => 'Beratungstermin mit ' . $customer->name,
        'description' => 'Demo-Termin für Testzwecke',
        'start_time' => now()->addDays(rand(1, 14))->setHour(rand(9, 17))->setMinute(0),
        'end_time' => now()->addDays(rand(1, 14))->setHour(rand(10, 18))->setMinute(0),
        'status' => 'scheduled',
        'location' => 'Demo GmbH, Musterstraße 123, Berlin',
        'metadata' => json_encode(['demo_data' => true])
    ]);
    $appointmentCount++;
    echo "✅ Demo-Termin erstellt: {$appointment->title}\n";
}

echo "\n=====================================\n";
echo "✅ Demo-Daten erfolgreich erstellt!\n";
echo "   - {$callCount} Demo-Anrufe\n";
echo "   - " . count($customers) . " Demo-Kunden\n";
echo "   - {$appointmentCount} Demo-Termine\n";
echo "\n🎯 Der Demo-Account ist bereit für Vorführungen!\n";
echo "   Email: fabianspitzer@icloud.com\n";
echo "   URL: https://api.askproai.de/business/login\n";