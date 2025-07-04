<?php

namespace App\Services\DataFlow;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Carbon\Carbon;
use App\Models\DataFlowLog;
use App\Traits\UsesMCPServers;

/**
 * Data Flow Logger Service
 * 
 * Tracks every data flow between external systems:
 * - Retell.ai → Our Middleware → Cal.com
 * - WhatsApp → Our System → Various Services
 * - Any API Call with full request/response tracking
 * 
 * Features:
 * - Correlation IDs for end-to-end tracking
 * - Automatic sequence diagram generation
 * - Performance metrics
 * - Error tracking and analysis
 */
class DataFlowLogger
{
    use UsesMCPServers;
    
    /**
     * Active flow tracking
     */
    protected array $activeFlows = [];
    
    /**
     * Flow types we track
     */
    const FLOW_TYPES = [
        'webhook_incoming' => 'Incoming webhook from external system',
        'api_outgoing' => 'Outgoing API call to external system',
        'internal_processing' => 'Internal data transformation',
        'webhook_response' => 'Response to incoming webhook',
        'async_job' => 'Asynchronous job processing',
        'mcp_call' => 'MCP server invocation'
    ];
    
    /**
     * Known external systems
     */
    const EXTERNAL_SYSTEMS = [
        'retell' => 'Retell.ai',
        'calcom' => 'Cal.com',
        'stripe' => 'Stripe',
        'whatsapp' => 'WhatsApp Business',
        'twilio' => 'Twilio',
        'sendgrid' => 'SendGrid',
        'google' => 'Google APIs',
        'internal' => 'Internal System'
    ];
    
    /**
     * Start tracking a new data flow
     * 
     * @param string $type Flow type from FLOW_TYPES
     * @param string $source Source system
     * @param string $destination Destination system
     * @param array $metadata Additional metadata
     * @return string Correlation ID
     */
    public function startFlow(string $type, string $source, string $destination, array $metadata = []): string
    {
        $correlationId = $metadata['correlation_id'] ?? Str::uuid()->toString();
        $parentId = $metadata['parent_correlation_id'] ?? null;
        
        $flow = [
            'correlation_id' => $correlationId,
            'parent_correlation_id' => $parentId,
            'type' => $type,
            'source' => $source,
            'destination' => $destination,
            'started_at' => microtime(true),
            'metadata' => $metadata,
            'steps' => [],
            'status' => 'started'
        ];
        
        // Store in memory for current request
        $this->activeFlows[$correlationId] = $flow;
        
        // Persist to database
        $this->persistFlow($flow, 'start');
        
        // Log
        Log::info('Data flow started', [
            'correlation_id' => $correlationId,
            'type' => $type,
            'source' => $source,
            'destination' => $destination
        ]);
        
        return $correlationId;
    }
    
    /**
     * Add a step to an active flow
     * 
     * @param string $correlationId
     * @param string $stepName
     * @param array $data Step data
     * @param string $status success|failure|processing
     */
    public function addFlowStep(string $correlationId, string $stepName, array $data = [], string $status = 'success'): void
    {
        if (!isset($this->activeFlows[$correlationId])) {
            // Try to load from cache
            $this->loadFlowFromCache($correlationId);
        }
        
        if (!isset($this->activeFlows[$correlationId])) {
            Log::warning('Cannot add step to unknown flow', ['correlation_id' => $correlationId]);
            return;
        }
        
        $step = [
            'name' => $stepName,
            'timestamp' => microtime(true),
            'status' => $status,
            'data' => $this->sanitizeData($data),
            'duration_ms' => null
        ];
        
        // Calculate duration from previous step
        $steps = &$this->activeFlows[$correlationId]['steps'];
        if (!empty($steps)) {
            $previousStep = end($steps);
            $step['duration_ms'] = ($step['timestamp'] - $previousStep['timestamp']) * 1000;
        }
        
        $steps[] = $step;
        
        // Update status if failed
        if ($status === 'failure') {
            $this->activeFlows[$correlationId]['status'] = 'failed';
        }
        
        // Persist step
        $this->persistFlowStep($correlationId, $step);
    }
    
