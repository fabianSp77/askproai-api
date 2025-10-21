<?php

namespace App\Services\Retell;

use App\Models\Branch;
use App\Models\RetellAgentPrompt;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Retell Agent Management Service
 *
 * Handles creation, deployment, and management of Retell AI agents
 */
class RetellAgentManagementService
{
    private string $baseUrl;
    private string $apiKey;
    private RetellPromptValidationService $validationService;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');
        $this->apiKey = config('services.retellai.api_key');
        $this->validationService = new RetellPromptValidationService();
    }

    /**
     * Deploy a prompt version to Retell API
     */
    public function deployPromptVersion(RetellAgentPrompt $promptVersion, ?User $deployedBy = null): array
    {
        try {
            // Validate before deploying
            $validationErrors = $promptVersion->validate();
            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed before deployment',
                    'errors' => $validationErrors,
                ];
            }

            $agentId = config('services.retellai.agent_id');
            $branch = $promptVersion->branch;

            // Prepare payload
            $payload = $this->buildAgentPayload($promptVersion);

            Log::info('Deploying Retell agent', [
                'agent_id' => $agentId,
                'branch_id' => $branch->id,
                'version' => $promptVersion->version,
            ]);

            // Call Retell API to create new agent version
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/create-agent", $payload);

            if (!$response->successful()) {
                $errorMessage = $response->json()['error_message'] ?? $response->body();
                throw new \Exception("Retell API error: $errorMessage");
            }

            $agentData = $response->json();

            // Update prompt version with deployment info
            $promptVersion->update([
                'is_active' => true,
                'deployed_at' => now(),
                'deployed_by' => $deployedBy?->id,
                'retell_agent_id' => $agentData['agent_id'] ?? $agentId,
                'retell_version' => $agentData['version'] ?? 0,
                'validation_status' => 'valid',
            ]);

            // Deactivate other versions
            RetellAgentPrompt::where('branch_id', $promptVersion->branch_id)
                ->where('id', '!=', $promptVersion->id)
                ->update(['is_active' => false]);

            return [
                'success' => true,
                'message' => 'Agent version deployed successfully',
                'retell_version' => $agentData['version'] ?? 0,
                'deployed_at' => $promptVersion->deployed_at,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to deploy Retell agent', [
                'error' => $e->getMessage(),
                'version_id' => $promptVersion->id,
            ]);

            return [
                'success' => false,
                'message' => 'Deployment failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Build agent payload for Retell API
     */
    private function buildAgentPayload(RetellAgentPrompt $promptVersion): array
    {
        // Get a published agent to copy configuration from
        $publishedAgent = $this->getPublishedAgent();

        return [
            'agent_name' => $promptVersion->branch->name . ' - Retell Agent',
            'agent_prompt' => $promptVersion->prompt_content,
            'language' => 'de-DE',
            'response_engine' => $publishedAgent['response_engine'] ?? [
                'type' => 'retell-llm',
                'llm_id' => 'llm_f3209286ed1caf6a75906d2645b9',
            ],
            'webhook_url' => $publishedAgent['webhook_url'] ?? config('app.url') . '/api/webhooks/retell',
            'voice_id' => $publishedAgent['voice_id'] ?? 'openai-Carola',
            'voice_temperature' => $publishedAgent['voice_temperature'] ?? 0.1,
            'voice_speed' => $publishedAgent['voice_speed'] ?? 1,
            'enable_backchannel' => $publishedAgent['enable_backchannel'] ?? true,
            'backchannel_frequency' => $publishedAgent['backchannel_frequency'] ?? 0.2,
            'max_call_duration_ms' => $publishedAgent['max_call_duration_ms'] ?? 300000,
            'interruption_sensitivity' => $publishedAgent['interruption_sensitivity'] ?? 0.5,
            'ambient_sound_volume' => $publishedAgent['ambient_sound_volume'] ?? 0.48,
            'responsiveness' => $publishedAgent['responsiveness'] ?? 1,
            'functions' => $promptVersion->functions_config,
        ];
    }

    /**
     * Get a published agent for configuration template
     */
    private function getPublishedAgent(): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/list-agents");

            if ($response->successful()) {
                $agents = $response->json();
                // Find a published version
                foreach ($agents as $agent) {
                    if ($agent['is_published'] ?? false) {
                        return $agent;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not fetch published agent configuration', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get agent status from Retell API
     */
    public function getAgentStatus(string $agentId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/agent/{$agentId}");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Failed to get agent status', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Rollback to previous version
     */
    public function rollbackToVersion(RetellAgentPrompt $promptVersion): array
    {
        try {
            // Validate the old version
            $validationErrors = $promptVersion->validate();
            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validationErrors,
                ];
            }

            // Deploy it
            return $this->deployPromptVersion($promptVersion);

        } catch (\Exception $e) {
            Log::error('Rollback failed', [
                'version_id' => $promptVersion->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Rollback failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get version history for branch
     */
    public function getVersionHistory(Branch $branch, int $limit = 10): array
    {
        return RetellAgentPrompt::where('branch_id', $branch->id)
            ->where('is_template', false)
            ->orderByDesc('version')
            ->limit($limit)
            ->with('deployedBy')
            ->get()
            ->toArray();
    }

    /**
     * Create new version from template
     */
    public function createFromTemplate(Branch $branch, string $templateName): RetellAgentPrompt
    {
        $templateService = new RetellPromptTemplateService();
        return $templateService->applyTemplateToBranch($branch->id, $templateName);
    }

    /**
     * Test if functions work (basic check)
     */
    public function testFunctions(array $functionsConfig): array
    {
        $requiredFunctions = ['list_services', 'collect_appointment_data'];
        $missingFunctions = [];

        $functionNames = array_column($functionsConfig, 'name');

        foreach ($requiredFunctions as $required) {
            if (!in_array($required, $functionNames)) {
                $missingFunctions[] = $required;
            }
        }

        return [
            'all_present' => empty($missingFunctions),
            'missing' => $missingFunctions,
            'total_functions' => count($functionsConfig),
        ];
    }

    /**
     * Update prompt content and deploy new version
     */
    public function updatePromptContent(RetellAgentPrompt $activePrompt, string $newPromptContent, ?User $updatedBy = null): array
    {
        try {
            // Validate new prompt
            $validationErrors = $this->validationService->validatePromptContent($newPromptContent);
            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'message' => 'Prompt validation failed',
                    'errors' => $validationErrors,
                ];
            }

            // Create new version
            $newVersion = RetellAgentPrompt::create([
                'branch_id' => $activePrompt->branch_id,
                'version' => RetellAgentPrompt::getNextVersionForBranch($activePrompt->branch_id),
                'prompt_content' => $newPromptContent,
                'functions_config' => $activePrompt->functions_config,
                'is_active' => false,
                'is_template' => false,
                'validation_status' => 'pending',
                'deployment_notes' => 'Updated prompt content',
            ]);

            // Deploy new version
            $deployResult = $this->deployPromptVersion($newVersion, $updatedBy);

            if ($deployResult['success']) {
                Log::info('Prompt updated successfully', [
                    'branch_id' => $activePrompt->branch_id,
                    'version' => $newVersion->version,
                ]);
            } else {
                // Delete the failed version
                $newVersion->delete();
            }

            return $deployResult;

        } catch (\Exception $e) {
            Log::error('Failed to update prompt', [
                'error' => $e->getMessage(),
                'version_id' => $activePrompt->id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update prompt: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update functions configuration and deploy new version
     */
    public function updateFunctions(RetellAgentPrompt $activePrompt, array $newFunctions, ?User $updatedBy = null): array
    {
        try {
            // Validate functions
            $validationErrors = $this->validationService->validateFunctionsConfig($newFunctions);
            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'message' => 'Functions validation failed',
                    'errors' => $validationErrors,
                ];
            }

            // Create new version
            $newVersion = RetellAgentPrompt::create([
                'branch_id' => $activePrompt->branch_id,
                'version' => RetellAgentPrompt::getNextVersionForBranch($activePrompt->branch_id),
                'prompt_content' => $activePrompt->prompt_content,
                'functions_config' => $newFunctions,
                'is_active' => false,
                'is_template' => false,
                'validation_status' => 'pending',
                'deployment_notes' => 'Updated functions configuration',
            ]);

            // Deploy new version
            $deployResult = $this->deployPromptVersion($newVersion, $updatedBy);

            if ($deployResult['success']) {
                Log::info('Functions updated successfully', [
                    'branch_id' => $activePrompt->branch_id,
                    'version' => $newVersion->version,
                    'function_count' => count($newFunctions),
                ]);
            } else {
                // Delete the failed version
                $newVersion->delete();
            }

            return $deployResult;

        } catch (\Exception $e) {
            Log::error('Failed to update functions', [
                'error' => $e->getMessage(),
                'version_id' => $activePrompt->id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update functions: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Add a custom function to existing functions
     */
    public function addCustomFunction(RetellAgentPrompt $activePrompt, array $customFunction, ?User $updatedBy = null): array
    {
        try {
            // Validate the new function
            $functionErrors = $this->validationService->validateFunction($customFunction, count($activePrompt->functions_config));
            if (!empty($functionErrors)) {
                return [
                    'success' => false,
                    'message' => 'Custom function validation failed',
                    'errors' => $functionErrors,
                ];
            }

            // Check for duplicate names
            $existingNames = array_column($activePrompt->functions_config, 'name');
            if (in_array($customFunction['name'], $existingNames)) {
                return [
                    'success' => false,
                    'message' => "Function name '{$customFunction['name']}' already exists",
                ];
            }

            // Add to existing functions
            $updatedFunctions = $activePrompt->functions_config;
            $updatedFunctions[] = $customFunction;

            // Validate all functions together
            $allValidationErrors = $this->validationService->validateFunctionsConfig($updatedFunctions);
            if (!empty($allValidationErrors)) {
                return [
                    'success' => false,
                    'message' => 'Functions validation failed after adding custom function',
                    'errors' => $allValidationErrors,
                ];
            }

            // Create new version
            $newVersion = RetellAgentPrompt::create([
                'branch_id' => $activePrompt->branch_id,
                'version' => RetellAgentPrompt::getNextVersionForBranch($activePrompt->branch_id),
                'prompt_content' => $activePrompt->prompt_content,
                'functions_config' => $updatedFunctions,
                'is_active' => false,
                'is_template' => false,
                'validation_status' => 'pending',
                'deployment_notes' => "Added custom function: {$customFunction['name']}",
            ]);

            // Deploy new version
            $deployResult = $this->deployPromptVersion($newVersion, $updatedBy);

            if ($deployResult['success']) {
                Log::info('Custom function added successfully', [
                    'branch_id' => $activePrompt->branch_id,
                    'version' => $newVersion->version,
                    'function_name' => $customFunction['name'],
                ]);
            } else {
                // Delete the failed version
                $newVersion->delete();
            }

            return $deployResult;

        } catch (\Exception $e) {
            Log::error('Failed to add custom function', [
                'error' => $e->getMessage(),
                'version_id' => $activePrompt->id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to add custom function: ' . $e->getMessage(),
            ];
        }
    }
}
