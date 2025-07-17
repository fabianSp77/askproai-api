<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MemoryBankAutomationService;

class MCPSessionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:session 
                            {action : start, end, status, or context}
                            {--task= : Task description when starting session}
                            {--summary= : Summary when ending session}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage development sessions with Memory Bank';

    protected MemoryBankAutomationService $memoryService;

    /**
     * Create a new command instance.
     */
    public function __construct(MemoryBankAutomationService $memoryService)
    {
        parent::__construct();
        $this->memoryService = $memoryService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'start':
                return $this->startSession();
            
            case 'end':
                return $this->endSession();
            
            case 'status':
                return $this->showStatus();
            
            case 'context':
                return $this->showContext();
            
            default:
                $this->error("Invalid action. Use: start, end, status, or context");
                return 1;
        }
    }

    protected function startSession(): int
    {
        $task = $this->option('task');
        
        $result = $this->memoryService->startSession($task);
        
        $this->info("🚀 Development session started!");
        $this->line("Session ID: <fg=yellow>{$result['session_id']}</>");
        
        if ($result['restored_from']) {
            $this->line("Restored from: {$result['restored_from']}");
        }
        
        if ($result['task']) {
            $this->line("Task: {$result['task']}");
        }
        
        $this->newLine();
        $this->line("💡 Tips:");
        $this->line("  - Use 'php artisan mcp:remember' to save important information");
        $this->line("  - Use 'php artisan mcp:session context' to see current context");
        $this->line("  - Use 'php artisan mcp:session end' when done");
        
        return 0;
    }

    protected function endSession(): int
    {
        $summary = $this->option('summary');
        
        if (!$summary && $this->confirm('Would you like to add a session summary?')) {
            $summary = $this->ask('Enter session summary');
        }
        
        $result = $this->memoryService->endSession($summary);
        
        if (!$result['success']) {
            $this->error($result['error']);
            return 1;
        }
        
        $this->info("✅ Session ended successfully!");
        $this->line("Session ID: {$result['session_id']}");
        $this->line("Duration: {$result['duration']}");
        $this->line("Activities recorded: {$result['activities']}");
        
        return 0;
    }

    protected function showStatus(): int
    {
        $context = $this->memoryService->getCurrentContext();
        
        if (isset($context['error'])) {
            $this->warn("No active session");
            return 0;
        }
        
        $session = $context['session'];
        $this->info("📊 Current Session Status");
        $this->line("Session ID: <fg=yellow>{$session['session_id']}</>");
        $this->line("Started: {$session['started_at']}");
        $this->line("Duration: " . $this->calculateDuration($session['started_at']));
        
        if ($session['task'] ?? null) {
            $this->line("Task: {$session['task']}");
        }
        
        // Show recommendations
        $recommendations = $this->memoryService->getRecommendations();
        if (!empty($recommendations)) {
            $this->newLine();
            $this->line("💡 Recommendations:");
            foreach ($recommendations as $rec) {
                $this->line("  - [{$rec['type']}] {$rec['message']}");
                if ($rec['action']) {
                    $this->line("    → {$rec['action']}");
                }
            }
        }
        
        return 0;
    }

    protected function showContext(): int
    {
        $context = $this->memoryService->getCurrentContext();
        
        if (isset($context['error'])) {
            $this->warn("No active session");
            return 0;
        }
        
        $this->info("🔍 Current Context");
        
        // Current task
        if ($context['current_task']) {
            $this->newLine();
            $this->line("<fg=cyan>Current Task:</>");
            $this->displayData($context['current_task'], 2);
        }
        
        // Recent activities
        if (!empty($context['recent_activities'])) {
            $this->newLine();
            $this->line("<fg=cyan>Recent Activities:</>");
            foreach ($context['recent_activities'] as $activity) {
                $this->line("  - [{$activity['value']['type']}] " . 
                    ($activity['value']['data']['title'] ?? 
                     $activity['value']['data']['content'] ?? 
                     'Activity'));
                $this->line("    " . $activity['value']['timestamp']);
            }
        }
        
        // Recent files
        if (!empty($context['recent_files'])) {
            $this->newLine();
            $this->line("<fg=cyan>Recently Modified Files:</>");
            foreach ($context['recent_files'] as $file) {
                $fileData = $file['value']['data'];
                $this->line("  - {$fileData['file']} ({$fileData['action']})");
            }
        }
        
        // Active bugs
        if (!empty($context['active_bugs'])) {
            $this->newLine();
            $this->line("<fg=cyan>Active Bug Investigations:</>");
            foreach ($context['active_bugs'] as $bug) {
                $bugData = $bug['value'];
                $this->line("  - [{$bugData['bug_id']}] {$bugData['symptom']}");
                $this->line("    Status: {$bugData['status']}");
            }
        }
        
        return 0;
    }

    protected function displayData(array $data, int $indent = 0): void
    {
        $spaces = str_repeat(' ', $indent);
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->line("{$spaces}{$key}:");
                $this->displayData($value, $indent + 2);
            } else {
                $value = is_string($value) ? $value : json_encode($value);
                $this->line("{$spaces}{$key}: {$value}");
            }
        }
    }

    protected function calculateDuration(string $startTime): string
    {
        $start = \Carbon\Carbon::parse($startTime);
        $diff = $start->diff(now());
        
        if ($diff->h > 0) {
            return $diff->h . ' hours, ' . $diff->i . ' minutes';
        }
        
        return $diff->i . ' minutes';
    }
}