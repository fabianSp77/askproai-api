<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeTag;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KnowledgeController extends Controller
{
    protected KnowledgeBaseService $knowledgeService;
    
    public function __construct(KnowledgeBaseService $knowledgeService)
    {
        $this->knowledgeService = $knowledgeService;
    }
    
    /**
     * Display the knowledge base home page
     */
    public function index()
    {
        $categories = Cache::remember('knowledge:categories', 300, function () {
            return $this->knowledgeService->getCategories();
        });
        
        $popularDocuments = Cache::remember('knowledge:popular', 300, function () {
            return $this->knowledgeService->getPopularDocuments(8);
        });
        
        $recentDocuments = Cache::remember('knowledge:recent', 300, function () {
            return $this->knowledgeService->getRecentDocuments(8);
        });
        
        $tags = Cache::remember('knowledge:tags', 300, function () {
            return $this->knowledgeService->getTags(30);
        });
        
        return view('knowledge.index', compact(
            'categories',
            'popularDocuments',
            'recentDocuments',
            'tags'
        ));
    }
    
    /**
     * Display a specific document
     */
    public function show($slug)
    {
        $document = $this->knowledgeService->getDocument($slug);
        
        if (!$document) {
            abort(404, 'Document not found');
        }
        
        $relatedDocuments = $this->knowledgeService->getRelatedDocuments($document);
        
        // Get navigation
        $previousDoc = $document->getPreviousDocument();
        $nextDoc = $document->getNextDocument();
        
        // Get table of contents from headers
        $toc = $this->extractTableOfContents($document->html_content);
        
        return view('knowledge.show', compact(
            'document',
            'relatedDocuments',
            'previousDoc',
            'nextDoc',
            'toc'
        ));
    }
    
    /**
     * Display documents in a category
     */
    public function category($slug)
    {
        $category = KnowledgeCategory::where('slug', $slug)
            ->with(['children', 'documents' => function ($query) {
                $query->published()->orderBy('order')->orderBy('title');
            }])
            ->firstOrFail();
        
        $breadcrumbs = $category->breadcrumbs;
        
        return view('knowledge.category', compact('category', 'breadcrumbs'));
    }
    
    /**
     * Display documents with a specific tag
     */
    public function tag($slug)
    {
        $tag = KnowledgeTag::where('slug', $slug)->firstOrFail();
        
        $documents = $tag->documents()
            ->published()
            ->orderBy('views_count', 'desc')
            ->paginate(20);
        
        return view('knowledge.tag', compact('tag', 'documents'));
    }
    
    /**
     * Search documents
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $filters = $request->only(['category_id', 'type', 'tags']);
        
        if (empty($query) && empty($filters)) {
            return redirect()->route('knowledge.index');
        }
        
        $results = $this->knowledgeService->search($query, $filters);
        
        // Get filter options
        $categories = KnowledgeCategory::visible()->get();
        $availableTags = KnowledgeTag::popular(20)->get();
        
        return view('knowledge.search', compact(
            'query',
            'results',
            'filters',
            'categories',
            'availableTags'
        ));
    }
    
    /**
     * Natural language search (AJAX)
     */
    public function naturalSearch(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json(['results' => []]);
        }
        
        $results = $this->knowledgeService->naturalLanguageSearch($query);
        
        return response()->json(['results' => $results]);
    }
    
    /**
     * Refresh document index (admin only)
     */
    public function refreshIndex()
    {
        $this->authorize('admin');
        
        $discovered = $this->knowledgeService->discoverDocuments();
        
        Cache::flush(); // Clear all knowledge caches
        
        return back()->with('success', 'Knowledge base index refreshed. ' . count($discovered) . ' documents indexed.');
    }
    
    /**
     * Extract table of contents from HTML
     */
    protected function extractTableOfContents(string $html): array
    {
        $toc = [];
        
        if (preg_match_all('/<h([2-4])\s+id="([^"]+)"[^>]*>(.+?)<\/h\1>/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $level = intval($match[1]);
                $id = $match[2];
                $text = strip_tags($match[3]);
                
                $toc[] = [
                    'level' => $level,
                    'id' => $id,
                    'text' => $text,
                ];
            }
        }
        
        return $toc;
    }
    
    /**
     * Get document edit link (for authenticated users)
     */
    public function getEditLink($slug)
    {
        $document = KnowledgeDocument::where('slug', $slug)->firstOrFail();
        
        if (!auth()->check() || !auth()->user()->can('edit', $document)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Generate VS Code link
        $vscodeLink = 'vscode://file' . $document->file_path;
        
        return response()->json([
            'file_path' => $document->file_path,
            'vscode_link' => $vscodeLink,
            'github_link' => $this->getGithubEditLink($document),
        ]);
    }
    
    /**
     * Get GitHub edit link if repository is configured
     */
    protected function getGithubEditLink(KnowledgeDocument $document): ?string
    {
        $githubRepo = config('knowledge.github_repo');
        if (!$githubRepo) {
            return null;
        }
        
        $branch = config('knowledge.github_branch', 'main');
        return "https://github.com/{$githubRepo}/edit/{$branch}/{$document->path}";
    }
    
    /**
     * Track document download
     */
    public function download($slug)
    {
        $document = KnowledgeDocument::where('slug', $slug)->firstOrFail();
        
        // Track download event
        $this->knowledgeService->trackAnalytics($document, 'download');
        
        $filename = $document->slug . '.md';
        $headers = [
            'Content-Type' => 'text/markdown',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        return response($document->content, 200, $headers);
    }
    
    /**
     * Export document as PDF
     */
    public function exportPdf($slug)
    {
        $document = KnowledgeDocument::where('slug', $slug)->firstOrFail();
        
        // Track export event
        $this->knowledgeService->trackAnalytics($document, 'export_pdf');
        
        // Generate PDF (using a package like barryvdh/laravel-dompdf)
        // This is a placeholder - you'd need to install and configure a PDF package
        
        return back()->with('info', 'PDF export feature coming soon!');
    }
}