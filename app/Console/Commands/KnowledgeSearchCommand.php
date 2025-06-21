<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KnowledgeBase\KnowledgeBaseService;

class KnowledgeSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'knowledge:search 
                            {query : Search query}
                            {--limit=10 : Maximum results}
                            {--type= : Filter by document type}
                            {--category= : Filter by category}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search the knowledge base from command line';

    protected KnowledgeBaseService $knowledgeService;

    public function __construct(KnowledgeBaseService $knowledgeService)
    {
        parent::__construct();
        $this->knowledgeService = $knowledgeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->argument('query');
        $limit = (int) $this->option('limit');
        
        $filters = [];
        if ($type = $this->option('type')) {
            $filters['type'] = $type;
        }
        if ($category = $this->option('category')) {
            $filters['category'] = $category;
        }

        $this->info("Searching for: {$query}");
        
        $results = $this->knowledgeService->search($query, $filters);
        $results = array_slice($results, 0, $limit);

        if (empty($results)) {
            $this->warn('No results found.');
            return 0;
        }

        $this->info("Found " . count($results) . " results:");
        $this->newLine();

        foreach ($results as $index => $result) {
            $this->line("<options=bold>" . ($index + 1) . ". {$result['title']}</>");
            $this->line("   <fg=gray>Type: {$result['type']} | Views: {$result['views_count']} | Score: " . round($result['relevance_score'], 2) . "</>");
            
            if ($result['excerpt']) {
                $excerpt = strip_tags($result['excerpt']);
                $this->line("   " . \Str::limit($excerpt, 150));
            }
            
            if ($result['category']) {
                $this->line("   <fg=cyan>Category: {$result['category']['name']}</>");
            }
            
            if (!empty($result['tags'])) {
                $tags = collect($result['tags'])->pluck('name')->implode(', ');
                $this->line("   <fg=yellow>Tags: {$tags}</>");
            }
            
            $this->newLine();
        }

        // Ask if user wants to open a document
        if ($this->confirm('Would you like to view one of these documents?')) {
            $choice = $this->ask('Enter the number of the document to view');
            
            if (is_numeric($choice) && isset($results[$choice - 1])) {
                $document = $results[$choice - 1];
                $this->viewDocument($document);
            } else {
                $this->error('Invalid selection.');
            }
        }

        return 0;
    }

    /**
     * View a document in the terminal
     */
    protected function viewDocument(array $document): void
    {
        $this->newLine();
        $this->line('<options=bold,underscore>' . $document['title'] . '</>');
        $this->newLine();

        // Fetch full document
        $fullDoc = \App\Models\KnowledgeDocument::where('slug', $document['slug'])->first();
        
        if (!$fullDoc) {
            $this->error('Could not load document.');
            return;
        }

        // Display content with basic formatting
        $content = $fullDoc->content;
        
        // Convert headers
        $content = preg_replace('/^# (.+)$/m', "\n<options=bold,underscore>$1</>\n", $content);
        $content = preg_replace('/^## (.+)$/m', "\n<options=bold>$1</>\n", $content);
        $content = preg_replace('/^### (.+)$/m', "\n<options=underscore>$1</>\n", $content);
        
        // Convert code blocks
        $content = preg_replace('/```(\w+)?\n([\s\S]*?)```/m', "\n<bg=gray;fg=white>$2</>\n", $content);
        
        // Convert inline code
        $content = preg_replace('/`([^`]+)`/', '<options=bold>$1</>', $content);
        
        // Convert bold
        $content = preg_replace('/\*\*([^*]+)\*\*/', '<options=bold>$1</>', $content);
        
        // Convert links
        $content = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '$1 <fg=blue>($2)</>', $content);
        
        $this->line($content);
        
        $this->newLine();
        $this->info('---');
        $this->line('<fg=gray>Reading time: ' . $fullDoc->reading_time . ' minutes</>');
        $this->line('<fg=gray>Last updated: ' . $fullDoc->updated_at->diffForHumans() . '</>');
        $this->line('<fg=gray>Views: ' . number_format($fullDoc->views_count) . '</>');
    }
}