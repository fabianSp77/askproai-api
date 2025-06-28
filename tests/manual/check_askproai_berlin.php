<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ASKPROAI BERLIN ANALYSE ===\n\n";

// 1. Company Check
$company = Company::find(85);
echo "UNTERNEHMEN:\n";
echo "- Name: " . $company->name . "\n";
echo "- ID: " . $company->id . "\n";
echo "- Aktiv: " . ($company->is_active ? 'JA' : 'NEIN') . "\n";
echo "- Retell API Key: " . (empty($company->retell_api_key) ? 'FEHLT' : 'VORHANDEN') . "\n";
echo "- Cal.com API Key: " . (empty($company->calcom_api_key) ? 'FEHLT' : 'VORHANDEN') . "\n";
echo "- Cal.com Team: " . $company->calcom_team_slug . "\n";
echo "- Standard Event Type ID: " . ($company->default_event_type_id ?? 'NICHT GESETZT') . "\n\n";

// 2. Branch Check
$branch = Branch::where('name', 'LIKE', '%Berlin%')->where('company_id', 85)->first();
if ($branch) {
    echo "FILIALE ASKPROAI BERLIN:\n";
    echo "- Name: " . $branch->name . "\n";
    echo "- ID: " . $branch->id . "\n";
    echo "- Aktiv: " . ($branch->active ? 'JA' : 'NEIN') . "\n";
    echo "- Telefonnummer: " . $branch->phone_number . "\n";
    echo "- Retell Agent ID: " . $branch->retell_agent_id . "\n";
    echo "- Cal.com Event Type ID: " . $branch->calcom_event_type_id . "\n";
    echo "- Calendar Mode: " . $branch->calendar_mode . "\n";
    
    // Check if can be activated
    if (!$branch->active) {
        echo "\nAKTIVIERUNGS-CHECK:\n";
        if ($branch->canBeActivated()) {
            echo "- Kann aktiviert werden: JA\n";
        } else {
            echo "- Kann aktiviert werden: NEIN\n";
            echo "- Fehlende Felder: " . json_encode($branch->getMissingRequiredFields()) . "\n";
        }
    }
    
    // 3. Staff Check
    echo "\nMITARBEITER:\n";
    $staff = Staff::where('home_branch_id', $branch->id)->get();
    foreach ($staff as $s) {
        echo "- " . $s->name . " (ID: " . $s->id . ", Company: " . ($s->company_id ?? 'FEHLT') . ", Cal.com User: " . ($s->calcom_user_id ?? 'FEHLT') . ")\n";
    }
    
    // 4. Event Types
    echo "\nCAL.COM EVENT TYPES:\n";
    if ($branch->calcom_event_type_id) {
        $eventType = CalcomEventType::find($branch->calcom_event_type_id);
        if ($eventType) {
            echo "- Branch Event Type: " . $eventType->title . " (Slug: " . $eventType->slug . ")\n";
        } else {
            echo "- Branch Event Type ID " . $branch->calcom_event_type_id . " NICHT GEFUNDEN!\n";
        }
    }
    
    // Get effective configuration
    echo "\nEFFEKTIVE KONFIGURATION:\n";
    $config = $branch->getEffectiveCalcomConfig();
    echo "- API Key: " . (empty($config['api_key']) ? 'FEHLT' : 'VORHANDEN') . "\n";
    echo "- Team Slug: " . ($config['team_slug'] ?? 'FEHLT') . "\n";
    echo "- Event Type ID: " . $branch->getEffectiveCalcomEventTypeId() . "\n";
    
} else {
    echo "FEHLER: Filiale 'AskProAI Berlin' nicht gefunden!\n";
}

// 5. Phone Number Assignment
echo "\nTELEFONNUMMERN-ZUORDNUNG:\n";
$phoneNumbers = \DB::table('phone_numbers')->where('branch_id', $branch->id)->get();
foreach ($phoneNumbers as $phone) {
    echo "- " . $phone->number . " (Aktiv: " . ($phone->active ? 'JA' : 'NEIN') . ")\n";
}

// 6. Recent Calls
echo "\nLETZTE ANRUFE AN DIESE FILIALE:\n";
$calls = \App\Models\Call::where('branch_id', $branch->id)
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get(['id', 'created_at', 'from_number', 'duration_sec', 'call_status']);
    
foreach ($calls as $call) {
    echo "- " . $call->created_at . " | Von: " . $call->from_number . " | Dauer: " . $call->duration_sec . "s | Status: " . $call->call_status . "\n";
}