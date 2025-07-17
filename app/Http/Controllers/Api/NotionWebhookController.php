<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\MCP\NotionMCPServer;
use App\Services\MCP\MemoryBankMCPServer;

class NotionWebhookController extends Controller
{
    protected NotionMCPServer $notion;
    protected MemoryBankMCPServer $memoryBank;

    public function __construct(
        NotionMCPServer $notion,
        MemoryBankMCPServer $memoryBank
    ) {
        $this->notion = $notion;
        $this->memoryBank = $memoryBank;
    }

    /**
     * Handle incoming Notion webhook
     */
    public function handleWebhook(Request $request)
    {
        // Log incoming webhook
        Log::info('Notion webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);

        // Verify webhook signature if provided
        if (!$this->verifyWebhookSignature($request)) {
            Log::warning('Invalid Notion webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Get webhook data
        $data = $request->all();
        $eventType = $data['type'] ?? null;

        // Store webhook event in memory bank for debugging
        $this->memoryBank->executeTool('store_memory', [
            'key' => 'notion_webhook_' . now()->timestamp,
            'value' => $data,
            'context' => 'webhooks',
            'tags' => ['notion', 'webhook', $eventType],
            'ttl' => 86400 // 24 hours
        ]);

        try {
            switch ($eventType) {
                case 'page.updated':
                    $this->handlePageUpdate($data);
                    break;
                
                case 'page.created':
                    $this->handlePageCreated($data);
                    break;
                
                case 'database.updated':
                    $this->handleDatabaseUpdate($data);
                    break;
                
                case 'block.updated':
                    $this->handleBlockUpdate($data);
                    break;
                
                case 'comment.created':
                    $this->handleCommentCreated($data);
                    break;
                
                default:
                    Log::info('Unhandled Notion webhook event type', ['type' => $eventType]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error processing Notion webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Verify webhook signature
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        // Notion doesn't use webhook signatures yet, but we prepare for it
        // For now, we can verify by API key or IP whitelist
        
        // Option 1: Verify by checking if we can access the resource
        // Option 2: Implement IP whitelist for Notion's servers
        
        return true; // For now, accept all requests
    }

    /**
     * Handle page update event
     */
    protected function handlePageUpdate(array $data)
    {
        $pageId = $data['page']['id'] ?? null;
        if (!$pageId) {
            return;
        }

        // Clear cache for this page
        Cache::forget("notion:page:{$pageId}");

        // Store update event
        $this->memoryBank->executeTool('store_memory', [
            'key' => "page_update_{$pageId}",
            'value' => [
                'page_id' => $pageId,
                'updated_at' => now(),
                'webhook_data' => $data
            ],
            'context' => 'notion_updates',
            'tags' => ['page_update', $pageId]
        ]);

        Log::info('Notion page updated', ['page_id' => $pageId]);
    }

    /**
     * Handle page created event
     */
    protected function handlePageCreated(array $data)
    {
        $pageId = $data['page']['id'] ?? null;
        $parentId = $data['page']['parent']['page_id'] ?? null;

        // If it's a task page, we might want to do something special
        if ($this->isTaskPage($data)) {
            $this->handleNewTask($data);
        }

        Log::info('Notion page created', [
            'page_id' => $pageId,
            'parent_id' => $parentId
        ]);
    }

    /**
     * Handle database update event
     */
    protected function handleDatabaseUpdate(array $data)
    {
        $databaseId = $data['database']['id'] ?? null;
        
        // Clear any cached queries for this database
        $pattern = "notion:database:{$databaseId}:*";
        $keys = Cache::get($pattern);
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Log::info('Notion database updated', ['database_id' => $databaseId]);
    }

    /**
     * Handle block update event
     */
    protected function handleBlockUpdate(array $data)
    {
        $blockId = $data['block']['id'] ?? null;
        $pageId = $data['block']['parent']['page_id'] ?? null;

        // Clear page cache since content changed
        if ($pageId) {
            Cache::forget("notion:page:{$pageId}");
        }

        Log::info('Notion block updated', [
            'block_id' => $blockId,
            'page_id' => $pageId
        ]);
    }

    /**
     * Handle comment created event
     */
    protected function handleCommentCreated(array $data)
    {
        $commentId = $data['comment']['id'] ?? null;
        $pageId = $data['comment']['parent']['page_id'] ?? null;
        $text = $data['comment']['rich_text'][0]['plain_text'] ?? '';

        // Store comment in memory for potential AI response
        $this->memoryBank->executeTool('store_memory', [
            'key' => "comment_{$commentId}",
            'value' => [
                'comment_id' => $commentId,
                'page_id' => $pageId,
                'text' => $text,
                'created_at' => now()
            ],
            'context' => 'notion_comments',
            'tags' => ['comment', 'needs_response'],
            'ttl' => 3600 // 1 hour
        ]);

        Log::info('Notion comment created', [
            'comment_id' => $commentId,
            'page_id' => $pageId,
            'text' => $text
        ]);
    }

    /**
     * Check if page is a task
     */
    protected function isTaskPage(array $data): bool
    {
        // Check if parent is a task database
        $parent = $data['page']['parent'] ?? [];
        if ($parent['type'] === 'database_id') {
            // You can check database properties or maintain a list of task database IDs
            return true;
        }
        
        return false;
    }

    /**
     * Handle new task creation
     */
    protected function handleNewTask(array $data)
    {
        $taskData = [
            'id' => $data['page']['id'],
            'properties' => $data['page']['properties'] ?? [],
            'created_at' => $data['page']['created_time'] ?? now()
        ];

        // Store in memory bank for processing
        $this->memoryBank->executeTool('store_memory', [
            'key' => 'new_task_' . $taskData['id'],
            'value' => $taskData,
            'context' => 'tasks',
            'tags' => ['new', 'unprocessed']
        ]);

        // Could trigger additional actions like:
        // - Send notification
        // - Create related items
        // - Update project status
    }

    /**
     * Test endpoint for webhook configuration
     */
    public function test(Request $request)
    {
        Log::info('Notion webhook test received', $request->all());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Webhook endpoint is working',
            'timestamp' => now()->toIso8601String()
        ]);
    }
}