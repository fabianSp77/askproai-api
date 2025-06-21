<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MCP\DatabaseMCPServer;
use App\Services\MCP\CalcomMCPServer;
use App\Services\MCP\RetellMCPServer;
use App\Services\MCP\SentryMCPServer;
use App\Services\MCP\QueueMCPServer;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use Illuminate\Support\Facades\Log;

class MCPController extends Controller
{
    protected DatabaseMCPServer $databaseMCP;
    protected CalcomMCPServer $calcomMCP;
    protected RetellMCPServer $retellMCP;
    protected SentryMCPServer $sentryMCP;
    protected QueueMCPServer $queueMCP;
    protected MCPOrchestrator $orchestrator;
    
    public function __construct(
        DatabaseMCPServer $databaseMCP,
        CalcomMCPServer $calcomMCP,
        RetellMCPServer $retellMCP,
        SentryMCPServer $sentryMCP,
        QueueMCPServer $queueMCP,
        MCPOrchestrator $orchestrator
    ) {
        $this->databaseMCP = $databaseMCP;
        $this->calcomMCP = $calcomMCP;
        $this->retellMCP = $retellMCP;
        $this->sentryMCP = $sentryMCP;
        $this->queueMCP = $queueMCP;
        $this->orchestrator = $orchestrator;
    }
    
    /**
     * Get MCP server information
     */
    public function info()
    {
        return response()->json([
            'name' => 'AskProAI MCP Server',
            'version' => '1.0.0',
            'protocol' => 'MCP/1.0',
            'capabilities' => [
                'database' => [
                    'schema' => true,
                    'query' => true,
                    'search' => true,
                    'stats' => true,
                ],
                'calcom' => [
                    'event_types' => true,
                    'availability' => true,
                    'bookings' => true,
                    'sync' => true,
                ],
                'retell' => [
                    'agents' => true,
                    'calls' => true,
                    'stats' => true,
                    'phone_numbers' => true,
                ],
                'sentry' => [
                    'issues' => true,
                    'events' => true,
                    'search' => true,
                    'performance' => true,
                ],
                'queue' => [
                    'overview' => true,
                    'failed_jobs' => true,
                    'recent_jobs' => true,
                    'retry' => true,
                    'metrics' => true,
                    'workers' => true,
                ],
            ],
            'endpoints' => [
                'database' => '/api/mcp/database/*',
                'calcom' => '/api/mcp/calcom/*',
                'retell' => '/api/mcp/retell/*',
                'sentry' => '/api/mcp/sentry/*',
                'queue' => '/api/mcp/queue/*',
            ],
        ]);
    }
    
    // Database MCP Endpoints
    
    public function databaseSchema(Request $request)
    {
        $params = $request->validate([
            'tables' => 'array',
            'tables.*' => 'string',
        ]);
        
        return response()->json($this->databaseMCP->getSchema($params));
    }
    
    public function databaseQuery(Request $request)
    {
        $validated = $request->validate([
            'sql' => 'required|string',
            'bindings' => 'array',
        ]);
        
        return response()->json($this->databaseMCP->query($validated['sql'], $validated['bindings'] ?? []));
    }
    
    public function databaseSearch(Request $request)
    {
        $validated = $request->validate([
            'search' => 'required|string|min:2',
            'tables' => 'array',
            'tables.*' => 'string',
        ]);
        
        return response()->json($this->databaseMCP->search($validated['search'], $validated['tables'] ?? []));
    }
    
    public function databaseFailedAppointments(Request $request)
    {
        $params = $request->validate([
            'hours' => 'integer|min:1|max:168',
            'limit' => 'integer|min:1|max:1000',
        ]);
        
        return response()->json($this->databaseMCP->getFailedAppointments($params));
    }
    
    public function databaseCallStats(Request $request)
    {
        $params = $request->validate([
            'days' => 'integer|min:1|max:90',
        ]);
        
        return response()->json($this->databaseMCP->getCallStats($params));
    }
    
    public function databaseTenantStats(Request $request)
    {
        $companyId = $request->input('company_id');
        
        return response()->json($this->databaseMCP->getTenantStats($companyId));
    }
    
    // Cal.com MCP Endpoints
    
