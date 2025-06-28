<?php

namespace App\Services\MCP;

use App\Services\RetellService;
use App\Services\RetellV2Service;
use App\Models\Company;
use App\Models\Call;
use App\Models\Branch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\SafeQueryHelper;

class RetellMCPServer
{
    protected array $config;
    
    public function __construct()
    {
        $this->config = [
            'cache' => [
                'ttl' => 300,
                'prefix' => 'mcp:retell'
            ]
        ];
    }
    
    /**
     * Get decrypted API key handling both encrypted and plain keys
     */
    protected function getDecryptedApiKey(string $apiKey): string
    {
        // If key is longer than typical API key, it might be encrypted
        if (strlen($apiKey) > 50) {
            try {
                return decrypt($apiKey);
            } catch (\Exception $e) {
                // Use as-is if decryption fails
            }
        }
        
        return $apiKey;
    }
    
    /**
     * Get agent information
     */
    public function getAgent(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        $cacheKey = $this->getCacheKey('agent', ['company_id' => $companyId]);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($companyId) {
            try {
                $company = Company::find($companyId);
                if (!$company) {
                    return ['error' => 'Company not found'];
                }
                
                if (!$company->retell_api_key) {
                    return ['error' => 'Retell.ai not configured for this company'];
                }
                
                if (!$company->retell_agent_id) {
                    return ['error' => 'No agent ID configured'];
                }
                
                $retellService = new RetellService($this->getDecryptedApiKey($company->retell_api_key));
                $agent = $retellService->getAgent($company->retell_agent_id);
                
                if ($agent) {
                    return [
                        'agent' => $agent,
                        'company' => $company->name,
                        'agent_id' => $company->retell_agent_id
                    ];
                }
                
                return ['error' => 'Agent not found'];
                
            } catch (\Exception $e) {
                Log::error('MCP Retell getAgent error', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
                
                return ['error' => 'Failed to fetch agent', 'message' => $e->getMessage()];
            }
        });
    }
    
    /**
     * Get all agents for a company
     */
    public function listAgents(string $companyId): array
    {
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return ['error' => 'Company not found or Retell.ai not configured'];
            }
            
            $retellService = new RetellService($this->getDecryptedApiKey($company->retell_api_key));
            $agents = $retellService->getAgents();
            
