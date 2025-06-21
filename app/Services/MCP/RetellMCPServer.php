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
                
                $retellService = new RetellService(decrypt($company->retell_api_key));
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
            
            $retellService = new RetellService(decrypt($company->retell_api_key));
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
                    $q->where('from_number', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('to_number', 'LIKE', "%{$searchTerm}%")
                      ->orWhereHas('customer', function ($q) use ($searchTerm) {
                          $q->where('name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
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
     * Generate cache key
     */
    protected function getCacheKey(string $type, array $params = []): string
    {
        $prefix = $this->config['cache']['prefix'];
        $key = "{$prefix}:{$type}";
        
        if (!empty($params)) {
            $key .= ':' . md5(json_encode($params));
        }
        
        return $key;
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
            
            $retellService = new RetellService(decrypt($company->retell_api_key));
            $response = $retellService->updateAgent($agentId, $config);
            
            if (!$response['success']) {
                return ['error' => 'Failed to update agent', 'message' => $response['error'] ?? 'Unknown error'];
            }
            
            // Clear related caches
            Cache::forget($this->getCacheKey('agent', ['company_id' => $companyId]));
            
            Log::info('MCP Retell agent updated', [
                'agent_id' => $agentId,
                'company_id' => $companyId
            ]);
            
            return [
                'agent' => $response['data'],
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
                
                $retellService = new RetellService(decrypt($company->retell_api_key));
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
            $retellService = new RetellV2Service(decrypt($company->retell_api_key));
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
            
            $retellService = new RetellV2Service(decrypt($company->retell_api_key));
            
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
            $retellService = new RetellV2Service(decrypt($company->retell_api_key));
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
                
                $retellService = new RetellV2Service(decrypt($company->retell_api_key));
                
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
}