<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeCodeSnippet;
use App\Models\KnowledgeComment;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class KnowledgeApiController extends Controller
{
    protected KnowledgeBaseService $knowledgeService;
    
    public function __construct(KnowledgeBaseService $knowledgeService)
    {
        $this->knowledgeService = $knowledgeService;
    }
    
    /**
     * Search documents
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'limit' => 'integer|min:1|max:50',
            'filters' => 'array',
        ]);
        
        $query = $request->input('q');
        $limit = $request->input('limit', 20);
        $filters = $request->input('filters', []);
        
        $results = $this->knowledgeService->search($query, $filters);
        
        // Limit results
        $results = array_slice($results, 0, $limit);
        
        return response()->json([
            'query' => $query,
            'results' => $results,
            'count' => count($results),
        ]);
    }
    
    /**
     * Get document by slug
     */
    public function show($slug)
    {
        $document = KnowledgeDocument::where('slug', $slug)
            ->with(['category', 'tags', 'codeSnippets'])
            ->published()
            ->first();
        
        if (!$document) {
            return response()->json(['error' => 'Document not found'], 404);
        }
        
        // Track view
        $this->knowledgeService->trackAnalytics($document, 'api_view');
        
        return response()->json([
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'slug' => $document->slug,
                'excerpt' => $document->excerpt,
                'content' => $document->content,
                'html_content' => $document->html_content,
                'type' => $document->type,
                'reading_time' => $document->reading_time,
                'views_count' => $document->views_count,
                'category' => $document->category ? [
                    'id' => $document->category->id,
                    'name' => $document->category->name,
                    'slug' => $document->category->slug,
                ] : null,
                'tags' => $document->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ];
                }),
                'code_snippets' => $document->codeSnippets->map(function ($snippet) {
                    return [
                        'id' => $snippet->id,
                        'language' => $snippet->language,
                        'title' => $snippet->title,
                        'code' => $snippet->code,
                        'is_executable' => $snippet->is_executable,
                    ];
                }),
                'created_at' => $document->created_at->toIso8601String(),
                'updated_at' => $document->updated_at->toIso8601String(),
            ],
        ]);
    }
    
    /**
     * Execute code snippet
     */
    public function executeCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10000',
            'language' => 'required|string|in:bash,shell,curl,javascript,js,sql',
        ]);
        
        $code = $request->input('code');
        $language = $request->input('language');
        
        // Security checks
        if (!$this->isCodeSafe($code, $language)) {
            return response()->json([
                'success' => false,
                'error' => 'Code contains potentially dangerous operations',
            ], 400);
        }
        
        try {
            $result = $this->executeCodeSafely($code, $language);
            
            // Track execution
            if (auth()->check()) {
                Log::info('Code executed', [
                    'user_id' => auth()->id(),
                    'language' => $language,
                    'code_length' => strlen($code),
                ]);
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Code execution failed', [
                'error' => $e->getMessage(),
                'language' => $language,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Execution failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get real-time updates via Server-Sent Events
     */
    public function stream()
    {
        return response()->stream(function () {
            $lastCheck = time();
            
            while (true) {
                // Check for updates every 5 seconds
                if (time() - $lastCheck >= 5) {
                    $updates = $this->checkForUpdates($lastCheck);
                    
                    if (!empty($updates)) {
                        echo "data: " . json_encode($updates) . "\n\n";
                        ob_flush();
                        flush();
                    }
                    
                    $lastCheck = time();
                }
                
                // Send heartbeat every 30 seconds
                if (time() % 30 == 0) {
                    echo ": heartbeat\n\n";
                    ob_flush();
                    flush();
                }
                
                usleep(100000); // Sleep for 100ms
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
    
    /**
     * Add comment to document
     */
    public function addComment(Request $request, $documentId)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:knowledge_comments,id',
            'position' => 'nullable|array',
        ]);
        
        $document = KnowledgeDocument::findOrFail($documentId);
        
        $comment = KnowledgeComment::create([
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'parent_id' => $request->input('parent_id'),
            'content' => $request->input('content'),
            'position' => $request->input('position'),
            'status' => KnowledgeComment::STATUS_ACTIVE,
        ]);
        
        // Load relationships
        $comment->load('user', 'replies');
        
        // Track comment event
        $this->knowledgeService->trackAnalytics($document, 'comment', [
            'comment_id' => $comment->id,
        ]);
        
        return response()->json([
            'comment' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'formatted_content' => $comment->formatted_content,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'avatar' => $comment->user->avatar_url ?? null,
                ],
                'position' => $comment->position,
                'status' => $comment->status,
                'created_at' => $comment->created_at->toIso8601String(),
            ],
        ]);
    }
    
    /**
     * Get comments for document
     */
    public function getComments($documentId)
    {
        $document = KnowledgeDocument::findOrFail($documentId);
        
        $comments = $document->comments()
            ->with(['user', 'replies.user'])
            ->active()
            ->root()
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'comments' => $comments->map(function ($comment) {
                return $this->formatComment($comment);
            }),
        ]);
    }
    
    /**
     * Copy code snippet
     */
    public function copyCode($snippetId)
    {
        $snippet = KnowledgeCodeSnippet::findOrFail($snippetId);
        
        // Increment usage count
        $snippet->incrementUsage();
        
        // Track copy event
        $this->knowledgeService->trackAnalytics($snippet->document, 'copy_code', [
            'snippet_id' => $snippet->id,
            'language' => $snippet->language,
        ]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Check if code is safe to execute
     */
    protected function isCodeSafe(string $code, string $language): bool
    {
        $dangerousPatterns = [
            // File system operations
            'rm\s+-rf',
            'rm\s+/',
            'format\s+c:',
            'del\s+/f',
            
            // Database operations
            'drop\s+database',
            'drop\s+table',
            'truncate\s+table',
            'delete\s+from',
            
            // System commands
            'sudo',
            'chmod\s+777',
            'eval\(',
            'exec\(',
            'system\(',
            '`.*`',
            '\$\(.*\)',
            
            // Network operations (except allowed curl)
            'wget\s+--post-file',
            'nc\s+-l',
            
            // Process operations
            'kill\s+-9',
            'killall',
            'pkill',
        ];
        
        $lowerCode = strtolower($code);
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $lowerCode)) {
                return false;
            }
        }
        
        // Additional checks for specific languages
        if ($language === 'sql') {
            // Only allow SELECT queries
            if (!preg_match('/^\s*select/i', trim($code))) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Execute code in a safe environment
     */
    protected function executeCodeSafely(string $code, string $language): array
    {
        switch ($language) {
            case 'bash':
            case 'shell':
            case 'curl':
                return $this->executeBash($code);
                
            case 'javascript':
            case 'js':
                return $this->executeJavaScript($code);
                
            case 'sql':
                return $this->executeSql($code);
                
            default:
                throw new \Exception('Unsupported language: ' . $language);
        }
    }
    
    /**
     * Execute bash/shell commands
     */
    protected function executeBash(string $code): array
    {
        // Only allow specific commands
        $allowedCommands = ['echo', 'cat', 'ls', 'pwd', 'date', 'curl', 'grep', 'awk', 'sed'];
        
        $firstCommand = explode(' ', trim($code))[0];
        if (!in_array($firstCommand, $allowedCommands)) {
            throw new \Exception('Command not allowed: ' . $firstCommand);
        }
        
        // Execute with timeout
        $result = Process::timeout(5)->run($code);
        
        return [
            'success' => $result->successful(),
            'output' => $result->successful() ? $result->output() : $result->errorOutput(),
            'exit_code' => $result->exitCode(),
        ];
    }
    
    /**
     * Execute JavaScript code
     */
    protected function executeJavaScript(string $code): array
    {
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'js_');
        file_put_contents($tempFile, $code);
        
        try {
            // Execute with Node.js
            $result = Process::timeout(3)->run("node {$tempFile}");
            
            return [
                'success' => $result->successful(),
                'output' => $result->successful() ? $result->output() : $result->errorOutput(),
            ];
        } finally {
            unlink($tempFile);
        }
    }
    
    /**
     * Execute SQL queries (read-only)
     */
    protected function executeSql(string $code): array
    {
        // Only allow SELECT queries
        if (!preg_match('/^\s*select/i', trim($code))) {
            throw new \Exception('Only SELECT queries are allowed');
        }
        
        try {
            // Execute on a read-only connection
            $results = \DB::connection('readonly')->select($code);
            
            return [
                'success' => true,
                'output' => json_encode($results, JSON_PRETTY_PRINT),
                'rows' => count($results),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Check for document updates
     */
    protected function checkForUpdates($since): array
    {
        $updates = [];
        
        // Check for new/updated documents
        $documents = KnowledgeDocument::where('updated_at', '>', date('Y-m-d H:i:s', $since))
            ->published()
            ->get();
        
        foreach ($documents as $doc) {
            $updates[] = [
                'type' => 'document_updated',
                'document' => [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'slug' => $doc->slug,
                ],
                'timestamp' => $doc->updated_at->timestamp,
            ];
        }
        
        return $updates;
    }
    
    /**
     * Format comment for response
     */
    protected function formatComment($comment): array
    {
        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'formatted_content' => $comment->formatted_content,
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
                'avatar' => $comment->user->avatar_url ?? null,
            ],
            'position' => $comment->position,
            'status' => $comment->status,
            'created_at' => $comment->created_at->toIso8601String(),
            'replies' => $comment->replies->map(function ($reply) {
                return $this->formatComment($reply);
            }),
        ];
    }
}