    /**
     * Track an API request
     * 
     * @param string $correlationId
     * @param string $system External system name
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $request Request data
     * @param string $direction incoming|outgoing
     */
    public function trackApiRequest(
        string $correlationId,
        string $system,
        string $method,
        string $endpoint,
        array $request = [],
        string $direction = 'outgoing'
    ): void {
        $stepName = $direction === 'outgoing' 
            ? "API Call to {$system}: {$method} {$endpoint}"
            : "API Request from {$system}: {$method} {$endpoint}";
            
        $this->addFlowStep($correlationId, $stepName, [
            'system' => $system,
            'method' => $method,
            'endpoint' => $endpoint,
            'headers' => $this->sanitizeHeaders($request['headers'] ?? []),
            'body' => $this->sanitizeData($request['body'] ?? []),
            'timestamp' => now()->toIso8601String()
        ]);
    }
    
    /**
     * Track an API response
     */
    public function trackApiResponse(
        string $correlationId,
        string $system,
        int $statusCode,
        array $response = [],
        float $duration = null
    ): void {
        $status = $statusCode >= 200 && $statusCode < 300 ? 'success' : 'failure';
        
        $this->addFlowStep($correlationId, "Response from {$system}: {$statusCode}", [
            'system' => $system,
            'status_code' => $statusCode,
            'headers' => $this->sanitizeHeaders($response['headers'] ?? []),
            'body' => $this->sanitizeData($response['body'] ?? []),
            'duration_ms' => $duration,
            'timestamp' => now()->toIso8601String()
        ], $status);
    }
    
    /**
     * Track internal data transformation
     */
    public function trackTransformation(
        string $correlationId,
        string $transformationName,
        array $inputData,
        array $outputData,
        array $metadata = []
    ): void {
        $this->addFlowStep($correlationId, "Transform: {$transformationName}", [
            'input_summary' => $this->generateDataSummary($inputData),
            'output_summary' => $this->generateDataSummary($outputData),
            'metadata' => $metadata,
            'timestamp' => now()->toIso8601String()
        ]);
    }
    
    /**
     * Complete a flow
     */
    public function completeFlow(string $correlationId, string $status = 'completed', array $summary = []): void
    {
        if (!isset($this->activeFlows[$correlationId])) {
            Log::warning('Cannot complete unknown flow', ['correlation_id' => $correlationId]);
            return;
        }
        
        $flow = &$this->activeFlows[$correlationId];
        $flow['completed_at'] = microtime(true);
        $flow['duration_ms'] = ($flow['completed_at'] - $flow['started_at']) * 1000;
        $flow['status'] = $status;
        $flow['summary'] = $summary;
        
        // Calculate statistics
        $flow['statistics'] = $this->calculateFlowStatistics($flow);
        
        // Persist completion
        $this->persistFlow($flow, 'complete');
        
        // Generate sequence diagram
        $flow['sequence_diagram'] = $this->generateSequenceDiagram($flow);
        
        // Log completion
        Log::info('Data flow completed', [
            'correlation_id' => $correlationId,
            'duration_ms' => $flow['duration_ms'],
            'steps_count' => count($flow['steps']),
            'status' => $status
        ]);
        
        // Clean up
        unset($this->activeFlows[$correlationId]);
        Cache::forget("dataflow:{$correlationId}");
    }
    
    /**
     * Get flow details
     */
    public function getFlow(string $correlationId): ?array
    {
        // Check active flows first
        if (isset($this->activeFlows[$correlationId])) {
            return $this->activeFlows[$correlationId];
        }
        
        // Try cache
        $cached = Cache::get("dataflow:{$correlationId}");
        if ($cached) {
            return $cached;
        }
        
        // Load from database
        return $this->loadFlowFromDatabase($correlationId);
    }
    
    /**
     * Find flows by criteria
     */
    public function findFlows(array $criteria = []): array
    {
        $query = DB::table('data_flow_logs');
        
        if (isset($criteria['source'])) {
            $query->where('source', $criteria['source']);
        }
        
        if (isset($criteria['destination'])) {
            $query->where('destination', $criteria['destination']);
        }
        
        if (isset($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }
        
        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }
        
        if (isset($criteria['date_from'])) {
            $query->where('created_at', '>=', $criteria['date_from']);
        }
        
        if (isset($criteria['date_to'])) {
            $query->where('created_at', '<=', $criteria['date_to']);
        }
        
        return $query->orderBy('created_at', 'desc')
                    ->limit($criteria['limit'] ?? 100)
                    ->get()
                    ->map(function ($flow) {
                        $flow->metadata = json_decode($flow->metadata, true);
                        $flow->steps = json_decode($flow->steps, true);
                        $flow->statistics = json_decode($flow->statistics, true);
                        return (array) $flow;
                    })
                    ->toArray();
    }
    
