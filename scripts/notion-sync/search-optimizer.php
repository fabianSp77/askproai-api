#!/usr/bin/env php
<?php

/**
 * Documentation Search Optimizer
 * 
 * Creates tags, glossary, command index, and FAQ database for better search
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class SearchOptimizer
{
    private array $tags = [];
    private array $glossary = [];
    private array $commands = [];
    private array $faqs = [];
    private array $searchIndex = [];
    
    /**
     * Optimize search for all documentation
     */
    public function optimize(): void
    {
        echo "🔍 Optimizing documentation search...\n";
        
        // Process all documentation files
        $this->processDocumentationFiles();
        
        // Generate search assets
        $this->generateTagSystem();
        $this->generateGlossary();
        $this->generateCommandIndex();
        $this->generateFAQDatabase();
        $this->generateSearchIndex();
        
        // Save all assets
        $this->saveSearchAssets();
        
        echo "✅ Search optimization complete!\n";
    }
    
    /**
     * Process all documentation files
     */
    private function processDocumentationFiles(): void
    {
        $docFiles = array_merge(
            glob(base_path('docs/**/*.md')),
            glob(base_path('*.md'))
        );
        
        foreach ($docFiles as $file) {
            echo "📄 Processing: " . basename($file) . "\n";
            $this->processFile($file);
        }
    }
    
    /**
     * Process individual file
     */
    private function processFile(string $file): void
    {
        $content = file_get_contents($file);
        $relativePath = str_replace(base_path() . '/', '', $file);
        
        // Extract tags
        $this->extractTags($content, $relativePath);
        
        // Extract glossary terms
        $this->extractGlossaryTerms($content, $relativePath);
        
        // Extract commands
        $this->extractCommands($content, $relativePath);
        
        // Extract FAQ entries
        $this->extractFAQs($content, $relativePath);
        
        // Build search index
        $this->indexContent($content, $relativePath);
    }
    
    /**
     * Extract tags from content
     */
    private function extractTags(string $content, string $file): void
    {
        // Extract from front matter
        if (preg_match('/^---\n(.*?)\n---/s', $content, $matches)) {
            if (preg_match('/tags:\s*\[(.*?)\]/s', $matches[1], $tagMatches)) {
                $tags = array_map('trim', explode(',', $tagMatches[1]));
                foreach ($tags as $tag) {
                    $this->addTag($tag, $file);
                }
            }
        }
        
        // Auto-generate tags from headers
        preg_match_all('/^#{1,3}\s+(.+)$/m', $content, $headers);
        foreach ($headers[1] as $header) {
            $words = explode(' ', strtolower($header));
            foreach ($words as $word) {
                if (strlen($word) > 4 && !in_array($word, ['this', 'that', 'with', 'from'])) {
                    $this->addTag($word, $file);
                }
            }
        }
        
        // Extract technology tags
        $technologies = [
            'laravel', 'php', 'mysql', 'redis', 'docker', 'vue', 'react',
            'filament', 'livewire', 'tailwind', 'api', 'webhook', 'queue',
            'horizon', 'retell', 'calcom', 'stripe', 'twilio'
        ];
        
        foreach ($technologies as $tech) {
            if (stripos($content, $tech) !== false) {
                $this->addTag($tech, $file);
            }
        }
    }
    
    /**
     * Extract glossary terms
     */
    private function extractGlossaryTerms(string $content, string $file): void
    {
        // Extract defined terms (pattern: **Term**: Definition)
        preg_match_all('/\*\*([^*]+)\*\*:\s*([^\n]+)/', $content, $matches);
        
        for ($i = 0; $i < count($matches[0]); $i++) {
            $term = trim($matches[1][$i]);
            $definition = trim($matches[2][$i]);
            
            if (strlen($term) < 30 && strlen($definition) > 10) {
                $this->glossary[$term] = [
                    'definition' => $definition,
                    'file' => $file,
                    'category' => $this->categorizeGlossaryTerm($term),
                ];
            }
        }
        
        // Extract from glossary sections
        if (preg_match('/##\s*Glossary(.*?)(?=##|$)/si', $content, $section)) {
            preg_match_all('/\n\s*-\s*\*\*([^*]+)\*\*:\s*([^\n]+)/', $section[1], $terms);
            
            for ($i = 0; $i < count($terms[0]); $i++) {
                $term = trim($terms[1][$i]);
                $definition = trim($terms[2][$i]);
                
                $this->glossary[$term] = [
                    'definition' => $definition,
                    'file' => $file,
                    'category' => $this->categorizeGlossaryTerm($term),
                ];
            }
        }
    }
    
    /**
     * Extract commands
     */
    private function extractCommands(string $content, string $file): void
    {
        // Extract bash commands
        preg_match_all('/```bash\n(.*?)\n```/s', $content, $bashBlocks);
        foreach ($bashBlocks[1] as $block) {
            $lines = explode("\n", $block);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line && !str_starts_with($line, '#')) {
                    $this->extractCommandFromLine($line, $file, 'bash');
                }
            }
        }
        
        // Extract PHP artisan commands
        preg_match_all('/php artisan ([a-z:-]+)/', $content, $artisanCommands);
        foreach ($artisanCommands[0] as $idx => $command) {
            $this->commands[] = [
                'command' => $command,
                'type' => 'artisan',
                'subcommand' => $artisanCommands[1][$idx],
                'file' => $file,
                'description' => $this->extractCommandDescription($content, $command),
            ];
        }
        
        // Extract npm/composer commands
        preg_match_all('/(npm|composer|yarn)\s+([\w:-]+)/', $content, $packageCommands);
        foreach ($packageCommands[0] as $idx => $command) {
            $this->commands[] = [
                'command' => $command,
                'type' => $packageCommands[1][$idx],
                'subcommand' => $packageCommands[2][$idx],
                'file' => $file,
                'description' => $this->extractCommandDescription($content, $command),
            ];
        }
    }
    
    /**
     * Extract FAQs
     */
    private function extractFAQs(string $content, string $file): void
    {
        // Pattern 1: FAQ sections
        if (preg_match('/##\s*FAQ(.*?)(?=##|$)/si', $content, $faqSection)) {
            preg_match_all('/###\s*(.+?)\n(.*?)(?=###|$)/s', $faqSection[1], $questions);
            
            for ($i = 0; $i < count($questions[0]); $i++) {
                $this->faqs[] = [
                    'question' => trim($questions[1][$i], '? '),
                    'answer' => trim($questions[2][$i]),
                    'file' => $file,
                    'category' => $this->categorizeFAQ($questions[1][$i]),
                ];
            }
        }
        
        // Pattern 2: Question headers (lines ending with ?)
        preg_match_all('/^###\s*(.+\?)\s*\n(.*?)(?=^###|\z)/ms', $content, $questionHeaders);
        
        for ($i = 0; $i < count($questionHeaders[0]); $i++) {
            $question = trim($questionHeaders[1][$i]);
            $answer = trim($questionHeaders[2][$i]);
            
            if (strlen($answer) > 20) {
                $this->faqs[] = [
                    'question' => $question,
                    'answer' => $answer,
                    'file' => $file,
                    'category' => $this->categorizeFAQ($question),
                ];
            }
        }
        
        // Pattern 3: Common issue patterns
        preg_match_all('/(?:Issue|Problem|Error):\s*(.+?)\n(?:Solution|Fix|Resolution):\s*(.+?)(?=\n\n|$)/si', 
            $content, $issues);
        
        for ($i = 0; $i < count($issues[0]); $i++) {
            $this->faqs[] = [
                'question' => 'How to fix: ' . trim($issues[1][$i]),
                'answer' => trim($issues[2][$i]),
                'file' => $file,
                'category' => 'troubleshooting',
            ];
        }
    }
    
    /**
     * Index content for search
     */
    private function indexContent(string $content, string $file): void
    {
        // Extract title
        $title = '';
        if (preg_match('/^#\s+(.+)$/m', $content, $match)) {
            $title = $match[1];
        }
        
        // Extract description
        $description = '';
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (trim($line) && !str_starts_with($line, '#') && strlen($line) > 20) {
                $description = $line;
                break;
            }
        }
        
        // Extract keywords
        $keywords = $this->extractKeywords($content);
        
        // Create search entry
        $this->searchIndex[] = [
            'file' => $file,
            'title' => $title,
            'description' => Str::limit($description, 200),
            'keywords' => $keywords,
            'tags' => $this->getTagsForFile($file),
            'type' => $this->getDocumentType($file),
            'weight' => $this->calculateSearchWeight($file, $content),
        ];
    }
    
    /**
     * Generate tag system
     */
    private function generateTagSystem(): void
    {
        echo "🏷️ Generating tag system...\n";
        
        // Sort tags by frequency
        arsort($this->tags);
        
        // Group tags by category
        $categorizedTags = [
            'technology' => [],
            'feature' => [],
            'integration' => [],
            'component' => [],
            'other' => [],
        ];
        
        foreach ($this->tags as $tag => $files) {
            $category = $this->categorizeTag($tag);
            $categorizedTags[$category][] = [
                'tag' => $tag,
                'count' => count($files),
                'files' => $files,
            ];
        }
        
        // Save tag system
        $tagSystem = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_tags' => count($this->tags),
            'categories' => $categorizedTags,
            'popular_tags' => array_slice(array_keys($this->tags), 0, 20),
        ];
        
        file_put_contents(
            base_path('docs/search/tags.json'),
            json_encode($tagSystem, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * Generate glossary
     */
    private function generateGlossary(): void
    {
        echo "📚 Generating glossary...\n";
        
        // Sort alphabetically
        ksort($this->glossary);
        
        // Group by first letter
        $alphabetical = [];
        foreach ($this->glossary as $term => $data) {
            $letter = strtoupper($term[0]);
            $alphabetical[$letter][] = array_merge(['term' => $term], $data);
        }
        
        // Generate markdown glossary
        $markdown = "# AskProAI Glossary\n\n";
        $markdown .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($alphabetical as $letter => $terms) {
            $markdown .= "## $letter\n\n";
            foreach ($terms as $term) {
                $markdown .= "**{$term['term']}**: {$term['definition']} ";
                $markdown .= "_([source]({$term['file']}))_\n\n";
            }
        }
        
        file_put_contents(base_path('docs/GLOSSARY.md'), $markdown);
        
        // Save JSON version
        file_put_contents(
            base_path('docs/search/glossary.json'),
            json_encode($this->glossary, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * Generate command index
     */
    private function generateCommandIndex(): void
    {
        echo "💻 Generating command index...\n";
        
        // Group by type
        $commandsByType = [];
        foreach ($this->commands as $cmd) {
            $commandsByType[$cmd['type']][] = $cmd;
        }
        
        // Generate markdown index
        $markdown = "# Command Reference\n\n";
        $markdown .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($commandsByType as $type => $commands) {
            $markdown .= "## " . ucfirst($type) . " Commands\n\n";
            
            // Sort commands
            usort($commands, fn($a, $b) => strcmp($a['command'], $b['command']));
            
            foreach ($commands as $cmd) {
                $markdown .= "### `{$cmd['command']}`\n";
                if ($cmd['description']) {
                    $markdown .= "{$cmd['description']}\n";
                }
                $markdown .= "_Source: [{$cmd['file']}]({$cmd['file']})_\n\n";
            }
        }
        
        file_put_contents(base_path('docs/COMMANDS.md'), $markdown);
        
        // Save JSON version
        file_put_contents(
            base_path('docs/search/commands.json'),
            json_encode($commandsByType, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * Generate FAQ database
     */
    private function generateFAQDatabase(): void
    {
        echo "❓ Generating FAQ database...\n";
        
        // Remove duplicates
        $uniqueFAQs = [];
        foreach ($this->faqs as $faq) {
            $key = md5($faq['question']);
            $uniqueFAQs[$key] = $faq;
        }
        
        // Group by category
        $categorizedFAQs = [];
        foreach ($uniqueFAQs as $faq) {
            $categorizedFAQs[$faq['category']][] = $faq;
        }
        
        // Generate markdown FAQ
        $markdown = "# Frequently Asked Questions\n\n";
        $markdown .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $markdown .= "Total Questions: " . count($uniqueFAQs) . "\n\n";
        
        foreach ($categorizedFAQs as $category => $faqs) {
            $markdown .= "## " . ucfirst(str_replace('_', ' ', $category)) . "\n\n";
            
            foreach ($faqs as $faq) {
                $markdown .= "### {$faq['question']}\n\n";
                $markdown .= "{$faq['answer']}\n\n";
                $markdown .= "_Source: [{$faq['file']}]({$faq['file']})_\n\n";
                $markdown .= "---\n\n";
            }
        }
        
        file_put_contents(base_path('docs/FAQ.md'), $markdown);
        
        // Save JSON version
        file_put_contents(
            base_path('docs/search/faq.json'),
            json_encode($categorizedFAQs, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * Generate search index
     */
    private function generateSearchIndex(): void
    {
        echo "🔎 Generating search index...\n";
        
        // Sort by weight
        usort($this->searchIndex, fn($a, $b) => $b['weight'] <=> $a['weight']);
        
        // Save search index
        file_put_contents(
            base_path('docs/search/index.json'),
            json_encode($this->searchIndex, JSON_PRETTY_PRINT)
        );
        
        // Generate search stats
        $stats = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_documents' => count($this->searchIndex),
            'total_tags' => count($this->tags),
            'total_glossary_terms' => count($this->glossary),
            'total_commands' => count($this->commands),
            'total_faqs' => count($this->faqs),
            'document_types' => array_count_values(array_column($this->searchIndex, 'type')),
        ];
        
        file_put_contents(
            base_path('docs/search/stats.json'),
            json_encode($stats, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * Save all search assets
     */
    private function saveSearchAssets(): void
    {
        // Create search directory if it doesn't exist
        $searchDir = base_path('docs/search');
        if (!is_dir($searchDir)) {
            mkdir($searchDir, 0755, true);
        }
        
        // Generate search.html interface
        $this->generateSearchInterface();
    }
    
    /**
     * Generate search interface
     */
    private function generateSearchInterface(): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>AskProAI Documentation Search</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .search-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .search-input { width: 100%; padding: 12px 20px; font-size: 16px; border: 2px solid #ddd; border-radius: 24px; outline: none; }
        .search-input:focus { border-color: #4CAF50; }
        .filters { margin-top: 10px; }
        .filter-tag { display: inline-block; padding: 5px 10px; margin: 2px; background: #e0e0e0; border-radius: 15px; cursor: pointer; font-size: 14px; }
        .filter-tag.active { background: #4CAF50; color: white; }
        .results { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .result-item { padding: 15px; border-bottom: 1px solid #eee; }
        .result-item:last-child { border-bottom: none; }
        .result-title { font-size: 18px; font-weight: bold; color: #333; text-decoration: none; }
        .result-title:hover { color: #4CAF50; }
        .result-description { color: #666; margin: 5px 0; }
        .result-meta { font-size: 12px; color: #999; }
        .result-tags { margin-top: 5px; }
        .result-tag { display: inline-block; padding: 2px 8px; margin: 2px; background: #f0f0f0; border-radius: 10px; font-size: 12px; }
        .no-results { text-align: center; padding: 40px; color: #666; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab { padding: 10px 20px; background: white; border-radius: 5px; cursor: pointer; }
        .tab.active { background: #4CAF50; color: white; }
        .highlight { background: yellow; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📚 AskProAI Documentation Search</h1>
        
        <div class="search-box">
            <input type="text" class="search-input" id="searchInput" placeholder="Search documentation, commands, FAQs...">
            <div class="filters" id="filters">
                <!-- Filters will be populated by JavaScript -->
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" data-tab="all">All</div>
            <div class="tab" data-tab="docs">Documentation</div>
            <div class="tab" data-tab="commands">Commands</div>
            <div class="tab" data-tab="faq">FAQs</div>
            <div class="tab" data-tab="glossary">Glossary</div>
        </div>
        
        <div class="results" id="results">
            <div class="no-results">Start typing to search...</div>
        </div>
    </div>
    
    <script>
        // Load search data
        let searchIndex = [];
        let commands = {};
        let faqs = {};
        let glossary = {};
        let tags = {};
        
        // Load all search data
        Promise.all([
            fetch('search/index.json').then(r => r.json()),
            fetch('search/commands.json').then(r => r.json()),
            fetch('search/faq.json').then(r => r.json()),
            fetch('search/glossary.json').then(r => r.json()),
            fetch('search/tags.json').then(r => r.json()),
        ]).then(([index, cmds, faq, gloss, tagData]) => {
            searchIndex = index;
            commands = cmds;
            faqs = faq;
            glossary = gloss;
            tags = tagData;
            
            // Populate filters
            populateFilters();
        });
        
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const resultsContainer = document.getElementById('results');
        let currentTab = 'all';
        let activeFilters = [];
        
        searchInput.addEventListener('input', performSearch);
        
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentTab = tab.dataset.tab;
                performSearch();
            });
        });
        
        function performSearch() {
            const query = searchInput.value.toLowerCase();
            
            if (query.length < 2) {
                resultsContainer.innerHTML = '<div class="no-results">Start typing to search...</div>';
                return;
            }
            
            let results = [];
            
            switch (currentTab) {
                case 'all':
                    results = searchAll(query);
                    break;
                case 'docs':
                    results = searchDocs(query);
                    break;
                case 'commands':
                    results = searchCommands(query);
                    break;
                case 'faq':
                    results = searchFAQs(query);
                    break;
                case 'glossary':
                    results = searchGlossary(query);
                    break;
            }
            
            displayResults(results, query);
        }
        
        function searchAll(query) {
            const results = [];
            
            // Search documentation
            results.push(...searchDocs(query).map(r => ({...r, type: 'doc'})));
            
            // Search commands
            results.push(...searchCommands(query).map(r => ({...r, type: 'command'})));
            
            // Search FAQs
            results.push(...searchFAQs(query).map(r => ({...r, type: 'faq'})));
            
            // Search glossary
            results.push(...searchGlossary(query).map(r => ({...r, type: 'glossary'})));
            
            // Sort by relevance
            return results.sort((a, b) => b.score - a.score).slice(0, 50);
        }
        
        function searchDocs(query) {
            return searchIndex
                .filter(doc => {
                    // Apply tag filters
                    if (activeFilters.length > 0) {
                        const hasTag = activeFilters.some(filter => 
                            doc.tags && doc.tags.includes(filter)
                        );
                        if (!hasTag) return false;
                    }
                    
                    // Search in title, description, and keywords
                    const searchText = `\${doc.title} \${doc.description} \${doc.keywords?.join(' ')}`.toLowerCase();
                    return searchText.includes(query);
                })
                .map(doc => ({
                    ...doc,
                    score: calculateScore(doc, query)
                }));
        }
        
        function searchCommands(query) {
            const results = [];
            
            Object.values(commands).forEach(cmdType => {
                cmdType.forEach(cmd => {
                    if (cmd.command.toLowerCase().includes(query) || 
                        (cmd.description && cmd.description.toLowerCase().includes(query))) {
                        results.push({
                            title: cmd.command,
                            description: cmd.description || 'No description available',
                            file: cmd.file,
                            type: 'command',
                            score: cmd.command.toLowerCase().includes(query) ? 10 : 5
                        });
                    }
                });
            });
            
            return results;
        }
        
        function searchFAQs(query) {
            const results = [];
            
            Object.values(faqs).forEach(category => {
                category.forEach(faq => {
                    if (faq.question.toLowerCase().includes(query) || 
                        faq.answer.toLowerCase().includes(query)) {
                        results.push({
                            title: faq.question,
                            description: faq.answer.substring(0, 200) + '...',
                            file: faq.file,
                            type: 'faq',
                            category: faq.category,
                            score: faq.question.toLowerCase().includes(query) ? 10 : 5
                        });
                    }
                });
            });
            
            return results;
        }
        
        function searchGlossary(query) {
            const results = [];
            
            Object.entries(glossary).forEach(([term, data]) => {
                if (term.toLowerCase().includes(query) || 
                    data.definition.toLowerCase().includes(query)) {
                    results.push({
                        title: term,
                        description: data.definition,
                        file: data.file,
                        type: 'glossary',
                        category: data.category,
                        score: term.toLowerCase().includes(query) ? 10 : 5
                    });
                }
            });
            
            return results;
        }
        
        function calculateScore(doc, query) {
            let score = 0;
            
            // Title match
            if (doc.title && doc.title.toLowerCase().includes(query)) {
                score += 10;
            }
            
            // Description match
            if (doc.description && doc.description.toLowerCase().includes(query)) {
                score += 5;
            }
            
            // Keyword match
            if (doc.keywords) {
                doc.keywords.forEach(keyword => {
                    if (keyword.toLowerCase().includes(query)) {
                        score += 3;
                    }
                });
            }
            
            // Weight bonus
            score += doc.weight || 0;
            
            return score;
        }
        
        function displayResults(results, query) {
            if (results.length === 0) {
                resultsContainer.innerHTML = '<div class="no-results">No results found</div>';
                return;
            }
            
            resultsContainer.innerHTML = results.map(result => `
                <div class="result-item">
                    <a href="\${result.file}" class="result-title">\${highlight(result.title, query)}</a>
                    <div class="result-description">\${highlight(result.description || '', query)}</div>
                    <div class="result-meta">
                        <span>\${result.type}</span> • 
                        <span>\${result.file}</span>
                    </div>
                    \${result.tags ? `
                        <div class="result-tags">
                            \${result.tags.map(tag => `<span class="result-tag">\${tag}</span>`).join('')}
                        </div>
                    ` : ''}
                </div>
            `).join('');
        }
        
        function highlight(text, query) {
            if (!query) return text;
            const regex = new RegExp(`(\${query})`, 'gi');
            return text.replace(regex, '<span class="highlight">$1</span>');
        }
        
        function populateFilters() {
            const filtersContainer = document.getElementById('filters');
            const popularTags = tags.popular_tags || [];
            
            filtersContainer.innerHTML = popularTags.map(tag => `
                <span class="filter-tag" data-tag="\${tag}">\${tag}</span>
            `).join('');
            
            // Add click handlers
            document.querySelectorAll('.filter-tag').forEach(filter => {
                filter.addEventListener('click', () => {
                    filter.classList.toggle('active');
                    const tag = filter.dataset.tag;
                    
                    if (filter.classList.contains('active')) {
                        activeFilters.push(tag);
                    } else {
                        activeFilters = activeFilters.filter(t => t !== tag);
                    }
                    
                    performSearch();
                });
            });
        }
    </script>
</body>
</html>
HTML;
        
        file_put_contents(base_path('public/docs/search.html'), $html);
    }
    
    // Helper methods
    
    private function addTag(string $tag, string $file): void
    {
        $tag = strtolower(trim($tag, '"\' '));
        if (!isset($this->tags[$tag])) {
            $this->tags[$tag] = [];
        }
        if (!in_array($file, $this->tags[$tag])) {
            $this->tags[$tag][] = $file;
        }
    }
    
    private function categorizeTag(string $tag): string
    {
        $technologies = ['php', 'laravel', 'mysql', 'redis', 'docker', 'vue', 'react'];
        $integrations = ['retell', 'calcom', 'stripe', 'twilio', 'webhook'];
        $features = ['appointment', 'booking', 'call', 'customer', 'billing'];
        
        if (in_array($tag, $technologies)) return 'technology';
        if (in_array($tag, $integrations)) return 'integration';
        if (in_array($tag, $features)) return 'feature';
        if (str_contains($tag, 'component') || str_contains($tag, 'resource')) return 'component';
        
        return 'other';
    }
    
    private function categorizeGlossaryTerm(string $term): string
    {
        $term = strtolower($term);
        
        if (preg_match('/api|endpoint|webhook/', $term)) return 'api';
        if (preg_match('/database|table|migration/', $term)) return 'database';
        if (preg_match('/component|service|provider/', $term)) return 'architecture';
        if (preg_match('/user|role|permission/', $term)) return 'auth';
        
        return 'general';
    }
    
    private function extractCommandFromLine(string $line, string $file, string $type): void
    {
        // Clean the line
        $line = trim($line);
        
        // Skip comments and empty lines
        if (empty($line) || str_starts_with($line, '#')) {
            return;
        }
        
        // Extract the actual command
        $command = explode(' ', $line)[0];
        
        $this->commands[] = [
            'command' => $line,
            'type' => $type,
            'subcommand' => $command,
            'file' => $file,
            'description' => '',
        ];
    }
    
    private function extractCommandDescription(string $content, string $command): string
    {
        // Try to find description near the command
        $pattern = '/' . preg_quote($command, '/') . '.*?#\s*(.+)/';
        if (preg_match($pattern, $content, $match)) {
            return trim($match[1]);
        }
        
        // Look for description in previous line
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            if (str_contains($line, $command) && $i > 0) {
                $prevLine = $lines[$i - 1];
                if (str_starts_with(trim($prevLine), '#')) {
                    return trim(substr($prevLine, 1));
                }
            }
        }
        
        return '';
    }
    
    private function categorizeFAQ(string $question): string
    {
        $question = strtolower($question);
        
        if (preg_match('/error|fix|issue|problem/', $question)) return 'troubleshooting';
        if (preg_match('/how to|setup|install|configure/', $question)) return 'howto';
        if (preg_match('/what is|what are|explain/', $question)) return 'concepts';
        if (preg_match('/api|integration/', $question)) return 'integration';
        
        return 'general';
    }
    
    private function extractKeywords(string $content): array
    {
        // Remove markdown syntax
        $text = preg_replace('/[#*`\[\]()]/', ' ', $content);
        
        // Extract words
        $words = str_word_count(strtolower($text), 1);
        
        // Filter stop words
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'and', 'a', 'an', 'as', 'are', 'was', 'were', 'to', 'in', 'for', 'of', 'with', 'by'];
        $words = array_diff($words, $stopWords);
        
        // Count frequency
        $wordCount = array_count_values($words);
        
        // Get top keywords
        arsort($wordCount);
        return array_keys(array_slice($wordCount, 0, 10));
    }
    
    private function getTagsForFile(string $file): array
    {
        $fileTags = [];
        foreach ($this->tags as $tag => $files) {
            if (in_array($file, $files)) {
                $fileTags[] = $tag;
            }
        }
        return $fileTags;
    }
    
    private function getDocumentType(string $file): string
    {
        if (str_contains($file, 'README')) return 'readme';
        if (str_contains($file, 'INSTALL')) return 'installation';
        if (str_contains($file, 'API')) return 'api';
        if (str_contains($file, 'GUIDE')) return 'guide';
        if (str_contains($file, 'TROUBLESHOOT')) return 'troubleshooting';
        if (str_contains($file, 'templates')) return 'template';
        
        return 'documentation';
    }
    
    private function calculateSearchWeight(string $file, string $content): int
    {
        $weight = 0;
        
        // README files get higher weight
        if (str_contains($file, 'README')) $weight += 10;
        
        // Main documentation files
        if ($file === 'CLAUDE.md') $weight += 20;
        
        // Recent files get higher weight
        $modTime = filemtime(base_path($file));
        $daysSinceModified = (time() - $modTime) / 86400;
        if ($daysSinceModified < 7) $weight += 5;
        if ($daysSinceModified < 30) $weight += 3;
        
        // Longer documents might be more comprehensive
        $wordCount = str_word_count($content);
        if ($wordCount > 1000) $weight += 3;
        if ($wordCount > 2000) $weight += 2;
        
        return $weight;
    }
}

// Run optimizer if executed directly
if (php_sapi_name() === 'cli') {
    $optimizer = new SearchOptimizer();
    $optimizer->optimize();
}