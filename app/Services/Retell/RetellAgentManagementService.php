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

            // Get current live agent to extract LLM ID
            $liveAgent = $this->getLiveAgent();

            if (!$liveAgent) {
                throw new \Exception('Could not fetch live agent configuration');
            }

            $llmId = $liveAgent['response_engine']['llm_id'] ?? null;

            if (!$llmId) {
                throw new \Exception('No LLM ID found in agent configuration');
            }

            Log::info('Deploying Retell agent prompt', [
                'agent_id' => $agentId,
                'llm_id' => $llmId,
                'branch_id' => $branch->id,
                'version' => $promptVersion->version,
            ]);

            // Update LLM with new prompt and functions
            $llmUpdateResult = $this->updateLlmData(
                $llmId,
                $promptVersion->prompt_content,
                $promptVersion->functions_config,
                $liveAgent['begin_message'] ?? null
            );

            if (!$llmUpdateResult['success']) {
                throw new \Exception($llmUpdateResult['message']);
            }

            $newLlmVersion = $llmUpdateResult['llm_version'] ?? 0;

            // CRITICAL: Update agent to use new LLM version
            $agentUpdateResult = $this->updateAgentLlmVersion($agentId, $llmId, $newLlmVersion);

            if (!$agentUpdateResult['success']) {
                Log::warning('Agent update failed but LLM was updated', [
                    'llm_version' => $newLlmVersion,
                    'error' => $agentUpdateResult['message']
                ]);
                // Don't fail deployment - LLM is updated, just warn
            }

            // Update prompt version with deployment info
            $promptVersion->update([
                'is_active' => true,
                'deployed_at' => now(),
                'deployed_by' => $deployedBy?->id,
                'retell_agent_id' => $agentId,
                'retell_version' => $newLlmVersion,
                'validation_status' => 'valid',
            ]);

            // Deactivate other versions
            RetellAgentPrompt::where('branch_id', $promptVersion->branch_id)
                ->where('id', '!=', $promptVersion->id)
                ->update(['is_active' => false]);

            Log::info('Successfully deployed prompt to Retell LLM and updated agent', [
                'llm_id' => $llmId,
                'llm_version' => $newLlmVersion,
                'agent_updated' => $agentUpdateResult['success'],
                'prompt_version_id' => $promptVersion->id
            ]);

            return [
                'success' => true,
                'message' => 'Prompt deployed successfully to Retell LLM (Version ' . $newLlmVersion . ')',
                'retell_version' => $newLlmVersion,
                'deployed_at' => $promptVersion->deployed_at,
                'llm_id' => $llmId,
                'agent_updated' => $agentUpdateResult['success'],
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
            ])->get("{$this->baseUrl}/get-agent/{$agentId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Failed to get agent from Retell API', [
                'agent_id' => $agentId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
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

    /**
     * Get LLM data (prompt + functions) from Retell API
     *
     * @param string $llmId The LLM ID
     * @return array|null LLM data or null if not found
     */
    public function getLlmData(string $llmId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/get-retell-llm/{$llmId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Failed to get LLM data from Retell API', [
                'llm_id' => $llmId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get LLM data', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Normalize function array fields to objects for Retell API
     * Retell API expects objects {} not arrays [] for certain fields
     */
    private function normalizeFunctionsForApi(array $functions): array
    {
        return array_map(function ($function) {
            // Convert empty arrays to empty objects
            if (isset($function['headers']) && is_array($function['headers']) && empty($function['headers'])) {
                $function['headers'] = (object)[];
            }
            if (isset($function['query_params']) && is_array($function['query_params']) && empty($function['query_params'])) {
                $function['query_params'] = (object)[];
            }
            if (isset($function['response_variables']) && is_array($function['response_variables']) && empty($function['response_variables'])) {
                $function['response_variables'] = (object)[];
            }

            return $function;
        }, $functions);
    }

    /**
     * Update agent to use a specific LLM version
     *
     * @param string $agentId The agent ID
     * @param string $llmId The LLM ID
     * @param int $llmVersion The LLM version to use
     * @return array Result with success status
     */
    public function updateAgentLlmVersion(string $agentId, string $llmId, int $llmVersion): array
    {
        try {
            // Get current agent data to preserve settings
            $currentAgent = $this->getAgentStatus($agentId);

            if (!$currentAgent) {
                throw new \Exception("Agent not found: $agentId");
            }

            // Build minimal update payload - only update LLM version
            $payload = [
                'response_engine' => [
                    'type' => 'retell-llm',
                    'llm_id' => $llmId,
                    'version' => $llmVersion
                ]
            ];

            Log::info('Updating agent to use new LLM version', [
                'agent_id' => $agentId,
                'llm_id' => $llmId,
                'llm_version' => $llmVersion
            ]);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json'
            ])->patch("{$this->baseUrl}/update-agent/{$agentId}", $payload);

            if (!$response->successful()) {
                $errorMessage = $response->json()['error'] ?? $response->json()['message'] ?? $response->body();
                throw new \Exception("Retell API error: $errorMessage");
            }

            $agentData = $response->json();

            Log::info('Successfully updated agent to new LLM version', [
                'agent_id' => $agentId,
                'llm_version' => $llmVersion
            ]);

            return [
                'success' => true,
                'message' => 'Agent updated to use LLM version ' . $llmVersion,
                'agent_data' => $agentData
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update agent LLM version', [
                'agent_id' => $agentId,
                'llm_id' => $llmId,
                'llm_version' => $llmVersion,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Agent update failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Update LLM data (prompt + functions) in Retell API
     *
     * @param string $llmId The LLM ID
     * @param string $prompt The new prompt content
     * @param array $functions The functions configuration
     * @param string|null $beginMessage Optional begin message
     * @return array Result with success status
     */
    public function updateLlmData(string $llmId, string $prompt, array $functions, ?string $beginMessage = null): array
    {
        try {
            // Get current LLM data to preserve other settings
            $currentLlm = $this->getLlmData($llmId);

            if (!$currentLlm) {
                throw new \Exception("LLM not found: $llmId");
            }

            // Normalize functions for API (empty arrays -> empty objects)
            $normalizedFunctions = $this->normalizeFunctionsForApi($functions);

            // Build payload preserving existing config
            $payload = [
                'general_prompt' => $prompt,
                'general_tools' => $normalizedFunctions,
                'begin_message' => $beginMessage ?? $currentLlm['begin_message'] ?? '',
                // Preserve existing config
                'model' => $currentLlm['model'] ?? 'gemini-2.5-flash',
                'model_temperature' => $currentLlm['model_temperature'] ?? 0.04,
                'model_high_priority' => $currentLlm['model_high_priority'] ?? false,
                'tool_call_strict_mode' => $currentLlm['tool_call_strict_mode'] ?? false,
                'start_speaker' => $currentLlm['start_speaker'] ?? 'agent',
            ];

            Log::info('Updating LLM in Retell API', [
                'llm_id' => $llmId,
                'prompt_length' => strlen($prompt),
                'functions_count' => count($functions)
            ]);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json'
            ])->patch("{$this->baseUrl}/update-retell-llm/{$llmId}", $payload);

            if (!$response->successful()) {
                $errorMessage = $response->json()['error'] ?? $response->json()['message'] ?? $response->body();
                throw new \Exception("Retell API error: $errorMessage");
            }

            $llmData = $response->json();

            Log::info('Successfully updated LLM in Retell API', [
                'llm_id' => $llmId,
                'version' => $llmData['version'] ?? 'unknown'
            ]);

            return [
                'success' => true,
                'message' => 'LLM updated successfully',
                'llm_version' => $llmData['version'] ?? 0,
                'llm_data' => $llmData
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update LLM', [
                'llm_id' => $llmId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'LLM update failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Get the currently live/published agent from Retell API
     * Uses the agent_id from config as the source of truth
     * Includes LLM data (prompt + functions) merged into response
     *
     * @return array|null Agent data or null if agent not found
     */
    public function getLiveAgent(): ?array
    {
        try {
            // Use configured agent ID as source of truth
            $configuredAgentId = config('services.retellai.agent_id');

            if (!$configuredAgentId) {
                Log::warning('No agent_id configured in services.retellai.agent_id');
                return null;
            }

            // Get full agent details directly
            $agentData = $this->getAgentStatus($configuredAgentId);

            if (!$agentData) {
                Log::warning('Could not fetch agent from Retell API', [
                    'agent_id' => $configuredAgentId
                ]);
                return null;
            }

            // Extract LLM ID from response_engine
            $llmId = $agentData['response_engine']['llm_id'] ?? null;

            if ($llmId) {
                // Fetch LLM data (contains prompt + functions)
                $llmData = $this->getLlmData($llmId);

                if ($llmData) {
                    // Merge LLM data into agent data
                    // Map general_prompt -> agent_prompt for compatibility
                    $agentData['agent_prompt'] = $llmData['general_prompt'] ?? '';
                    $agentData['functions'] = $llmData['general_tools'] ?? [];
                    $agentData['begin_message'] = $llmData['begin_message'] ?? '';
                    $agentData['llm_version'] = $llmData['version'] ?? null;
                    $agentData['llm_model'] = $llmData['model'] ?? null;

                    Log::info('Successfully merged LLM data into agent response', [
                        'agent_id' => $configuredAgentId,
                        'llm_id' => $llmId,
                        'prompt_length' => strlen($agentData['agent_prompt']),
                        'functions_count' => count($agentData['functions'])
                    ]);
                } else {
                    Log::warning('Could not fetch LLM data, agent will have no prompt/functions', [
                        'llm_id' => $llmId
                    ]);
                }
            } else {
                Log::warning('Agent has no LLM ID in response_engine', [
                    'agent_id' => $configuredAgentId
                ]);
            }

            Log::info('Successfully fetched live agent from Retell API', [
                'agent_id' => $configuredAgentId,
                'agent_name' => $agentData['agent_name'] ?? 'Unknown',
                'has_prompt' => isset($agentData['agent_prompt']) && !empty($agentData['agent_prompt'])
            ]);

            return $agentData;

        } catch (\Exception $e) {
            Log::error('Failed to get live agent from Retell API', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * List all agents from Retell API
     *
     * @return array Array of agents or empty array on failure
     */
    public function listAgents(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/list-agents");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to list agents from Retell API', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('Exception while listing agents from Retell API', [
                'error' => $e->getMessage()
            ]);
        }

        return [];
    }

    /**
     * Check sync status between local DB and Retell API
     *
     * @param Branch $branch
     * @return array Sync status with details
     */
    public function checkSync(Branch $branch): array
    {
        try {
            // Get active prompt from local DB
            $activePrompt = $branch->retellAgentPrompts()
                ->where('is_active', true)
                ->first();

            if (!$activePrompt) {
                return [
                    'in_sync' => false,
                    'status' => 'no_local_agent',
                    'message' => 'Kein aktiver Agent in der Datenbank gefunden',
                    'local' => null,
                    'live' => null,
                ];
            }

            // Get live agent from Retell API
            $liveAgent = $this->getLiveAgent();

            if (!$liveAgent) {
                return [
                    'in_sync' => false,
                    'status' => 'no_live_agent',
                    'message' => 'Kein veröffentlichter Agent auf Retell API gefunden',
                    'local' => [
                        'agent_id' => $activePrompt->retell_agent_id,
                        'version' => $activePrompt->version,
                        'deployed_at' => $activePrompt->deployed_at?->format('Y-m-d H:i:s'),
                    ],
                    'live' => null,
                ];
            }

            // Compare agent IDs
            $localAgentId = $activePrompt->retell_agent_id;
            $liveAgentId = $liveAgent['agent_id'];

            $inSync = ($localAgentId === $liveAgentId);

            // Get full details of local agent from Retell API if different
            $localAgentDetails = null;
            if (!$inSync && $localAgentId) {
                $localAgentDetails = $this->getAgentStatus($localAgentId);
            }

            return [
                'in_sync' => $inSync,
                'status' => $inSync ? 'synced' : 'out_of_sync',
                'message' => $inSync
                    ? 'Lokaler Agent ist mit Retell API synchronisiert'
                    : 'Lokaler Agent unterscheidet sich vom veröffentlichten Agent auf Retell API',
                'local' => [
                    'agent_id' => $localAgentId,
                    'agent_name' => $localAgentDetails['agent_name'] ?? $activePrompt->branch->name . ' - Retell Agent',
                    'version' => $activePrompt->version,
                    'deployed_at' => $activePrompt->deployed_at?->format('Y-m-d H:i:s'),
                    'prompt_length' => strlen($activePrompt->prompt_content ?? ''),
                    'functions_count' => count($activePrompt->functions_config ?? []),
                    'is_published' => $localAgentDetails['is_published'] ?? false,
                ],
                'live' => [
                    'agent_id' => $liveAgentId,
                    'agent_name' => $liveAgent['agent_name'] ?? 'Unknown',
                    'prompt_length' => strlen($liveAgent['agent_prompt'] ?? ''),
                    'functions_count' => count($liveAgent['functions'] ?? []),
                    'is_published' => true,
                    'last_modification_timestamp' => $liveAgent['last_modification_timestamp'] ?? null,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check sync status', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'in_sync' => false,
                'status' => 'error',
                'message' => 'Fehler beim Überprüfen des Sync-Status: ' . $e->getMessage(),
                'local' => null,
                'live' => null,
            ];
        }
    }
}
