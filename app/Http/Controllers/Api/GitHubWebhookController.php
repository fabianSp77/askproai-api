<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GitHubNotionIntegrationService;
use App\Services\MemoryBankAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitHubWebhookController extends Controller
{
    protected GitHubNotionIntegrationService $integration;
    protected MemoryBankAutomationService $memory;
    
    public function __construct(
        GitHubNotionIntegrationService $integration,
        MemoryBankAutomationService $memory
    ) {
        $this->integration = $integration;
        $this->memory = $memory;
    }
    
    /**
     * Handle incoming GitHub webhook
     */
    public function handleWebhook(Request $request)
    {
        // Verify webhook signature
        if (!$this->verifySignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        $event = $request->header('X-GitHub-Event');
        $payload = $request->all();
        
        // Store webhook event in memory bank for debugging
        $this->memory->rememberContext('github_webhook', [
            'event' => $event,
            'payload' => $payload,
            'received_at' => now()->toDateTimeString()
        ], ['webhook', 'github']);
        
        Log::info('GitHub webhook received', [
            'event' => $event,
            'action' => $payload['action'] ?? null
        ]);
        
        try {
            switch ($event) {
                case 'issues':
                    return $this->handleIssueEvent($payload);
                    
                case 'pull_request':
                    return $this->handlePullRequestEvent($payload);
                    
                case 'release':
                    return $this->handleReleaseEvent($payload);
                    
                case 'ping':
                    return response()->json(['message' => 'Pong!']);
                    
                default:
                    Log::info('Unhandled GitHub event: ' . $event);
                    return response()->json(['message' => 'Event not handled']);
            }
        } catch (\Exception $e) {
            Log::error('GitHub webhook processing failed', [
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
    
    /**
     * Handle issue events
     */
    protected function handleIssueEvent(array $payload)
    {
        $action = $payload['action'] ?? '';
        $issue = $payload['issue'] ?? [];
        $repository = $payload['repository'] ?? [];
        
        if (!in_array($action, ['opened', 'edited', 'closed', 'reopened'])) {
            return response()->json(['message' => 'Action not tracked']);
        }
        
        // Get configured Notion database for this repository
        $owner = $repository['owner']['login'] ?? '';
        $repo = $repository['name'] ?? '';
        $configKey = "github_notion_config_{$owner}_{$repo}";
        $config = cache($configKey);
        
        if (!$config || !isset($config['issues_database'])) {
            return response()->json(['message' => 'Repository not configured for issue sync']);
        }
        
        // Queue the sync job
        dispatch(function () use ($owner, $repo, $config) {
            $this->integration->syncIssuesToTasks($owner, $repo, $config['issues_database']);
        })->afterResponse();
        
        return response()->json(['message' => 'Issue sync queued']);
    }
    
    /**
     * Handle pull request events
     */
    protected function handlePullRequestEvent(array $payload)
    {
        $action = $payload['action'] ?? '';
        $pr = $payload['pull_request'] ?? [];
        $repository = $payload['repository'] ?? [];
        
        if (!in_array($action, ['opened', 'edited', 'closed', 'reopened', 'synchronize'])) {
            return response()->json(['message' => 'Action not tracked']);
        }
        
        // Get configured Notion database for this repository
        $owner = $repository['owner']['login'] ?? '';
        $repo = $repository['name'] ?? '';
        $configKey = "github_notion_config_{$owner}_{$repo}";
        $config = cache($configKey);
        
        if (!$config || !isset($config['prs_database'])) {
            return response()->json(['message' => 'Repository not configured for PR sync']);
        }
        
        // Queue the sync job
        dispatch(function () use ($owner, $repo, $config) {
            $this->integration->syncPRsToReviews($owner, $repo, $config['prs_database']);
        })->afterResponse();
        
        return response()->json(['message' => 'Pull request sync queued']);
    }
    
    /**
     * Handle release events
     */
    protected function handleReleaseEvent(array $payload)
    {
        $action = $payload['action'] ?? '';
        $release = $payload['release'] ?? [];
        $repository = $payload['repository'] ?? [];
        
        if ($action !== 'published') {
            return response()->json(['message' => 'Only published releases are synced']);
        }
        
        // Get configured Notion parent page for this repository
        $owner = $repository['owner']['login'] ?? '';
        $repo = $repository['name'] ?? '';
        $configKey = "github_notion_config_{$owner}_{$repo}";
        $config = cache($configKey);
        
        if (!$config || !isset($config['releases_parent'])) {
            return response()->json(['message' => 'Repository not configured for release sync']);
        }
        
        // Queue the sync job
        dispatch(function () use ($owner, $repo, $config) {
            $this->integration->syncReleasesToDocs($owner, $repo, $config['releases_parent']);
        })->afterResponse();
        
        return response()->json(['message' => 'Release sync queued']);
    }
    
    /**
     * Verify GitHub webhook signature
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            return false;
        }
        
        $secret = config('integrations.github.webhook_secret');
        
        if (!$secret) {
            Log::warning('GitHub webhook secret not configured');
            return true; // Allow in development
        }
        
        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
}