    /**
     * Generate flow statistics
     */
    public function generateFlowStatistics(array $criteria = []): array
    {
        $flows = $this->findFlows($criteria);
        
        $stats = [
            'total_flows' => count($flows),
            'by_type' => [],
            'by_source' => [],
            'by_destination' => [],
            'by_status' => [],
            'average_duration_ms' => 0,
            'total_api_calls' => 0,
            'error_rate' => 0,
            'busiest_hour' => null,
            'slowest_flows' => [],
            'failed_flows' => []
        ];
        
        $totalDuration = 0;
        $errorCount = 0;
        $hourCounts = [];
        
        foreach ($flows as $flow) {
            // Count by type
            $stats['by_type'][$flow['type']] = ($stats['by_type'][$flow['type']] ?? 0) + 1;
            
            // Count by source/destination
            $stats['by_source'][$flow['source']] = ($stats['by_source'][$flow['source']] ?? 0) + 1;
            $stats['by_destination'][$flow['destination']] = ($stats['by_destination'][$flow['destination']] ?? 0) + 1;
            
            // Count by status
            $stats['by_status'][$flow['status']] = ($stats['by_status'][$flow['status']] ?? 0) + 1;
            
            // Duration stats
            if (isset($flow['duration_ms'])) {
                $totalDuration += $flow['duration_ms'];
            }
            
            // Error tracking
            if ($flow['status'] === 'failed') {
                $errorCount++;
                $stats['failed_flows'][] = [
                    'correlation_id' => $flow['correlation_id'],
                    'type' => $flow['type'],
                    'error' => $flow['summary']['error'] ?? 'Unknown error'
                ];
            }
            
            // Hour distribution
            $hour = Carbon::parse($flow['created_at'])->format('H');
            $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
            
            // API call counting
            foreach ($flow['steps'] as $step) {
                if (str_contains($step['name'], 'API Call')) {
                    $stats['total_api_calls']++;
                }
            }
        }
        
        // Calculate averages
        if ($stats['total_flows'] > 0) {
            $stats['average_duration_ms'] = $totalDuration / $stats['total_flows'];
            $stats['error_rate'] = ($errorCount / $stats['total_flows']) * 100;
        }
        
        // Find busiest hour
        if (!empty($hourCounts)) {
            arsort($hourCounts);
            $stats['busiest_hour'] = key($hourCounts);
        }
        
        // Find slowest flows
        usort($flows, function ($a, $b) {
            return ($b['duration_ms'] ?? 0) <=> ($a['duration_ms'] ?? 0);
        });
        
        $stats['slowest_flows'] = array_slice(array_map(function ($flow) {
            return [
                'correlation_id' => $flow['correlation_id'],
                'type' => $flow['type'],
                'duration_ms' => $flow['duration_ms'] ?? 0
            ];
        }, $flows), 0, 5);
        
        return $stats;
    }
    
    /**
     * Generate sequence diagram for a flow
     */
    protected function generateSequenceDiagram(array $flow): string
    {
        $diagram = "sequenceDiagram\n";
        $diagram .= "    participant Client\n";
        $diagram .= "    participant Middleware as Our System\n";
        
        // Add participants based on systems involved
        $systems = array_unique(array_merge(
            [$flow['source'], $flow['destination']],
            array_column($flow['steps'], 'data.system')
        ));
        
        foreach ($systems as $system) {
            if ($system && $system !== 'internal') {
                $name = self::EXTERNAL_SYSTEMS[$system] ?? $system;
                $diagram .= "    participant {$system} as {$name}\n";
            }
        }
        
        $diagram .= "\n";
        
        // Add flow steps
        foreach ($flow['steps'] as $step) {
            $from = 'Middleware';
            $to = 'Middleware';
            $message = $step['name'];
            
            // Determine participants
            if (str_contains($step['name'], 'from')) {
                preg_match('/from\s+(\w+)/', $step['name'], $matches);
                $from = $matches[1] ?? 'Client';
            }
            
            if (str_contains($step['name'], 'to')) {
                preg_match('/to\s+(\w+)/', $step['name'], $matches);
                $to = $matches[1] ?? 'Middleware';
            }
            
            // Format message
            $duration = isset($step['duration_ms']) ? " ({$step['duration_ms']}ms)" : '';
            $status = $step['status'] === 'failure' ? ' ❌' : '';
            
            $diagram .= "    {$from}->>{$to}: {$message}{$duration}{$status}\n";
            
            // Add response if applicable
            if (str_contains($step['name'], 'Response')) {
                $diagram .= "    {$to}-->>{$from}: Response\n";
            }
        }
        
        // Add total duration
        $totalDuration = round($flow['duration_ms'] ?? 0, 2);
        $diagram .= "\n    Note over Client,Middleware: Total Duration: {$totalDuration}ms\n";
        
        return $diagram;
    }
    
