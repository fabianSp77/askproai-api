<?php

namespace App\Services\KnowledgeBase;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSearchIndex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentIndexer
{
    protected int $chunkSize = 500; // Characters per chunk
    protected int $chunkOverlap = 100; // Overlap between chunks
    
    /**
     * Index a document for search
     */
    public function indexDocument(KnowledgeDocument $document): void
    {
        // Clear existing index entries
        KnowledgeSearchIndex::where('document_id', $document->id)->delete();
        
        // Split document into chunks
        $chunks = $this->createChunks($document);
        
        foreach ($chunks as $chunk) {
            $this->indexChunk($document, $chunk);
        }
        
        Log::info('Document indexed', [
            'document_id' => $document->id,
            'chunks_count' => count($chunks)
        ]);
    }
    
    /**
     * Create chunks from document content
     */
    protected function createChunks(KnowledgeDocument $document): array
    {
        $content = $document->content;
        $chunks = [];
        
        // Split by sections (headers)
        $sections = preg_split('/^#{1,3}\s+(.+)$/m', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $currentSection = null;
        for ($i = 0; $i < count($sections); $i++) {
            if ($i % 2 === 1) {
                // This is a header
                $currentSection = trim($sections[$i]);
            } else {
                // This is content
                $sectionContent = trim($sections[$i]);
                if (!empty($sectionContent)) {
                    // Split large sections into smaller chunks
                    if (strlen($sectionContent) > $this->chunkSize) {
                        $subChunks = $this->splitIntoChunks($sectionContent);
                        foreach ($subChunks as $subChunk) {
                            $chunks[] = [
                                'title' => $currentSection,
                                'content' => $subChunk
                            ];
                        }
                    } else {
                        $chunks[] = [
                            'title' => $currentSection,
                            'content' => $sectionContent
                        ];
                    }
                }
            }
        }
        
        // If no sections found, chunk the entire content
        if (empty($chunks)) {
            $fullChunks = $this->splitIntoChunks($content);
            foreach ($fullChunks as $chunk) {
                $chunks[] = [
                    'title' => null,
                    'content' => $chunk
                ];
            }
        }
        
        return $chunks;
    }
    
    /**
     * Split text into overlapping chunks
     */
    protected function splitIntoChunks(string $text): array
    {
        $chunks = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        
        $currentChunk = '';
        $currentLength = 0;
        
        foreach ($sentences as $sentence) {
            $sentenceLength = strlen($sentence);
            
            if ($currentLength + $sentenceLength > $this->chunkSize && !empty($currentChunk)) {
                $chunks[] = trim($currentChunk);
                
                // Start new chunk with overlap
                $overlap = $this->getOverlapText($currentChunk, $this->chunkOverlap);
                $currentChunk = $overlap . ' ' . $sentence;
                $currentLength = strlen($currentChunk);
            } else {
                $currentChunk .= ' ' . $sentence;
                $currentLength += $sentenceLength + 1;
            }
        }
        
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }
        
        return $chunks;
    }
    
    /**
     * Get overlap text from the end of a chunk
     */
    protected function getOverlapText(string $text, int $length): string
    {
        $words = explode(' ', $text);
        $overlapWords = [];
        $currentLength = 0;
        
        for ($i = count($words) - 1; $i >= 0; $i--) {
            $wordLength = strlen($words[$i]);
            if ($currentLength + $wordLength > $length) {
                break;
            }
            array_unshift($overlapWords, $words[$i]);
            $currentLength += $wordLength + 1;
        }
        
        return implode(' ', $overlapWords);
    }
    
    /**
     * Index a single chunk
     */
    protected function indexChunk(KnowledgeDocument $document, array $chunk): void
    {
        // Extract keywords
        $keywords = $this->extractKeywords($chunk['content']);
        
        // Generate embedding (placeholder for actual implementation)
        $embedding = $this->generateEmbedding($chunk['content']);
        
        KnowledgeSearchIndex::create([
            'document_id' => $document->id,
            'section_title' => $chunk['title'],
            'content_chunk' => $chunk['content'],
            'keywords' => $keywords,
            'embedding' => $embedding,
            'relevance_score' => 1.0,
        ]);
    }
    
