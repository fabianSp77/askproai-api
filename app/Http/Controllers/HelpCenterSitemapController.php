<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class HelpCenterSitemapController extends Controller
{
    /**
     * Generate XML sitemap for help center
     */
    public function index()
    {
        $categories = $this->getCategories();
        $articles = [];
        
        // Collect all articles with their URLs
        foreach ($categories as $categoryKey => $category) {
            foreach ($category['articles'] as $article) {
                $articles[] = [
                    'url' => route('help.article', [
                        'category' => $categoryKey,
                        'topic' => $article['topic']
                    ]),
                    'lastmod' => $this->getLastModified($categoryKey, $article['topic']),
                    'priority' => $this->getPriority($categoryKey, $article['topic']),
                    'changefreq' => 'weekly'
                ];
            }
        }
        
        // Add main pages
        $mainPages = [
            [
                'url' => route('help.index'),
                'lastmod' => now()->toIso8601String(),
                'priority' => '1.0',
                'changefreq' => 'daily'
            ],
            [
                'url' => route('help.search'),
                'lastmod' => now()->toIso8601String(),
                'priority' => '0.9',
                'changefreq' => 'daily'
            ]
        ];
        
        $urls = array_merge($mainPages, $articles);
        
        return response()
            ->view('help-center.sitemap', compact('urls'))
            ->header('Content-Type', 'text/xml');
    }
    
    /**
     * Get categories with articles
     */
    protected function getCategories()
    {
        $categories = [
            'getting-started' => [
                'name' => 'Erste Schritte',
                'articles' => []
            ],
            'appointments' => [
                'name' => 'Termine verwalten',
                'articles' => []
            ],
            'account' => [
                'name' => 'Ihr Konto',
                'articles' => []
            ],
            'billing' => [
                'name' => 'Rechnungen & Zahlungen',
                'articles' => []
            ],
            'troubleshooting' => [
                'name' => 'Fehlerbehebung',
                'articles' => []
            ],
            'faq' => [
                'name' => 'Häufige Fragen',
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
                        $topic = str_replace('.md', '', $file->getFilename());
                        $category['articles'][] = [
                            'topic' => $topic
                        ];
                    }
                }
            }
        }
        
        return $categories;
    }
    
    /**
     * Get last modified date for article
     */
    protected function getLastModified($category, $topic)
    {
        $filePath = resource_path("docs/help-center/{$category}/{$topic}.md");
        if (File::exists($filePath)) {
            return File::lastModified($filePath);
        }
        return now()->timestamp;
    }
    
    /**
     * Get priority based on category
     */
    protected function getPriority($category, $topic)
    {
        // High priority articles
        $highPriority = [
            'getting-started' => ['first-call', 'portal-overview'],
            'appointments' => ['book-by-phone', 'view-appointments'],
            'account' => ['registration', 'login', 'password-change']
        ];
        
        if (isset($highPriority[$category]) && in_array($topic, $highPriority[$category])) {
            return '0.8';
        }
        
        // Category-based priority
        $categoryPriority = [
            'getting-started' => '0.7',
            'appointments' => '0.7',
            'account' => '0.6',
            'billing' => '0.5',
            'troubleshooting' => '0.6',
            'faq' => '0.4'
        ];
        
        return $categoryPriority[$category] ?? '0.5';
    }
}