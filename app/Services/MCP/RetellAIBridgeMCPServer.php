<?php

namespace App\Services\MCP;

use App\Exceptions\MCPException;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\RetellAICallCampaign;
use App\Services\AgentSelectionService;
use App\Services\PhoneNumberResolver;
use App\Services\MCP\RetellMCPServer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Bridge MCP Server for Retell.ai external MCP integration.
 *
 * This server bridges our Laravel application with the external
 *
 * @abhaybabbar/retellai-mcp-server to enable AI-initiated calls
 */
class RetellAIBridgeMCPServer
{
    protected RetellMCPServer $retellMCPServer;

    protected PhoneNumberResolver $phoneResolver;

    protected AgentSelectionService $agentSelector;

    protected string $externalMCPUrl;

    protected ?string $externalMCPToken;

    public function __construct(
        RetellMCPServer $retellMCPServer,
        PhoneNumberResolver $phoneResolver,
        AgentSelectionService $agentSelector
    ) {
        $this->retellMCPServer = $retellMCPServer;
        $this->phoneResolver = $phoneResolver;
        $this->agentSelector = $agentSelector;
        $this->externalMCPUrl = config('services.retell_mcp.url', 'http://localhost:3001');
        $this->externalMCPToken = config('services.retell_mcp.token');
    }