    /**
     * Extract keywords from text
     */
    protected function extractKeywords(string $text): array
    {
        // Remove common words
        $stopWords = [
            'the', 'is', 'at', 'which', 'on', 'a', 'an', 'as', 'are', 'was', 'were',
            'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
            'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these',
            'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'them', 'their',
            'what', 'which', 'who', 'when', 'where', 'why', 'how', 'all', 'each',
            'every', 'some', 'any', 'many', 'much', 'most', 'other', 'into', 'through',
            'during', 'before', 'after', 'above', 'below', 'to', 'from', 'in', 'out',
            'off', 'over', 'under', 'again', 'then', 'once'
        ];
        
        // Extract words
        $words = str_word_count(strtolower($text), 1);
        $wordCounts = array_count_values($words);
        
        // Filter and sort
        $keywords = [];
        foreach ($wordCounts as $word => $count) {
            if (strlen($word) > 3 && !in_array($word, $stopWords)) {
                $keywords[$word] = $count;
            }
        }
        
        arsort($keywords);
        
        // Take top keywords
        return array_slice(array_keys($keywords), 0, 20);
    }
    
    /**
     * Generate embedding vector for text
     */
    protected function generateEmbedding(string $text): ?array
    {
        try {
            // Use OpenAI embeddings API if configured
            $apiKey = config('services.openai.api_key');
            if ($apiKey) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/embeddings', [
                    'model' => 'text-embedding-ada-002',
                    'input' => Str::limit($text, 8000),
                ]);
                
                if ($response->successful()) {
                    return $response->json('data.0.embedding');
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate embedding', [
                'error' => $e->getMessage()
            ]);
        }
        
        // Fallback: Generate simple vector based on keywords
        return $this->generateSimpleEmbedding($text);
    }
    
    /**
     * Generate simple embedding without external API
     */
    protected function generateSimpleEmbedding(string $text): array
    {
        // Create a simple 128-dimensional vector based on text features
        $vector = array_fill(0, 128, 0.0);
        
        // Use various text features to populate vector
        $features = [
            strlen($text) / 1000,
            str_word_count($text) / 100,
            substr_count($text, '.') / 10,
            substr_count($text, '?') / 5,
            substr_count($text, '!') / 5,
            substr_count($text, 'function') / 10,
            substr_count($text, 'class') / 10,
            substr_count($text, 'api') / 10,
            substr_count($text, 'endpoint') / 10,
            substr_count($text, 'database') / 10,
        ];
        
        // Fill first 10 dimensions with features
        for ($i = 0; $i < min(10, count($features)); $i++) {
            $vector[$i] = min(1.0, $features[$i]);
        }
        
        // Use character distribution for remaining dimensions
        $chars = count_chars($text, 1);
        $totalChars = array_sum($chars);
        
        $i = 10;
        foreach ($chars as $ascii => $count) {
            if ($i >= 128) break;
            $vector[$i] = $count / $totalChars;
            $i++;
        }
        
        return $vector;
    }
    
    /**
     * Update search statistics
     */
    public function updateSearchStats(array $documentIds, string $query): void
    {
        // Increment search count for found documents
        KnowledgeDocument::whereIn('id', $documentIds)
            ->increment('search_count');
        
        // Log search query for analytics
        DB::table('knowledge_analytics')->insert([
            'document_id' => null,
            'user_id' => auth()->id(),
            'event_type' => 'search',
            'event_data' => json_encode([
                'query' => $query,
                'results_count' => count($documentIds),
                'document_ids' => $documentIds
            ]),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    /**
     * Rebuild entire search index
     */
    public function rebuildIndex(): void
    {
        Log::info('Starting search index rebuild');
        
        // Clear existing index
        KnowledgeSearchIndex::truncate();
        
        // Re-index all documents
        $documents = KnowledgeDocument::all();
        $count = 0;
        
        foreach ($documents as $document) {
            $this->indexDocument($document);
            $count++;
            
            if ($count % 10 === 0) {
                Log::info('Index rebuild progress', ['processed' => $count]);
            }
        }
        
        Log::info('Search index rebuild complete', ['total_documents' => $count]);
    }
}