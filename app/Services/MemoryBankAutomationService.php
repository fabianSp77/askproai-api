<?php

namespace App\Services;

use App\Services\MCP\MemoryBankMCPServer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class MemoryBankAutomationService
{
    protected MemoryBankMCPServer $memoryBank;
    protected array $config;
    
    public function __construct(MemoryBankMCPServer $memoryBank)
    {
        $this->memoryBank = $memoryBank;
        $this->config = [
            'auto_save_interval' => 300, // 5 minutes
            'session_ttl' => 86400, // 24 hours
            'max_context_size' => 50, // Maximum items per context
        ];
    }
    
    /**
     * Start a new development session
     */
    public function startSession(?string $task = null): array
    {
        $sessionId = 'session_' . now()->format('Y-m-d_His');
        $userId = Auth::id() ?? 'cli_user';
        
        // Check for previous session
        $lastSession = $this->getLastSession();
        
        // Create new session
        $sessionData = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'started_at' => now()->toDateTimeString(),
            'task' => $task,
            'previous_session' => $lastSession ? $lastSession['session_id'] : null,
            'environment' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'git_branch' => $this->getCurrentGitBranch(),
            ]
        ];
        
        // Store session start
        $result = $this->memoryBank->executeTool('store_memory', [
            'key' => 'current_session',
            'value' => $sessionData,
            'context' => 'sessions',
            'tags' => ['active', 'development'],
            'ttl' => $this->config['session_ttl']
        ]);
        
        // Restore context from last session if exists
        if ($lastSession && !$task) {
            $this->restoreContext($lastSession);
        }
        
        Log::info('Development session started', ['session_id' => $sessionId]);
        
        return [
            'session_id' => $sessionId,
            'restored_from' => $lastSession ? $lastSession['session_id'] : null,
            'task' => $task ?? ($lastSession['current_task'] ?? null)
        ];
    }
    
    /**
     * End current session and save summary
     */
    public function endSession(?string $summary = null): array
    {
        $currentSession = $this->getCurrentSession();
        if (!$currentSession) {
            return ['success' => false, 'error' => 'No active session'];
        }
        
        // Collect session data
        $sessionSummary = [
            'session_id' => $currentSession['session_id'],
            'started_at' => $currentSession['started_at'],
            'ended_at' => now()->toDateTimeString(),
            'duration_minutes' => Carbon::parse($currentSession['started_at'])->diffInMinutes(now()),
            'summary' => $summary,
            'activities' => $this->getSessionActivities($currentSession['session_id']),
            'files_modified' => $this->getModifiedFiles(),
            'decisions_made' => $this->getSessionDecisions($currentSession['session_id']),
        ];
        
        // Store session summary
        $this->memoryBank->executeTool('store_memory', [
            'key' => 'session_summary_' . $currentSession['session_id'],
            'value' => $sessionSummary,
            'context' => 'session_history',
            'tags' => ['completed', 'summary']
        ]);
        
        // Clear current session
        $this->memoryBank->executeTool('update_memory', [
            'key' => 'current_session',
            'value' => ['active' => false, 'ended_at' => now()->toDateTimeString()],
            'context' => 'sessions'
        ]);
        
        return [
            'success' => true,
            'session_id' => $currentSession['session_id'],
            'duration' => $sessionSummary['duration_minutes'] . ' minutes',
            'activities' => count($sessionSummary['activities'])
        ];
    }
    
    /**
     * Remember current work context
     */
    public function rememberContext(string $type, array $data, array $tags = []): array
    {
        $session = $this->getCurrentSession();
        $sessionId = $session ? $session['session_id'] : 'no_session';
        
        $contextData = [
            'type' => $type,
            'data' => $data,
            'session_id' => $sessionId,
            'timestamp' => now()->toDateTimeString(),
            'user' => Auth::user()->name ?? 'System',
        ];
        
        $key = $type . '_' . now()->timestamp;
        $defaultTags = [$type, 'context', $sessionId];
        $allTags = array_merge($defaultTags, $tags);
        
        return $this->memoryBank->executeTool('store_memory', [
            'key' => $key,
            'value' => $contextData,
            'context' => 'work_context',
            'tags' => $allTags,
            'ttl' => $this->config['session_ttl']
        ]);
    }
    
    /**
     * Track file modifications
     */
    public function trackFileChange(string $file, string $action, ?string $description = null): void
    {
        $this->rememberContext('file_change', [
            'file' => $file,
            'action' => $action, // created, modified, deleted
            'description' => $description,
            'line_count' => $this->getFileLineCount($file),
        ], ['file_tracking']);
    }
    
    /**
     * Record architectural decision
     */
    public function recordDecision(string $title, string $decision, array $alternatives = [], string $reason = ''): array
    {
        $decisionData = [
            'title' => $title,
            'decision' => $decision,
            'alternatives' => $alternatives,
            'reason' => $reason,
            'date' => now()->toDateString(),
            'recorded_by' => Auth::user()->name ?? 'System',
        ];
        
        $key = 'decision_' . now()->format('Y-m-d') . '_' . \Str::slug($title);
        
        return $this->memoryBank->executeTool('store_memory', [
            'key' => $key,
            'value' => $decisionData,
            'context' => 'decisions',
            'tags' => ['architecture', 'decision', 'permanent']
        ]);
    }
    
    /**
     * Track bug investigation
     */
    public function trackBugInvestigation(string $bugId, string $symptom, array $investigation): array
    {
        $bugData = [
            'bug_id' => $bugId,
            'symptom' => $symptom,
            'investigation' => $investigation,
            'status' => 'investigating',
            'started_at' => now()->toDateTimeString(),
        ];
        
        return $this->memoryBank->executeTool('store_memory', [
            'key' => 'bug_' . $bugId,
            'value' => $bugData,
            'context' => 'bugs',
            'tags' => ['bug', 'investigation', 'active']
        ]);
    }
    
    /**
     * Update bug status
     */
    public function updateBugStatus(string $bugId, string $status, ?string $solution = null): array
    {
        $updates = [
            'status' => $status,
            'updated_at' => now()->toDateTimeString()
        ];
        
        if ($solution) {
            $updates['solution'] = $solution;
            $updates['resolved_at'] = now()->toDateTimeString();
        }
        
        return $this->memoryBank->executeTool('update_memory', [
            'key' => 'bug_' . $bugId,
            'value' => $updates,
            'context' => 'bugs'
        ]);
    }
    
    /**
     * Remember code pattern or snippet
     */
    public function rememberPattern(string $name, string $pattern, string $description, array $useCases = []): array
    {
        $patternData = [
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description,
            'use_cases' => $useCases,
            'language' => $this->detectLanguage($pattern),
            'created_by' => Auth::user()->name ?? 'System',
        ];
        
        return $this->memoryBank->executeTool('store_memory', [
            'key' => 'pattern_' . \Str::slug($name),
            'value' => $patternData,
            'context' => 'patterns',
            'tags' => ['pattern', 'reusable', $patternData['language']]
        ]);
    }
    
    /**
     * Get current context summary
     */
    public function getCurrentContext(): array
    {
        $session = $this->getCurrentSession();
        if (!$session) {
            return ['error' => 'No active session'];
        }
        
        // Get recent activities
        $recentActivities = $this->memoryBank->executeTool('search_memories', [
            'query' => $session['session_id'],
            'context' => 'work_context',
            'limit' => 10
        ]);
        
        // Get current task
        $currentTask = $this->memoryBank->executeTool('retrieve_memory', [
            'key' => 'current_task',
            'context' => 'tasks'
        ]);
        
        // Get recent files
        $recentFiles = $this->memoryBank->executeTool('search_memories', [
            'query' => 'file_change',
            'tags' => [$session['session_id']],
            'limit' => 5
        ]);
        
        return [
            'session' => $session,
            'current_task' => $currentTask['success'] ? $currentTask['data']['value'] : null,
            'recent_activities' => $recentActivities['data']['results'] ?? [],
            'recent_files' => $recentFiles['data']['results'] ?? [],
            'active_bugs' => $this->getActiveBugs(),
        ];
    }
    
    /**
     * Search across all memories
     */
    public function search(string $query, ?string $context = null, array $tags = []): array
    {
        return $this->memoryBank->executeTool('search_memories', [
            'query' => $query,
            'context' => $context,
            'tags' => $tags,
            'limit' => 20
        ]);
    }
    
    /**
     * Export session data
     */
    public function exportSession(string $sessionId, string $format = 'json'): array
    {
        $contexts = ['sessions', 'work_context', 'decisions', 'bugs', 'patterns'];
        
        return $this->memoryBank->executeTool('export_memories', [
            'contexts' => $contexts,
            'format' => $format
        ]);
    }
    
    /**
     * Get recommendations based on context
     */
    public function getRecommendations(): array
    {
        $context = $this->getCurrentContext();
        $recommendations = [];
        
        // Check for uncommitted changes
        if ($this->hasUncommittedChanges()) {
            $recommendations[] = [
                'type' => 'git',
                'message' => 'You have uncommitted changes',
                'action' => 'git commit -m "your message"'
            ];
        }
        
        // Check for active bugs
        if (!empty($context['active_bugs'])) {
            $recommendations[] = [
                'type' => 'bugs',
                'message' => 'You have ' . count($context['active_bugs']) . ' active bug investigations',
                'action' => 'Continue investigating or mark as resolved'
            ];
        }
        
        // Check session duration
        $session = $context['session'];
        if ($session) {
            $duration = Carbon::parse($session['started_at'])->diffInHours(now());
            if ($duration > 4) {
                $recommendations[] = [
                    'type' => 'break',
                    'message' => 'You\'ve been working for ' . round($duration, 1) . ' hours',
                    'action' => 'Consider taking a break'
                ];
            }
        }
        
        return $recommendations;
    }
    
    // Helper methods
    
    protected function getCurrentSession(): ?array
    {
        $result = $this->memoryBank->executeTool('retrieve_memory', [
            'key' => 'current_session',
            'context' => 'sessions'
        ]);
        
        if ($result['success'] && ($result['data']['value']['active'] ?? true)) {
            return $result['data']['value'];
        }
        
        return null;
    }
    
    protected function getLastSession(): ?array
    {
        $sessions = $this->memoryBank->executeTool('search_memories', [
            'query' => 'session_',
            'context' => 'sessions',
            'limit' => 2
        ]);
        
        if ($sessions['success'] && count($sessions['data']['results']) > 1) {
            return $sessions['data']['results'][1]['value'];
        }
        
        return null;
    }
    
    protected function restoreContext(array $lastSession): void
    {
        Log::info('Restoring context from previous session', [
            'session_id' => $lastSession['session_id']
        ]);
        
        // Restore current task if any
        if (isset($lastSession['current_task'])) {
            $this->memoryBank->executeTool('store_memory', [
                'key' => 'current_task',
                'value' => $lastSession['current_task'],
                'context' => 'tasks',
                'tags' => ['restored', 'active']
            ]);
        }
    }
    
    protected function getSessionActivities(string $sessionId): array
    {
        $activities = $this->memoryBank->executeTool('search_memories', [
            'query' => $sessionId,
            'context' => 'work_context',
            'limit' => 100
        ]);
        
        return $activities['data']['results'] ?? [];
    }
    
    protected function getSessionDecisions(string $sessionId): array
    {
        $decisions = $this->memoryBank->executeTool('search_memories', [
            'query' => now()->toDateString(),
            'context' => 'decisions',
            'limit' => 10
        ]);
        
        return array_filter($decisions['data']['results'] ?? [], function($decision) use ($sessionId) {
            return ($decision['value']['session_id'] ?? '') === $sessionId;
        });
    }
    
    protected function getModifiedFiles(): array
    {
        $fileChanges = $this->memoryBank->executeTool('search_memories', [
            'query' => 'file_change',
            'tags' => ['file_tracking'],
            'limit' => 50
        ]);
        
        $files = [];
        foreach ($fileChanges['data']['results'] ?? [] as $change) {
            $file = $change['value']['data']['file'] ?? '';
            if ($file && !in_array($file, $files)) {
                $files[] = $file;
            }
        }
        
        return $files;
    }
    
    protected function getActiveBugs(): array
    {
        $bugs = $this->memoryBank->executeTool('search_memories', [
            'query' => 'investigating',
            'context' => 'bugs',
            'limit' => 10
        ]);
        
        return array_filter($bugs['data']['results'] ?? [], function($bug) {
            return ($bug['value']['status'] ?? '') === 'investigating';
        });
    }
    
    protected function getCurrentGitBranch(): string
    {
        $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null'));
        return $branch ?: 'unknown';
    }
    
    protected function hasUncommittedChanges(): bool
    {
        $status = shell_exec('git status --porcelain 2>/dev/null');
        return !empty(trim($status));
    }
    
    protected function getFileLineCount(string $file): int
    {
        if (file_exists($file)) {
            return count(file($file));
        }
        return 0;
    }
    
    protected function detectLanguage(string $code): string
    {
        if (strpos($code, '<?php') !== false) return 'php';
        if (strpos($code, 'function') !== false && strpos($code, '=>') !== false) return 'javascript';
        if (strpos($code, 'def ') !== false) return 'python';
        if (strpos($code, 'class ') !== false && strpos($code, '{') !== false) return 'java';
        return 'unknown';
    }
}