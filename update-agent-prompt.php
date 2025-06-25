<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== Update Agent Prompt to Use Dynamic Variables ===\n\n";

// Configuration
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';
$llmId = 'llm_f3209286ed1caf6a75906d2645b9';

try {
    $retellService = new RetellV2Service();
    
    echo "1. Getting current LLM configuration...\n";
    $llmConfig = $retellService->getRetellLLM($llmId);
    
    if (!$llmConfig) {
        throw new Exception("Could not fetch LLM configuration");
    }
    
    echo "✅ Found LLM configuration\n";
    echo "   - Model: " . ($llmConfig['model'] ?? 'N/A') . "\n";
    echo "   - Functions: " . count($llmConfig['general_tools'] ?? []) . "\n";
    
    // Check current prompt for variables
    $currentPrompt = $llmConfig['general_prompt'] ?? '';
    echo "\n2. Analyzing current prompt...\n";
    
    // The prompt should use variables like this:
    // {{caller_phone_number}} - for phone number
    // {{current_time_berlin}} - for current time
    // {{current_date}} - for current date
    // {{weekday}} - for weekday
    
    // Update the prompt to include variable references
    $updatedPrompt = $currentPrompt;
    
    // Add variable information at the beginning of the prompt
    $variableInfo = "## Verfügbare Systemvariablen
Du hast Zugriff auf folgende Systemvariablen:
- {{caller_phone_number}} - Die Telefonnummer des Anrufers (wenn verfügbar)
- {{current_time_berlin}} - Aktuelle Zeit in Berlin (Format: YYYY-MM-DD HH:MM:SS)
- {{current_date}} - Aktuelles Datum (Format: YYYY-MM-DD)
- {{current_time}} - Aktuelle Uhrzeit (Format: HH:MM)
- {{weekday}} - Aktueller Wochentag auf Deutsch
- {{company_name}} - Name der Firma

WICHTIG: Wenn {{caller_phone_number}} vorhanden ist, frage NICHT nach der Telefonnummer!

";
    
    // Check if variable info is already in prompt
    if (strpos($updatedPrompt, '## Verfügbare Systemvariablen') === false) {
        // Add it after the Role section
        $updatedPrompt = str_replace('# Role', $variableInfo . '# Role', $updatedPrompt);
    }
    
    // Update the kontaktdaten section to use the variable
    $kontaktdatenSection = "## Kontaktdaten-Erfassung
### Telefonnummer - KRITISCH WICHTIG:
- Das System stellt die Telefonnummer des Anrufers in {{caller_phone_number}} bereit
- Wenn {{caller_phone_number}} vorhanden ist: NIEMALS nach der Telefonnummer fragen!
- NUR wenn {{caller_phone_number}} leer oder 'unknown' ist, dann frage: \"Unter welcher Telefonnummer kann ich Sie für Rückfragen erreichen?\"
- Bei der collect_appointment_data Funktion:
  - Wenn {{caller_phone_number}} vorhanden: Verwende diesen Wert für 'telefonnummer'
  - Wenn nicht vorhanden: Verwende die manuell erfragte Nummer";
    
    // Find and replace the Kontaktdaten section
    $pattern = '/## Kontaktdaten-Erfassung.*?(?=##|$)/s';
    if (preg_match($pattern, $updatedPrompt)) {
        $updatedPrompt = preg_replace($pattern, $kontaktdatenSection . "\n\n", $updatedPrompt);
    }
    
    // Update the Zeit section to use variables
    $zeitSection = "## Aktuelle Zeit (Systemvariablen)
- Die aktuellen Zeitinformationen werden automatisch bereitgestellt:
  - {{current_time_berlin}} - Vollständige Zeit mit Datum (z.B. \"2025-06-25 15:30:45\")
  - {{current_date}} - Nur Datum (z.B. \"2025-06-25\")
  - {{current_time}} - Nur Uhrzeit (z.B. \"15:30\")
  - {{weekday}} - Wochentag auf Deutsch (z.B. \"Mittwoch\")
- Verwende diese Variablen für:
  - Begrüßung basierend auf Tageszeit
  - Relative Datumsberechnungen (morgen = current_date + 1 Tag)
  - Wochentag-Ansagen
- WICHTIG: Verlasse dich IMMER auf diese Variablen, nicht auf die current_time_berlin Funktion!";
    
    // Find and replace the Zeit section
    $pattern = '/## Aktuelle Zeit.*?(?=##|$)/s';
    if (preg_match($pattern, $updatedPrompt)) {
        $updatedPrompt = preg_replace($pattern, $zeitSection . "\n\n", $updatedPrompt);
    }
    
    // Update collect_appointment_data function usage in prompt
    $collectDataPattern = '/```json\s*{\s*"datum".*?"email".*?}\s*```/s';
    $newCollectDataExample = '```json
{
  "datum": "24.06.2025",
  "uhrzeit": "16:30",
  "name": "Hans Schuster",
  "telefonnummer": "{{caller_phone_number}}",
  "dienstleistung": "Beratung",
  "email": "hans@beispiel.de"
}
```';
    
    if (preg_match($collectDataPattern, $updatedPrompt)) {
        $updatedPrompt = preg_replace($collectDataPattern, $newCollectDataExample, $updatedPrompt, 1);
    }
    
    echo "\n3. Updating LLM configuration...\n";
    
    // Update only the prompt
    $updateData = [
        'general_prompt' => $updatedPrompt
    ];
    
    $result = $retellService->updateRetellLLM($llmId, $updateData);
    
    if ($result) {
        echo "\n✅ Successfully updated agent prompt!\n";
        echo "\nChanges made:\n";
        echo "- Added system variable documentation\n";
        echo "- Updated phone number handling to use {{caller_phone_number}}\n";
        echo "- Updated time handling to use system variables\n";
        echo "- Modified collect_appointment_data examples\n";
        
        // Also update the collect_appointment_data function to accept the variable
        echo "\n4. Updating collect_appointment_data function...\n";
        
        $tools = $llmConfig['general_tools'] ?? [];
        $toolsUpdated = false;
        
        foreach ($tools as &$tool) {
            if ($tool['name'] === 'collect_appointment_data') {
                // Update the parameter description
                if (isset($tool['parameters']['properties']['telefonnummer'])) {
                    $tool['parameters']['properties']['telefonnummer']['description'] = 
                        'Telefonnummer des Kunden (nutze {{caller_phone_number}} wenn verfügbar)';
                }
                $toolsUpdated = true;
                break;
            }
        }
        
        if ($toolsUpdated) {
            $result = $retellService->updateRetellLLM($llmId, [
                'general_tools' => $tools
            ]);
            
            if ($result) {
                echo "✅ Updated collect_appointment_data function\n";
            }
        }
        
    } else {
        echo "\n❌ Failed to update LLM configuration\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// Clear cache
echo "5. Clearing cache...\n";
\Illuminate\Support\Facades\Cache::forget("retell_llm_data_{$llmId}");
\Illuminate\Support\Facades\Cache::forget("retell_llm_functions_{$llmId}");
echo "✅ Cache cleared\n";

echo "\nNext steps:\n";
echo "1. Test with a real phone call to verify variables are passed\n";
echo "2. Monitor logs during the call:\n";
echo "   tail -f storage/logs/laravel.log | grep -E 'collect_appointment|dynamic_variables'\n";
echo "3. The agent should now use the caller's phone number automatically\n";
echo "4. The agent should use the correct current date/time for calculations\n";

echo "\nDone!\n";