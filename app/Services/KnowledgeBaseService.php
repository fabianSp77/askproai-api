<?php

namespace App\Services;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeTag;
use App\Models\KnowledgeSearchIndex;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use Symfony\Component\Finder\Finder;

class KnowledgeBaseService
{
    protected CommonMarkConverter $markdown;
    protected array $indexedFiles = [];
    protected array $errors = [];
    
    public function __construct()
    {
        $this->markdown = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]);
        
        $this->markdown->getEnvironment()->addExtension(new GithubFlavoredMarkdownExtension());
        $this->markdown->getEnvironment()->addExtension(new TableExtension());
        $this->markdown->getEnvironment()->addExtension(new TaskListExtension());
        $this->markdown->getEnvironment()->addExtension(new AttributesExtension());
    }
    
    /**
     * Discover and index all documentation files
     */
    public function discoverAndIndexDocuments(array $paths = []): array
    {
        $this->indexedFiles = [];
        $this->errors = [];
        
        if (empty($paths)) {
            $paths = [
                base_path('*.md'),
                base_path('docs'),
                base_path('resources/docs'),
            ];
        }
        
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->indexDirectory($path);
            } elseif (is_file($path)) {
                $this->indexFile($path);
            } else {
                // Handle glob pattern
                $files = glob($path);
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $this->indexFile($file);
                    }
                }
            }
        }
        
        return [
            'indexed' => $this->indexedFiles,
            'errors' => $this->errors,
            'total' => count($this->indexedFiles),
        ];
    }
    
    /**
     * Index a directory of documentation files
     */
    protected function indexDirectory(string $directory): void
    {
        $finder = new Finder();
        $finder->files()
            ->in($directory)
            ->name(['*.md', '*.markdown'])
            ->sortByName();
            
        foreach ($finder as $file) {
            $this->indexFile($file->getRealPath());
        }
    }
    
    /**
     * Index a single documentation file
     */
    public function indexFile(string $filePath): ?KnowledgeDocument
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }
            
            $relativePath = $this->getRelativePath($filePath);
            $content = file_get_contents($filePath);
            $fileModifiedAt = filemtime($filePath);
            
            // Extract metadata from content
            $metadata = $this->extractMetadata($content);
            $rawContent = $this->stripMetadata($content);
            
            // Parse title and content
            $title = $metadata['title'] ?? $this->extractTitle($rawContent, $filePath);
            $slug = $metadata['slug'] ?? Str::slug($title);
            
            // Check if document already exists
            $document = KnowledgeDocument::where('file_path', $relativePath)->first();
            
            // Skip if file hasn't been modified
            if ($document && $document->file_modified_at && 
                $document->file_modified_at->timestamp >= $fileModifiedAt) {
                return $document;
            }
            
            // Create or update document
            $document = KnowledgeDocument::updateOrCreate(
                ['file_path' => $relativePath],
                [
                    'title' => $title,
                    'slug' => $this->ensureUniqueSlug($slug, $document?->id),
                    'excerpt' => $this->generateExcerpt($rawContent),
                    'content' => $this->markdown->convert($rawContent)->getContent(),
                    'raw_content' => $rawContent,
                    'file_type' => 'markdown',
                    'category_id' => $this->determineCategoryId($filePath, $metadata),
                    'metadata' => array_merge($metadata, [
                        'file_size' => filesize($filePath),
                        'word_count' => str_word_count($rawContent),
                        'reading_time' => $this->calculateReadingTime($rawContent),
                    ]),
                    'status' => $metadata['status'] ?? 'published',
                    'file_modified_at' => $fileModifiedAt,
                    'last_indexed_at' => now(),
                ]
            );
            
            // Process tags
            $this->processTags($document, $metadata['tags'] ?? []);
            
            // Index for search
            $this->indexForSearch($document);
            
            // Find related documents
            $this->findRelatedDocuments($document);
            
            $this->indexedFiles[] = $relativePath;
            
            return $document;
            
        } catch (\Exception $e) {
            $this->errors[] = [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ];
            Log::error('Knowledge base indexing error', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Extract front matter metadata from markdown content
     */
    protected function extractMetadata(string $content): array
    {
        $metadata = [];
        
        // Check for YAML front matter
        if (preg_match('/^---\n(.*?)\n---/s', $content, $matches)) {
            try {
                $yaml = yaml_parse($matches[1]);
                if (is_array($yaml)) {
                    $metadata = $yaml;
                }
            } catch (\Exception $e) {
                // Ignore YAML parse errors
            }
        }
        
        return $metadata;
    }
    
    /**
     * Strip front matter from content
     */
    protected function stripMetadata(string $content): string
    {
        return preg_replace('/^---\n.*?\n---\n/s', '', $content);
    }
    
    /**
     * Extract title from content or filename
     */
    protected function extractTitle(string $content, string $filePath): string
    {
        // Try to extract from first heading
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        
        // Fall back to filename
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        return Str::title(str_replace(['-', '_'], ' ', $filename));
    }
    
    /**
     * Generate excerpt from content
     */
    protected function generateExcerpt(string $content): string
    {
        // Remove headings
        $text = preg_replace('/^#+\s+.+$/m', '', $content);
        
        // Remove code blocks
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        $text = preg_replace('/`[^`]+`/', '', $text);
        
        // Remove links but keep text
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);
        
        // Clean up
        $text = strip_tags($text);
        $text = trim(preg_replace('/\s+/', ' ', $text));
        
        return Str::limit($text, 300);
    }
    
    /**
     * Calculate reading time in minutes
     */
    protected function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count($content);
        $wordsPerMinute = 200; // Average reading speed
        
        return max(1, ceil($wordCount / $wordsPerMinute));
    }
    
    /**
     * Ensure slug is unique
     */
    protected function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $count = 1;
        
        while (true) {
            $query = KnowledgeDocument::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            if (!$query->exists()) {
                break;
            }
            
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        
        return $slug;
    }
    
    /**
     * Get relative path from base path
     */
    protected function getRelativePath(string $filePath): string
    {
        $basePath = base_path();
        if (str_starts_with($filePath, $basePath)) {
            return ltrim(substr($filePath, strlen($basePath)), '/\\');
        }
        return $filePath;
    }
    
    /**
     * Determine category ID based on file path and metadata
     */
    protected function determineCategoryId(string $filePath, array $metadata): ?int
    {
        // Check metadata first
        if (!empty($metadata['category'])) {
            $category = KnowledgeCategory::where('slug', Str::slug($metadata['category']))->first();
            if ($category) {
                return $category->id;
            }
        }
        
        // Try to determine from file path
        $relativePath = $this->getRelativePath($filePath);
        $pathParts = explode('/', dirname($relativePath));
        
        // Skip if in root directory
        if (empty($pathParts) || $pathParts[0] === '.') {
            return null;
        }
        
        // Find or create category based on directory
        $parentId = null;
        foreach ($pathParts as $part) {
            if (empty($part) || $part === '.') {
                continue;
            }
            
            $slug = Str::slug($part);
            $name = Str::title(str_replace(['-', '_'], ' ', $part));
            
            $category = KnowledgeCategory::firstOrCreate(
                ['slug' => $slug, 'parent_id' => $parentId],
                ['name' => $name, 'order' => 0]
            );
            
            $parentId = $category->id;
        }
        
        return $parentId;
    }
    
    /**
     * Process and attach tags to document
     */
    protected function processTags(KnowledgeDocument $document, array $tags): void
    {
        if (empty($tags)) {
            // Auto-generate tags from content
            $tags = $this->extractAutoTags($document->raw_content);
        }
        
        $tagIds = [];
        foreach ($tags as $tagName) {
            $slug = Str::slug($tagName);
            $tag = KnowledgeTag::firstOrCreate(
                ['slug' => $slug],
                ['name' => $tagName, 'color' => $this->generateTagColor($tagName)]
            );
            $tagIds[] = $tag->id;
        }
        
        $document->tags()->sync($tagIds);
    }
    
    /**
     * Extract auto tags from content
     */
    protected function extractAutoTags(string $content): array
    {
        $tags = [];
        
        // Extract technology mentions
        $technologies = [
            'laravel', 'php', 'mysql', 'redis', 'docker', 'nginx', 'apache',
            'javascript', 'vue', 'react', 'tailwind', 'alpine', 'livewire',
            'filament', 'horizon', 'echo', 'pusher', 'websocket',
            'api', 'rest', 'graphql', 'webhook', 'oauth', 'jwt',
            'stripe', 'paypal', 'aws', 'digitalocean', 'kubernetes',
        ];
        
        $lowerContent = strtolower($content);
        foreach ($technologies as $tech) {
            if (str_contains($lowerContent, $tech)) {
                $tags[] = $tech;
            }
        }
        
        // Extract from headings
        if (preg_match_all('/^##\s+(.+)$/m', $content, $matches)) {
            foreach ($matches[1] as $heading) {
                $words = explode(' ', strtolower($heading));
                foreach ($words as $word) {
                    if (strlen($word) > 4 && !in_array($word, ['this', 'that', 'with', 'from'])) {
                        $tags[] = $word;
                    }
                }
            }
        }
        
        return array_unique(array_slice($tags, 0, 10)); // Limit to 10 tags
    }
    
    /**
     * Generate a color for a tag
     */
    protected function generateTagColor(string $tagName): string
    {
        $hash = md5($tagName);
        $hue = hexdec(substr($hash, 0, 2)) * 360 / 255;
        $saturation = 60 + (hexdec(substr($hash, 2, 2)) * 20 / 255);
        $lightness = 40 + (hexdec(substr($hash, 4, 2)) * 20 / 255);
        
        return "hsl({$hue}, {$saturation}%, {$lightness}%)";
    }
    
    /**
     * Index document for search
     */
    protected function indexForSearch(KnowledgeDocument $document): void
    {
        // Clear existing index entries
        $document->searchIndexes()->delete();
        
        $terms = [];
        
        // Index title
        $titleWords = str_word_count(strtolower($document->title), 1);
        foreach ($titleWords as $word) {
            if (strlen($word) > 2) {
                $terms[] = [
                    'document_id' => $document->id,
                    'term' => $word,
                    'field' => 'title',
                    'relevance' => 2.0,
                ];
            }
        }
        
        // Index content
        $contentWords = str_word_count(strtolower(strip_tags($document->content)), 1);
        $wordCounts = array_count_values($contentWords);
        $maxCount = max($wordCounts);
        
        foreach ($wordCounts as $word => $count) {
            if (strlen($word) > 3 && $count > 1) {
                $relevance = 0.5 + (0.5 * $count / $maxCount);
                $terms[] = [
                    'document_id' => $document->id,
                    'term' => $word,
                    'field' => 'content',
                    'relevance' => $relevance,
                ];
            }
        }
        
        // Index tags
        foreach ($document->tags as $tag) {
            $terms[] = [
                'document_id' => $document->id,
                'term' => strtolower($tag->name),
                'field' => 'tag',
                'relevance' => 1.5,
            ];
        }
        
        // Batch insert
        if (!empty($terms)) {
            KnowledgeSearchIndex::insert(array_map(function ($term) {
                return array_merge($term, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }, $terms));
        }
    }
    
    /**
     * Find related documents
     */
    protected function findRelatedDocuments(KnowledgeDocument $document): void
    {
        // Clear existing relationships
        DB::table('knowledge_related_documents')
            ->where('document_id', $document->id)
            ->delete();
            
        // Find by shared tags
        $relatedByTags = KnowledgeDocument::where('id', '!=', $document->id)
            ->whereHas('tags', function ($query) use ($document) {
                $query->whereIn('knowledge_tags.id', $document->tags->pluck('id'));
            })
            ->withCount(['tags' => function ($query) use ($document) {
                $query->whereIn('knowledge_tags.id', $document->tags->pluck('id'));
            }])
            ->having('tags_count', '>', 0)
            ->orderBy('tags_count', 'desc')
            ->limit(5)
            ->get();
            
        foreach ($relatedByTags as $related) {
            DB::table('knowledge_related_documents')->insert([
                'document_id' => $document->id,
                'related_document_id' => $related->id,
                'relevance_score' => $related->tags_count / max($document->tags->count(), 1),
                'relation_type' => 'similar',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Find by category
        if ($document->category_id) {
            $relatedByCategory = KnowledgeDocument::where('id', '!=', $document->id)
                ->where('category_id', $document->category_id)
                ->inRandomOrder()
                ->limit(3)
                ->get();
                
            foreach ($relatedByCategory as $related) {
                DB::table('knowledge_related_documents')->insert([
                    'document_id' => $document->id,
                    'related_document_id' => $related->id,
                    'relevance_score' => 0.5,
                    'relation_type' => 'same_category',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
    
    /**
     * Search documents
     */
    public function search(string $query, array $filters = []): Collection
    {
        $query = strtolower(trim($query));
        $words = str_word_count($query, 1);
        
        // Search in index
        $documentIds = KnowledgeSearchIndex::whereIn('term', $words)
            ->select('document_id', DB::raw('SUM(relevance) as total_relevance'))
            ->groupBy('document_id')
            ->orderBy('total_relevance', 'desc')
            ->pluck('document_id');
            
        // Build document query
        $documentsQuery = KnowledgeDocument::whereIn('id', $documentIds)
            ->orWhere('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%");
            
        // Apply filters
        if (!empty($filters['category_id'])) {
            $documentsQuery->where('category_id', $filters['category_id']);
        }
        
        if (!empty($filters['tags'])) {
            $documentsQuery->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('knowledge_tags.id', $filters['tags']);
            });
        }
        
        if (!empty($filters['status'])) {
            $documentsQuery->where('status', $filters['status']);
        }
        
        $documents = $documentsQuery->with(['category', 'tags'])->get();
        
        // Order by relevance
        if ($documentIds->isNotEmpty()) {
            $documents = $documents->sortBy(function ($doc) use ($documentIds) {
                $position = $documentIds->search($doc->id);
                return $position === false ? 999 : $position;
            });
        }
        
        return $documents;
    }
}