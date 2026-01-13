<?php

namespace App\Services\Retell;

use App\Models\Branch;
use App\Models\Company;
use App\Models\RetellAgent;
use App\Services\RetellApiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for provisioning Retell AI agents for companies and branches.
 *
 * This service handles the automatic creation, configuration, and management
 * of Retell agents as part of the multi-tenant onboarding process.
 */
class RetellProvisioningService
{
    private RetellApiClient $apiClient;

    /**
     * Default template agent ID to clone from (configured in services.retellai.template_agent_id)
     */
    private ?string $templateAgentId;

    /**
     * Default webhook URL pattern for agent callbacks
     */
    private string $webhookUrlPattern;

    public function __construct(RetellApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
        $this->templateAgentId = config('services.retellai.template_agent_id');
        $this->webhookUrlPattern = config('services.retellai.webhook_url_pattern', '{app_url}/api/retell/webhook');
    }

    /**
     * Provision a new Retell agent for a company.
     *
     * Creates a new agent (optionally cloned from template) and links it to the company.
     *
     * @param Company $company The company to provision for
     * @param array $options Additional configuration options
     * @return array{success: bool, agent_id: ?string, message: string, agent_data: ?array}
     */
    public function provisionForCompany(Company $company, array $options = []): array
    {
        Log::info('Provisioning Retell agent for company', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'options' => array_keys($options),
        ]);

