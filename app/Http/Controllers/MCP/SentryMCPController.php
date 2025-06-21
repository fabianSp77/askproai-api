<?php

namespace App\Http\Controllers\MCP;

use App\Http\Controllers\Controller;
use App\Services\MCP\SentryMCPServer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SentryMCPController extends Controller
{
    protected SentryMCPServer $sentryServer;
    
    public function __construct(SentryMCPServer $sentryServer)
    {
        $this->sentryServer = $sentryServer;
    }
    
    /**
     * MCP Server info endpoint
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'name' => config('mcp-sentry.server.name'),
            'version' => config('mcp-sentry.server.version'),
            'description' => config('mcp-sentry.server.description'),
            'capabilities' => config('mcp-sentry.capabilities'),
        ]);
    }
    
    /**
     * List recent Sentry issues
     */
    public function listIssues(Request $request): JsonResponse
    {
        $params = $request->validate([
            'limit' => 'integer|min:1|max:100',
            'sort' => 'string|in:date,priority,freq,new',
            'query' => 'string|nullable',
        ]);
        
        $issues = $this->sentryServer->listIssues($params);
        
        return response()->json([
            'issues' => $issues,
            'count' => count($issues),
        ]);
    }
    
    /**
     * Get specific issue details
     */
    public function getIssue(string $issueId): JsonResponse
    {
        $issue = $this->sentryServer->getIssue($issueId);
        
        if (isset($issue['error'])) {
            return response()->json($issue, 404);
        }
        
        return response()->json($issue);
    }
    
    /**
     * Get latest event for an issue (includes stack trace)
     */
    public function getLatestEvent(string $issueId): JsonResponse
    {
        $event = $this->sentryServer->getLatestEvent($issueId);
        
        if (isset($event['error'])) {
            return response()->json($event, 404);
        }
        
        return response()->json($event);
    }
    
    /**
     * Search issues
     */
    public function searchIssues(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string',
            'limit' => 'integer|min:1|max:100',
        ]);
        
        $issues = $this->sentryServer->searchIssues(
            $validated['query'],
            ['limit' => $validated['limit'] ?? 25]
        );
        
        return response()->json([
            'issues' => $issues,
            'count' => count($issues),
            'query' => $validated['query'],
        ]);
    }
    
    /**
     * Get performance data
     */
    public function getPerformance(): JsonResponse
    {
        $performance = $this->sentryServer->getPerformanceData();
        
        if (isset($performance['error'])) {
            return response()->json($performance, 403);
        }
        
        return response()->json($performance);
    }
    
    /**
     * Clear MCP cache
     */
    public function clearCache(): JsonResponse
    {
        $this->sentryServer->clearCache();
        
        return response()->json([
            'message' => 'Cache cleared successfully',
        ]);
    }
}