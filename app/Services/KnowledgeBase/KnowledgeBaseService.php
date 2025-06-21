<?php

namespace App\Services\KnowledgeBase;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeTag;
use App\Models\KnowledgeVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\CommonMarkConverter;

class KnowledgeBaseService
{
    protected DocumentIndexer $indexer;
    protected DocumentProcessor $processor;
    protected SearchService $searchService;
    protected FileWatcher $fileWatcher;
    protected MarkdownEnhancer $markdownEnhancer;
    protected CommonMarkConverter $markdownConverter;
    
    public function __construct(
        DocumentIndexer $indexer,
        DocumentProcessor $processor,
        SearchService $searchService,
        FileWatcher $fileWatcher,
        MarkdownEnhancer $markdownEnhancer
    ) {
        $this->indexer = $indexer;
        $this->processor = $processor;
        $this->searchService = $searchService;
        $this->fileWatcher = $fileWatcher;
        $this->markdownEnhancer = $markdownEnhancer;
        
        // Initialize enhanced markdown converter
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new TaskListExtension());
        
        $this->markdownConverter = new CommonMarkConverter([], $environment);
    }
    
    /**
     * Discover and index all documentation files
     */
    public function discoverDocuments(): array
    {
        $discovered = [];
        $basePaths = [
            base_path(), // Root directory markdown files
            resource_path('docs'), // Docs directory
        ];
        
        foreach ($basePaths as $basePath) {
            if (!File::exists($basePath)) {
                continue;
            }
            
            $files = File::allFiles($basePath);
            
            foreach ($files as $file) {
                if ($file->getExtension() !== 'md') {
                    continue;
                }
                
                // Skip vendor and other non-documentation files
                $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
                if ($this->shouldSkipFile($relativePath)) {
                    continue;
                }
                
                try {
                    $document = $this->indexDocument($file);
                    if ($document) {
                        $discovered[] = $document;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to index document', [
                        'file' => $file->getPathname(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $discovered;
    }
    
    /**
     * Index a single document file
     */
    public function indexDocument(\SplFileInfo $file): ?KnowledgeDocument
    {
        $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
        $content = File::get($file->getPathname());
        $hash = md5($content);
        
        // Check if document already exists
        $document = KnowledgeDocument::where('path', $relativePath)->first();
        
        // Skip if content hasn't changed
        if ($document && $document->hash === $hash) {
            return $document;
        }
        
        // Process the document
        $processed = $this->processor->process($content, $relativePath);
        
        // Create or update document
        $document = KnowledgeDocument::updateOrCreate(
            ['path' => $relativePath],
            [
                'title' => $processed['title'],
                'slug' => Str::slug($processed['title']),
                'excerpt' => $processed['excerpt'],
                'content' => $content,
                'html_content' => $this->markdownEnhancer->enhance($this->markdownConverter->convert($content)),
                'type' => $this->detectDocumentType($relativePath, $content),
                'metadata' => $processed['metadata'],
                'auto_tags' => $processed['tags'],
                'hash' => $hash,
                'last_modified_at' => $file->getMTime(),
                'reading_time' => $this->calculateReadingTime($content),
            ]
        );
        
        // Auto-categorize
        if (!$document->category_id) {
            $category = $this->autoAssignCategory($document);
            if ($category) {
                $document->category_id = $category->id;
                $document->save();
            }
        }
        
        // Create version if content changed
        if ($document->wasChanged('content')) {
            $this->createVersion($document, $content);
        }
        
        // Update search index
        $this->indexer->indexDocument($document);
        
        // Extract and store code snippets
        $this->processor->extractCodeSnippets($document);
        
        // Detect relationships
        $this->processor->detectRelationships($document);
        
        return $document;
    }
    
    /**
     * Search documents
     */
    public function search(string $query, array $filters = []): array
    {
        return $this->searchService->search($query, $filters);
    }
    
    /**
     * Get document by slug
     */
    public function getDocument(string $slug): ?KnowledgeDocument
    {
        $document = KnowledgeDocument::where('slug', $slug)
            ->with(['category', 'tags', 'codeSnippets'])
            ->first();
            
        if ($document) {
            // Track view
            $this->trackAnalytics($document, 'view');
            
            // Increment view count
            $document->increment('views_count');
        }
        
        return $document;
    }
    
    /**
     * Get related documents
     */
    public function getRelatedDocuments(KnowledgeDocument $document, int $limit = 5): array
    {
        $related = $document->relatedDocuments()
            ->where('is_auto_detected', true)
            ->orderBy('strength', 'desc')
            ->limit($limit)
            ->get();
            
        if ($related->count() < $limit) {
            // Fill with documents from same category
            $categoryDocs = KnowledgeDocument::where('category_id', $document->category_id)
                ->where('id', '!=', $document->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->orderBy('views_count', 'desc')
                ->limit($limit - $related->count())
                ->get();
                
            $related = $related->concat($categoryDocs);
        }
        
        return $related->toArray();
    }
    
    /**
     * Get popular documents
     */
    public function getPopularDocuments(int $limit = 10): array
    {
        return KnowledgeDocument::where('status', 'published')
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    /**
     * Get recent documents
     */
    public function getRecentDocuments(int $limit = 10): array
    {
        return KnowledgeDocument::where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    /**
     * Get categories with document count
     */
    public function getCategories(): array
    {
        return KnowledgeCategory::withCount('documents')
            ->where('is_visible', true)
            ->orderBy('order')
            ->get()
            ->toArray();
    }
    
    /**
     * Get tags with usage count
     */
    public function getTags(int $limit = 50): array
    {
        return KnowledgeTag::orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    /**
     * Start file watching for real-time updates
     */
    public function startWatching(): void
    {
        $this->fileWatcher->watch([
            base_path() . '/*.md',
            resource_path('docs') . '/**/*.md',
        ], function ($event, $path) {
            Log::info('Document changed', ['event' => $event, 'path' => $path]);
            
            if ($event === 'deleted') {
                $this->removeDocument($path);
            } else {
                $file = new \SplFileInfo($path);
                $this->indexDocument($file);
            }
        });
    }
    
    /**
     * Track analytics event
     */
    protected function trackAnalytics(KnowledgeDocument $document, string $eventType, array $data = []): void
    {
        DB::table('knowledge_analytics')->insert([
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'event_type' => $eventType,
            'event_data' => json_encode($data),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    /**
     * Create document version
     */
    protected function createVersion(KnowledgeDocument $document, string $content): void
    {
        $lastVersion = $document->versions()->orderBy('version_number', 'desc')->first();
        $versionNumber = $lastVersion ? $lastVersion->version_number + 1 : 1;
        
        KnowledgeVersion::create([
            'document_id' => $document->id,
            'version_number' => $versionNumber,
            'content' => $content,
            'diff' => $lastVersion ? $this->processor->generateDiff($lastVersion->content, $content) : null,
            'commit_message' => 'Auto-indexed from file system',
            'created_by' => auth()->id(),
        ]);
    }
    
    /**
     * Auto-assign category based on content and path
     */
    protected function autoAssignCategory(KnowledgeDocument $document): ?KnowledgeCategory
    {
        // Try to determine from path
        $pathParts = explode('/', $document->path);
        $potentialCategoryName = null;
        
        if (str_contains($document->path, 'docs/')) {
            $potentialCategoryName = $pathParts[1] ?? null;
        } else {
            // Try to extract from filename patterns
            if (preg_match('/^(.*?)_/', basename($document->path), $matches)) {
                $potentialCategoryName = $matches[1];
            }
        }
        
        if ($potentialCategoryName) {
            $category = KnowledgeCategory::where('slug', Str::slug($potentialCategoryName))->first();
            if ($category) {
                return $category;
            }
        }
        
        // Use AI to categorize based on content
        return $this->processor->suggestCategory($document);
    }
    
    /**
     * Detect document type from path and content
     */
    protected function detectDocumentType(string $path, string $content): string
    {
        // API documentation
        if (str_contains(strtolower($path), 'api') || str_contains($content, 'endpoint')) {
            return 'api';
        }
        
        // Installation/setup guides
        if (str_contains(strtolower($path), 'install') || str_contains(strtolower($path), 'setup')) {
            return 'guide';
        }
        
        // Technical specifications
        if (str_contains(strtolower($path), 'spec') || str_contains(strtolower($path), 'technical')) {
            return 'specification';
        }
        
        // Status reports
        if (str_contains(strtolower($path), 'status') || str_contains(strtolower($path), 'report')) {
            return 'report';
        }
        
        return 'markdown';
    }
    
    /**
     * Calculate reading time for document
     */
    protected function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        $readingSpeed = 200; // Average words per minute
        
        return max(1, ceil($wordCount / $readingSpeed));
    }
    
    /**
     * Check if file should be skipped
     */
    protected function shouldSkipFile(string $path): bool
    {
        $skipPatterns = [
            'vendor/',
            'node_modules/',
            'storage/',
            '.git/',
            'tests/',
            'bootstrap/',
            'public/',
        ];
        
        foreach ($skipPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Remove document from index
     */
    protected function removeDocument(string $path): void
    {
        $relativePath = str_replace(base_path() . '/', '', $path);
        $document = KnowledgeDocument::where('path', $relativePath)->first();
        
        if ($document) {
            $document->delete();
            Log::info('Document removed from index', ['path' => $relativePath]);
        }
    }
}