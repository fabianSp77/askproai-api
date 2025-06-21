<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentryMCPServer
{
    protected ?string $apiUrl;
    protected ?string $authToken;
    protected ?string $organization;
    protected ?string $project;
    protected array $config;
    
    public function __construct()
    {
        $this->config = config('mcp-sentry', []);
        $this->apiUrl = $this->config['sentry']['api_url'] ?? null;
        $this->authToken = $this->config['sentry']['auth_token'] ?? null;
        $this->organization = $this->config['sentry']['organization'] ?? null;
        $this->project = $this->config['sentry']['project'] ?? null;
    }
    
    /**
     * List recent issues from Sentry
     */
    public function listIssues(array $params = []): array
    {
        if (!$this->authToken) {
            return ['error' => 'Sentry not configured'];
        }
        
        $cacheKey = $this->getCacheKey('issues', $params);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'] ?? 300, function () use ($params) {
            $query = array_merge([
                'statsPeriod' => ($this->config['filters']['days_back'] ?? 7) . 'd',
                'query' => 'is:unresolved level:' . ($this->config['filters']['min_level'] ?? 'warning'),
                'limit' => $params['limit'] ?? 25,
                'sort' => $params['sort'] ?? 'date',
            ], $params);
            
            $response = Http::withToken($this->authToken)
                ->get("{$this->apiUrl}organizations/{$this->organization}/issues/", $query);
            
            if ($response->successful()) {
                return $this->formatIssueList($response->json());
            }
            
            Log::error('Failed to fetch Sentry issues', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return ['error' => 'Failed to fetch issues from Sentry'];
        });
    }
    
    /**
     * Get detailed information about a specific issue
     */
    public function getIssue(string $issueId): array
    {
        $cacheKey = $this->getCacheKey('issue', ['id' => $issueId]);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($issueId) {
            $response = Http::withToken($this->authToken)
                ->get("{$this->apiUrl}issues/{$issueId}/");
            
            if ($response->successful()) {
                return $this->formatIssueDetail($response->json());
            }
            
            Log::error('Failed to fetch Sentry issue detail', [
                'issue_id' => $issueId,
                'status' => $response->status()
            ]);
            
            return ['error' => 'Failed to fetch issue details'];
        });
    }
    
    /**
     * Get the latest event (with stack trace) for an issue
     */
    public function getLatestEvent(string $issueId): array
    {
        $response = Http::withToken($this->authToken)
            ->get("{$this->apiUrl}issues/{$issueId}/events/latest/");
        
        if ($response->successful()) {
            return $this->formatEvent($response->json());
        }
        
        return ['error' => 'Failed to fetch event details'];
    }
    
    /**
     * Search issues with specific query
     */
    public function searchIssues(string $query, array $params = []): array
    {
        $params['query'] = $query;
        return $this->listIssues($params);
    }
    
    /**
     * Get performance data for the project
     */
    public function getPerformanceData(): array
    {
        if (!$this->config['capabilities']['get_performance']) {
            return ['error' => 'Performance data access is disabled'];
        }
        
        $response = Http::withToken($this->authToken)
            ->get("{$this->apiUrl}organizations/{$this->organization}/events/", [
                'project' => $this->project,
                'field' => ['transaction', 'p95()', 'count()'],
                'statsPeriod' => '24h',
                'query' => 'event.type:transaction',
            ]);
        
        if ($response->successful()) {
            return $this->formatPerformanceData($response->json());
        }
        
        return ['error' => 'Failed to fetch performance data'];
    }
    
    /**
     * Format issue list for MCP response
     */
    protected function formatIssueList(array $issues): array
    {
        return array_map(function ($issue) {
            return [
                'id' => $issue['id'],
                'title' => $issue['title'],
                'culprit' => $issue['culprit'] ?? 'Unknown',
                'level' => $issue['level'],
                'status' => $issue['status'],
                'count' => $issue['count'] ?? 0,
                'userCount' => $issue['userCount'] ?? 0,
                'firstSeen' => $issue['firstSeen'],
                'lastSeen' => $issue['lastSeen'],
                'permalink' => $issue['permalink'] ?? null,
                'shortId' => $issue['shortId'] ?? $issue['id'],
            ];
        }, $issues);
    }
    
    /**
     * Format issue detail for MCP response
     */
    protected function formatIssueDetail(array $issue): array
    {
        return [
            'id' => $issue['id'],
            'title' => $issue['title'],
            'culprit' => $issue['culprit'] ?? 'Unknown',
            'level' => $issue['level'],
            'status' => $issue['status'],
            'platform' => $issue['platform'] ?? 'Unknown',
            'type' => $issue['type'] ?? 'error',
            'metadata' => $issue['metadata'] ?? [],
            'count' => $issue['count'] ?? 0,
            'userCount' => $issue['userCount'] ?? 0,
            'firstSeen' => $issue['firstSeen'],
            'lastSeen' => $issue['lastSeen'],
            'tags' => $issue['tags'] ?? [],
            'annotations' => $issue['annotations'] ?? [],
        ];
    }
    
    /**
     * Format event data for MCP response
     */
    protected function formatEvent(array $event): array
    {
        return [
            'id' => $event['id'],
            'message' => $event['message'] ?? '',
            'platform' => $event['platform'] ?? 'Unknown',
            'datetime' => $event['datetime'],
            'tags' => $event['tags'] ?? [],
            'contexts' => $event['contexts'] ?? [],
            'user' => $event['user'] ?? null,
            'request' => $event['request'] ?? null,
            'exception' => $this->formatException($event['exception'] ?? []),
            'breadcrumbs' => $event['breadcrumbs'] ?? [],
        ];
    }
    
    /**
     * Format exception data
     */
    protected function formatException(array $exception): array
    {
        if (empty($exception['values'])) {
            return [];
        }
        
        return array_map(function ($exc) {
            return [
                'type' => $exc['type'] ?? 'Unknown',
                'value' => $exc['value'] ?? '',
                'stacktrace' => $this->formatStacktrace($exc['stacktrace'] ?? []),
            ];
        }, $exception['values']);
    }
    
    /**
     * Format stacktrace
     */
    protected function formatStacktrace(array $stacktrace): array
    {
        if (empty($stacktrace['frames'])) {
            return [];
        }
        
        return array_map(function ($frame) {
            return [
                'filename' => $frame['filename'] ?? 'Unknown',
                'function' => $frame['function'] ?? 'Unknown',
                'lineNo' => $frame['lineNo'] ?? 0,
                'colNo' => $frame['colNo'] ?? 0,
                'absPath' => $frame['absPath'] ?? '',
                'context' => $frame['context'] ?? [],
                'vars' => $frame['vars'] ?? [],
            ];
        }, array_reverse($stacktrace['frames'])); // Reverse to show top of stack first
    }
    
    /**
     * Format performance data
     */
    protected function formatPerformanceData(array $data): array
    {
        return [
            'transactions' => array_map(function ($item) {
                return [
                    'transaction' => $item['transaction'],
                    'p95' => $item['p95()'],
                    'count' => $item['count()'],
                ];
            }, $data['data'] ?? []),
            'meta' => $data['meta'] ?? [],
        ];
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
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey('issues'));
        Cache::forget($this->getCacheKey('performance'));
    }
}