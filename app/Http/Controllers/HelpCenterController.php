<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;

class HelpCenterController extends Controller
{
    protected $converter;
    
    public function __construct()
    {
        // Configure the markdown converter
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        
        $this->converter = new CommonMarkConverter([], $environment);
    }
    
    /**
     * Display the help center index
     */
    public function index()
    {
        $categories = $this->getCategories();
        $popularArticles = $this->getPopularArticles();
        
        return view('help-center.index', compact('categories', 'popularArticles'));
    }
    
    /**
     * Display a specific help article
     */
    public function article($category, $topic)
    {
        $filePath = resource_path("docs/help-center/{$category}/{$topic}.md");
        
        if (!File::exists($filePath)) {
            abort(404, 'Hilfe-Artikel nicht gefunden');
        }
        
        $markdown = File::get($filePath);
        $html = $this->converter->convert($markdown);
        
        // Extract title from markdown
        $title = $this->extractTitle($markdown);
        
        // Get related articles
        $relatedArticles = $this->getRelatedArticles($category, $topic);
        
        // Get category articles for sidebar
        $categoryArticles = $this->getCategoryArticles($category);
        
        $breadcrumbs = [
            ['name' => 'Hilfe-Center', 'url' => route('help.index')],
            ['name' => $this->formatCategoryName($category), 'url' => '#'],
            ['name' => $title, 'url' => null]
        ];
        
        $content = $html;
        
        return view('help-center.article', compact(
            'title', 
            'content', 
            'category', 
            'topic',
            'breadcrumbs',
            'relatedArticles',
            'categoryArticles'
        ));
    }
    
    /**
     * Search help articles
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $results = [];
        
        if ($query) {
            $docsPath = resource_path('docs/help-center');
            $files = File::allFiles($docsPath);
            
            foreach ($files as $file) {
                if ($file->getExtension() !== 'md') continue;
                
                $content = $file->getContents();
                $relativePath = str_replace($docsPath . '/', '', $file->getPathname());
                $relativePath = str_replace('.md', '', $relativePath);
                
                // Calculate relevance score
                $score = $this->calculateRelevance($content, $query);
                
                if ($score > 0) {
                    $parts = explode('/', $relativePath);
                    $title = $this->extractTitle($content);
                    $excerpt = $this->extractExcerpt($content, $query);
                    
                    $results[] = [
                        'title' => $title,
                        'excerpt' => $excerpt,
                        'category' => $parts[0] ?? '',
                        'topic' => $parts[1] ?? '',
                        'url' => route('help.article', [
                            'category' => $parts[0] ?? '',
                            'topic' => $parts[1] ?? ''
                        ]),
                        'score' => $score
                    ];
                }
            }
            
            // Sort by relevance score
            usort($results, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
        }
        
        return view('help-center.search', compact('query', 'results'));
    }
    
    /**
     * Get all categories with their articles
     */
    protected function getCategories()
    {
        $categories = [
            'getting-started' => [
                'name' => 'Erste Schritte',
                'icon' => 'lightning-bolt',
                'articles' => []
            ],
            'appointments' => [
                'name' => 'Termine verwalten',
                'icon' => 'calendar',
                'articles' => []
            ],
            'account' => [
                'name' => 'Ihr Konto',
                'icon' => 'user',
                'articles' => []
            ],
            'billing' => [
                'name' => 'Rechnungen & Zahlungen',
                'icon' => 'credit-card',
                'articles' => []
            ],
            'troubleshooting' => [
                'name' => 'Fehlerbehebung',
                'icon' => 'exclamation-circle',
                'articles' => []
            ],
            'faq' => [
                'name' => 'Häufige Fragen',
                'icon' => 'question-mark-circle',
                'articles' => []
            ]
        ];
        
        // Populate articles for each category
        foreach ($categories as $categoryKey => &$category) {
            $categoryPath = resource_path("docs/help-center/{$categoryKey}");
            if (File::exists($categoryPath)) {
                $files = File::files($categoryPath);
                foreach ($files as $file) {
                    if ($file->getExtension() === 'md') {
                        $content = File::get($file);
                        $topic = str_replace('.md', '', $file->getFilename());
                        $category['articles'][] = [
                            'title' => $this->extractTitle($content),
                            'topic' => $topic,
                            'url' => route('help.article', [
                                'category' => $categoryKey,
                                'topic' => $topic
                            ])
                        ];
                    }
                }
            }
        }
        
        return $categories;
    }
    
