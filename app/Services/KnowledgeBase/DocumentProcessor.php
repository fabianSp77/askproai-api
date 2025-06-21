<?php

namespace App\Services\KnowledgeBase;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeCodeSnippet;
use App\Models\KnowledgeRelationship;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DocumentProcessor
{
    protected array $codeLanguages = [
        'php', 'javascript', 'js', 'typescript', 'ts', 'python', 'java', 
        'bash', 'shell', 'sql', 'json', 'yaml', 'yml', 'xml', 'html', 
        'css', 'scss', 'vue', 'react', 'jsx', 'tsx'
    ];
    
    /**
     * Process markdown content and extract metadata
     */
    public function process(string $content, string $path): array
    {
        $lines = explode("\n", $content);
        $metadata = $this->extractFrontmatter($lines);
        $title = $metadata['title'] ?? $this->extractTitle($lines) ?? $this->generateTitleFromPath($path);
        $excerpt = $metadata['description'] ?? $metadata['excerpt'] ?? $this->generateExcerpt($content);
        $tags = $this->extractTags($content, $metadata);
        
        return [
            'title' => $title,
            'excerpt' => $excerpt,
            'metadata' => $metadata,
            'tags' => $tags,
        ];
    }
    
    /**
     * Extract frontmatter from markdown
     */
    protected function extractFrontmatter(array &$lines): array
    {
        $metadata = [];
        
        if (count($lines) > 0 && trim($lines[0]) === '---') {
            $frontmatterLines = [];
            $i = 1;
            
            while ($i < count($lines) && trim($lines[$i]) !== '---') {
                $frontmatterLines[] = $lines[$i];
                $i++;
            }
            
            if ($i < count($lines) && trim($lines[$i]) === '---') {
                // Remove frontmatter lines from content
                array_splice($lines, 0, $i + 1);
                
                // Parse YAML-like frontmatter
                foreach ($frontmatterLines as $line) {
                    if (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches)) {
                        $key = trim($matches[1]);
                        $value = trim($matches[2]);
                        
                        // Handle arrays
                        if ($key === 'tags' || $key === 'categories') {
                            $value = array_map('trim', explode(',', $value));
                        }
                        
                        $metadata[$key] = $value;
                    }
                }
            }
        }
        
        return $metadata;
    }
    
    /**
     * Extract title from markdown content
     */
    protected function extractTitle(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return null;
    }
    
    /**
     * Generate title from file path
     */
    protected function generateTitleFromPath(string $path): string
    {
        $filename = basename($path, '.md');
        $filename = str_replace(['_', '-'], ' ', $filename);
        return Str::title($filename);
    }
    
    /**
     * Generate excerpt from content
     */
    protected function generateExcerpt(string $content): string
    {
        // Remove code blocks
        $content = preg_replace('/```[\s\S]*?```/', '', $content);
        
        // Remove headers
        $content = preg_replace('/^#+\s+.+$/m', '', $content);
        
        // Remove links but keep link text
        $content = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $content);
        
        // Remove markdown formatting
        $content = strip_tags($content);
        
        // Get first meaningful paragraph
        $paragraphs = array_filter(explode("\n\n", $content));
        $excerpt = $paragraphs[0] ?? '';
        
        return Str::limit(trim($excerpt), 200);
    }
    
    /**
     * Extract tags from content using AI
     */
    protected function extractTags(string $content, array $metadata): array
    {
        $tags = [];
        
        // Use existing tags from metadata
        if (isset($metadata['tags'])) {
            $tags = is_array($metadata['tags']) ? $metadata['tags'] : [$metadata['tags']];
        }
        
        // Extract technology mentions
        $techPatterns = [
            'Laravel', 'PHP', 'JavaScript', 'Vue.js', 'React', 'MySQL', 'PostgreSQL',
            'Redis', 'Docker', 'API', 'REST', 'GraphQL', 'Webhook', 'WebSocket',
            'Authentication', 'Authorization', 'Testing', 'PHPUnit', 'Deployment'
        ];
        
        foreach ($techPatterns as $tech) {
            if (stripos($content, $tech) !== false) {
                $tags[] = $tech;
            }
        }
        
        // Extract from headers
        if (preg_match_all('/^##\s+(.+)$/m', $content, $matches)) {
            foreach ($matches[1] as $header) {
                $words = str_word_count($header, 1);
                foreach ($words as $word) {
                    if (strlen($word) > 4 && !in_array(strtolower($word), ['this', 'that', 'with', 'from'])) {
                        $tags[] = $word;
                    }
                }
            }
        }
        
        return array_unique(array_map('trim', $tags));
    }
    
    /**
     * Extract code snippets from document
     */
    public function extractCodeSnippets(KnowledgeDocument $document): void
    {
        $content = $document->content;
        
        // Extract fenced code blocks
        if (preg_match_all('/```(\w+)?\n([\s\S]*?)```/m', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $language = $match[1] ?: 'text';
                $code = trim($match[2]);
                
                if (empty($code)) {
                    continue;
                }
                
                // Try to extract title from comment
                $title = null;
                if (preg_match('/^(?:\/\/|#|--)\s*(.+)$/m', $code, $titleMatch)) {
                    $title = trim($titleMatch[1]);
                }
                
                // Determine if code is executable
                $isExecutable = $this->isCodeExecutable($language, $code);
                
                KnowledgeCodeSnippet::create([
                    'document_id' => $document->id,
                    'language' => strtolower($language),
                    'title' => $title,
                    'code' => $code,
                    'is_executable' => $isExecutable,
                    'execution_config' => $isExecutable ? $this->getExecutionConfig($language) : null,
                ]);
            }
        }
    }
    
    /**
     * Detect relationships between documents
     */
    public function detectRelationships(KnowledgeDocument $document): void
    {
        // Clear existing auto-detected relationships
        KnowledgeRelationship::where('source_document_id', $document->id)
            ->where('is_auto_detected', true)
            ->delete();
        
        // Find documents mentioned in content
        $content = strtolower($document->content);
        $allDocuments = KnowledgeDocument::where('id', '!=', $document->id)->get();
        
        foreach ($allDocuments as $otherDoc) {
            $strength = 0;
            
            // Check for direct mentions
            if (str_contains($content, strtolower($otherDoc->title))) {
                $strength += 0.8;
            }
            
            // Check for filename mentions
            $filename = basename($otherDoc->path);
            if (str_contains($content, strtolower($filename))) {
                $strength += 0.5;
            }
            
            // Check for shared tags
            $sharedTags = array_intersect(
                $document->auto_tags ?? [],
                $otherDoc->auto_tags ?? []
            );
            $strength += count($sharedTags) * 0.1;
            
            // Check for links
            if (preg_match('/\[.*?\]\(.*?' . preg_quote($otherDoc->slug, '/') . '.*?\)/', $content)) {
                $strength = 1.0;
            }
            
            if ($strength >= 0.3) {
                KnowledgeRelationship::create([
                    'source_document_id' => $document->id,
                    'target_document_id' => $otherDoc->id,
                    'relationship_type' => 'related',
                    'strength' => min(1.0, $strength),
                    'is_auto_detected' => true,
                ]);
            }
        }
        
        // Detect sequential relationships based on naming
        $this->detectSequentialRelationships($document);
    }
    
    /**
     * Detect sequential relationships (next/previous)
     */
    protected function detectSequentialRelationships(KnowledgeDocument $document): void
    {
        $path = $document->path;
        
        // Look for numbered patterns
        if (preg_match('/(\d+)/', basename($path), $matches)) {
            $number = intval($matches[1]);
            $pattern = str_replace($matches[1], '%d', $path);
            
            // Look for previous
            $prevPath = sprintf($pattern, $number - 1);
            $prevDoc = KnowledgeDocument::where('path', $prevPath)->first();
            if ($prevDoc) {
                KnowledgeRelationship::create([
                    'source_document_id' => $document->id,
                    'target_document_id' => $prevDoc->id,
                    'relationship_type' => 'previous',
                    'strength' => 1.0,
                    'is_auto_detected' => true,
                ]);
            }
            
            // Look for next
            $nextPath = sprintf($pattern, $number + 1);
            $nextDoc = KnowledgeDocument::where('path', $nextPath)->first();
            if ($nextDoc) {
                KnowledgeRelationship::create([
                    'source_document_id' => $document->id,
                    'target_document_id' => $nextDoc->id,
                    'relationship_type' => 'next',
                    'strength' => 1.0,
                    'is_auto_detected' => true,
                ]);
            }
        }
    }
    
    /**
     * Generate diff between two versions
     */
    public function generateDiff(string $oldContent, string $newContent): string
    {
        $oldLines = explode("\n", $oldContent);
        $newLines = explode("\n", $newContent);
        
        $diff = [];
        $maxLines = max(count($oldLines), count($newLines));
        
        for ($i = 0; $i < $maxLines; $i++) {
            $oldLine = $oldLines[$i] ?? null;
            $newLine = $newLines[$i] ?? null;
            
            if ($oldLine === $newLine) {
                continue;
            }
            
            if ($oldLine === null) {
                $diff[] = "+ {$newLine}";
            } elseif ($newLine === null) {
                $diff[] = "- {$oldLine}";
            } else {
                $diff[] = "- {$oldLine}";
                $diff[] = "+ {$newLine}";
            }
        }
        
        return implode("\n", $diff);
    }
    
    /**
     * Suggest category using AI
     */
    public function suggestCategory(KnowledgeDocument $document): ?KnowledgeCategory
    {
        try {
            // Use OpenAI or local categorization logic
            $categories = KnowledgeCategory::all();
            $content = Str::limit($document->content, 1000);
            
            // Simple keyword-based categorization for now
            $categoryScores = [];
            
            foreach ($categories as $category) {
                $score = 0;
                $categoryKeywords = explode(' ', strtolower($category->name . ' ' . $category->description));
                
                foreach ($categoryKeywords as $keyword) {
                    if (strlen($keyword) > 3) {
                        $score += substr_count(strtolower($content), $keyword);
                    }
                }
                
                $categoryScores[$category->id] = $score;
            }
            
            arsort($categoryScores);
            $topCategoryId = array_key_first($categoryScores);
            
            if ($topCategoryId && $categoryScores[$topCategoryId] > 0) {
                return $categories->find($topCategoryId);
            }
        } catch (\Exception $e) {
            Log::error('Failed to suggest category', [
                'document' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
    
    /**
     * Check if code is executable
     */
    protected function isCodeExecutable(string $language, string $code): bool
    {
        // API calls are executable
        if (in_array($language, ['bash', 'shell', 'curl']) && str_contains($code, 'curl')) {
            return true;
        }
        
        // SQL queries (SELECT only for safety)
        if ($language === 'sql' && stripos($code, 'select') === 0) {
            return true;
        }
        
        // JavaScript that doesn't require DOM
        if (in_array($language, ['javascript', 'js']) && !preg_match('/document\.|window\.|DOM/i', $code)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get execution configuration for language
     */
    protected function getExecutionConfig(string $language): array
    {
        $configs = [
            'bash' => [
                'sandbox' => true,
                'timeout' => 5000,
                'allowed_commands' => ['curl', 'echo', 'cat', 'grep', 'awk', 'sed'],
            ],
            'sql' => [
                'sandbox' => true,
                'timeout' => 3000,
                'read_only' => true,
            ],
            'javascript' => [
                'sandbox' => true,
                'timeout' => 3000,
                'environment' => 'node',
            ],
        ];
        
        return $configs[$language] ?? [];
    }
}