        try {
            // Check if company already has an agent
            if (!empty($company->retell_agent_id) && !($options['force_new'] ?? false)) {
                return [
                    'success' => false,
                    'agent_id' => $company->retell_agent_id,
                    'message' => 'Company already has a Retell agent. Use force_new=true to create a new one.',
                    'agent_data' => null,
                ];
            }

            // Determine provisioning method
            $templateId = $options['template_agent_id'] ?? $this->templateAgentId;
            $agentData = null;

            if ($templateId && ($options['clone_template'] ?? true)) {
                // Clone from template
                $agentData = $this->cloneFromTemplate($company, $templateId, $options);
            } else {
                // Create new agent from scratch
                $agentData = $this->createNewAgent($company, $options);
            }

            if (!$agentData || empty($agentData['agent_id'])) {
                return [
                    'success' => false,
                    'agent_id' => null,
                    'message' => 'Failed to create Retell agent via API',
                    'agent_data' => null,
                ];
            }

            // Store in database
            $this->storeAgentRecord($company, $agentData, $options);

            // Update company with agent ID
            $company->update([
                'retell_agent_id' => $agentData['agent_id'],
                'retell_enabled' => true,
            ]);

            Log::info('Successfully provisioned Retell agent for company', [
                'company_id' => $company->id,
                'agent_id' => $agentData['agent_id'],
            ]);

            return [
                'success' => true,
                'agent_id' => $agentData['agent_id'],
                'message' => 'Agent successfully provisioned',
                'agent_data' => $agentData,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to provision Retell agent for company', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'agent_id' => null,
                'message' => 'Provisioning failed: ' . $e->getMessage(),
                'agent_data' => null,
            ];
        }
    }

    /**
     * Provision a Retell agent for a specific branch.
     *
     * Branches can optionally have their own agents (different from company default).
     *
     * @param Branch $branch The branch to provision for
     * @param array $options Additional configuration options
     * @return array{success: bool, agent_id: ?string, message: string, agent_data: ?array}
     */
    public function provisionForBranch(Branch $branch, array $options = []): array
    {
        Log::info('Provisioning Retell agent for branch', [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'company_id' => $branch->company_id,
        ]);

        try {
            // Check if branch already has an agent
            if (!empty($branch->retell_agent_id) && !($options['force_new'] ?? false)) {
                return [
                    'success' => false,
                    'agent_id' => $branch->retell_agent_id,
                    'message' => 'Branch already has a Retell agent. Use force_new=true to create a new one.',
                    'agent_data' => null,
                ];
            }

            // Use company's agent as template if available, otherwise use default template
            $templateId = $options['template_agent_id']
                ?? $branch->company->retell_agent_id
                ?? $this->templateAgentId;

            $agentData = null;

            if ($templateId) {
                $agentData = $this->cloneFromTemplate($branch->company, $templateId, array_merge($options, [
                    'agent_name_suffix' => ' - ' . $branch->name,
                    'branch_id' => $branch->id,
                ]));
            } else {
                $agentData = $this->createNewAgent($branch->company, array_merge($options, [
                    'agent_name_suffix' => ' - ' . $branch->name,
                    'branch_id' => $branch->id,
                ]));
            }

            if (!$agentData || empty($agentData['agent_id'])) {
                return [
                    'success' => false,
                    'agent_id' => null,
                    'message' => 'Failed to create Retell agent via API',
                    'agent_data' => null,
                ];
            }

            // Update branch with agent ID
            $branch->update([
                'retell_agent_id' => $agentData['agent_id'],
                'retell_agent_cache' => $agentData,
                'retell_last_sync' => now(),
            ]);

            // Store in RetellAgent model
            $this->storeAgentRecord($branch->company, $agentData, array_merge($options, [
                'branch_id' => $branch->id,
            ]));

            Log::info('Successfully provisioned Retell agent for branch', [
                'branch_id' => $branch->id,
                'agent_id' => $agentData['agent_id'],
            ]);

            return [
                'success' => true,
                'agent_id' => $agentData['agent_id'],
                'message' => 'Agent successfully provisioned for branch',
                'agent_data' => $agentData,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to provision Retell agent for branch', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'agent_id' => null,
                'message' => 'Provisioning failed: ' . $e->getMessage(),
                'agent_data' => null,
            ];
        }
    }

    /**
     * Clone an agent from a template with company-specific configuration.
     */
    protected function cloneFromTemplate(Company $company, string $templateId, array $options = []): ?array
    {
        $agentName = $this->generateAgentName($company, $options);
        $webhookUrl = $this->generateWebhookUrl($company, $options);

        $overrides = [
            'agent_name' => $agentName,
            'webhook_url' => $webhookUrl,
            'metadata' => array_merge($options['metadata'] ?? [], [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'provisioned_at' => now()->toISOString(),
                'provisioned_by' => 'auto-provisioning',
                'branch_id' => $options['branch_id'] ?? null,
            ]),
        ];

        // Add any custom overrides from options
        if (!empty($options['voice_id'])) {
            $overrides['voice_id'] = $options['voice_id'];
        }
        if (!empty($options['language'])) {
            $overrides['language'] = $options['language'];
        }

        return $this->apiClient->cloneAgent($templateId, $overrides);
    }

    /**
     * Create a new agent from scratch.
     */
    protected function createNewAgent(Company $company, array $options = []): ?array
    {
        $agentName = $this->generateAgentName($company, $options);
        $webhookUrl = $this->generateWebhookUrl($company, $options);

        $config = [
            'agent_name' => $agentName,
            'voice_id' => $options['voice_id'] ?? '11labs-Adrian',
            'language' => $options['language'] ?? 'de-DE',
            'webhook_url' => $webhookUrl,
            'enable_backchannel' => $options['enable_backchannel'] ?? true,
            'backchannel_frequency' => $options['backchannel_frequency'] ?? 0.8,
            'interruption_sensitivity' => $options['interruption_sensitivity'] ?? 0.8,
            'max_call_duration_ms' => $options['max_call_duration_ms'] ?? 600000, // 10 min default
            'end_call_after_silence_ms' => $options['end_call_after_silence_ms'] ?? 30000, // 30s silence
            'metadata' => array_merge($options['metadata'] ?? [], [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'provisioned_at' => now()->toISOString(),
                'provisioned_by' => 'auto-provisioning',
                'branch_id' => $options['branch_id'] ?? null,
            ]),
        ];

        // Add LLM websocket URL if provided
        if (!empty($options['llm_websocket_url'])) {
            $config['llm_websocket_url'] = $options['llm_websocket_url'];
        }

        return $this->apiClient->createAgent($config);
    }

    /**
     * Generate a unique agent name for the company.
     */
    protected function generateAgentName(Company $company, array $options = []): string
    {
        $baseName = $options['agent_name'] ?? $company->name . ' AI Assistant';
        $suffix = $options['agent_name_suffix'] ?? '';

        return Str::limit($baseName . $suffix, 100);
    }

    /**
     * Generate the webhook URL for the agent.
     */
    protected function generateWebhookUrl(Company $company, array $options = []): string
    {
        if (!empty($options['webhook_url'])) {
            return $options['webhook_url'];
        }

        $pattern = $this->webhookUrlPattern;
        $appUrl = config('app.url');

        $url = str_replace('{app_url}', $appUrl, $pattern);
        $url = str_replace('{company_id}', (string) $company->id, $url);

        if (!empty($options['branch_id'])) {
            $url .= '?branch_id=' . $options['branch_id'];
        }

        return $url;
    }

    /**
     * Store the agent record in the database.
     */
    protected function storeAgentRecord(Company $company, array $agentData, array $options = []): RetellAgent
    {
        return RetellAgent::updateOrCreate(
            ['agent_id' => $agentData['agent_id']],
            [
                'company_id' => $company->id,
                'agent_name' => $agentData['agent_name'] ?? 'Unknown',
                'voice_id' => $agentData['voice_id'] ?? null,
                'voice_model' => $agentData['voice_model'] ?? null,
                'language' => $agentData['language'] ?? 'de-DE',
                'llm_model' => $agentData['llm_model'] ?? null,
                'webhook_url' => $agentData['webhook_url'] ?? null,
                'is_active' => true,
                'max_call_duration' => isset($agentData['max_call_duration_ms'])
                    ? $agentData['max_call_duration_ms'] / 1000 / 60 // Convert ms to minutes
                    : null,
                'interruption_sensitivity' => $agentData['interruption_sensitivity'] ?? null,
                'backchannel_frequency' => $agentData['backchannel_frequency'] ?? null,
                'metadata' => $agentData['metadata'] ?? null,
                'settings' => [
                    'provisioned_via' => 'RetellProvisioningService',
                    'provisioned_at' => now()->toISOString(),
                    'branch_id' => $options['branch_id'] ?? null,
                    'source_agent_id' => $options['template_agent_id'] ?? null,
                ],
            ]
        );
    }

    /**
     * Deprovision (delete) an agent for a company.
     */
    public function deprovisionForCompany(Company $company): array
    {
        if (empty($company->retell_agent_id)) {
            return [
                'success' => false,
                'message' => 'Company has no provisioned agent',
            ];
        }

        $agentId = $company->retell_agent_id;

        try {
            // Delete from Retell API
            $deleted = $this->apiClient->deleteAgent($agentId);

            if ($deleted) {
                // Update company
                $company->update([
                    'retell_agent_id' => null,
                    'retell_enabled' => false,
                ]);

                // Soft-delete local record
                RetellAgent::where('agent_id', $agentId)->update(['is_active' => false]);

                return [
                    'success' => true,
                    'message' => 'Agent successfully deprovisioned',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete agent from Retell API',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to deprovision agent', [
                'company_id' => $company->id,
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Deprovisioning failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync agent data from Retell API to local database.
     */
    public function syncAgentFromApi(string $agentId): ?RetellAgent
    {
        $agentData = $this->apiClient->getAgent($agentId);

        if (!$agentData) {
            return null;
        }

        // Find or create local record
        $localAgent = RetellAgent::where('agent_id', $agentId)->first();

        if (!$localAgent) {
            Log::warning('Agent not found locally during sync', ['agent_id' => $agentId]);
            return null;
        }

        $localAgent->update([
            'agent_name' => $agentData['agent_name'] ?? $localAgent->agent_name,
            'voice_id' => $agentData['voice_id'] ?? $localAgent->voice_id,
            'language' => $agentData['language'] ?? $localAgent->language,
            'webhook_url' => $agentData['webhook_url'] ?? $localAgent->webhook_url,
            'metadata' => $agentData['metadata'] ?? $localAgent->metadata,
        ]);

        return $localAgent;
    }

    /**
     * Get provisioning status for a company.
     */
    public function getProvisioningStatus(Company $company): array
    {
        $status = [
            'has_agent' => !empty($company->retell_agent_id),
            'agent_id' => $company->retell_agent_id,
            'retell_enabled' => $company->retell_enabled ?? false,
            'api_status' => null,
            'local_record' => null,
        ];

        if ($status['has_agent']) {
            // Check API status
            $apiData = $this->apiClient->getAgent($company->retell_agent_id);
            $status['api_status'] = $apiData ? 'active' : 'not_found';

            // Check local record
            $localAgent = RetellAgent::where('agent_id', $company->retell_agent_id)->first();
            $status['local_record'] = $localAgent ? [
                'id' => $localAgent->id,
                'is_active' => $localAgent->is_active,
                'call_count' => $localAgent->call_count,
                'last_used_at' => $localAgent->last_used_at,
            ] : null;
        }

        return $status;
    }

    /**
     * Get available template agents for cloning.
     */
    public function getAvailableTemplates(): array
    {
        $agents = $this->apiClient->getAgents();

        if (empty($agents)) {
            return [];
        }

        // Filter to only include template-like agents (those with 'template' in name or metadata)
        return collect($agents)->filter(function ($agent) {
            $name = strtolower($agent['agent_name'] ?? '');
            $isTemplate = str_contains($name, 'template') || str_contains($name, 'vorlage');

            // Also include the configured template agent
            if ($this->templateAgentId && ($agent['agent_id'] ?? null) === $this->templateAgentId) {
                $isTemplate = true;
            }

            return $isTemplate;
        })->values()->toArray();
    }
}
