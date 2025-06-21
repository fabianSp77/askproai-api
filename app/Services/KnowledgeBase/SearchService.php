<?php

namespace App\Services\KnowledgeBase;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSearchIndex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SearchService
{
    protected DocumentIndexer $indexer;
    
    public function __construct(DocumentIndexer $indexer)
    {
        $this->indexer = $indexer;
    }
    
    /**
     * Search documents
     */
    public function search(string $query, array $filters = []): array
    {
        $results = [];
        
        // Use semantic search if embeddings are available
        if ($this->hasEmbeddingSupport()) {
            $results = $this->semanticSearch($query, $filters);
        }
        
        // Fallback to full-text search
        if (empty($results)) {
            $results = $this->fullTextSearch($query, $filters);
        }
        
        // Update search statistics
        $documentIds = array_column($results, 'id');
        $this->indexer->updateSearchStats($documentIds, $query);
        
        return $results;
    }
    
    /**
     * Semantic search using embeddings
     */
    protected function semanticSearch(string $query, array $filters): array
    {
        try {
            // Generate embedding for query
            $queryEmbedding = $this->generateQueryEmbedding($query);
            if (!$queryEmbedding) {
                return [];
            }
            
            // Search using vector similarity
            $searchQuery = KnowledgeSearchIndex::query()
                ->select('knowledge_search_index.*', 'knowledge_documents.*')
                ->join('knowledge_documents', 'knowledge_documents.id', '=', 'knowledge_search_index.document_id')
                ->where('knowledge_documents.status', 'published');
            
            // Apply filters
            $searchQuery = $this->applyFilters($searchQuery, $filters);
            
            // Calculate vector similarity (cosine similarity)
            $results = $searchQuery->get()->map(function ($item) use ($queryEmbedding) {
                $docEmbedding = $item->embedding;
                if (!$docEmbedding) {
                    return null;
                }
                
                $similarity = $this->cosineSimilarity($queryEmbedding, $docEmbedding);
                $item->similarity_score = $similarity;
                return $item;
            })->filter()->sortByDesc('similarity_score')->take(20);
            
            return $this->formatResults($results);
            
        } catch (\Exception $e) {
            Log::error('Semantic search failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Full-text search
     */
    protected function fullTextSearch(string $query, array $filters): array
    {
        $searchQuery = KnowledgeDocument::query()
            ->where('status', 'published');
        
        // MySQL full-text search
        if (DB::connection()->getDriverName() === 'mysql') {
            $searchQuery->whereRaw(
                "MATCH(title, content) AGAINST(? IN BOOLEAN MODE)",
                [$this->prepareFullTextQuery($query)]
            );
        } else {
            // Fallback for other databases
            $searchQuery->where(function ($q) use ($query) {
                $escapedQuery = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $query);
                $q->where('title', 'LIKE', '%' . $escapedQuery . '%')
                  ->orWhere('content', 'LIKE', '%' . $escapedQuery . '%');
            });
        }
        
        // Apply filters
        $searchQuery = $this->applyFilters($searchQuery, $filters);
        
        // Order by relevance
        if (DB::connection()->getDriverName() === 'mysql') {
            $searchQuery->selectRaw(
                "*, MATCH(title, content) AGAINST(? IN BOOLEAN MODE) as relevance",
                [$this->prepareFullTextQuery($query)]
            )->orderByDesc('relevance');
        } else {
            $searchQuery->orderByDesc('views_count');
        }
        
        $results = $searchQuery->limit(20)->get();
        
        // Highlight search terms
        $results->transform(function ($doc) use ($query) {
            $doc->highlighted_excerpt = $this->highlightTerms($doc->excerpt ?? $doc->content, $query);
            return $doc;
        });
        
        return $this->formatResults($results);
    }
    
    /**
     * Natural language search
     */
    public function naturalLanguageSearch(string $query): array
    {
        // Extract intent from query
        $intent = $this->extractIntent($query);
        
        switch ($intent['type']) {
            case 'how_to':
                return $this->searchHowTo($intent['topic']);
                
            case 'error':
                return $this->searchError($intent['error_message']);
                
            case 'api':
                return $this->searchApi($intent['endpoint']);
                
            case 'code':
                return $this->searchCode($intent['language'], $intent['keywords']);
                
            default:
                return $this->search($query);
        }
    }
    
    /**
     * Search for how-to guides
     */
    protected function searchHowTo(string $topic): array
    {
        return $this->search($topic, [
            'type' => 'guide',
            'title_contains' => ['how', 'setup', 'install', 'configure']
        ]);
    }
    
    /**
     * Search for error solutions
     */
    protected function searchError(string $errorMessage): array
    {
        return $this->search($errorMessage, [
            'content_contains' => ['error', 'fix', 'solution', 'troubleshoot']
        ]);
    }
    
    /**
     * Search API documentation
     */
    protected function searchApi(string $endpoint): array
    {
        return $this->search($endpoint, [
            'type' => 'api',
            'content_contains' => ['endpoint', 'request', 'response']
        ]);
    }
    
    /**
     * Search code examples
     */
    protected function searchCode(string $language, array $keywords): array
    {
        $query = implode(' ', $keywords);
        
        // Search in code snippets
        $snippets = DB::table('knowledge_code_snippets')
            ->where('language', $language)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $escapedKeyword = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $keyword);
                    $q->orWhere('code', 'LIKE', '%' . $escapedKeyword . '%')
                      ->orWhere('title', 'LIKE', '%' . $escapedKeyword . '%');
                }
            })
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();
        
        // Get parent documents
        $documentIds = $snippets->pluck('document_id')->unique();
        $documents = KnowledgeDocument::whereIn('id', $documentIds)->get();
        
        return $this->formatResults($documents);
    }
    
    /**
     * Apply search filters
     */
    protected function applyFilters($query, array $filters)
    {
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('slug', $filters['tags']);
            });
        }
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        if (isset($filters['title_contains'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['title_contains'] as $term) {
                    $escapedTerm = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $term);
                    $q->orWhere('title', 'LIKE', '%' . $escapedTerm . '%');
                }
            });
        }
        
        if (isset($filters['content_contains'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['content_contains'] as $term) {
                    $escapedTerm = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $term);
                    $q->orWhere('content', 'LIKE', '%' . $escapedTerm . '%');
                }
            });
        }
        
        return $query;
    }
    
    /**
     * Format search results
     */
    protected function formatResults($results): array
    {
        return $results->map(function ($doc) {
            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'slug' => $doc->slug,
                'excerpt' => $doc->highlighted_excerpt ?? $doc->excerpt,
                'type' => $doc->type,
                'category' => $doc->category ? [
                    'id' => $doc->category->id,
                    'name' => $doc->category->name,
                    'slug' => $doc->category->slug,
                ] : null,
                'tags' => $doc->tags ? $doc->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ];
                })->toArray() : [],
                'relevance_score' => $doc->relevance ?? $doc->similarity_score ?? 1.0,
                'views_count' => $doc->views_count,
                'reading_time' => $doc->reading_time,
                'created_at' => $doc->created_at->toIso8601String(),
                'updated_at' => $doc->updated_at->toIso8601String(),
            ];
        })->toArray();
    }
    
    /**
     * Prepare query for MySQL full-text search
     */
    protected function prepareFullTextQuery(string $query): string
    {
        // Split into words
        $words = preg_split('/\s+/', $query);
        
        // Build boolean mode query
        $prepared = [];
        foreach ($words as $word) {
            if (strlen($word) > 2) {
                // Require word and allow wildcard
                $prepared[] = '+' . $word . '*';
            }
        }
        
        return implode(' ', $prepared);
    }
    
    /**
     * Highlight search terms in text
     */
    protected function highlightTerms(string $text, string $query): string
    {
        $words = preg_split('/\s+/', $query);
        
        foreach ($words as $word) {
            if (strlen($word) > 2) {
                $text = preg_replace(
                    '/(' . preg_quote($word, '/') . ')/i',
                    '<mark>$1</mark>',
                    $text
                );
            }
        }
        
        return $text;
    }
    
    /**
     * Extract intent from natural language query
     */
    protected function extractIntent(string $query): array
    {
        $query = strtolower($query);
        
        // How-to queries
        if (preg_match('/how (to|do|can)/i', $query)) {
            return [
                'type' => 'how_to',
                'topic' => trim(preg_replace('/how (to|do|can)/i', '', $query))
            ];
        }
        
        // Error queries
        if (str_contains($query, 'error') || str_contains($query, 'exception')) {
            return [
                'type' => 'error',
                'error_message' => $query
            ];
        }
        
        // API queries
        if (preg_match('/(api|endpoint|route)\s+(.+)/i', $query, $matches)) {
            return [
                'type' => 'api',
                'endpoint' => $matches[2]
            ];
        }
        
        // Code queries
        $languages = ['php', 'javascript', 'python', 'sql', 'bash'];
        foreach ($languages as $lang) {
            if (str_contains($query, $lang)) {
                $keywords = array_diff(
                    explode(' ', $query),
                    [$lang, 'code', 'example', 'snippet']
                );
                return [
                    'type' => 'code',
                    'language' => $lang,
                    'keywords' => $keywords
                ];
            }
        }
        
        return ['type' => 'general'];
    }
    
    /**
     * Generate embedding for search query
     */
    protected function generateQueryEmbedding(string $query): ?array
    {
        try {
            $apiKey = config('services.openai.api_key');
            if ($apiKey) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/embeddings', [
                    'model' => 'text-embedding-ada-002',
                    'input' => $query,
                ]);
                
                if ($response->successful()) {
                    return $response->json('data.0.embedding');
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate query embedding', [
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
    
    /**
     * Calculate cosine similarity between vectors
     */
    protected function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        return $dotProduct / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Check if embedding support is available
     */
    protected function hasEmbeddingSupport(): bool
    {
        return !empty(config('services.openai.api_key'));
    }
}