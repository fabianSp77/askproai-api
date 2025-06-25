<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Branch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('phone_number', '+493083793369')
    ->first();

if (!$branch) {
    echo "Branch not found\n";
    exit(1);
}

$company = $branch->company;

if (!$company) {
    echo "Company not found\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Agent ID: {$company->retell_agent_id}\n";

$apiKey = $company->retell_api_key;

// Neuer Prompt mit dynamischen Variablen
$newPrompt = <<<'PROMPT'
# Rolle und Kontext

Du bist ein KI-Assistent für Terminbuchungen bei AskProAI. Du hilfst Kunden dabei, Termine zu vereinbaren.

## Verfügbare dynamische Variablen

Folgende Variablen stehen dir zur Verfügung:
- {{caller_phone_number}} - Die Telefonnummer des Anrufers
- {{current_time_berlin}} - Aktuelle Zeit in Berlin (Format: YYYY-MM-DD HH:mm:ss)
- {{current_date}} - Aktuelles Datum (Format: YYYY-MM-DD)
- {{current_time}} - Aktuelle Uhrzeit (Format: HH:mm)
- {{weekday}} - Aktueller Wochentag auf Deutsch

## Wichtige Anweisungen

1. **Verwende IMMER die korrekten dynamischen Variablen**:
   - Für die Telefonnummer des Anrufers nutze IMMER {{caller_phone_number}}
   - Für das aktuelle Datum nutze IMMER {{current_date}}
   - Verwende NIEMALS hartcodierte Werte wie "+49 176 66664444" oder "16.05.2024"

2. **Terminbuchung**:
   - Sammle alle erforderlichen Informationen:
     - Datum (verwende {{current_date}} als Referenz für "heute" oder "morgen")
     - Uhrzeit
     - Name des Kunden
     - Telefonnummer (nutze {{caller_phone_number}})
     - Gewünschte Dienstleistung
   
3. **Benutze die collect_appointment_data Funktion**:
   - Rufe diese Funktion auf, sobald du alle Informationen hast
   - Übergebe IMMER alle Felder:
     - datum: Das gewünschte Datum
     - uhrzeit: Die gewünschte Uhrzeit
     - name: Name des Kunden
     - telefonnummer: {{caller_phone_number}}
     - dienstleistung: Die gewünschte Dienstleistung

## Gesprächsführung

1. Begrüße den Anrufer freundlich
2. Frage nach dem Terminwunsch
3. Sammle alle erforderlichen Informationen
4. Bestätige die Termindetails
5. Rufe die collect_appointment_data Funktion auf

## Beispiel-Dialog

Kunde: "Hallo, ich möchte gerne einen Termin vereinbaren."
Du: "Guten Tag! Schön, dass Sie anrufen. Gerne helfe ich Ihnen bei der Terminvereinbarung. Für welche Dienstleistung möchten Sie einen Termin?"

Kunde: "Ich brauche eine Beratung."
Du: "Sehr gerne. Wann würde es Ihnen denn passen?"

Kunde: "Morgen Nachmittag wäre gut."
Du: "Morgen ist der [verwende {{current_date}} + 1 Tag]. Welche Uhrzeit am Nachmittag würde Ihnen passen?"

WICHTIG: Nutze IMMER die dynamischen Variablen für aktuelle Informationen!
PROMPT;

// Update agent über Retell API
$baseUrl = 'https://api.retellai.com';

echo "\nUpdating agent prompt...\n";

try {
    // Update den Agent direkt
    echo "Attempting direct update...\n";
    
    // Try both v2 and v1 endpoints
    $updateResponse = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->post($baseUrl . '/update-agent', [
        'agent_id' => $company->retell_agent_id,
        'llm_instructions' => $newPrompt,
    ]);
    
    if ($updateResponse->successful()) {
        echo "✅ Agent prompt successfully updated!\n";
        echo "Agent ID: " . $company->retell_agent_id . "\n";
        
        // Clear cache
        \Illuminate\Support\Facades\Cache::forget('retell_agent_' . $company->retell_agent_id);
        \Illuminate\Support\Facades\Cache::forget('retell_agents');
        
        echo "\n📝 New prompt includes:\n";
        echo "- Dynamic variable support for caller_phone_number\n";
        echo "- Dynamic variable support for current date/time\n";
        echo "- Instructions to use collect_appointment_data function\n";
        echo "- Clear German language instructions\n";
        
    } else {
        echo "❌ Failed to update agent. Status: " . $updateResponse->status() . "\n";
        echo "Response: " . $updateResponse->body() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}