<?php

namespace App\Services\Retell;

use App\Models\RetellAgentPrompt;
use Illuminate\Database\Eloquent\Collection;

/**
 * Retell Prompt Template Service
 *
 * Manages predefined prompt templates and template operations
 */
class RetellPromptTemplateService
{
    /**
     * Get all available templates
     */
    public function getTemplates(): Collection
    {
        return RetellAgentPrompt::where('is_template', true)
            ->orderBy('template_name')
            ->get();
    }

    /**
     * Get template by name
     */
    public function getTemplate(string $templateName): ?RetellAgentPrompt
    {
        return RetellAgentPrompt::where('is_template', true)
            ->where('template_name', $templateName)
            ->first();
    }

    /**
     * Apply template to branch
     */
    public function applyTemplateToBranch(string $branchId, string $templateName): RetellAgentPrompt
    {
        $template = $this->getTemplate($templateName);

        if (!$template) {
            throw new \Exception("Template not found: $templateName");
        }

        $nextVersion = RetellAgentPrompt::getNextVersionForBranch($branchId);

        return RetellAgentPrompt::create([
            'branch_id' => $branchId,
            'version' => $nextVersion,
            'prompt_content' => $template->prompt_content,
            'functions_config' => $template->functions_config,
            'is_active' => false,
            'is_template' => false,
            'validation_status' => 'valid',
            'deployment_notes' => "Created from template: $templateName",
        ]);
    }

    /**
     * Create template from existing prompt
     */
    public function createTemplate(string $templateName, string $promptContent, array $functionsConfig): RetellAgentPrompt
    {
        // Templates need a branch_id for DB constraint, use a UUID format
        // branch_id is char(36), so we create a proper UUID
        $templateBranchId = (string) \Illuminate\Support\Str::uuid();

        return RetellAgentPrompt::create([
            'branch_id' => $templateBranchId,
            'version' => 1,
            'template_name' => $templateName,
            'is_template' => true,
            'prompt_content' => $promptContent,
            'functions_config' => $functionsConfig,
            'validation_status' => 'valid',
        ]);
    }

    /**
     * Get default template (Dynamic Service Selection)
     */
    public function getDefaultTemplate(): RetellAgentPrompt
    {
        $template = $this->getTemplate('dynamic-service-selection-v127');

        if (!$template) {
            return $this->createDefaultTemplate();
        }

        return $template;
    }

    /**
     * Create or update the default template
     */
    private function createDefaultTemplate(): RetellAgentPrompt
    {
        $defaultPrompt = file_get_contents(base_path('retell_agent_prompt_v127_with_list_services.md'));
        $defaultFunctions = $this->getDefaultFunctions();

        $template = $this->getTemplate('dynamic-service-selection-v127');

        if ($template) {
            $template->update([
                'prompt_content' => $defaultPrompt,
                'functions_config' => $defaultFunctions,
                'validation_status' => 'valid',
            ]);
            return $template;
        }

        return $this->createTemplate('dynamic-service-selection-v127', $defaultPrompt, $defaultFunctions);
    }

    /**
     * Get default function definitions
     */
    public function getDefaultFunctions(): array
    {
        return [
            [
                'name' => 'list_services',
                'description' => 'Get available services for this company. Shows all services with duration and price.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => []
                ]
            ],
            [
                'name' => 'collect_appointment_data',
                'description' => 'Collect and verify appointment data. First call without bestaetigung to check availability, then call with bestaetigung: true to book.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'call_id' => ['type' => 'string', 'description' => 'Unique call identifier'],
                        'service_id' => ['type' => 'number', 'description' => 'Service ID from list_services'],
                        'name' => ['type' => 'string', 'description' => 'Customer name'],
                        'datum' => ['type' => 'string', 'description' => 'Date DD.MM.YYYY'],
                        'uhrzeit' => ['type' => 'string', 'description' => 'Time HH:MM'],
                        'dienstleistung' => ['type' => 'string', 'description' => 'Service name'],
                        'bestaetigung' => ['type' => 'boolean', 'description' => 'Confirm booking'],
                        'email' => ['type' => 'string', 'description' => 'Email (optional)']
                    ],
                    'required' => ['call_id', 'service_id', 'name', 'datum', 'uhrzeit', 'dienstleistung']
                ]
            ],
            [
                'name' => 'cancel_appointment',
                'description' => 'Cancel an existing appointment',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'call_id' => ['type' => 'string'],
                        'appointment_id' => ['type' => 'string']
                    ],
                    'required' => ['call_id', 'appointment_id']
                ]
            ],
            [
                'name' => 'reschedule_appointment',
                'description' => 'Reschedule an existing appointment to a new time',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'call_id' => ['type' => 'string'],
                        'appointment_id' => ['type' => 'string'],
                        'neues_datum' => ['type' => 'string'],
                        'neue_uhrzeit' => ['type' => 'string']
                    ],
                    'required' => ['call_id', 'appointment_id', 'neues_datum', 'neue_uhrzeit']
                ]
            ]
        ];
    }

    /**
     * Seed default templates
     */
    public function seedDefaultTemplates(): void
    {
        // Create default template if not exists
        if (!$this->getTemplate('dynamic-service-selection-v127')) {
            $this->createDefaultTemplate();
        }

        // Create basic template if not exists
        if (!$this->getTemplate('basic-appointment-booking')) {
            $this->createTemplate(
                'basic-appointment-booking',
                $this->getBasicBookingPrompt(),
                $this->getDefaultFunctions()
            );
        }

        // Create info-only template if not exists
        if (!$this->getTemplate('information-only')) {
            $this->createTemplate(
                'information-only',
                $this->getInformationOnlyPrompt(),
                [[
                    'name' => 'get_opening_hours',
                    'description' => 'Get opening hours for the company',
                    'parameters' => ['type' => 'object', 'properties' => [], 'required' => []]
                ]]
            );
        }
    }

    /**
     * Basic booking prompt (simplified)
     */
    private function getBasicBookingPrompt(): string
    {
        return <<<'PROMPT'
# Retell Agent Prompt - Basic Appointment Booking

Du bist ein hilfreicher Buchungsassistent für Terminbuchungen.

## Workflow

1. Begrüße den Kunden freundlich
2. Frage nach dem Namen des Kunden
3. Frage nach dem gewünschten Termin (Datum)
4. Frage nach der gewünschten Zeit
5. Prüfe Verfügbarkeit mit collect_appointment_data (bestaetigung: false)
6. Bei Verfügbarkeit: Frage nach Bestätigung
7. Buche Termin mit collect_appointment_data (bestaetigung: true)

## Wichtige Regeln

- Sei freundlich und professionell
- Bestätige alle Angaben des Kunden
- Bei Fehlern: Entschuldige Dich und biete Alternativen an
- Nur bestätigen wenn Status "booked" ist

PROMPT;
    }

    /**
     * Information only prompt
     */
    private function getInformationOnlyPrompt(): string
    {
        return <<<'PROMPT'
# Retell Agent Prompt - Information Only

Du bist ein hilfreicher Informationsassistent.

## Aufgabe

Beantworte Fragen der Kunden über:
- Öffnungszeiten
- Verfügbare Services
- Allgemeine Informationen zum Unternehmen

## Wichtige Regeln

- Sei freundlich und hilfreich
- Gebe genaue Informationen
- Bei Fragen, die du nicht beantworten kannst: "Bitte rufen Sie uns an..."

PROMPT;
    }
}
