<?php

namespace App\Services\MCP;

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Models\Call;
use App\Services\RetellV2Service;
use App\Exceptions\SecurityException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * SECURE VERSION: Retell.ai MCP Server with proper tenant isolation
 * 
 * This server handles Retell.ai integration with strict multi-tenant security.
 * All operations are scoped to the authenticated company context.
 * 
 * Security Features:
 * - Mandatory company context validation
 * - No arbitrary company fallbacks
 * - All queries properly scoped to company
 * - Audit logging for all operations
 * - API key encryption/decryption
 */
class SecureRetellMCPServer extends BaseMCPServer
{
    /**
     * @var Company|null Current company context
     */
    protected ?Company $company = null;
    
    /**
     * @var RetellV2Service|null Retell service instance
     */
    protected ?RetellV2Service $retellService = null;
    
    /**
     * @var bool Audit logging enabled
     */
    protected bool $auditEnabled = true;
    
    /**
     * Constructor - resolves company context
     */
    public function __construct()
    {
        parent::__construct();
        $this->resolveCompanyContext();
    }
    
    /**
     * Set company context explicitly (only for super admins or system operations)
     */
    public function setCompanyContext(Company $company): self
    {
        // Only allow super admins or system operations to override context
        if (Auth::check() && !Auth::user()->hasRole('super_admin')) {
            throw new SecurityException('Unauthorized company context override');
        }
        
        $this->company = $company;
        $this->initializeRetellService();
        
        $this->auditAccess('company_context_override', [
            'company_id' => $company->id,
            'user_id' => Auth::id(),
        ]);
        
        return $this;
    }
    
    /**
     * Get server info
     */
    public function getServerInfo(): array
    {
        return [
            'name' => 'secure-retell-mcp-server',
            'version' => '1.0.0',
            'description' => 'Secure Retell.ai integration with tenant isolation',
            'endpoints' => [
                'create-agent',
                'update-agent',
                'list-agents',
                'sync-agents',
                'get-calls',
                'get-call-details',
                'create-phone-call',
                'test-agent',
                'configure-voice'
            ]
        ];
    }
    