    /**
     * Generate Mermaid chart URL for visualization
     */
    public function generateMermaidUrl(string $correlationId): string
    {
        $flow = $this->getFlow($correlationId);
        if (!$flow) {
            return '';
        }
        
        $diagram = $this->generateSequenceDiagram($flow);
        $encoded = base64_encode($diagram);
        
        return "https://mermaid.ink/img/{$encoded}";
    }
    
    /**
     * Sanitize sensitive data
     */
    protected function sanitizeData(array $data): array
    {
        $sensitiveKeys = [
            'password', 'token', 'api_key', 'secret',
            'authorization', 'credit_card', 'ssn'
        ];
        
        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            foreach ($sensitiveKeys as $sensitive) {
                if (stripos($key, $sensitive) !== false) {
                    $value = '***REDACTED***';
                    break;
                }
            }
        });
        
        return $data;
    }
    
    /**
     * Sanitize headers
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sanitized = $this->sanitizeData($headers);
        
        // Additional header-specific sanitization
        $headerKeys = ['authorization', 'x-api-key', 'cookie'];
        foreach ($headerKeys as $key) {
            if (isset($sanitized[$key])) {
                $sanitized[$key] = '***REDACTED***';
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Generate data summary
     */
    protected function generateDataSummary(array $data): array
    {
        return [
            'keys' => array_keys($data),
            'size' => strlen(json_encode($data)),
            'type' => $this->detectDataType($data)
        ];
    }
    
    /**
     * Detect data type
     */
    protected function detectDataType(array $data): string
    {
        // Check for common patterns
        if (isset($data['appointment_id']) || isset($data['booking_id'])) {
            return 'appointment';
        }
        
        if (isset($data['call_id']) || isset($data['phone_number'])) {
            return 'phone_call';
        }
        
        if (isset($data['customer_id']) || isset($data['email'])) {
            return 'customer';
        }
        
        if (isset($data['payment_intent']) || isset($data['invoice_id'])) {
            return 'payment';
        }
        
        return 'generic';
    }
    
    /**
     * Calculate flow statistics
     */
    protected function calculateFlowStatistics(array $flow): array
    {
        $stats = [
            'total_steps' => count($flow['steps']),
            'successful_steps' => 0,
            'failed_steps' => 0,
            'api_calls' => 0,
            'transformations' => 0,
            'total_duration_ms' => $flow['duration_ms'] ?? 0,
            'step_durations' => []
        ];
        
        foreach ($flow['steps'] as $step) {
            if ($step['status'] === 'success') {
                $stats['successful_steps']++;
            } else {
                $stats['failed_steps']++;
            }
            
            if (str_contains($step['name'], 'API')) {
                $stats['api_calls']++;
            }
            
            if (str_contains($step['name'], 'Transform')) {
                $stats['transformations']++;
            }
            
            if (isset($step['duration_ms'])) {
                $stats['step_durations'][] = [
                    'name' => $step['name'],
                    'duration_ms' => $step['duration_ms']
                ];
            }
        }
        
        // Sort by duration
        usort($stats['step_durations'], function ($a, $b) {
            return $b['duration_ms'] <=> $a['duration_ms'];
        });
        
        return $stats;
    }
    
    /**
     * Persist flow to database
     */
    protected function persistFlow(array $flow, string $action): void
    {
        try {
            if ($action === 'start') {
                DB::table('data_flow_logs')->insert([
                    'correlation_id' => $flow['correlation_id'],
                    'parent_correlation_id' => $flow['parent_correlation_id'],
                    'type' => $flow['type'],
                    'source' => $flow['source'],
                    'destination' => $flow['destination'],
                    'status' => $flow['status'],
                    'metadata' => json_encode($flow['metadata']),
                    'steps' => json_encode($flow['steps']),
                    'started_at' => Carbon::createFromTimestamp($flow['started_at']),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                DB::table('data_flow_logs')
                    ->where('correlation_id', $flow['correlation_id'])
                    ->update([
                        'status' => $flow['status'],
                        'steps' => json_encode($flow['steps']),
                        'statistics' => json_encode($flow['statistics'] ?? []),
                        'summary' => json_encode($flow['summary'] ?? []),
                        'completed_at' => isset($flow['completed_at']) 
                            ? Carbon::createFromTimestamp($flow['completed_at'])
                            : null,
                        'duration_ms' => $flow['duration_ms'] ?? null,
                        'sequence_diagram' => $flow['sequence_diagram'] ?? null,
                        'updated_at' => now()
                    ]);
            }
            
            // Cache for quick access
            Cache::put("dataflow:{$flow['correlation_id']}", $flow, 300); // 5 minutes
            
        } catch (\Exception $e) {
            Log::error('Failed to persist data flow', [
                'correlation_id' => $flow['correlation_id'],
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Persist flow step
     */
    protected function persistFlowStep(string $correlationId, array $step): void
    {
        try {
            // Update steps in database
            $current = DB::table('data_flow_logs')
                ->where('correlation_id', $correlationId)
                ->first();
                
            if ($current) {
                $steps = json_decode($current->steps, true) ?? [];
                $steps[] = $step;
                
                DB::table('data_flow_logs')
                    ->where('correlation_id', $correlationId)
                    ->update([
                        'steps' => json_encode($steps),
                        'updated_at' => now()
                    ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to persist flow step', [
                'correlation_id' => $correlationId,
                'step' => $step['name'],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Load flow from cache
     */
    protected function loadFlowFromCache(string $correlationId): void
    {
        $cached = Cache::get("dataflow:{$correlationId}");
        if ($cached) {
            $this->activeFlows[$correlationId] = $cached;
        }
    }
    
    /**
     * Load flow from database
     */
    protected function loadFlowFromDatabase(string $correlationId): ?array
    {
        $record = DB::table('data_flow_logs')
            ->where('correlation_id', $correlationId)
            ->first();
            
        if (!$record) {
            return null;
        }
        
        return [
            'correlation_id' => $record->correlation_id,
            'parent_correlation_id' => $record->parent_correlation_id,
            'type' => $record->type,
            'source' => $record->source,
            'destination' => $record->destination,
            'status' => $record->status,
            'metadata' => json_decode($record->metadata, true),
            'steps' => json_decode($record->steps, true),
            'statistics' => json_decode($record->statistics, true),
            'summary' => json_decode($record->summary, true),
            'started_at' => $record->started_at ? Carbon::parse($record->started_at)->timestamp : null,
            'completed_at' => $record->completed_at ? Carbon::parse($record->completed_at)->timestamp : null,
            'duration_ms' => $record->duration_ms,
            'sequence_diagram' => $record->sequence_diagram
        ];
    }
    
    /**
     * Create webhook tracking helper
     */
    public function trackWebhook(Request $request, string $provider): string
    {
        $correlationId = $this->startFlow(
            'webhook_incoming',
            $provider,
            'internal',
            [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]
        );
        
        $this->trackApiRequest(
            $correlationId,
            $provider,
            $request->method(),
            $request->path(),
            [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ],
            'incoming'
        );
        
        return $correlationId;
    }
    
    /**
     * Create HTTP client tracking helper
     */
    public function trackHttpRequest(string $correlationId, string $system, string $method, string $url, array $options = []): void
    {
        $this->trackApiRequest(
            $correlationId,
            $system,
            $method,
            $url,
            [
                'headers' => $options['headers'] ?? [],
                'body' => $options['body'] ?? $options['json'] ?? []
            ]
        );
    }
    
    /**
     * Track HTTP response helper
     */
    public function trackHttpResponse(string $correlationId, string $system, Response $response, float $duration = null): void
    {
        $this->trackApiResponse(
            $correlationId,
            $system,
            $response->status(),
            [
                'headers' => $response->headers(),
                'body' => $response->json() ?? $response->body()
            ],
            $duration
        );
    }
}