    public function calcomEventTypes(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|string|exists:companies,id',
        ]);
        
        return response()->json($this->calcomMCP->getEventTypes($validated['company_id']));
    }
    
    public function calcomAvailability(Request $request)
    {
        $params = $request->validate([
            'company_id' => 'required|string|exists:companies,id',
            'event_type_id' => 'required|integer',
            'date_from' => 'date',
            'date_to' => 'date|after:date_from',
        ]);
        
        return response()->json($this->calcomMCP->checkAvailability($params));
    }
    
    public function calcomBookings(Request $request)
    {
        $params = $request->validate([
            'company_id' => 'required|string|exists:companies,id',
            'status' => 'string|in:upcoming,past,cancelled',
            'date_from' => 'date',
            'date_to' => 'date|after:date_from',
        ]);
        
        return response()->json($this->calcomMCP->getBookings($params));
    }
    
    public function calcomAssignments($companyId)
    {
        return response()->json($this->calcomMCP->getEventTypeAssignments($companyId));
    }
    
    public function calcomSync(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|string|exists:companies,id',
        ]);
        
        return response()->json($this->calcomMCP->syncEventTypes($validated['company_id']));
    }
    
    public function calcomTest($companyId)
    {
        return response()->json($this->calcomMCP->testConnection($companyId));
    }
    
    // Retell.ai MCP Endpoints
    
    public function retellAgent($companyId)
    {
        return response()->json($this->retellMCP->getAgent($companyId));
    }
    
    public function retellAgents($companyId)
    {
        return response()->json($this->retellMCP->listAgents($companyId));
    }
    
    public function retellCallStats(Request $request)
    {
        $params = $request->validate([
            'company_id' => 'required|string|exists:companies,id',
            'days' => 'integer|min:1|max:90',
            'branch_id' => 'string|exists:branches,id',
        ]);
        
        return response()->json($this->retellMCP->getCallStats($params));
    }
    
    public function retellRecentCalls(Request $request)
    {
        $params = $request->validate([
            'company_id' => 'required|string|exists:companies,id',
            'limit' => 'integer|min:1|max:100',
            'status' => 'string|in:completed,failed,in_progress',
        ]);
        
        return response()->json($this->retellMCP->getRecentCalls($params));
    }
    
    public function retellCallDetails($callId)
    {
        return response()->json($this->retellMCP->getCallDetails($callId));
    }
    
    public function retellSearchCalls(Request $request)
    {
        $params = $request->validate([
            'company_id' => 'required|string|exists:companies,id',
            'search' => 'string|min:2',
            'limit' => 'integer|min:1|max:100',
        ]);
        
        return response()->json($this->retellMCP->searchCalls($params));
    }
    
    public function retellPhoneNumbers($companyId)
    {
        return response()->json($this->retellMCP->getPhoneNumbers($companyId));
    }
    
    public function retellTest($companyId)
    {
        return response()->json($this->retellMCP->testConnection($companyId));
    }
    
    // Sentry MCP Endpoints (existing)
    
    public function sentryIssues(Request $request)
    {
        $params = $request->validate([
            'limit' => 'integer|min:1|max:100',
            'sort' => 'string|in:date,priority,freq,new',
            'query' => 'string',
        ]);
        
        return response()->json($this->sentryMCP->listIssues($params));
    }
    
    public function sentryIssueDetails($issueId)
    {
        return response()->json($this->sentryMCP->getIssue($issueId));
    }
    
    public function sentryLatestEvent($issueId)
    {
        return response()->json($this->sentryMCP->getLatestEvent($issueId));
    }
    
    public function sentrySearchIssues(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
            'limit' => 'integer|min:1|max:100',
        ]);
        
        return response()->json($this->sentryMCP->searchIssues($validated['query'], [
            'limit' => $validated['limit'] ?? 25
        ]));
    }
    
    public function sentryPerformance()
    {
        return response()->json($this->sentryMCP->getPerformanceData());
    }
    
    // Queue MCP Endpoints
    
    public function queueOverview()
    {
        return response()->json($this->queueMCP->getOverview());
    }
    
    public function queueFailedJobs(Request $request)
    {
        $params = $request->validate([
            'limit' => 'integer|min:1|max:100',
            'tag' => 'string',
        ]);
        
        return response()->json($this->queueMCP->getFailedJobs($params));
    }
    
    public function queueRecentJobs(Request $request)
    {
        $params = $request->validate([
            'limit' => 'integer|min:1|max:100',
            'queue' => 'string',
        ]);
        
        return response()->json($this->queueMCP->getRecentJobs($params));
    }
    
    public function queueJobDetails($jobId)
    {
        return response()->json($this->queueMCP->getJobDetails($jobId));
    }
    
    public function queueRetryJob(Request $request, $jobId)
    {
        return response()->json($this->queueMCP->retryJob($jobId));
    }
    
    public function queueMetrics(Request $request)
    {
        $params = $request->validate([
            'period' => 'string|in:hour,day,week',
        ]);
        
        return response()->json($this->queueMCP->getMetrics($params));
    }
    
    public function queueWorkers()
    {
        return response()->json($this->queueMCP->getWorkers());
    }
    
    public function queueSearchJobs(Request $request)
    {
        $params = $request->validate([
            'query' => 'required|string|min:2',
            'type' => 'string|in:all,failed,completed',
            'limit' => 'integer|min:1|max:100',
        ]);
        
        return response()->json($this->queueMCP->searchJobs($params));
    }
    
    // Cache Management
    
    public function clearCache(Request $request, $service)
    {
        $params = $request->all();
        
        switch ($service) {
            case 'database':
                $this->databaseMCP->clearCache();
                break;
            case 'calcom':
                $this->calcomMCP->clearCache($params);
                break;
            case 'retell':
                $this->retellMCP->clearCache($params);
                break;
            case 'sentry':
                $this->sentryMCP->clearCache();
                break;
            case 'queue':
                $this->queueMCP->clearCache();
                break;
            default:
                return response()->json(['error' => 'Invalid service'], 400);
        }
        
        return response()->json(['message' => 'Cache cleared successfully']);
    }
    
    // Orchestrator Endpoints
    
    /**
     * Execute MCP request through orchestrator
     */
    public function execute(Request $request)
    {
        $validated = $request->validate([
            'service' => 'required|string|in:webhook,calcom,database,queue,retell',
            'operation' => 'required|string',
            'params' => 'array',
            'tenant_id' => 'integer',
            'metadata' => 'array',
        ]);
        
        try {
            // Create MCP request
            $mcpRequest = new MCPRequest(
                service: $validated['service'],
                operation: $validated['operation'],
                params: $validated['params'] ?? [],
                tenantId: $validated['tenant_id'] ?? null,
                metadata: $validated['metadata'] ?? []
            );
            
            // Execute through orchestrator
            $response = $this->orchestrator->route($mcpRequest);
            
            return $response->toHttpResponse();
            
        } catch (\Exception $e) {
            Log::error('MCP API error', [
                'service' => $validated['service'] ?? 'unknown',
                'operation' => $validated['operation'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            
            return MCPResponse::fromException($e)->toHttpResponse(200, 500);
        }
    }
    
    /**
     * Get orchestrator health status
     */
    public function orchestratorHealth()
    {
        try {
            $health = $this->orchestrator->healthCheck();
            
            return response()->json([
                'success' => true,
                'data' => $health,
                'timestamp' => now()->toIso8601String(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Health check failed',
                'message' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
    
    /**
     * Get orchestrator metrics
     */
    public function orchestratorMetrics()
    {
        try {
            $metrics = $this->orchestrator->getMetrics();
            
            return response()->json([
                'success' => true,
                'data' => $metrics,
                'timestamp' => now()->toIso8601String(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get metrics',
                'message' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
    
    /**
     * Execute batch MCP requests
     */
    public function batch(Request $request)
    {
        $validated = $request->validate([
            'requests' => 'required|array|max:50',
            'requests.*.service' => 'required|string|in:webhook,calcom,database,queue,retell',
            'requests.*.operation' => 'required|string',
            'requests.*.params' => 'array',
            'parallel' => 'boolean',
        ]);
        
        $results = [];
        $parallel = $validated['parallel'] ?? false;
        
        try {
            foreach ($validated['requests'] as $index => $requestData) {
                $mcpRequest = MCPRequest::fromArray($requestData);
                
                try {
                    $response = $this->orchestrator->route($mcpRequest);
                    $results[] = [
                        'index' => $index,
                        'success' => $response->isSuccess(),
                        'data' => $response->getData(),
                        'error' => $response->getError(),
                        'metadata' => $response->getMetadata(),
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'index' => $index,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'batch_size' => count($validated['requests']),
                'results' => $results,
                'timestamp' => now()->toIso8601String(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Batch execution failed',
                'message' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}