    /**
     * Execute a Retell operation
     */
    public function execute(string $operation, array $params = []): array
    {
        $this->ensureCompanyContext();
        
        $this->logDebug("Executing secure Retell operation", [
            'operation' => $operation,
            'params' => $params,
            'company_id' => $this->company->id
        ]);
        
        try {
            switch ($operation) {
                case 'create-agent':
                    return $this->createAgentSecure($params);
                    
                case 'update-agent':
                    return $this->updateAgentSecure($params);
                    
                case 'list-agents':
                    return $this->listAgentsSecure($params);
                    
                case 'sync-agents':
                    return $this->syncAgentsSecure($params);
                    
                case 'get-calls':
                    return $this->getCallsSecure($params);
                    
                case 'get-call-details':
                    return $this->getCallDetailsSecure($params);
                    
                case 'create-phone-call':
                    return $this->createPhoneCallSecure($params);
                    
                case 'test-agent':
                    return $this->testAgentSecure($params);
                    
                case 'configure-voice':
                    return $this->configureVoiceSecure($params);
                    
                default:
                    return $this->errorResponse("Unknown operation: {$operation}");
            }
        } catch (\Exception $e) {
            $this->logError("Secure Retell operation failed", $e, [
                'operation' => $operation,
                'company_id' => $this->company->id
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Create a new Retell agent with company context
     */
    protected function createAgentSecure(array $params): array
    {
        $this->validateParams($params, ['agent_name']);
        $this->auditAccess('create_agent', $params);
        
        // Get branches for the company
        $branches = Branch::where('company_id', $this->company->id)->get();
        
        if ($branches->isEmpty()) {
            throw new SecurityException('No branches found for company');
        }
        
        // Configure agent with company context
        $agentConfig = [
            'agent_name' => $params['agent_name'],
            'voice_id' => $params['voice_id'] ?? '11labs-Aria',
            'language' => $params['language'] ?? 'de',
            'greeting_message' => $params['greeting_message'] ?? $this->getDefaultGreeting(),
            'webhook_url' => config('app.url') . '/api/retell/webhook',
            'fallback_voice_id' => 'openai-Alloy',
            'enable_voicemail' => $params['enable_voicemail'] ?? false,
            'voicemail_message' => $params['voicemail_message'] ?? $this->getDefaultVoicemail(),
            'end_call_after_silence_ms' => $params['end_call_after_silence_ms'] ?? 30000,
            'max_call_duration_ms' => $params['max_call_duration_ms'] ?? 3600000,
            'general_prompt' => $this->generateSecurePrompt($params, $branches),
            'llm_websocket_url' => 'wss://api.retellai.com/llm-websocket'
        ];
        
        // Create agent via API
        $response = $this->retellService->createAgent($agentConfig);
        
        if (!$response['success']) {
            throw new \Exception('Failed to create agent: ' . ($response['error'] ?? 'Unknown error'));
        }
        
        $agentData = $response['data'];
        
        // Store agent in database with company context
        $retellAgent = RetellAgent::create([
            'company_id' => $this->company->id, // CRITICAL: Force company context
            'agent_id' => $agentData['agent_id'],
            'name' => $agentData['agent_name'],
            'voice_id' => $agentData['voice_id'],
            'language' => $agentData['language'],
            'config' => json_encode($agentData),
            'is_active' => true
        ]);
        
        // Create phone numbers for each branch
        foreach ($branches as $branch) {
            $this->createPhoneNumberForBranch($branch, $retellAgent);
        }
        
        return $this->successResponse([
            'agent_id' => $retellAgent->agent_id,
            'name' => $retellAgent->name,
            'branches_configured' => $branches->count()
        ]);
    }
    
    /**
     * Update agent with security validation
     */
    protected function updateAgentSecure(array $params): array
    {
        $this->validateParams($params, ['agent_id']);
        
        // Validate agent belongs to company
        $agent = RetellAgent::where('agent_id', $params['agent_id'])
            ->where('company_id', $this->company->id) // CRITICAL: Company scope
            ->first();
            
        if (!$agent) {
            throw new SecurityException('Agent not found or does not belong to company');
        }
        
        $this->auditAccess('update_agent', [
            'agent_id' => $agent->agent_id,
            'updates' => array_keys($params)
        ]);
        
        // Update via API
        $updateData = array_filter([
            'agent_name' => $params['agent_name'] ?? null,
            'voice_id' => $params['voice_id'] ?? null,
            'language' => $params['language'] ?? null,
            'greeting_message' => $params['greeting_message'] ?? null,
            'general_prompt' => $params['general_prompt'] ?? null,
            'enable_voicemail' => $params['enable_voicemail'] ?? null,
            'voicemail_message' => $params['voicemail_message'] ?? null,
        ], function($value) { return $value !== null; });
        
        if (!empty($updateData)) {
            $response = $this->retellService->updateAgent($agent->agent_id, $updateData);
            
            if (!$response['success']) {
                throw new \Exception('Failed to update agent: ' . ($response['error'] ?? 'Unknown error'));
            }
            
            // Update local record
            $agent->update([
                'name' => $updateData['agent_name'] ?? $agent->name,
                'voice_id' => $updateData['voice_id'] ?? $agent->voice_id,
                'language' => $updateData['language'] ?? $agent->language,
                'config' => json_encode($response['data'])
            ]);
        }
        
        return $this->successResponse([
            'agent_id' => $agent->agent_id,
            'updated' => true
        ]);
    }
    
    /**
     * List agents for the company
     */
    protected function listAgentsSecure(array $params): array
    {
        $this->auditAccess('list_agents');
        
        $agents = RetellAgent::where('company_id', $this->company->id) // Company scope
            ->when(isset($params['is_active']), function($query) use ($params) {
                $query->where('is_active', $params['is_active']);
            })
            ->get();
            
        return $this->successResponse([
            'agents' => $agents->map(function($agent) {
                return [
                    'agent_id' => $agent->agent_id,
                    'name' => $agent->name,
                    'voice_id' => $agent->voice_id,
                    'language' => $agent->language,
                    'is_active' => $agent->is_active,
                    'created_at' => $agent->created_at->toIso8601String()
                ];
            }),
            'total' => $agents->count()
        ]);
    }
    
    /**
     * Sync agents from Retell API
     */
    protected function syncAgentsSecure(array $params): array
    {
        $this->auditAccess('sync_agents');
        
        // Get all agents from API
        $response = $this->retellService->listAgents();
        
        if (!$response['success']) {
            throw new \Exception('Failed to fetch agents: ' . ($response['error'] ?? 'Unknown error'));
        }
        
        $apiAgents = collect($response['data']);
        $syncedCount = 0;
        
        // Get existing agent IDs for this company
        $existingAgentIds = RetellAgent::where('company_id', $this->company->id)
            ->pluck('agent_id')
            ->toArray();
            
        foreach ($apiAgents as $apiAgent) {
            // Only sync agents that belong to this company
            if (in_array($apiAgent['agent_id'], $existingAgentIds)) {
                RetellAgent::where('agent_id', $apiAgent['agent_id'])
                    ->where('company_id', $this->company->id) // Double check company
                    ->update([
                        'name' => $apiAgent['agent_name'],
                        'voice_id' => $apiAgent['voice_id'],
                        'language' => $apiAgent['language'],
                        'config' => json_encode($apiAgent)
                    ]);
                    
                $syncedCount++;
            }
        }
        
        return $this->successResponse([
            'synced' => $syncedCount,
            'total_company_agents' => count($existingAgentIds)
        ]);
    }
    
    /**
     * Get calls for the company
     */
    protected function getCallsSecure(array $params): array
    {
        $this->auditAccess('get_calls', $params);
        
        $query = Call::where('company_id', $this->company->id); // Company scope
        
        // Apply filters
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }
        
        if (isset($params['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($params['date_from']));
        }
        
        if (isset($params['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($params['date_to']));
        }
        
        if (isset($params['branch_id'])) {
            // Validate branch belongs to company
            $branch = Branch::where('id', $params['branch_id'])
                ->where('company_id', $this->company->id)
                ->first();
                
            if ($branch) {
                $query->where('branch_id', $branch->id);
            }
        }
        
        $calls = $query->orderBy('created_at', 'desc')
            ->limit($params['limit'] ?? 50)
            ->get();
            
        return $this->successResponse([
            'calls' => $calls->map(function($call) {
                return [
                    'id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'from_number' => $call->from_number,
                    'to_number' => $call->to_number,
                    'status' => $call->status,
                    'duration_sec' => $call->duration_sec,
                    'created_at' => $call->created_at->toIso8601String(),
                    'customer' => $call->customer ? [
                        'id' => $call->customer->id,
                        'name' => $call->customer->full_name
                    ] : null,
                    'appointment' => $call->appointment ? [
                        'id' => $call->appointment->id,
                        'starts_at' => $call->appointment->starts_at->toIso8601String()
                    ] : null
                ];
            }),
            'total' => $calls->count()
        ]);
    }
    
    /**
     * Get call details with security validation
     */
    protected function getCallDetailsSecure(array $params): array
    {
        $this->validateParams($params, ['call_id']);
        
        $call = Call::where('id', $params['call_id'])
            ->where('company_id', $this->company->id) // CRITICAL: Company scope
            ->with(['customer', 'appointment', 'branch'])
            ->first();
            
        if (!$call) {
            throw new SecurityException('Call not found or does not belong to company');
        }
        
        $this->auditAccess('get_call_details', ['call_id' => $call->id]);
        
        // Get additional details from Retell if available
        $retellDetails = null;
        if ($call->retell_call_id) {
            try {
                $response = $this->retellService->getCall($call->retell_call_id);
                if ($response['success']) {
                    $retellDetails = $response['data'];
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch Retell call details', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $this->successResponse([
            'call' => [
                'id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'status' => $call->status,
                'duration_sec' => $call->duration_sec,
                'recording_url' => $call->recording_url,
                'transcript' => $call->transcript,
                'sentiment' => $call->sentiment,
                'created_at' => $call->created_at->toIso8601String(),
                'ended_at' => $call->ended_at?->toIso8601String(),
                'customer' => $call->customer,
                'appointment' => $call->appointment,
                'branch' => $call->branch ? [
                    'id' => $call->branch->id,
                    'name' => $call->branch->name
                ] : null,
                'retell_details' => $retellDetails
            ]
        ]);
    }
    
    /**
     * Create outbound phone call
     */
    protected function createPhoneCallSecure(array $params): array
    {
        $this->validateParams($params, ['to_number']);
        
        // Get agent for company
        $agent = RetellAgent::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->first();
            
        if (!$agent) {
            throw new SecurityException('No active agent found for company');
        }
        
        // Get phone number for outbound calls
        $phoneNumber = PhoneNumber::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->where('type', 'outbound')
            ->first();
            
        if (!$phoneNumber) {
            // Fallback to any active number
            $phoneNumber = PhoneNumber::where('company_id', $this->company->id)
                ->where('is_active', true)
                ->first();
        }
        
        if (!$phoneNumber) {
            throw new SecurityException('No phone number available for outbound calls');
        }
        
        $this->auditAccess('create_phone_call', [
            'to_number' => $params['to_number'],
            'agent_id' => $agent->agent_id
        ]);
        
        // Create call via API
        $response = $this->retellService->createPhoneCall([
            'agent_id' => $agent->agent_id,
            'from_number' => $phoneNumber->phone_number,
            'to_number' => $params['to_number'],
            'metadata' => [
                'company_id' => $this->company->id,
                'branch_id' => $phoneNumber->branch_id,
                'initiated_by' => Auth::id()
            ]
        ]);
        
        if (!$response['success']) {
            throw new \Exception('Failed to create call: ' . ($response['error'] ?? 'Unknown error'));
        }
        
        // Create call record
        $call = Call::create([
            'company_id' => $this->company->id,
            'branch_id' => $phoneNumber->branch_id,
            'retell_call_id' => $response['data']['call_id'],
            'from_number' => $phoneNumber->phone_number,
            'to_number' => $params['to_number'],
            'status' => 'initiated',
            'direction' => 'outbound',
            'metadata' => json_encode($response['data'])
        ]);
        
        return $this->successResponse([
            'call_id' => $call->id,
            'retell_call_id' => $call->retell_call_id,
            'status' => 'initiated'
        ]);
    }
    
    /**
     * Test agent configuration
     */
    protected function testAgentSecure(array $params): array
    {
        $this->validateParams($params, ['agent_id']);
        
        // Validate agent belongs to company
        $agent = RetellAgent::where('agent_id', $params['agent_id'])
            ->where('company_id', $this->company->id)
            ->first();
            
        if (!$agent) {
            throw new SecurityException('Agent not found or does not belong to company');
        }
        
        $this->auditAccess('test_agent', ['agent_id' => $agent->agent_id]);
        
        // Test agent configuration
        $tests = [
            'api_connection' => false,
            'agent_exists' => false,
            'voice_available' => false,
            'webhook_configured' => false
        ];
        
        try {
            // Test API connection
            $response = $this->retellService->getAgent($agent->agent_id);
            $tests['api_connection'] = true;
            
            if ($response['success']) {
                $tests['agent_exists'] = true;
                
                // Check voice
                if (!empty($response['data']['voice_id'])) {
                    $tests['voice_available'] = true;
                }
                
                // Check webhook
                if (!empty($response['data']['webhook_url'])) {
                    $tests['webhook_configured'] = true;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Agent test failed', [
                'agent_id' => $agent->agent_id,
                'error' => $e->getMessage()
            ]);
        }
        
        return $this->successResponse([
            'agent_id' => $agent->agent_id,
            'tests' => $tests,
            'all_passed' => !in_array(false, $tests, true)
        ]);
    }
    
    /**
     * Configure voice settings
     */
    protected function configureVoiceSecure(array $params): array
    {
        $this->validateParams($params, ['agent_id', 'voice_settings']);
        
        // Validate agent belongs to company
        $agent = RetellAgent::where('agent_id', $params['agent_id'])
            ->where('company_id', $this->company->id)
            ->first();
            
        if (!$agent) {
            throw new SecurityException('Agent not found or does not belong to company');
        }
        
        $this->auditAccess('configure_voice', [
            'agent_id' => $agent->agent_id,
            'settings' => array_keys($params['voice_settings'])
        ]);
        
        // Update voice configuration
        $voiceConfig = array_merge([
            'voice_id' => $agent->voice_id,
            'voice_speed' => 1.0,
            'voice_temperature' => 0.7,
            'interruption_sensitivity' => 0.5,
            'enable_backchannel' => true,
            'backchannel_frequency' => 0.8,
            'backchannel_words' => ['Ja', 'Okay', 'Verstehe', 'Genau']
        ], $params['voice_settings']);
        
        $response = $this->retellService->updateAgent($agent->agent_id, [
            'voice_id' => $voiceConfig['voice_id'],
            'voice_speed' => $voiceConfig['voice_speed'],
            'voice_temperature' => $voiceConfig['voice_temperature'],
            'interruption_sensitivity' => $voiceConfig['interruption_sensitivity'],
            'enable_backchannel' => $voiceConfig['enable_backchannel'],
            'backchannel_frequency' => $voiceConfig['backchannel_frequency'],
            'backchannel_words' => $voiceConfig['backchannel_words']
        ]);
        
        if (!$response['success']) {
            throw new \Exception('Failed to update voice settings');
        }
        
        // Update local record
        $agent->update([
            'voice_id' => $voiceConfig['voice_id'],
            'config' => json_encode($response['data'])
        ]);
        
        return $this->successResponse([
            'agent_id' => $agent->agent_id,
            'voice_configured' => true
        ]);
    }
    
    /**
     * Generate secure prompt with company context
     */
    protected function generateSecurePrompt(array $params, $branches): string
    {
        $companyName = $this->company->name;
        $branchNames = $branches->pluck('name')->implode(', ');
        
        $prompt = $params['general_prompt'] ?? "Du bist ein freundlicher AI-Assistent für {$companyName}.";
        
        // Add security context
        $prompt .= "\n\nWICHTIG: Du darfst nur Informationen über {$companyName} und deren Filialen ({$branchNames}) geben.";
        $prompt .= "\nDu darfst keine Informationen über andere Unternehmen preisgeben.";
        $prompt .= "\nAlle Termine müssen für {$companyName} gebucht werden.";
        
        return $prompt;
    }
    
    /**
     * Get default greeting message
     */
    protected function getDefaultGreeting(): string
    {
        return "Guten Tag! Sie sind verbunden mit {$this->company->name}. Wie kann ich Ihnen helfen?";
    }
    
    /**
     * Get default voicemail message
     */
    protected function getDefaultVoicemail(): string
    {
        return "Vielen Dank für Ihren Anruf bei {$this->company->name}. Leider sind momentan alle Mitarbeiter im Gespräch. Bitte hinterlassen Sie eine Nachricht mit Ihrem Namen und Ihrer Telefonnummer. Wir rufen Sie schnellstmöglich zurück.";
    }
    
    /**
     * Create phone number for branch
     */
    protected function createPhoneNumberForBranch(Branch $branch, RetellAgent $agent): void
    {
        try {
            // This would integrate with Twilio/Retell to provision a number
            // For now, log the requirement
            Log::info('Phone number needed for branch', [
                'company_id' => $this->company->id,
                'branch_id' => $branch->id,
                'agent_id' => $agent->agent_id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create phone number', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Initialize Retell service with company API key
     */
    protected function initializeRetellService(): void
    {
        if (!$this->company || !$this->company->retell_api_key) {
            $this->retellService = null;
            return;
        }
        
        try {
            $apiKey = decrypt($this->company->retell_api_key);
            $this->retellService = new RetellV2Service($apiKey);
        } catch (\Exception $e) {
            Log::error('Failed to initialize Retell service', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            $this->retellService = null;
        }
    }
    
    /**
     * Resolve company context from authenticated user
     */
    protected function resolveCompanyContext(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user->company_id) {
                $this->company = Company::find($user->company_id);
                $this->initializeRetellService();
            }
        }
    }
    
    /**
     * Ensure company context is set
     */
    protected function ensureCompanyContext(): void
    {
        if (!$this->company) {
            throw new SecurityException('No valid company context for Retell operations');
        }
        
        if (!$this->retellService) {
            throw new SecurityException('Retell service not initialized - check API key configuration');
        }
    }
    
    /**
     * Audit access to Retell operations
     */
    protected function auditAccess(string $operation, array $context = []): void
    {
        if (!$this->auditEnabled) {
            return;
        }
        
        try {
            if (DB::connection()->getSchemaBuilder()->hasTable('security_audit_logs')) {
                DB::table('security_audit_logs')->insert([
                    'event_type' => 'retell_mcp_access',
                    'user_id' => Auth::id(),
                    'company_id' => $this->company->id ?? null,
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'url' => request()->fullUrl() ?? 'console',
                    'metadata' => json_encode(array_merge($context, [
                        'operation' => $operation,
                        'user_agent' => request()->userAgent()
                    ])),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('SecureRetellMCP: Audit logging failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Disable audit logging (for testing)
     */
    public function disableAudit(): self
    {
        $this->auditEnabled = false;
        return $this;
    }
}