    /**
     * Get popular articles
     */
    protected function getPopularArticles()
    {
        return [
            [
                'title' => 'Wie buche ich einen Termin per Telefon?',
                'excerpt' => 'Schritt-für-Schritt Anleitung für die Terminbuchung über unseren KI-Assistenten...',
                'url' => route('help.article', ['category' => 'getting-started', 'topic' => 'first-call'])
            ],
            [
                'title' => 'Passwort vergessen - was nun?',
                'excerpt' => 'So setzen Sie Ihr Passwort schnell und sicher zurück...',
                'url' => route('help.article', ['category' => 'account', 'topic' => 'password-change'])
            ],
            [
                'title' => 'Termine absagen oder verschieben',
                'excerpt' => 'Flexibel bleiben - so ändern Sie Ihre Termine ohne Gebühren...',
                'url' => route('help.article', ['category' => 'appointments', 'topic' => 'cancel-reschedule'])
            ],
            [
                'title' => 'Rechnungen herunterladen',
                'excerpt' => 'Alle Rechnungen als PDF für Ihre Unterlagen speichern...',
                'url' => route('help.article', ['category' => 'billing', 'topic' => 'view-invoices'])
            ]
        ];
    }
    
    /**
     * Get related articles for a topic
     */
    protected function getRelatedArticles($category, $currentTopic)
    {
        $articles = [];
        $categoryPath = resource_path("docs/help-center/{$category}");
        
        if (File::exists($categoryPath)) {
            $files = File::files($categoryPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'md') {
                    $topic = str_replace('.md', '', $file->getFilename());
                    if ($topic !== $currentTopic) {
                        $content = File::get($file);
                        $articles[] = [
                            'title' => $this->extractTitle($content),
                            'topic' => $topic,
                            'url' => route('help.article', [
                                'category' => $category,
                                'topic' => $topic
                            ])
                        ];
                    }
                }
            }
        }
        
        return array_slice($articles, 0, 3); // Return max 3 related articles
    }
    
    /**
     * Get all articles in a category
     */
    protected function getCategoryArticles($category)
    {
        $articles = [];
        $categoryPath = resource_path("docs/help-center/{$category}");
        
        if (File::exists($categoryPath)) {
            $files = File::files($categoryPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'md') {
                    $topic = str_replace('.md', '', $file->getFilename());
                    $content = File::get($file);
                    $articles[] = [
                        'title' => $this->extractTitle($content),
                        'topic' => $topic,
                        'url' => route('help.article', [
                            'category' => $category,
                            'topic' => $topic
                        ])
                    ];
                }
            }
        }
        
        return $articles;
    }
    
    /**
     * Extract title from markdown content
     */
    protected function extractTitle($markdown)
    {
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
            return $matches[1];
        }
        return 'Untitled';
    }
    
    /**
     * Extract excerpt around search query
     */
    protected function extractExcerpt($content, $query)
    {
        $plainText = strip_tags($content);
        $position = stripos($plainText, $query);
        
        if ($position !== false) {
            $start = max(0, $position - 100);
            $excerpt = substr($plainText, $start, 200);
            
            // Highlight the query
            $excerpt = str_ireplace($query, "<strong>{$query}</strong>", $excerpt);
            
            if ($start > 0) {
                $excerpt = '...' . $excerpt;
            }
            if (strlen($plainText) > $start + 200) {
                $excerpt .= '...';
            }
            
            return $excerpt;
        }
        
        return Str::limit($plainText, 200);
    }
    
    /**
     * Calculate relevance score for search
     */
    protected function calculateRelevance($content, $query)
    {
        $score = 0;
        $lowerContent = strtolower($content);
        $lowerQuery = strtolower($query);
        
        // Title match (highest weight)
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            $title = strtolower($matches[1]);
            if (strpos($title, $lowerQuery) !== false) {
                $score += 10;
            }
        }
        
        // Count occurrences in content
        $score += substr_count($lowerContent, $lowerQuery);
        
        // Bonus for word boundaries
        if (preg_match('/\b' . preg_quote($lowerQuery, '/') . '\b/i', $content)) {
            $score += 5;
        }
        
        return $score;
    }
    
    /**
     * Format category name for display
     */
    protected function formatCategoryName($category)
    {
        $names = [
            'getting-started' => 'Erste Schritte',
            'appointments' => 'Termine',
            'account' => 'Konto',
            'billing' => 'Rechnungen',
            'troubleshooting' => 'Fehlerbehebung',
            'faq' => 'FAQ'
        ];
        
        return $names[$category] ?? ucfirst(str_replace('-', ' ', $category));
    }
}