<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MemoryBankAutomationService;

class MCPRememberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:remember 
                            {type : Type of memory (task, decision, bug, pattern, note)}
                            {content : Content to remember}
                            {--tags=* : Additional tags}
                            {--description= : Additional description}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remember something in the Memory Bank';

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
        $type = $this->argument('type');
        $content = $this->argument('content');
        $tags = $this->option('tags') ?: [];
        $description = $this->option('description');

        $this->info("Remembering {$type}...");

        try {
            switch ($type) {
                case 'task':
                    $result = $this->rememberTask($content, $description, $tags);
                    break;
                
                case 'decision':
                    $result = $this->rememberDecision($content, $description, $tags);
                    break;
                
                case 'bug':
                    $result = $this->rememberBug($content, $description, $tags);
                    break;
                
                case 'pattern':
                    $result = $this->rememberPattern($content, $description, $tags);
                    break;
                
                case 'note':
                default:
                    $result = $this->rememberNote($content, $description, $tags);
                    break;
            }

            if ($result['success'] ?? false) {
                $this->info("✅ Successfully remembered {$type}!");
                $this->line("Key: " . ($result['data']['key'] ?? 'N/A'));
                $this->line("Context: " . ($result['data']['context'] ?? 'N/A'));
                
                if (!empty($tags)) {
                    $this->line("Tags: " . implode(', ', $tags));
                }
            } else {
                $this->error("Failed to remember: " . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function rememberTask(string $content, ?string $description, array $tags): array
    {
        return $this->memoryService->rememberContext('task', [
            'title' => $content,
            'description' => $description,
            'status' => 'active',
            'created_at' => now()->toDateTimeString()
        ], array_merge($tags, ['task', 'todo']));
    }

    protected function rememberDecision(string $content, ?string $description, array $tags): array
    {
        // Parse decision format: "title: decision"
        $parts = explode(':', $content, 2);
        $title = trim($parts[0]);
        $decision = isset($parts[1]) ? trim($parts[1]) : $title;

        return $this->memoryService->recordDecision(
            $title,
            $decision,
            [], // alternatives can be added via interactive mode later
            $description ?? ''
        );
    }

    protected function rememberBug(string $content, ?string $description, array $tags): array
    {
        // Parse bug format: "bug-id: symptom"
        $parts = explode(':', $content, 2);
        $bugId = trim($parts[0]);
        $symptom = isset($parts[1]) ? trim($parts[1]) : $content;

        return $this->memoryService->trackBugInvestigation(
            $bugId,
            $symptom,
            ['initial_description' => $description ?? '']
        );
    }

    protected function rememberPattern(string $content, ?string $description, array $tags): array
    {
        // For patterns, content is the pattern name
        $this->info("Enter the pattern code (press Ctrl+D when done):");
        $patternCode = '';
        
        while ($line = fgets(STDIN)) {
            $patternCode .= $line;
        }

        return $this->memoryService->rememberPattern(
            $content,
            trim($patternCode),
            $description ?? '',
            $tags
        );
    }

    protected function rememberNote(string $content, ?string $description, array $tags): array
    {
        return $this->memoryService->rememberContext('note', [
            'content' => $content,
            'description' => $description,
            'timestamp' => now()->toDateTimeString()
        ], array_merge($tags, ['note']));
    }
}