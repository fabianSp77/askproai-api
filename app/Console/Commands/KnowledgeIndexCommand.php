<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KnowledgeBaseService;
use App\Services\FileWatcherService;
use App\Models\KnowledgeCategory;

class KnowledgeIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'knowledge:index 
                            {--path=* : Specific paths to index} 
                            {--force : Force re-index all files}
                            {--create-categories : Create default categories}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index documentation files for the knowledge base';

    protected KnowledgeBaseService $knowledgeService;
    protected FileWatcherService $fileWatcher;

    /**
     * Create a new command instance.
     */
    public function __construct(KnowledgeBaseService $knowledgeService, FileWatcherService $fileWatcher)
    {
        parent::__construct();
        $this->knowledgeService = $knowledgeService;
        $this->fileWatcher = $fileWatcher;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting knowledge base indexing...');
        
        // Create default categories if requested
        if ($this->option('create-categories')) {
            $this->createDefaultCategories();
        }
        
        // Get paths to index
        $paths = $this->option('path');
        if (empty($paths)) {
            $paths = config('knowledge.watch_paths', []);
        }
        
        $this->info('Indexing paths: ' . implode(', ', $paths));
        
        // Force re-index if requested
        if ($this->option('force')) {
            $this->warn('Force re-indexing all files...');
            $result = $this->fileWatcher->forceReindex();
        } else {
            $result = $this->knowledgeService->discoverAndIndexDocuments($paths);
        }
        
        // Display results
        $this->info("\nIndexing complete!");
        $this->info("Total files indexed: {$result['total']}");
        
        if (!empty($result['indexed'])) {
            $this->info("\nIndexed files:");
            foreach ($result['indexed'] as $file) {
                $this->line("  ✓ {$file}");
            }
        }
        
        if (!empty($result['errors'])) {
            $this->error("\nErrors encountered:");
            foreach ($result['errors'] as $error) {
                $this->error("  ✗ {$error['file']}: {$error['error']}");
            }
        }
        
        // Display summary
        $this->table(
            ['Metric', 'Count'],
            [
                ['Documents', \App\Models\KnowledgeDocument::count()],
                ['Categories', \App\Models\KnowledgeCategory::count()],
                ['Tags', \App\Models\KnowledgeTag::count()],
                ['Search Index Entries', \App\Models\KnowledgeSearchIndex::count()],
            ]
        );
        
        return self::SUCCESS;
    }
    
    /**
     * Create default categories
     */
    protected function createDefaultCategories(): void
    {
        $this->info('Creating default categories...');
        
        $categories = config('knowledge.default_categories', []);
        
        foreach ($categories as $categoryData) {
            $category = KnowledgeCategory::firstOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'name' => $categoryData['name'],
                    'icon' => $categoryData['icon'] ?? null,
                    'description' => $categoryData['description'] ?? null,
                    'order' => $categoryData['order'] ?? 0,
                ]
            );
            
            $this->info("  ✓ Created/Updated category: {$category->name}");
        }
    }
}