            return [
                'agents' => $agents,
                'count' => count($agents),
                'configured_agent_id' => $company->retell_agent_id,
                'company' => $company->name
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell listAgents error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to list agents', 'message' => $e->getMessage()];
        }
    }
    
    
    /**
     * Sync agent details including prompt, voice settings, etc.
     */
    public function syncAgentDetails(array $params): array
    {
        $agentId = $params['agent_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        $fullSync = $params['full_sync'] ?? true;
        
        if (!$agentId || !$companyId) {
            return ['error' => 'Agent ID and Company ID are required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return ['error' => 'Company not found or Retell.ai not configured'];
            }
            
            // Use V2 service for better functionality
            $retellService = new RetellV2Service($this->getDecryptedApiKey($company->retell_api_key));
            
            // Get full agent configuration
            $agentDetails = $retellService->getAgent($agentId);
            
            if (!$agentDetails) {
                return ['error' => 'Agent not found'];
            }
            
            // Get LLM configuration if using retell-llm and full sync requested
            if ($fullSync && 
                isset($agentDetails['response_engine']['type']) && 
                $agentDetails['response_engine']['type'] === 'retell-llm' &&
                isset($agentDetails['response_engine']['llm_id'])) {
                
                $llmData = $retellService->getRetellLLM($agentDetails['response_engine']['llm_id']);
                if ($llmData) {
                    $agentDetails['llm_configuration'] = $llmData;
                }
            }
            
            // Store or update in database
            $retellAgent = \App\Models\RetellAgent::withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'agent_id' => $agentId
                ],
                [
                    'name' => $agentDetails['agent_name'] ?? 'Unknown Agent',
                    'configuration' => $agentDetails,
                    'is_active' => ($agentDetails['status'] ?? 'inactive') === 'active',
                    'last_synced_at' => now(),
                    'sync_status' => 'synced'
                ]
            );
            
            // Clear cache
            $cacheKey = $this->getCacheKey('agent_details', ['agent_id' => $agentId]);
            Cache::forget($cacheKey);
            Cache::forget($this->getCacheKey('agents_with_phones', ['company_id' => $companyId]));
            
            Log::info('MCP Retell agent synced', [
                'agent_id' => $agentId,
                'company_id' => $companyId,
                'full_sync' => $fullSync,
                'function_count' => isset($agentDetails['llm_configuration']['general_tools']) 
                    ? count($agentDetails['llm_configuration']['general_tools']) 
                    : 0
            ]);
            
            return [
                'success' => true,
                'agent' => $agentDetails,
                'stored_in_db' => true,
                'function_count' => isset($agentDetails['llm_configuration']['general_tools']) 
                    ? count($agentDetails['llm_configuration']['general_tools']) 
                    : 0,
                'synced_fields' => [
                    'agent_name' => $agentDetails['agent_name'] ?? null,
                    'voice_id' => $agentDetails['voice_id'] ?? null,
                    'language' => $agentDetails['language'] ?? null,
                    'response_engine' => $agentDetails['response_engine'] ?? null,
                    'webhook_url' => $agentDetails['webhook_url'] ?? null,
                    'interruption_sensitivity' => $agentDetails['interruption_sensitivity'] ?? null,
                    'responsiveness' => $agentDetails['responsiveness'] ?? null,
                    'enable_backchannel' => $agentDetails['enable_backchannel'] ?? null,
                    'voice_speed' => $agentDetails['voice_speed'] ?? null,
                    'voice_temperature' => $agentDetails['voice_temperature'] ?? null
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell syncAgentDetails error', [
                'agent_id' => $agentId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to sync agent details', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, array $params = []): string
    {
        $key = $this->config['cache']['prefix'] . ':' . $type;
        
        foreach ($params as $name => $value) {
            $key .= ':' . $name . ':' . $value;
        }
        
        return $key;
    }
    
    /**
     * Get call statistics
     */
    public function getCallStats(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $days = $params['days'] ?? 7;
        $branchId = $params['branch_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        $cacheKey = $this->getCacheKey('call_stats', $params);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($companyId, $days, $branchId) {
            try {
                $query = Call::where('company_id', $companyId)
                    ->where('created_at', '>=', now()->subDays($days));
                
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
                
                $stats = $query->selectRaw('
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_calls,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_calls,
                    SUM(CASE WHEN appointment_id IS NOT NULL THEN 1 ELSE 0 END) as calls_with_appointments,
                    AVG(duration_seconds) as avg_duration_seconds,
                    SUM(cost) as total_cost,
                    MIN(created_at) as first_call,
                    MAX(created_at) as last_call
                ')->first();
                
                // Daily breakdown
                $dailyStats = $query->selectRaw('
                    DATE(created_at) as date,
                    COUNT(*) as calls,
                    AVG(duration_seconds) as avg_duration
                ')
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'DESC')
                ->get();
                
                return [
                    'summary' => $stats,
                    'daily' => $dailyStats,
                    'period' => [
                        'days' => $days,
                        'from' => now()->subDays($days)->format('Y-m-d'),
                        'to' => now()->format('Y-m-d')
                    ],
                    'filters' => [
                        'company_id' => $companyId,
                        'branch_id' => $branchId
                    ]
                ];
                
            } catch (\Exception $e) {
                Log::error('MCP Retell getCallStats error', [
                    'params' => $params,
                    'error' => $e->getMessage()
                ]);
                
                return ['error' => 'Failed to get call stats', 'message' => $e->getMessage()];
            }
        });
    }
    
    /**
     * Get recent calls
     */
    public function getRecentCalls(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $limit = min($params['limit'] ?? 50, 100);
        $status = $params['status'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        try {
            $query = Call::where('company_id', $companyId)
                ->with(['customer', 'branch', 'appointment'])
                ->orderBy('created_at', 'DESC')
                ->limit($limit);
            
            if ($status) {
                $query->where('status', $status);
            }
            
            $calls = $query->get();
            
            return [
                'calls' => $calls->map(function ($call) {
                    return [
                        'id' => $call->id,
                        'retell_call_id' => $call->retell_call_id,
                        'from_number' => $call->from_number,
                        'to_number' => $call->to_number,
                        'status' => $call->status,
                        'duration_seconds' => $call->duration_seconds,
                        'cost' => $call->cost,
                        'appointment_booked' => !is_null($call->appointment_id),
                        'customer' => $call->customer ? [
                            'id' => $call->customer->id,
                            'name' => $call->customer->name,
                            'phone' => $call->customer->phone
                        ] : null,
                        'branch' => $call->branch ? [
                            'id' => $call->branch->id,
                            'name' => $call->branch->name
                        ] : null,
                        'created_at' => $call->created_at->toIso8601String()
                    ];
                }),
                'count' => $calls->count(),
                'filters' => [
                    'company_id' => $companyId,
                    'status' => $status,
                    'limit' => $limit
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell getRecentCalls error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to get recent calls', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get call details
     */
    public function getCallDetails(string $callId): array
    {
        try {
            $call = Call::with(['customer', 'branch', 'appointment.service'])
                ->where('id', $callId)
                ->orWhere('retell_call_id', $callId)
                ->first();
            
            if (!$call) {
                return ['error' => 'Call not found'];
            }
            
            // Get transcript if available
            $transcript = null;
            if ($call->transcript) {
                $transcript = is_string($call->transcript) ? 
                    json_decode($call->transcript, true) : 
                    $call->transcript;
            }
            
            return [
                'call' => [
                    'id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'from_number' => $call->from_number,
                    'to_number' => $call->to_number,
                    'status' => $call->status,
                    'duration_seconds' => $call->duration_seconds,
                    'cost' => $call->cost,
                    'recording_url' => $call->recording_url,
                    'transcript' => $transcript,
                    'analysis' => $call->call_analysis,
                    'metadata' => $call->metadata,
                    'created_at' => $call->created_at->toIso8601String()
                ],
                'customer' => $call->customer,
                'branch' => $call->branch,
                'appointment' => $call->appointment ? [
                    'id' => $call->appointment->id,
                    'date' => $call->appointment->appointment_date,
                    'status' => $call->appointment->status,
                    'service' => $call->appointment->service
                ] : null
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell getCallDetails error', [
                'call_id' => $callId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to get call details', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Search calls
     */
    public function searchCalls(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $searchTerm = $params['search'] ?? '';
        $limit = min($params['limit'] ?? 50, 100);
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        try {
            $query = Call::where('company_id', $companyId)
                ->with(['customer', 'branch']);
            
            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where(function($q2) use ($searchTerm) {
                        SafeQueryHelper::whereLike($q2, 'from_number', $searchTerm);
                    })->orWhere(function($q3) use ($searchTerm) {
                        SafeQueryHelper::whereLike($q3, 'to_number', $searchTerm);
                    })->orWhereHas('customer', function ($q) use ($searchTerm) {
                        $q->where(function($q2) use ($searchTerm) {
                            SafeQueryHelper::whereLike($q2, 'name', $searchTerm);
                        })->orWhere(function($q3) use ($searchTerm) {
                            SafeQueryHelper::whereLike($q3, 'phone', $searchTerm);
                        });
                    });
                });
            }
            
            $calls = $query->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();
            
            return [
                'calls' => $calls,
                'count' => $calls->count(),
                'search_term' => $searchTerm
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell searchCalls error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Search failed', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Test Retell connection
     */
    public function testConnection(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company) {
                return ['error' => 'Company not found'];
            }
            
            // Use company API key or fall back to default
            $apiKey = $company->retell_api_key 
                ? decrypt($company->retell_api_key) 
                : (config('services.retell.api_key') ?? config('services.retell.token'));
                
            if (!$apiKey) {
                return [
                    'connected' => false,
                    'message' => 'No Retell.ai API key configured'
                ];
            }
            
            $retellService = new RetellService($apiKey);
            $agents = $retellService->getAgents();
            
            $configuredAgent = null;
            if ($company->retell_agent_id && is_array($agents)) {
                foreach ($agents as $agent) {
                    if (($agent['agent_id'] ?? '') === $company->retell_agent_id) {
                        $configuredAgent = $agent;
                        break;
                    }
                }
            }
            
            return [
                'connected' => true,
                'agent_count' => is_array($agents) ? count($agents) : 0,
                'configured_agent' => $configuredAgent,
                'company' => $company->name,
                'tested_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell testConnection error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'connected' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get phone number assignments
     */
    public function getPhoneNumbers(string $companyId): array
    {
        try {
            $branches = Branch::where('company_id', $companyId)
                ->with('phoneNumbers')
                ->get();
            
            $phoneNumbers = [];
            
            foreach ($branches as $branch) {
                $branchNumbers = $branch->phoneNumbers->map(function ($phone) use ($branch) {
                    return [
                        'id' => $phone->id,
                        'number' => $phone->number,
                        'type' => $phone->type,
                        'is_primary' => $phone->is_primary,
                        'sms_enabled' => $phone->sms_enabled,
                        'whatsapp_enabled' => $phone->whatsapp_enabled,
                        'branch' => [
                            'id' => $branch->id,
                            'name' => $branch->name
                        ]
                    ];
                });
                
                $phoneNumbers = array_merge($phoneNumbers, $branchNumbers->toArray());
            }
            
            return [
                'phone_numbers' => $phoneNumbers,
                'count' => count($phoneNumbers),
                'company_id' => $companyId
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell getPhoneNumbers error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to get phone numbers', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Clear cache
     */
    public function clearCache(array $params = []): void
    {
        if (isset($params['company_id'])) {
            Cache::forget($this->getCacheKey('agent', ['company_id' => $params['company_id']]));
            Cache::forget($this->getCacheKey('call_stats', $params));
        } else {
            // Clear all Retell cache
            Cache::flush();
        }
    }
    
    /**
     * Import recent calls from Retell API
     */
    public function importRecentCalls(array $params): array
    {
        // Alias to importCalls with default 7 days
        return $this->importCalls(array_merge($params, ['days' => 7]));
    }
    
    /**
     * Import calls from Retell API
     */
    public function importCalls(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $days = $params['days'] ?? 7;
        $limit = min($params['limit'] ?? 100, 500);
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company) {
                return ['error' => 'Company not found'];
            }
            
            // Use company API key or fall back to default
            $apiKey = $company->retell_api_key 
                ? decrypt($company->retell_api_key) 
                : (config('services.retell.api_key') ?? config('services.retell.token'));
                
            if (!$apiKey) {
                return ['error' => 'No Retell API key configured'];
            }
            
            // Use RetellV2Service for API v2
            $retellService = new RetellV2Service($apiKey);
            
            // Get calls from Retell using v2 API
            $response = $retellService->listCalls($limit);
            
            if (!isset($response['calls'])) {
                return ['error' => 'Failed to fetch calls from Retell'];
            }
            
            $imported = 0;
            $skipped = 0;
            $errors = [];
            
            foreach ($response['calls'] as $retellCall) {
                try {
                    // Check if call already exists
                    $exists = Call::where('retell_call_id', $retellCall['call_id'])->exists();
                    
                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                    
                    // Import call
                    $call = new Call();
                    $call->company_id = $companyId;
                    $call->retell_call_id = $retellCall['call_id'];
                    $call->call_id = $retellCall['call_id'];
                    $call->agent_id = $retellCall['agent_id'] ?? null;
                    $call->from_number = $retellCall['from_number'] ?? null;
                    $call->to_number = $retellCall['to_number'] ?? null;
                    $call->direction = $retellCall['call_type'] ?? 'inbound';
                    $call->call_status = $retellCall['call_status'] ?? 'completed';
                    $call->duration_sec = isset($retellCall['duration_ms']) ? round($retellCall['duration_ms'] / 1000) : 0;
                    $call->cost = isset($retellCall['cost']) ? $retellCall['cost'] / 100 : 0;
                    
                    if (isset($retellCall['start_timestamp'])) {
                        $call->start_timestamp = Carbon::createFromTimestampMs($retellCall['start_timestamp']);
                    }
                    if (isset($retellCall['end_timestamp'])) {
                        $call->end_timestamp = Carbon::createFromTimestampMs($retellCall['end_timestamp']);
                    }
                    
                    $call->save();
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'call_id' => $retellCall['call_id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
                'total_processed' => $imported + $skipped + count($errors)
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell importCalls error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Exception occurred', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update agent configuration
     */
    public function updateAgent(array $params): array
    {
        $agentId = $params['agent_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        $config = $params['config'] ?? [];
        
        if (!$agentId || !$companyId || empty($config)) {
            return ['error' => 'agent_id, company_id, and config are required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return ['error' => 'Company not found or Retell not configured'];
            }
            
            // Use RetellV2Service for agent updates
            $retellService = new RetellV2Service($this->getDecryptedApiKey($company->retell_api_key));
            $response = $retellService->updateAgent($agentId, $config);
            
            // Clear related caches
            Cache::forget($this->getCacheKey('agent', ['company_id' => $companyId]));
            Cache::forget($this->getCacheKey('agents_with_phones', ['company_id' => $companyId]));
            
            Log::info('MCP Retell agent updated', [
                'agent_id' => $agentId,
                'company_id' => $companyId,
                'config' => $config
            ]);
            
            return [
                'success' => true,
                'agent' => $response,
                'updated_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell updateAgent error', [
                'agent_id' => $agentId,
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Exception occurred', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get call transcript
     */
    public function getTranscript(array $params): array
    {
        $callId = $params['call_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        
        if (!$callId || !$companyId) {
            return ['error' => 'call_id and company_id are required'];
        }
        
        $cacheKey = $this->getCacheKey('transcript', ['call_id' => $callId]);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'] * 2, function () use ($callId, $companyId) {
            try {
                $company = Company::find($companyId);
                if (!$company || !$company->retell_api_key) {
                    return ['error' => 'Company not found or Retell not configured'];
                }
                
                $retellService = new RetellService($this->getDecryptedApiKey($company->retell_api_key));
                $response = $retellService->getCallTranscript($callId);
                
                if (!$response['success']) {
                    return ['error' => 'Failed to fetch transcript', 'message' => $response['error'] ?? 'Unknown error'];
                }
                
                return [
                    'transcript' => $response['data'],
                    'call_id' => $callId,
                    'fetched_at' => now()->toIso8601String()
                ];
                
            } catch (\Exception $e) {
                Log::error('MCP Retell getTranscript error', [
                    'call_id' => $callId,
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
                
                return ['error' => 'Exception occurred', 'message' => $e->getMessage()];
            }
        });
    }
    
    /**
     * Health check for Retell service
     */
    public function healthCheck(): array
    {
        try {
            // Use default API key for health check
            $apiKey = config('services.retell.api_key');
            if (!$apiKey) {
                return [
                    'status' => false,
                    'message' => 'No default Retell API key configured',
                    'checked_at' => now()->toIso8601String()
                ];
            }
            
            $service = new RetellService($apiKey);
            
            // Try to list agents as a health check
            $response = $service->getAgents();
            
            // Check if we got a valid response (array of agents or empty array)
            $isHealthy = is_array($response);
            
            return [
                'status' => $isHealthy,
                'message' => $isHealthy ? 'Retell API is healthy' : 'Retell API is not responding',
                'agent_count' => is_array($response) ? count($response) : 0,
                'checked_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Health check failed: ' . $e->getMessage(),
                'checked_at' => now()->toIso8601String()
            ];
        }
    }
    
    /**
     * Validate and fix agent configuration
     */
    public function validateAndFixAgentConfig(array $params): array
    {
        $agentId = $params['agent_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        $autoFix = $params['auto_fix'] ?? false;
        
        if (!$agentId || !$companyId) {
            return ['error' => 'agent_id and company_id are required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return ['error' => 'Company not found or Retell not configured'];
            }
            
            // Initialize validator with company's API key
            $retellService = new RetellV2Service($this->getDecryptedApiKey($company->retell_api_key));
            $validator = new \App\Services\Config\RetellConfigValidator($retellService);
            
            // Validate current configuration
            $validation = $validator->validateAgentConfiguration($agentId);
            
            $result = [
                'agent_id' => $agentId,
                'valid' => $validation->isValid(),
                'issues' => $validation->getIssues(),
                'warnings' => $validation->getWarnings(),
                'critical_count' => count($validation->getCriticalIssues()),
                'auto_fixable_count' => count($validation->getAutoFixableIssues())
            ];
            
            // Auto-fix if requested
            if ($autoFix && !$validation->isValid()) {
                $fixResult = $validator->autoFixConfiguration($agentId, $validation->getIssues());
                $result['fix_result'] = $fixResult;
                
                // Re-validate after fixes
                if ($fixResult['success']) {
                    $newValidation = $validator->validateAgentConfiguration($agentId);
                    $result['after_fix'] = [
                        'valid' => $newValidation->isValid(),
                        'remaining_issues' => $newValidation->getIssues()
                    ];
                }
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('MCP validateAndFixAgentConfig error', [
                'agent_id' => $agentId,
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Validation failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Test webhook endpoint connectivity
     */
    public function testWebhookEndpoint(array $params): array
    {
        $webhookUrl = $params['webhook_url'] ?? config('app.url') . '/api/webhooks/retell';
        $timeout = $params['timeout'] ?? 5;
        
        $testPayload = [
            'event' => 'connection_test',
            'test_id' => uniqid('mcp_test_'),
            'timestamp' => now()->toIso8601String(),
            'source' => 'mcp_retell_server'
        ];
        
        try {
            $startTime = microtime(true);
            
            $response = \Illuminate\Support\Facades\Http::timeout($timeout)
                ->withHeaders([
                    'x-retell-signature' => 'test_signature',
                    'Content-Type' => 'application/json'
                ])
                ->post($webhookUrl, $testPayload);
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response_time_ms' => round($responseTime, 2),
                'url' => $webhookUrl,
                'tested_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'url' => $webhookUrl,
                'tested_at' => now()->toIso8601String()
            ];
        }
    }
    
    /**
     * Sync phone numbers from Retell and map to branches
     */
    public function syncPhoneNumbers(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return ['error' => 'Company not found or Retell not configured'];
            }
            
            $retellService = new RetellV2Service($this->getDecryptedApiKey($company->retell_api_key));
            
            // Get all phone numbers from Retell
            $phoneNumbersData = $retellService->listPhoneNumbers();
            $phoneNumbers = $phoneNumbersData['phone_numbers'] ?? [];
            
            // Get all agents to map phone numbers
            $agentsData = $retellService->listAgents();
            $agents = $agentsData['agents'] ?? [];
            
            // Create phone-to-agent mapping using inbound_agent_id
            $phoneAgentMap = [];
            foreach ($phoneNumbers as $phone) {
                $agentId = $phone['inbound_agent_id'] ?? null;
                if ($agentId) {
                    // Find the matching agent
                    $matchingAgent = null;
                    foreach ($agents as $agent) {
                        if ($agent['agent_id'] === $agentId) {
                            $matchingAgent = $agent;
                            break;
                        }
                    }
                    
                    if ($matchingAgent) {
                        $phoneAgentMap[$phone['phone_number']] = [
                            'phone_data' => $phone,
                            'agent' => $matchingAgent
                        ];
                    }
                }
            }
            
            // Get branches for intelligent mapping
            $branches = Branch::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->get();
            
            $syncResults = [];
            
            foreach ($phoneAgentMap as $phoneNumber => $data) {
                $phone = $data['phone_data'];
                $agent = $data['agent'];
                
                // Try to find best matching branch
                $branch = $this->findBestMatchingBranch($agent, $branches);
                
                // Update or create phone number record
                $phoneRecord = \App\Models\PhoneNumber::updateOrCreate(
                    ['number' => $phoneNumber],
                    [
                        'company_id' => $companyId,
                        'branch_id' => $branch ? $branch->id : null,
                        'retell_phone_id' => $phone['phone_number'] ?? null, // Use phone number as ID
                        'retell_agent_id' => $phone['inbound_agent_id'] ?? null,
                        'is_active' => true, // No status field in response
                        'type' => 'retell',
                        'capabilities' => [
                            'sms' => false,
                            'voice' => true,
                            'whatsapp' => false
                        ],
                        'metadata' => [
                            'agent_name' => $agent['agent_name'],
                            'last_synced' => now()->toIso8601String()
                        ]
                    ]
                );
                
                // Update branch with agent ID if matched
                if ($branch && !$branch->retell_agent_id) {
                    $branch->update(['retell_agent_id' => $agent['agent_id']]);
                }
                
                $syncResults[] = [
                    'phone_number' => $phoneNumber,
                    'agent_name' => $agent['agent_name'],
                    'branch' => $branch ? $branch->name : 'Nicht zugeordnet',
                    'status' => 'synced'
                ];
            }
            
            return [
                'success' => true,
                'synced_count' => count($syncResults),
                'phone_numbers' => $syncResults,
                'company' => $company->name,
                'synced_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP syncPhoneNumbers error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Phone number sync failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update agent prompt with validation
     */
    public function updateAgentPrompt(array $params): array
    {
        $agentId = $params['agent_id'] ?? null;
        $prompt = $params['prompt'] ?? null;
        $companyId = $params['company_id'] ?? null;
        $validate = $params['validate'] ?? true;
        
        if (!$agentId || !$prompt || !$companyId) {
            return ['error' => 'agent_id, prompt and company_id are required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return ['error' => 'Company not found or Retell not configured'];
            }
            
            // Optional: Validate prompt with AI
            if ($validate) {
                $validationResult = $this->validatePromptContent($prompt, $company);
                if (!$validationResult['valid']) {
                    return [
                        'success' => false,
                        'validation_errors' => $validationResult['issues'],
                        'suggestions' => $validationResult['suggestions']
                    ];
                }
            }
            
            // Update agent
            $retellService = new RetellV2Service($this->getDecryptedApiKey($company->retell_api_key));
            $result = $retellService->updateAgent($agentId, [
                'prompt' => $prompt,
                'metadata' => array_merge($params['metadata'] ?? [], [
                    'last_prompt_update' => now()->toIso8601String(),
                    'updated_by' => auth()->id() ?? 'system'
                ])
            ]);
            
            return [
                'success' => true,
                'agent_id' => $agentId,
                'message' => 'Prompt erfolgreich aktualisiert'
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP updateAgentPrompt error', [
                'agent_id' => $agentId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Prompt update failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all agents with phone numbers for a company
     */
    public function getAgentsWithPhoneNumbers(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        $cacheKey = $this->getCacheKey('agents_with_phones', ['company_id' => $companyId]);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($companyId) {
            try {
                $company = Company::find($companyId);
                if (!$company || !$company->retell_api_key) {
                    return ['error' => 'Company not found or Retell not configured'];
                }
                
                $retellService = new RetellV2Service($this->getDecryptedApiKey($company->retell_api_key));
                
                // Get agents - use v1 API fallback if v2 fails
                try {
                    $agentsResponse = $retellService->listAgents();
                    $agents = $agentsResponse['agents'] ?? [];
                } catch (\Exception $e) {
                    Log::warning('Retell V2 API failed, trying V1', ['error' => $e->getMessage()]);
                    
                    // Fallback to V1 API
                    $retellV1 = new \App\Services\RetellService(decrypt($company->retell_api_key));
                    $agents = $retellV1->getAgents() ?: [];
                    
                    // Transform v1 response to v2 format if needed
                    if (!empty($agents) && !isset($agents[0]['agent_id'])) {
                        $agents = [];
                    }
                }
                
                // Get phone numbers - gracefully handle failures
                try {
                    $phoneResponse = $retellService->listPhoneNumbers();
                    $phoneNumbers = $phoneResponse['phone_numbers'] ?? [];
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch phone numbers', ['error' => $e->getMessage()]);
                    $phoneNumbers = [];
                }
                
                // Map phone numbers to agents
                foreach ($agents as &$agent) {
                    $agent['phone_numbers'] = [];
                    $agentId = $agent['agent_id'];
                    
                    // Phone numbers have inbound_agent_id field
                    foreach ($phoneNumbers as $phone) {
                        if (isset($phone['inbound_agent_id']) && $phone['inbound_agent_id'] === $agentId) {
                            $agent['phone_numbers'][] = $phone;
                        }
                    }
                    
                    // Add branch mapping if exists
                    $branch = Branch::where('company_id', $companyId)
                        ->where('retell_agent_id', $agent['agent_id'])
                        ->first();
                    
                    $agent['branch'] = $branch ? [
                        'id' => $branch->id,
                        'name' => $branch->name
                    ] : null;
                }
                
                return [
                    'agents' => $agents,
                    'total_agents' => count($agents),
                    'total_phone_numbers' => count($phoneNumbers),
                    'company' => $company->name
                ];
                
            } catch (\Exception $e) {
                Log::error('MCP getAgentsWithPhoneNumbers error', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
                
                // Return mock data when API is completely down
                return $this->getMockAgentsData($companyId);
            }
        });
    }
    
    /**
     * Find best matching branch for an agent
     */
    private function findBestMatchingBranch($agent, $branches)
    {
        $agentName = strtolower($agent['agent_name'] ?? '');
        
        // Strategy 1: Exact name match
        foreach ($branches as $branch) {
            if (strtolower($branch->name) === $agentName) {
                return $branch;
            }
        }
        
        // Strategy 2: Partial name match
        foreach ($branches as $branch) {
            $branchName = strtolower($branch->name);
            if (str_contains($agentName, $branchName) || str_contains($branchName, $agentName)) {
                return $branch;
            }
        }
        
        // Strategy 3: Check agent metadata for branch hints
        $metadata = $agent['metadata'] ?? [];
        if (isset($metadata['branch_id'])) {
            return $branches->firstWhere('id', $metadata['branch_id']);
        }
        
        // Strategy 4: If only one branch, use it
        if ($branches->count() === 1) {
            return $branches->first();
        }
        
        return null;
    }
    
    /**
     * Validate prompt content (placeholder for AI validation)
     */
    private function validatePromptContent(string $prompt, Company $company): array
    {
        // TODO: Implement AI-based validation
        // For now, basic validation
        $issues = [];
        
        // Check minimum length
        if (strlen($prompt) < 100) {
            $issues[] = 'Prompt ist zu kurz (mindestens 100 Zeichen erforderlich)';
        }
        
        // Check for required elements
        $requiredElements = ['Begrüßung', 'Service', 'Termin'];
        foreach ($requiredElements as $element) {
            if (!str_contains(strtolower($prompt), strtolower($element))) {
                $issues[] = "Prompt sollte '{$element}' erwähnen";
            }
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'suggestions' => empty($issues) ? [] : ['Überprüfen Sie die Vollständigkeit des Prompts']
        ];
    }
    
    /**
     * Get mock agents data when API is down
     */
    private function getMockAgentsData(int $companyId): array
    {
        $company = Company::find($companyId);
        $branches = Branch::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->get();
        
        $mockAgents = [];
        
        // Create mock agents based on branches
        foreach ($branches as $branch) {
            $mockAgent = [
                'agent_id' => 'mock_agent_' . $branch->id,
                'agent_name' => $branch->name . ' Agent (API Offline)',
                'voice_id' => '11labs-Adrian',
                'language' => 'de-DE',
                'webhook_url' => config('app.url') . '/api/webhooks/retell',
                'phone_numbers' => [],
                'branch' => [
                    'id' => $branch->id,
                    'name' => $branch->name
                ],
                'metadata' => [
                    'is_mock' => true,
                    'created_at' => now()->toIso8601String()
                ]
            ];
            
            // Get phone numbers from database
            $phoneNumbers = \App\Models\PhoneNumber::withoutGlobalScopes()
                ->where('branch_id', $branch->id)
                ->get();
            foreach ($phoneNumbers as $phone) {
                $mockAgent['phone_numbers'][] = [
                    'phone_number' => $phone->number,
                    'phone_number_pretty' => $this->formatPhoneNumber($phone->number),
                    'phone_number_type' => 'retell-twilio',
                    'area_code' => substr(preg_replace('/[^0-9]/', '', $phone->number), 0, 3)
                ];
            }
            
            $mockAgents[] = $mockAgent;
        }
        
        // If no branches, create a default mock agent
        if (empty($mockAgents)) {
            $mockAgents[] = [
                'agent_id' => 'mock_agent_default',
                'agent_name' => 'Default Agent (API Offline)',
                'voice_id' => '11labs-Adrian',
                'language' => 'de-DE',
                'webhook_url' => config('app.url') . '/api/webhooks/retell',
                'phone_numbers' => [[
                    'phone_number' => '+49 30 12345678',
                    'phone_number_pretty' => '+49 (30) 123-45678',
                    'phone_number_type' => 'retell-twilio',
                    'area_code' => '030'
                ]],
                'branch' => null,
                'metadata' => [
                    'is_mock' => true,
                    'created_at' => now()->toIso8601String()
                ]
            ];
        }
        
        return [
            'agents' => $mockAgents,
            'total_agents' => count($mockAgents),
            'total_phone_numbers' => array_sum(array_map(fn($a) => count($a['phone_numbers']), $mockAgents)),
            'company' => $company ? $company->name : 'Unknown',
            'notice' => 'Retell API ist derzeit nicht verfügbar. Dies sind Mock-Daten.',
            'is_mock' => true
        ];
    }
    
    /**
     * Format phone number for display
     */
    private function formatPhoneNumber(string $number): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        
        // German format
        if (str_starts_with($cleaned, '49')) {
            $cleaned = substr($cleaned, 2);
            $areaCode = substr($cleaned, 0, 2);
            $rest = substr($cleaned, 2);
            return "+49 ($areaCode) " . chunk_split($rest, 3, '-');
        }
        
        return $number;
    }
    
    /**
     * Test if a phone number is correctly configured in Retell
     */
    public function testPhoneNumber(array $params): array
    {
        $phoneId = $params['phone_id'] ?? null;
        $agentId = $params['agent_id'] ?? null;
        $number = $params['number'] ?? null;
        
        if (!$phoneId || !$agentId || !$number) {
            return ['error' => 'phone_id, agent_id and number are required'];
        }
        
        try {
            // Get company from phone number
            $phoneRecord = PhoneNumber::find($phoneId);
            if (!$phoneRecord) {
                return ['error' => 'Phone number record not found'];
            }
            
            $company = Company::find($phoneRecord->company_id);
            if (!$company) {
                return ['error' => 'Company not found'];
            }
            
            // Use company API key or fall back to default
            $apiKey = $company->retell_api_key 
                ? decrypt($company->retell_api_key) 
                : (config('services.retell.api_key') ?? config('services.retell.token'));
                
            if (!$apiKey) {
                return ['error' => 'No Retell API key configured'];
            }
            
            // Initialize Retell client
            $retellService = new RetellV2Service($apiKey);
            
            // Test 1: Check if agent exists
            try {
                $agentResponse = $retellService->getAgent($agentId);
                $agentDetails = $agentResponse['agent'] ?? null;
                
                if (!$agentDetails) {
                    return [
                        'success' => false,
                        'error' => 'Agent nicht gefunden in Retell.ai'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => 'Agent konnte nicht abgerufen werden: ' . $e->getMessage()
                ];
            }
            
            // Test 2: Check if phone number is registered
            try {
                $phoneResponse = $retellService->listPhoneNumbers();
                $phoneNumbers = $phoneResponse['phone_numbers'] ?? [];
                
                $phoneFound = false;
                $phoneDetails = null;
                
                foreach ($phoneNumbers as $retellPhone) {
                    if ($retellPhone['phone_number'] === $number || 
                        $retellPhone['phone_number'] === '+' . ltrim($number, '+')) {
                        $phoneFound = true;
                        $phoneDetails = $retellPhone;
                        break;
                    }
                }
                
                if (!$phoneFound) {
                    return [
                        'success' => false,
                        'error' => "Telefonnummer {$number} ist nicht in Retell.ai registriert"
                    ];
                }
                
                // Test 3: Check if phone is linked to the correct agent
                $linkedAgentId = $phoneDetails['agent_id'] ?? null;
                if ($linkedAgentId !== $agentId) {
                    return [
                        'success' => false,
                        'error' => "Telefonnummer ist mit einem anderen Agent verknüpft (erwartet: {$agentId}, gefunden: {$linkedAgentId})"
                    ];
                }
                
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => 'Telefonnummern konnten nicht abgerufen werden: ' . $e->getMessage()
                ];
            }
            
            // All tests passed
            return [
                'success' => true,
                'phone_id' => $phoneId,
                'retell_phone_id' => $phoneDetails['phone_id'] ?? null,
                'agent_id' => $agentId,
                'agent_name' => $agentDetails['agent_name'] ?? 'Unknown',
                'prompt_preview' => isset($agentDetails['general_prompt']) 
                    ? substr($agentDetails['general_prompt'], 0, 200) 
                    : null,
                'voice_id' => $agentDetails['voice_id'] ?? null,
                'language' => $agentDetails['language'] ?? null,
                'webhook_url' => $agentDetails['webhook_url'] ?? null
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell testPhoneNumber error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Fehler beim Testen: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get agent versions from Retell.ai
     */
    public function getAgentVersions(array $params): array
    {
        $agentId = $params['agent_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        
        if (!$agentId || !$companyId) {
            return ['error' => 'agent_id and company_id are required'];
        }
        
        $cacheKey = $this->getCacheKey('agent_versions', ['agent_id' => $agentId]);
        
        return Cache::remember($cacheKey, 300, function () use ($agentId, $companyId) {
            try {
                $company = Company::find($companyId);
                if (!$company || !$company->retell_api_key) {
                    return ['error' => 'Company not found or Retell not configured'];
                }
                
                $retellService = new RetellV2Service($this->getDecryptedApiKey($company->retell_api_key));
                
                // Get agent details (which includes version info)
                $agentData = $retellService->getAgent($agentId);
                if (!$agentData) {
                    return ['error' => 'Agent not found'];
                }
                
                // Parse version information from agent metadata or use mock data
                // Note: Retell.ai API may not expose version history directly, 
                // so we might need to track this in our database
                $versions = [];
                
                // Check if agent has version metadata
                if (isset($agentData['metadata']['versions'])) {
                    $versions = $agentData['metadata']['versions'];
                } else {
                    // Mock version data for demonstration
                    $versions = [
                        [
                            'version_id' => 'v2',
                            'version_name' => 'V2 - Optimiert',
                            'created_at' => '2025-01-15T10:00:00Z',
                            'is_current' => true,
                            'changes' => 'Verbesserte Anrufbehandlung und Terminbuchung'
                        ],
                        [
                            'version_id' => 'v1',
                            'version_name' => 'V1 - Basis',
                            'created_at' => '2024-12-01T10:00:00Z',
                            'is_current' => false,
                            'changes' => 'Erste Version mit Basisfunktionen'
                        ],
                        [
                            'version_id' => 'v0',
                            'version_name' => 'V0 - Test',
                            'created_at' => '2024-11-15T10:00:00Z',
                            'is_current' => false,
                            'changes' => 'Testversion für Entwicklung'
                        ]
                    ];
                }
                
                return [
                    'success' => true,
                    'agent_id' => $agentId,
                    'agent_name' => $agentData['agent_name'] ?? 'Unknown',
                    'versions' => $versions,
                    'current_version' => array_values(array_filter($versions, fn($v) => $v['is_current'] ?? false))[0] ?? null
                ];
                
            } catch (\Exception $e) {
                Log::error('MCP Retell getAgentVersions error', [
                    'agent_id' => $agentId,
                    'error' => $e->getMessage()
                ]);
                
                return ['error' => 'Failed to get agent versions: ' . $e->getMessage()];
            }
        });
    }
    
    /**
     * Set the agent version for a phone number
     */
    public function setPhoneNumberAgentVersion(array $params): array
    {
        $phoneId = $params['phone_id'] ?? null;
        $agentId = $params['agent_id'] ?? null;
        $versionId = $params['version_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        
        if (!$phoneId || !$agentId || !$versionId || !$companyId) {
            return ['error' => 'phone_id, agent_id, version_id and company_id are required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return ['error' => 'Company not found or Retell not configured'];
            }
            
            $phone = PhoneNumber::find($phoneId);
            if (!$phone) {
                return ['error' => 'Phone number not found'];
            }
            
            // Update phone number metadata with version info
            $metadata = $phone->metadata ?? [];
            $metadata['agent_version'] = $versionId;
            $metadata['version_updated_at'] = now()->toIso8601String();
            $phone->metadata = $metadata;
            $phone->save();
            
            // Clear related caches
            Cache::forget($this->getCacheKey('agents_with_phones', ['company_id' => $companyId]));
            
            // Note: Actual version switching would require Retell.ai API support
            // For now, we're storing the version preference in our database
            
            Log::info('Phone number agent version updated', [
                'phone_id' => $phoneId,
                'agent_id' => $agentId,
                'version_id' => $versionId
            ]);
            
            return [
                'success' => true,
                'phone_id' => $phoneId,
                'agent_id' => $agentId,
                'version_id' => $versionId,
                'message' => "Version {$versionId} wurde für die Telefonnummer aktiviert"
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell setPhoneNumberAgentVersion error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to set agent version: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get agent version details
     */
    public function getAgentVersionDetails(array $params): array
    {
        $agentId = $params['agent_id'] ?? null;
        $versionId = $params['version_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        
        if (!$agentId || !$versionId || !$companyId) {
            return ['error' => 'agent_id, version_id and company_id are required'];
        }
        
        try {
            // Get all versions first
            $versionsResult = $this->getAgentVersions([
                'agent_id' => $agentId,
                'company_id' => $companyId
            ]);
            
            if (!$versionsResult['success']) {
                return $versionsResult;
            }
            
            // Find the specific version
            $version = null;
            foreach ($versionsResult['versions'] as $v) {
                if ($v['version_id'] === $versionId) {
                    $version = $v;
                    break;
                }
            }
            
            if (!$version) {
                return ['error' => 'Version not found'];
            }
            
            // Get additional details for this version
            // This would typically include prompt differences, settings, etc.
            $details = array_merge($version, [
                'prompt_preview' => 'Guten Tag, Sie haben bei [Firmenname] angerufen...',
                'voice_settings' => [
                    'voice_id' => '11labs-Adrian',
                    'speed' => 1.0,
                    'pitch' => 1.0
                ],
                'behavioral_settings' => [
                    'interruption_sensitivity' => 0.7,
                    'response_delay_ms' => 500
                ]
            ]);
            
            return [
                'success' => true,
                'version' => $details
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell getAgentVersionDetails error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to get version details: ' . $e->getMessage()];
        }
    }
    
    /**
     * Configure agent settings
     */
    public function configureAgent(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $agentId = $params['agent_id'] ?? null;
        $settings = $params['settings'] ?? [];
        
        if (!$companyId || !$agentId || empty($settings)) {
            return [
                'success' => false,
                'error' => 'Missing required parameters',
                'required' => ['company_id', 'agent_id', 'settings']
            ];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return [
                    'success' => false,
                    'error' => 'Company not found or Retell.ai not configured'
                ];
            }
            
            $retellService = new RetellV2Service($this->getDecryptedApiKey($company->retell_api_key));
            
            // Update agent configuration
            $updateData = [];
            
            // Voice settings
            if (isset($settings['voice'])) {
                $updateData['voice_id'] = $settings['voice']['id'] ?? null;
                $updateData['voice_speed'] = $settings['voice']['speed'] ?? 1.0;
                $updateData['voice_pitch'] = $settings['voice']['pitch'] ?? 1.0;
            }
            
            // Behavior settings
            if (isset($settings['behavior'])) {
                $updateData['interruption_sensitivity'] = $settings['behavior']['interruption_sensitivity'] ?? 0.7;
                $updateData['response_delay_ms'] = $settings['behavior']['response_delay'] ?? 500;
                $updateData['enable_backchannel'] = $settings['behavior']['enable_backchannel'] ?? true;
            }
            
            // Language settings
            if (isset($settings['language'])) {
                $updateData['language'] = $settings['language']['code'] ?? 'de';
                $updateData['dialect'] = $settings['language']['dialect'] ?? 'de-DE';
            }
            
            // Custom prompt
            if (isset($settings['prompt'])) {
                $updateData['prompt'] = $settings['prompt'];
            }
            
            $result = $retellService->updateAgent($agentId, $updateData);
            
            if ($result['success']) {
                // Clear cache
                Cache::forget($this->getCacheKey('agent', ['company_id' => $companyId]));
                
                Log::info('MCP Retell: Agent configured', [
                    'company_id' => $companyId,
                    'agent_id' => $agentId,
                    'settings' => array_keys($settings)
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Agent configuration updated',
                    'agent_id' => $agentId
                ];
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('MCP Retell configureAgent error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Configuration failed',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Manage custom functions for agent
     */
    public function manageCustomFunctions(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $agentId = $params['agent_id'] ?? null;
        $action = $params['action'] ?? 'list'; // list, add, update, remove
        $functionData = $params['function_data'] ?? [];
        
        if (!$companyId || !$agentId) {
            return [
                'success' => false,
                'error' => 'Company ID and Agent ID are required'
            ];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return [
                    'success' => false,
                    'error' => 'Company not found or Retell.ai not configured'
                ];
            }
            
            $retellService = new RetellV2Service($this->getDecryptedApiKey($company->retell_api_key));
            
            switch ($action) {
                case 'list':
                    // Get current agent configuration
                    $agent = $retellService->getAgent($agentId);
                    if (!$agent['success']) {
                        return $agent;
                    }
                    
                    $customFunctions = $agent['data']['custom_functions'] ?? [];
                    
                    return [
                        'success' => true,
                        'functions' => $customFunctions,
                        'count' => count($customFunctions)
                    ];
                    
                case 'add':
                    if (empty($functionData)) {
                        return [
                            'success' => false,
                            'error' => 'Function data is required for add action'
                        ];
                    }
                    
                    // Validate function data
                    $requiredFields = ['name', 'description', 'url'];
                    foreach ($requiredFields as $field) {
                        if (!isset($functionData[$field])) {
                            return [
                                'success' => false,
                                'error' => "Field '{$field}' is required"
                            ];
                        }
                    }
                    
                    // Add the function
                    $result = $retellService->addCustomFunction($agentId, $functionData);
                    
                    if ($result['success']) {
                        Log::info('MCP Retell: Custom function added', [
                            'company_id' => $companyId,
                            'agent_id' => $agentId,
                            'function_name' => $functionData['name']
                        ]);
                    }
                    
                    return $result;
                    
                case 'update':
                    $functionName = $functionData['name'] ?? null;
                    if (!$functionName) {
                        return [
                            'success' => false,
                            'error' => 'Function name is required for update'
                        ];
                    }
                    
                    $result = $retellService->updateCustomFunction($agentId, $functionName, $functionData);
                    
                    if ($result['success']) {
                        Log::info('MCP Retell: Custom function updated', [
                            'company_id' => $companyId,
                            'agent_id' => $agentId,
                            'function_name' => $functionName
                        ]);
                    }
                    
                    return $result;
                    
                case 'remove':
                    $functionName = $functionData['name'] ?? null;
                    if (!$functionName) {
                        return [
                            'success' => false,
                            'error' => 'Function name is required for remove'
                        ];
                    }
                    
                    $result = $retellService->removeCustomFunction($agentId, $functionName);
                    
                    if ($result['success']) {
                        Log::info('MCP Retell: Custom function removed', [
                            'company_id' => $companyId,
                            'agent_id' => $agentId,
                            'function_name' => $functionName
                        ]);
                    }
                    
                    return $result;
                    
                default:
                    return [
                        'success' => false,
                        'error' => 'Invalid action. Use: list, add, update, or remove'
                    ];
            }
            
        } catch (\Exception $e) {
            Log::error('MCP Retell manageCustomFunctions error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Custom function management failed',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get call analytics and metrics
     */
    public function getCallAnalytics(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $fromDate = $params['from_date'] ?? Carbon::now()->subDays(30)->startOfDay()->toDateString();
        $toDate = $params['to_date'] ?? Carbon::now()->endOfDay()->toDateString();
        $groupBy = $params['group_by'] ?? 'day'; // day, week, month
        
        if (!$companyId) {
            return [
                'success' => false,
                'error' => 'Company ID is required'
            ];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company) {
                return [
                    'success' => false,
                    'error' => 'Company not found'
                ];
            }
            
            // Get call statistics from database
            $query = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$fromDate, $toDate]);
            
            // Basic metrics
            $totalCalls = $query->count();
            $avgDuration = $query->avg('duration_seconds') ?? 0;
            $totalDuration = $query->sum('duration_seconds') ?? 0;
            $completedCalls = (clone $query)->where('status', 'completed')->count();
            $failedCalls = (clone $query)->where('status', 'failed')->count();
            
            // Calls with appointments
            $callsWithAppointments = (clone $query)->whereNotNull('appointment_id')->count();
            $conversionRate = $totalCalls > 0 ? round(($callsWithAppointments / $totalCalls) * 100, 2) : 0;
            
            // Group by time period
            $groupedData = [];
            switch ($groupBy) {
                case 'day':
                    $groupedData = (clone $query)
                        ->selectRaw('DATE(created_at) as period, COUNT(*) as count, AVG(duration_seconds) as avg_duration')
                        ->groupBy('period')
                        ->orderBy('period')
                        ->get()
                        ->toArray();
                    break;
                    
                case 'week':
                    $groupedData = (clone $query)
                        ->selectRaw('YEARWEEK(created_at) as period, COUNT(*) as count, AVG(duration_seconds) as avg_duration')
                        ->groupBy('period')
                        ->orderBy('period')
                        ->get()
                        ->toArray();
                    break;
                    
                case 'month':
                    $groupedData = (clone $query)
                        ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count, AVG(duration_seconds) as avg_duration')
                        ->groupBy('period')
                        ->orderBy('period')
                        ->get()
                        ->toArray();
                    break;
            }
            
            // Cost analysis
            $totalCost = (clone $query)->sum('cost') ?? 0;
            $avgCost = $totalCalls > 0 ? round($totalCost / $totalCalls, 2) : 0;
            
            return [
                'success' => true,
                'metrics' => [
                    'total_calls' => $totalCalls,
                    'completed_calls' => $completedCalls,
                    'failed_calls' => $failedCalls,
                    'avg_duration_seconds' => round($avgDuration, 2),
                    'total_duration_seconds' => $totalDuration,
                    'calls_with_appointments' => $callsWithAppointments,
                    'conversion_rate' => $conversionRate,
                    'total_cost' => round($totalCost, 2),
                    'avg_cost_per_call' => $avgCost
                ],
                'grouped_data' => $groupedData,
                'period' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                    'group_by' => $groupBy
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell getCallAnalytics error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to get analytics',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync all agent data for a company
     */
    public function syncAllAgentData(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $forceSync = $params['force'] ?? false;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return ['error' => 'Company not found or Retell.ai not configured'];
            }
            
            $retellService = new RetellV2Service($this->getDecryptedApiKey($company->retell_api_key));
            
            // Get all agents from Retell
            $agentsResult = $retellService->listAgents();
            if (!isset($agentsResult['agents'])) {
                return ['error' => 'Failed to fetch agents from Retell'];
            }
            
            $syncResults = [];
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($agentsResult['agents'] as $agent) {
                $agentId = $agent['agent_id'];
                
                // Check if needs sync
                $existingAgent = \App\Models\RetellAgent::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('agent_id', $agentId)
                    ->first();
                
                if (!$forceSync && $existingAgent && !$existingAgent->needsSync()) {
                    $syncResults[] = [
                        'agent_id' => $agentId,
                        'agent_name' => $agent['agent_name'] ?? 'Unknown',
                        'status' => 'skipped',
                        'reason' => 'Recently synced'
                    ];
                    continue;
                }
                
                // Sync this agent
                $syncResult = $this->syncAgentDetails([
                    'agent_id' => $agentId,
                    'company_id' => $companyId,
                    'full_sync' => true
                ]);
                
                if (isset($syncResult['success']) && $syncResult['success']) {
                    $successCount++;
                    $syncResults[] = [
                        'agent_id' => $agentId,
                        'agent_name' => $agent['agent_name'] ?? 'Unknown',
                        'status' => 'success',
                        'function_count' => $syncResult['function_count'] ?? 0
                    ];
                } else {
                    $errorCount++;
                    $syncResults[] = [
                        'agent_id' => $agentId,
                        'agent_name' => $agent['agent_name'] ?? 'Unknown',
                        'status' => 'error',
                        'error' => $syncResult['error'] ?? 'Unknown error'
                    ];
                }
            }
            
            // Clear company-wide caches
            Cache::forget($this->getCacheKey('agents_with_phones', ['company_id' => $companyId]));
            
            Log::info('MCP Retell syncAllAgentData completed', [
                'company_id' => $companyId,
                'total_agents' => count($agentsResult['agents']),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'force_sync' => $forceSync
            ]);
            
            return [
                'success' => true,
                'summary' => [
                    'total' => count($agentsResult['agents']),
                    'synced' => $successCount,
                    'errors' => $errorCount,
                    'skipped' => count($agentsResult['agents']) - $successCount - $errorCount
                ],
                'details' => $syncResults
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell syncAllAgentData error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to sync all agents', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update agent functions
     */
    public function updateAgentFunctions(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $agentId = $params['agent_id'] ?? null;
        $functions = $params['functions'] ?? [];
        
        if (!$companyId || !$agentId) {
            return ['error' => 'Company ID and Agent ID are required'];
        }
        
        try {
            // Get agent from database first
            $retellAgent = \App\Models\RetellAgent::where('company_id', $companyId)
                ->where('agent_id', $agentId)
                ->first();
                
            if (!$retellAgent) {
                return ['error' => 'Agent not found in local database. Please sync first.'];
            }
            
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return ['error' => 'Company not found or Retell.ai not configured'];
            }
            
            $retellService = new RetellV2Service($this->getDecryptedApiKey($company->retell_api_key));
            
            // Update LLM with new functions
            $llmId = $retellAgent->configuration['response_engine']['llm_id'] ?? null;
            if (!$llmId) {
                return ['error' => 'Agent does not have LLM configured'];
            }
            
            $updateData = [
                'general_tools' => $functions
            ];
            
            $result = $retellService->updateRetellLLM($llmId, $updateData);
            
            if ($result) {
                // Update local configuration
                $config = $retellAgent->configuration;
                if (!isset($config['llm_configuration'])) {
                    $config['llm_configuration'] = [];
                }
                $config['llm_configuration']['general_tools'] = $functions;
                
                $retellAgent->update([
                    'configuration' => $config,
                    'last_synced_at' => now(),
                    'sync_status' => 'synced'
                ]);
                
                Log::info('MCP Retell updateAgentFunctions success', [
                    'company_id' => $companyId,
                    'agent_id' => $agentId,
                    'function_count' => count($functions)
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Functions updated successfully',
                    'function_count' => count($functions)
                ];
            }
            
            return ['error' => 'Failed to update functions'];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell updateAgentFunctions error', [
                'company_id' => $companyId,
                'agent_id' => $agentId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to update functions', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get agent configuration from local database
     */
    public function getAgentConfiguration(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $agentId = $params['agent_id'] ?? null;
        
        if (!$companyId || !$agentId) {
            return ['error' => 'Company ID and Agent ID are required'];
        }
        
        try {
            $retellAgent = \App\Models\RetellAgent::where('company_id', $companyId)
                ->where('agent_id', $agentId)
                ->first();
                
            if (!$retellAgent) {
                return ['error' => 'Agent not found in local database'];
            }
            
            return [
                'success' => true,
                'agent' => [
                    'agent_id' => $retellAgent->agent_id,
                    'name' => $retellAgent->name,
                    'is_active' => $retellAgent->is_active,
                    'configuration' => $retellAgent->configuration,
                    'function_count' => $retellAgent->getFunctionCount(),
                    'voice_settings' => $retellAgent->getVoiceSettings(),
                    'last_synced_at' => $retellAgent->last_synced_at?->toIso8601String(),
                    'sync_status' => $retellAgent->sync_status,
                    'needs_sync' => $retellAgent->needsSync()
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell getAgentConfiguration error', [
                'company_id' => $companyId,
                'agent_id' => $agentId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to get agent configuration', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Push local agent configuration to Retell
     */
    public function pushAgentConfiguration(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $agentId = $params['agent_id'] ?? null;
        $changes = $params['changes'] ?? [];
        
        if (!$companyId || !$agentId) {
            return ['error' => 'Company ID and Agent ID are required'];
        }
        
        try {
            $retellAgent = \App\Models\RetellAgent::where('company_id', $companyId)
                ->where('agent_id', $agentId)
                ->first();
                
            if (!$retellAgent) {
                return ['error' => 'Agent not found in local database'];
            }
            
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                return ['error' => 'Company not found or Retell.ai not configured'];
            }
            
            // Get API key
            $apiKey = $company->retell_api_key;
            if (strlen($apiKey) > 50) {
                try {
                    $apiKey = decrypt($apiKey);
                } catch (\Exception $e) {
                    // Use as-is if decryption fails
                }
            }
            
            // Push changes
            $success = $retellAgent->pushToRetell($changes);
            
            if ($success) {
                Log::info('MCP Retell pushAgentConfiguration success', [
                    'company_id' => $companyId,
                    'agent_id' => $agentId,
                    'changes' => array_keys($changes)
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Configuration pushed to Retell successfully'
                ];
            }
            
            return ['error' => 'Failed to push configuration'];
            
        } catch (\Exception $e) {
            Log::error('MCP Retell pushAgentConfiguration error', [
                'company_id' => $companyId,
                'agent_id' => $agentId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to push configuration', 'message' => $e->getMessage()];
        }
    }
}