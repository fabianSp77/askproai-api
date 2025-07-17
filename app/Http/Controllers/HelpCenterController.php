<?php

namespace App\Http\Controllers;

use App\Models\HelpArticleFeedback;
use App\Models\HelpArticleView;
use App\Models\HelpSearchQuery;
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
        $popularArticles = $this->getPopularArticlesFromAnalytics();
        $searchTrends = HelpSearchQuery::getPopularQueries(10, 7);
        $totalViews = HelpArticleView::where('created_at', '>=', now()->subDays(30))->count();
        
        return view('help-center.index', compact('categories', 'popularArticles', 'searchTrends', 'totalViews'));
    }
    
    /**
     * Display a specific help article
     */
    public function article($category, $topic, Request $request)
    {
        $filePath = resource_path("docs/help-center/{$category}/{$topic}.md");
        
        if (!File::exists($filePath)) {
            abort(404, 'Hilfe-Artikel nicht gefunden');
        }
        
        $markdown = File::get($filePath);
        $html = $this->converter->convert($markdown);
        
        // Extract title from markdown
        $title = $this->extractTitle($markdown);
        
        // Track article view
        $this->trackArticleView($category, $topic, $title, $request);
        
        // Get analytics data
        $viewCount = HelpArticleView::getViewCount($category, $topic);
        $feedbackStats = HelpArticleFeedback::getFeedbackStats($category, $topic);
        
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
            'categoryArticles',
            'viewCount',
            'feedbackStats'
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
            
            // Track search query
            $this->trackSearchQuery($query, count($results), $request);
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
    
    /**
     * Track article view
     */
    protected function trackArticleView($category, $topic, $title, Request $request)
    {
        HelpArticleView::create([
            'category' => $category,
            'topic' => $topic,
            'title' => $title,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'portal_user_id' => auth('portal')->id(),
            'session_id' => session()->getId(),
            'referrer' => $request->header('referer')
        ]);
    }
    
    /**
     * Track search query
     */
    protected function trackSearchQuery($query, $resultsCount, Request $request)
    {
        HelpSearchQuery::create([
            'query' => $query,
            'results_count' => $resultsCount,
            'ip_address' => $request->ip(),
            'portal_user_id' => auth('portal')->id(),
            'session_id' => session()->getId()
        ]);
    }
    
    /**
     * Track search click
     */
    public function trackSearchClick(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
            'clicked_url' => 'required|string'
        ]);
        
        // Update the most recent search query for this session
        HelpSearchQuery::where('session_id', session()->getId())
            ->where('query', $request->query)
            ->whereNull('clicked_result')
            ->latest()
            ->first()
            ?->update(['clicked_result' => $request->clicked_url]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Submit article feedback
     */
    public function submitFeedback(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'topic' => 'required|string',
            'helpful' => 'required|boolean',
            'comment' => 'nullable|string|max:1000'
        ]);
        
        // Check if user already submitted feedback for this article in this session
        $existingFeedback = HelpArticleFeedback::where('category', $request->category)
            ->where('topic', $request->topic)
            ->where('session_id', session()->getId())
            ->first();
        
        if ($existingFeedback) {
            return response()->json([
                'success' => false,
                'message' => 'Sie haben bereits Feedback für diesen Artikel abgegeben.'
            ], 422);
        }
        
        HelpArticleFeedback::create([
            'category' => $request->category,
            'topic' => $request->topic,
            'helpful' => $request->helpful,
            'comment' => $request->comment,
            'ip_address' => $request->ip(),
            'portal_user_id' => auth('portal')->id(),
            'session_id' => session()->getId()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Vielen Dank für Ihr Feedback!'
        ]);
    }
    
    /**
     * Get popular articles from analytics
     */
    protected function getPopularArticlesFromAnalytics()
    {
        $popularFromViews = HelpArticleView::getPopularArticles(4, 30);
        
        $articles = [];
        foreach ($popularFromViews as $article) {
            $articles[] = [
                'title' => $article->title,
                'excerpt' => $this->getArticleExcerpt($article->category, $article->topic),
                'url' => route('help.article', [
                    'category' => $article->category,
                    'topic' => $article->topic
                ]),
                'views' => $article->view_count
            ];
        }
        
        // If we don't have enough popular articles from analytics, fall back to defaults
        if (count($articles) < 4) {
            return $this->getPopularArticles();
        }
        
        return $articles;
    }
    
    /**
     * Get article excerpt
     */
    protected function getArticleExcerpt($category, $topic)
    {
        $filePath = resource_path("docs/help-center/{$category}/{$topic}.md");
        
        if (!File::exists($filePath)) {
            return '';
        }
        
        $content = File::get($filePath);
        // Remove markdown headers and get first paragraph
        $content = preg_replace('/^#.*$/m', '', $content);
        $content = trim($content);
        
        return Str::limit(strip_tags($content), 150);
    }
    
    /**
     * Display analytics dashboard
     */
    public function dashboard(Request $request)
    {
        // Check if user is admin
        if (!auth('portal')->check() || !auth('portal')->user()->is_admin) {
            abort(403, 'Zugriff verweigert');
        }
        
        $days = $request->get('days', 30);
        
        // Get analytics data
        $popularArticles = HelpArticleView::getPopularArticles(10, $days);
        $searchQueries = HelpSearchQuery::getPopularQueries(20, $days);
        $noResultQueries = HelpSearchQuery::getNoResultQueries(10, $days);
        $searchTrends = HelpSearchQuery::getSearchTrends($days);
        $viewTrends = HelpArticleView::getViewsByPeriod(null, null, $days);
        $leastHelpful = HelpArticleFeedback::getLeastHelpfulArticles(10);
        $recentComments = HelpArticleFeedback::getRecentComments(20);
        
        // Calculate totals
        $totalViews = HelpArticleView::where('created_at', '>=', now()->subDays($days))->count();
        $uniqueVisitors = HelpArticleView::where('created_at', '>=', now()->subDays($days))
            ->distinct('session_id')
            ->count('session_id');
        $totalSearches = HelpSearchQuery::where('created_at', '>=', now()->subDays($days))->count();
        $searchConversionRate = HelpSearchQuery::getConversionRate($days);
        
        return view('help-center.dashboard', compact(
            'popularArticles',
            'searchQueries',
            'noResultQueries',
            'searchTrends',
            'viewTrends',
            'leastHelpful',
            'recentComments',
            'totalViews',
            'uniqueVisitors',
            'totalSearches',
            'searchConversionRate',
            'days'
        ));
    }
}