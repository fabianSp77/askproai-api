<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AgentConfigurationProcessor;
use Illuminate\Support\Facades\File;

class ProcessAgentConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:process-config 
                            {input : Input JSON file path}
                            {--output= : Output JSON file path (default: processed_<input>)}
                            {--enhance-prompt : Enhance the agent prompt for German market}
                            {--fix-webhooks : Fix webhook URLs to point to AskProAI}
                            {--optimize-voice : Optimize voice settings for German}
                            {--add-functions : Add standard AskProAI functions}
                            {--preset= : Apply a preset configuration (booking, support, sales)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and modify Retell agent configuration';

    protected AgentConfigurationProcessor $processor;

    /**
     * Create a new command instance.
     */
    public function __construct(AgentConfigurationProcessor $processor)
    {
        parent::__construct();
        $this->processor = $processor;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inputFile = $this->argument('input');
        
        // Check if input file exists
        if (!File::exists($inputFile)) {
            $this->error("Input file not found: {$inputFile}");
            return 1;
        }
        
        // Read input configuration
        $content = File::get($inputFile);
        $config = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON in input file: " . json_last_error_msg());
            return 1;
        }
        
        $this->info("Processing agent configuration: " . ($config['agent_name'] ?? 'Unknown'));
        
        // Apply modifications based on options
        $modifications = [];
        
        if ($this->option('fix-webhooks')) {
            $modifications['webhook_settings.url'] = 'https://api.askproai.de/api/retell/webhook';
            $modifications['webhook_settings.listening_events'] = [
                'call_started',
                'call_ended', 
                'call_analyzed'
            ];
            $this->info("✓ Fixed webhook configuration");
        }
        
        if ($this->option('optimize-voice')) {
            if (isset($config['voice_id']) && str_starts_with($config['voice_id'], 'elevenlabs-')) {
                $modifications['voice_temperature'] = 0.7;
                $modifications['voice_speed'] = 1.0;
            }
            $modifications['language'] = 'de';
            $modifications['interruption_sensitivity'] = 0.7;
            $modifications['enable_backchannel'] = true;
            $this->info("✓ Optimized voice settings for German");
        }
        
        // Apply preset if specified
        if ($preset = $this->option('preset')) {
            $presetMods = $this->getPresetModifications($preset);
            $modifications = array_merge($modifications, $presetMods);
            $this->info("✓ Applied preset: {$preset}");
        }
        
        // Process configuration
        $config = $this->processor->processConfiguration($config, $modifications);
        
        // Enhance prompt if requested
        if ($this->option('enhance-prompt')) {
            $basePrompt = $config['general_prompt'] ?? $config['prompt'] ?? '';
            $config['general_prompt'] = $this->processor->enhancePrompt($basePrompt, [
                'language' => 'de',
                'include_booking_context' => true,
                'professional_tone' => true,
                'collect_data' => true
            ]);
            $this->info("✓ Enhanced agent prompt for German market");
        }
        
        // Add functions if requested
        if ($this->option('add-functions')) {
            $config = $this->addStandardFunctions($config);
            $this->info("✓ Added standard AskProAI functions");
        }
        
        // Determine output file
        $outputFile = $this->option('output') ?? 'processed_' . basename($inputFile);
        
        // Save processed configuration
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        File::put($outputFile, $json);
        
        $this->info("\n✅ Configuration processed successfully!");
        $this->info("Output saved to: {$outputFile}");
        
        // Display summary of changes
        $this->displaySummary($config);
        
        return 0;
    }
    
    /**
     * Get preset modifications
     */
    protected function getPresetModifications(string $preset): array
    {
        $presets = [
            'booking' => [
                'response_speed' => 800,
                'interruption_sensitivity' => 0.6,
                'end_call_after_silence_ms' => 15000,
                'voice_temperature' => 0.6,
                'general_prompt' => $this->getBookingPrompt()
            ],
            'support' => [
                'response_speed' => 1000,
                'interruption_sensitivity' => 0.8,
                'end_call_after_silence_ms' => 25000,
                'voice_temperature' => 0.8,
                'enable_backchannel' => true,
                'general_prompt' => $this->getSupportPrompt()
            ],
            'sales' => [
                'response_speed' => 900,
                'interruption_sensitivity' => 0.7,
                'end_call_after_silence_ms' => 20000,
                'voice_temperature' => 0.9,
                'voice_speed' => 1.1,
                'general_prompt' => $this->getSalesPrompt()
            ]
        ];
        
        return $presets[$preset] ?? [];
    }
    
    /**
     * Get booking agent prompt
     */
    protected function getBookingPrompt(): string
    {
        return <<<PROMPT
Du bist ein professioneller KI-Assistent für Terminbuchungen. Deine Aufgabe ist es, Anrufer freundlich und effizient bei der Terminvereinbarung zu unterstützen.

WICHTIGE REGELN:
- Sprich IMMER auf Deutsch, niemals auf Englisch
- Verwende die Sie-Form, außer der Anrufer bietet das Du an
- Sei höflich, professionell und hilfsbereit
- Erfasse alle notwendigen Informationen für die Terminbuchung

DATENERFASSUNG:
1. Name des Anrufers
2. Telefonnummer (zur Bestätigung)
3. Gewünschte Dienstleistung
4. Bevorzugter Termin (Datum und Uhrzeit)
5. Besondere Anliegen oder Wünsche

ABLAUF:
1. Begrüße den Anrufer freundlich
2. Frage nach dem Anliegen
3. Erfasse systematisch alle notwendigen Daten
4. Prüfe die Verfügbarkeit
5. Bestätige den Termin
6. Fasse alle Details zusammen
7. Verabschiede dich professionell

Wenn du nicht weiterweißt, biete an, den Anrufer mit einem Mitarbeiter zu verbinden.
PROMPT;
    }
    
    /**
     * Get support agent prompt
     */
    protected function getSupportPrompt(): string
    {
        return <<<PROMPT
Du bist ein hilfsbereiter KI-Support-Assistent. Deine Aufgabe ist es, Anrufern bei ihren Fragen und Problemen zu helfen.

WICHTIGE REGELN:
- Sprich IMMER auf Deutsch
- Sei geduldig und verständnisvoll
- Höre aktiv zu und stelle Rückfragen
- Biete konkrete Lösungen an

VORGEHENSWEISE:
1. Begrüße den Anrufer freundlich
2. Erfrage das konkrete Anliegen
3. Stelle gezielte Rückfragen zum besseren Verständnis
4. Biete Lösungsvorschläge an
5. Erkläre Schritte verständlich
6. Frage nach, ob das Problem gelöst wurde
7. Biete weitere Hilfe an

Bei komplexen Problemen, die du nicht lösen kannst, biete eine Weiterleitung an einen Spezialisten an.
PROMPT;
    }
    
    /**
     * Get sales agent prompt
     */
    protected function getSalesPrompt(): string
    {
        return <<<PROMPT
Du bist ein professioneller Verkaufsberater. Deine Aufgabe ist es, Interessenten kompetent zu beraten und bei der Auswahl der passenden Produkte oder Dienstleistungen zu unterstützen.

WICHTIGE REGELN:
- Sprich IMMER auf Deutsch
- Sei enthusiastisch aber nicht aufdringlich
- Höre auf die Bedürfnisse des Kunden
- Biete maßgeschneiderte Lösungen an

VERKAUFSPROZESS:
1. Freundliche Begrüßung
2. Bedarfsanalyse durch gezielte Fragen
3. Vorstellung passender Lösungen
4. Nutzen und Vorteile hervorheben
5. Einwände professionell behandeln
6. Nächste Schritte vereinbaren
7. Professioneller Abschluss

Fokussiere dich auf den Mehrwert für den Kunden und baue Vertrauen auf.
PROMPT;
    }
    
    /**
     * Add standard functions to configuration
     */
    protected function addStandardFunctions(array $config): array
    {
        $standardFunctions = [
            [
                'name' => 'check_availability',
                'url' => 'https://api.askproai.de/api/mcp/calcom/availability',
                'method' => 'POST',
                'description' => 'Verfügbare Termine prüfen',
                'speak_during_execution' => true,
                'speak_during_execution_message' => 'Einen Moment, ich prüfe die verfügbaren Termine...',
                'speak_after_execution' => true,
                'speak_after_execution_message' => 'Ich habe folgende Termine gefunden:',
                'parameters' => [
                    ['name' => 'date', 'type' => 'string', 'required' => true],
                    ['name' => 'service', 'type' => 'string', 'required' => true]
                ]
            ],
            [
                'name' => 'book_appointment',
                'url' => 'https://api.askproai.de/api/mcp/calcom/booking',
                'method' => 'POST',
                'description' => 'Termin buchen',
                'speak_during_execution' => true,
                'speak_during_execution_message' => 'Ich buche jetzt Ihren Termin...',
                'speak_after_execution' => true,
                'speak_after_execution_message' => 'Ihr Termin wurde erfolgreich gebucht!',
                'parameters' => [
                    ['name' => 'customer_name', 'type' => 'string', 'required' => true],
                    ['name' => 'customer_phone', 'type' => 'string', 'required' => true],
                    ['name' => 'date', 'type' => 'string', 'required' => true],
                    ['name' => 'time', 'type' => 'string', 'required' => true],
                    ['name' => 'service', 'type' => 'string', 'required' => true]
                ]
            ]
        ];
        
        // Add to response engine if using retell-llm
        if (isset($config['response_engine']['type']) && 
            $config['response_engine']['type'] === 'retell-llm') {
            
            // This would need to be handled via LLM configuration
            // For now, we'll add a note
            $config['_functions_to_add'] = $standardFunctions;
            $config['_note'] = 'Functions need to be added via Retell LLM configuration';
        }
        
        return $config;
    }
    
    /**
     * Display summary of configuration
     */
    protected function displaySummary(array $config): void
    {
        $this->info("\n📋 Configuration Summary:");
        $this->table(
            ['Setting', 'Value'],
            [
                ['Agent Name', $config['agent_name'] ?? 'Not set'],
                ['Language', $config['language'] ?? 'Not set'],
                ['Voice', $config['voice_id'] ?? 'Not set'],
                ['Response Engine', $config['response_engine']['type'] ?? 'Not set'],
                ['Webhook URL', $config['webhook_settings']['url'] ?? 'Not set'],
                ['Response Speed', ($config['response_speed'] ?? 'Not set') . ' ms'],
                ['Interruption Sensitivity', $config['interruption_sensitivity'] ?? 'Not set'],
            ]
        );
    }
}