    /**
     * Create an outbound AI call.
     */
    public function createOutboundCall(array $params): array
    {
        $this->validateParams($params, ['company_id', 'to_number']);

        try {
            $company = Company::findOrFail($params['company_id']);

            // Select agent if not provided
            if (! isset($params['agent_id']) || empty($params['agent_id'])) {
                $context = [
                    'company_id' => $company->id,
                    'purpose' => $params['purpose'] ?? 'outbound_call',
                    'customer_id' => $params['customer_id'] ?? null,
                    'service_id' => $params['service_id'] ?? null,
                    'branch_id' => $params['branch_id'] ?? null,
                    'language' => $params['language'] ?? $company->default_language ?? 'de',
                ];

                $selectedAgent = $this->agentSelector->selectAgent($context);

                if (! $selectedAgent) {
                    throw new MCPException('No suitable agent found for this call');
                }

                $params['agent_id'] = $selectedAgent->retell_agent_id;

                Log::info('Agent selected for outbound call', [
                    'agent_id' => $selectedAgent->retell_agent_id,
                    'agent_name' => $selectedAgent->name,
                    'context' => $context,
                ]);
            }

            // Prepare call parameters
            $callParams = [
                'to_number' => $this->normalizePhoneNumber($params['to_number']),
                'from_number' => $params['from_number'] ?? $this->getDefaultFromNumber($company),
                'agent_id' => $params['agent_id'],
                'metadata' => [
                    'company_id' => $company->id,
                    'campaign_id' => $params['campaign_id'] ?? null,
                    'customer_id' => $params['customer_id'] ?? null,
                    'purpose' => $params['purpose'] ?? 'outbound_call',
                    'initiated_by' => auth()->user()->id ?? 'system',
                ],
            ];

            // Add dynamic variables if provided
            if (isset($params['dynamic_variables'])) {
                $callParams['dynamic_variables'] = $params['dynamic_variables'];
            }

            // Call external MCP server
            $response = $this->callExternalMCP('create_call', $callParams);

            if (! $response['success']) {
                throw new MCPException('Failed to create call: ' . ($response['error'] ?? 'Unknown error'));
            }

            // Store call record in our database
            $call = $this->createCallRecord($company, $callParams, $response['result']);

            // Log the outbound call
            Log::info('Outbound AI call created', [
                'call_id' => $call->id,
                'retell_call_id' => $response['result']['call_id'] ?? null,
                'to_number' => $callParams['to_number'],
                'purpose' => $callParams['metadata']['purpose'],
            ]);

            return [
                'success' => true,
                'call_id' => $call->id,
                'retell_call_id' => $response['result']['call_id'] ?? null,
                'status' => 'initiated',
                'message' => 'Outbound call initiated successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create outbound call', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            throw new MCPException('Failed to create outbound call: ' . $e->getMessage());
        }
    }

    /**
     * Create a call campaign for bulk outbound calls.
     */
    public function createCallCampaign(array $params): array
    {
        $this->validateParams($params, ['company_id', 'name', 'agent_id', 'target_type']);

        try {
            $company = Company::findOrFail($params['company_id']);

            // Create campaign record
            $campaign = RetellAICallCampaign::create([
                'company_id' => $company->id,
                'name' => $params['name'],
                'description' => $params['description'] ?? null,
                'agent_id' => $params['agent_id'],
                'target_type' => $params['target_type'], // 'all_customers', 'inactive_customers', 'custom_list'
                'target_criteria' => $params['target_criteria'] ?? [],
                'schedule_type' => $params['schedule_type'] ?? 'immediate', // 'immediate', 'scheduled', 'recurring'
                'scheduled_at' => $params['scheduled_at'] ?? null,
                'dynamic_variables' => $params['dynamic_variables'] ?? [],
                'status' => 'draft',
                'created_by' => auth()->user()->id,
            ]);

            // Get target customers based on criteria
            $targetCustomers = $this->getTargetCustomers($campaign);
            $campaign->total_targets = $targetCustomers->count();
            $campaign->save();

            Log::info('Call campaign created', [
                'campaign_id' => $campaign->id,
                'name' => $campaign->name,
                'targets' => $campaign->total_targets,
            ]);

            return [
                'success' => true,
                'campaign_id' => $campaign->id,
                'total_targets' => $campaign->total_targets,
                'status' => $campaign->status,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create call campaign', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            throw new MCPException('Failed to create campaign: ' . $e->getMessage());
        }
    }

    /**
     * Start a call campaign.
     */
    public function startCampaign(array $params): array
    {
        $this->validateParams($params, ['campaign_id']);

        try {
            $campaign = RetellAICallCampaign::findOrFail($params['campaign_id']);

            if ($campaign->status !== 'draft') {
                throw new MCPException('Campaign must be in draft status to start');
            }

            // Check if batch processing is enabled
            $useBatchProcessing = config('retell-mcp.batch_processing.enabled', true);

            // Update campaign status
            $campaign->status = 'running';
            $campaign->started_at = now();
            $campaign->save();

            // Queue campaign processing job
            if ($useBatchProcessing) {
                // Use new batch processing job
                \App\Jobs\ProcessRetellAICampaignBatchJob::dispatch($campaign)
                    ->onQueue(config('retell-mcp.batch_processing.queue_name', 'campaigns'));
                $processingType = 'batch';
            } else {
                // Use legacy job
                \App\Jobs\ProcessRetellAICampaignJob::dispatch($campaign)->onQueue('campaigns');
                $processingType = 'sequential';
            }

            return [
                'success' => true,
                'campaign_id' => $campaign->id,
                'status' => 'running',
                'processing_type' => $processingType,
                'message' => 'Campaign started successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to start campaign', [
                'error' => $e->getMessage(),
                'campaign_id' => $params['campaign_id'],
            ]);

            throw new MCPException('Failed to start campaign: ' . $e->getMessage());
        }
    }

    /**
     * Test voice configuration with a test call.
     */
    public function testVoiceConfiguration(array $params): array
    {
        $this->validateParams($params, ['company_id', 'agent_id', 'test_number']);

        try {
            // Create test call with special metadata
            $testParams = array_merge($params, [
                'to_number' => $params['test_number'],
                'purpose' => 'voice_test',
                'dynamic_variables' => [
                    'test_mode' => true,
                    'test_scenario' => $params['test_scenario'] ?? 'greeting',
                ],
            ]);

            $result = $this->createOutboundCall($testParams);

            // Store test metadata for later analysis
            Cache::put(
                "voice_test:{$result['call_id']}",
                [
                    'agent_id' => $params['agent_id'],
                    'test_scenario' => $params['test_scenario'] ?? 'greeting',
                    'started_at' => now()->toISOString(),
                ],
                3600 // 1 hour TTL
            );

            return array_merge($result, [
                'test_mode' => true,
                'message' => 'Test call initiated. Monitor the call for voice quality and agent behavior.',
            ]);
        } catch (\Exception $e) {
            Log::error('Voice test failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            throw new MCPException('Voice test failed: ' . $e->getMessage());
        }
    }

    /**
     * Get available MCP tools from external server.
     */
    public function getAvailableTools(): array
    {
        try {
            $response = Http::get($this->externalMCPUrl . '/mcp/tools');

            if ($response->successful()) {
                return $response->json();
            }

            throw new MCPException('Failed to fetch available tools');
        } catch (\Exception $e) {
            Log::error('Failed to get MCP tools', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Unable to connect to MCP server',
                'tools' => [],
            ];
        }
    }

    /**
     * Health check for external MCP server.
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(5)->get($this->externalMCPUrl . '/health');

            if ($response->successful()) {
                return array_merge($response->json(), [
                    'bridge_status' => 'connected',
                ]);
            }

            return [
                'status' => 'unhealthy',
                'bridge_status' => 'disconnected',
                'error' => 'MCP server not responding',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'bridge_status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Call external MCP server.
     */
    protected function callExternalMCP(string $tool, array $params): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($this->externalMCPToken) {
            $headers['Authorization'] = 'Bearer ' . $this->externalMCPToken;
        }

        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->post($this->externalMCPUrl . '/mcp/execute', [
                'tool' => $tool,
                'params' => $params,
                'metadata' => [
                    'source' => 'laravel_bridge',
                    'user_id' => auth()->user()->id ?? null,
                    'company_id' => $params['company_id'] ?? null,
                ],
            ]);

        if (! $response->successful()) {
            throw new MCPException('MCP server error: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Create call record in database.
     */
    protected function createCallRecord(Company $company, array $params, array $retellResponse): Call
    {
        return Call::create([
            'company_id' => $company->id,
            'branch_id' => $params['metadata']['branch_id'] ?? null,
            'customer_id' => $params['metadata']['customer_id'] ?? null,
            'retell_call_id' => $retellResponse['call_id'] ?? Str::uuid(),
            'from_number' => $params['from_number'],
            'to_number' => $params['to_number'],
            'direction' => 'outbound',
            'status' => 'initiated',
            'metadata' => array_merge($params['metadata'], [
                'mcp_bridge' => true,
                'retell_response' => $retellResponse,
            ]),
            'created_at' => now(),
        ]);
    }

    /**
     * Get target customers for campaign.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getTargetCustomers(RetellAICallCampaign $campaign)
    {
        $query = Customer::where('company_id', $campaign->company_id);

        switch ($campaign->target_type) {
            case 'inactive_customers':
                $inactiveDays = $campaign->target_criteria['inactive_days'] ?? 90;
                $query->whereDoesntHave('appointments', function ($q) use ($inactiveDays) {
                    $q->where('created_at', '>=', now()->subDays($inactiveDays));
                });

                break;
            case 'custom_list':
                if (isset($campaign->target_criteria['customer_ids'])) {
                    $query->whereIn('id', $campaign->target_criteria['customer_ids']);
                }

                break;
            case 'all_customers':
            default:
                // No additional filters
                break;
        }

        // Apply additional filters
        if (isset($campaign->target_criteria['has_phone']) && $campaign->target_criteria['has_phone']) {
            $query->whereNotNull('phone');
        }

        return $query->get();
    }

    /**
     * Get default from number for company.
     */
    protected function getDefaultFromNumber(Company $company): string
    {
        // Try to get from company's phone numbers
        $phoneNumber = $company->phoneNumbers()
            ->where('is_active', true)
            ->where('type', 'outbound')
            ->first();

        if ($phoneNumber) {
            return $phoneNumber->number;
        }

        // Fallback to company's main number or config default
        return $company->phone ?? config('services.retell.default_from_number');
    }

    /**
     * Normalize phone number.
     */
    protected function normalizePhoneNumber(string $number): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $number);

        // Add country code if missing (assuming German numbers)
        if (strlen($cleaned) === 10) {
            $cleaned = '49' . $cleaned;
        } elseif (substr($cleaned, 0, 1) === '0') {
            $cleaned = '49' . substr($cleaned, 1);
        }

        // Add + prefix
        if (substr($cleaned, 0, 1) !== '+') {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Validate required parameters.
     *
     * @throws MCPException
     */
    protected function validateParams(array $params, array $required): void
    {
        foreach ($required as $param) {
            if (! isset($params[$param]) || empty($params[$param])) {
                throw new MCPException("Missing required parameter: {$param}", MCPException::INVALID_PARAMS);
            }
        }
    }
}
