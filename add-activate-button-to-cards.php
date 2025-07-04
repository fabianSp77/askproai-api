<?php

/**
 * Fügt Activate-Button zu Agent-Karten hinzu
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== Add Activate Button to Agent Cards ===\n\n";

// Path to the blade template
$bladePath = resource_path('views/filament/admin/pages/partials/retell-control-center/retell-agent-card.blade.php');

if (!file_exists($bladePath)) {
    echo "❌ Blade template nicht gefunden: $bladePath\n";
    exit(1);
}

// Read current content
$content = file_get_contents($bladePath);

// Find the button group section (around line 230-240)
$searchPattern = '/<div class="flex justify-between items-center mt-4">/';
$replacePattern = '<div class="flex justify-between items-center mt-4">
    {{-- Activate Button for Inactive Agents --}}
    @if(!$agent[\'is_active\'])
        <button 
            wire:click="activateAgent(\'{{ $agent[\'agent_id\'] }}\')"
            class="px-3 py-1 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
            title="Agent aktivieren"
        >
            <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Aktivieren
        </button>
    @endif';

// Check if pattern exists
if (preg_match($searchPattern, $content)) {
    // Replace the pattern
    $newContent = preg_replace($searchPattern, $replacePattern, $content, 1);
    
    // Create backup
    $backupPath = $bladePath . '.backup.' . date('Y-m-d-H-i-s');
    file_put_contents($backupPath, $content);
    echo "✅ Backup erstellt: $backupPath\n";
    
    // Write new content
    file_put_contents($bladePath, $newContent);
    echo "✅ Activate-Button hinzugefügt!\n";
    
    // Now add the method to the page class
    $pageClassPath = app_path('Filament/Admin/Pages/RetellUltimateControlCenter.php');
    $pageContent = file_get_contents($pageClassPath);
    
    // Find where to insert the new method (before the last closing brace)
    $methodToAdd = '
    public function activateAgent($agentId)
    {
        try {
            $agent = RetellAgent::where(\'agent_id\', $agentId)->first();
            if ($agent) {
                // Deactivate all other agents with same base name
                RetellAgent::where(\'base_name\', $agent->base_name)
                    ->where(\'id\', \'!=\', $agent->id)
                    ->update([\'is_active\' => false, \'active\' => false]);
                
                // Activate this agent
                $agent->update([
                    \'is_active\' => true,
                    \'active\' => true,
                    \'is_published\' => true
                ]);
                
                // Update phone numbers
                PhoneNumber::where(\'retell_agent_id\', \'LIKE\', $agent->base_name . \'%\')
                    ->update([\'retell_agent_id\' => $agentId]);
                
                $this->dispatch(\'notify\', [
                    \'type\' => \'success\',
                    \'message\' => \'Agent erfolgreich aktiviert!\'
                ]);
                
                $this->loadAgents();
            }
        } catch (\Exception $e) {
            $this->dispatch(\'notify\', [
                \'type\' => \'error\',
                \'message\' => \'Fehler beim Aktivieren: \' . $e->getMessage()
            ]);
        }
    }';
    
    // Insert before the last closing brace
    $lastBracePos = strrpos($pageContent, '}');
    $newPageContent = substr($pageContent, 0, $lastBracePos) . $methodToAdd . "\n" . substr($pageContent, $lastBracePos);
    
    // Backup and save
    file_put_contents($pageClassPath . '.backup', $pageContent);
    file_put_contents($pageClassPath, $newPageContent);
    echo "✅ activateAgent() Methode hinzugefügt!\n";
    
} else {
    echo "❌ Button-Bereich nicht gefunden. Manuelle Änderung erforderlich.\n";
}

echo "\n✅ Fertig! Activate-Button sollte jetzt bei inaktiven Agents angezeigt werden.\n";
echo "Cache leeren: php artisan optimize:clear\n";