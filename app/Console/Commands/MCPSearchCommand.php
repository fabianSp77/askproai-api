<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MemoryBankAutomationService;

class MCPSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:search 
                            {query : Search query}
                            {--context= : Specific context to search in}
                            {--tags=* : Filter by tags}
                            {--limit=10 : Maximum results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search through Memory Bank memories';

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
        $query = $this->argument('query');
        $context = $this->option('context');
        $tags = $this->option('tags') ?: [];
        $limit = (int) $this->option('limit');

        $this->info("Searching for: {$query}");
        
        if ($context) {
            $this->line("In context: {$context}");
        }
        
        if (!empty($tags)) {
            $this->line("With tags: " . implode(', ', $tags));
        }

        $result = $this->memoryService->search($query, $context, $tags);

        if (!$result['success']) {
            $this->error("Search failed: " . ($result['error'] ?? 'Unknown error'));
            return 1;
        }

        $results = $result['data']['results'] ?? [];
        $count = min(count($results), $limit);

        if ($count === 0) {
            $this->warn("No results found.");
            return 0;
        }

        $this->info("Found {$count} result(s):");
        $this->newLine();

        foreach (array_slice($results, 0, $limit) as $index => $memory) {
            $this->displayMemory($index + 1, $memory);
        }

        if (count($results) > $limit) {
            $this->newLine();
            $this->line("... and " . (count($results) - $limit) . " more results.");
        }

        return 0;
    }

    protected function displayMemory(int $index, array $memory): void
    {
        $this->line("<fg=cyan>[{$index}]</> <fg=yellow>{$memory['key']}</>");
        $this->line("   Context: <fg=green>{$memory['context']}</>");
        
        if (!empty($memory['tags'])) {
            $this->line("   Tags: " . implode(', ', $memory['tags']));
        }
        
        $value = $memory['value'];
        if (is_array($value)) {
            $this->displayValue($value, 3);
        } else {
            $this->line("   Value: {$value}");
        }
        
        if (isset($memory['relevance'])) {
            $this->line("   Relevance: " . round($memory['relevance'], 2));
        }
        
        $this->newLine();
    }

    protected function displayValue(array $value, int $indent = 0): void
    {
        $spaces = str_repeat(' ', $indent);
        
        foreach ($value as $key => $val) {
            if (is_array($val)) {
                $this->line("{$spaces}{$key}:");
                $this->displayValue($val, $indent + 3);
            } else {
                $val = is_string($val) ? $val : json_encode($val);
                if (strlen($val) > 80) {
                    $val = substr($val, 0, 77) . '...';
                }
                $this->line("{$spaces}{$key}: {$val}");
            }
        }
    }
}