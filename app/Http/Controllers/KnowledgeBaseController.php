<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeTag;
use App\Models\KnowledgeAnalytic;
use App\Models\KnowledgeFeedback;
use App\Services\KnowledgeBaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class KnowledgeBaseController extends Controller
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
        $categories = Cache::remember('knowledge_categories', 300, function () {
            return KnowledgeCategory::with(['children' => function ($query) {
                $query->ordered()->withCount('documents');
            }])
                ->root()
                ->ordered()
                ->withCount('documents')
                ->get();
        });
        
        $popularDocuments = Cache::remember('knowledge_popular_docs', 300, function () {
            return KnowledgeDocument::published()
                ->orderBy('view_count', 'desc')
                ->limit(10)
                ->get();
        });
        
        $recentDocuments = Cache::remember('knowledge_recent_docs', 300, function () {
            return KnowledgeDocument::published()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        });
        
        $popularTags = Cache::remember('knowledge_popular_tags', 300, function () {
            return KnowledgeTag::withCount('documents')
                ->having('documents_count', '>', 0)
                ->orderBy('documents_count', 'desc')
                ->limit(20)
                ->get();
        });
        
        return view('knowledge.index', compact(
            'categories',
            'popularDocuments',
            'recentDocuments',
            'popularTags'
        ));
    }
    
    /**
     * Display documents in a category
     */
    public function category($slug)
    {
        $category = KnowledgeCategory::where('slug', $slug)
            ->with(['parent', 'children' => function ($query) {
                $query->ordered()->withCount('documents');
            }])
            ->firstOrFail();
        
        $documents = $category->documents()
            ->published()
            ->with(['tags'])
            ->orderBy('order')
            ->orderBy('title')
            ->paginate(20);
        
        // Get breadcrumbs
        $breadcrumbs = $this->getCategoryBreadcrumbs($category);
        
        return view('knowledge.category', compact('category', 'documents', 'breadcrumbs'));
    }
    
    /**
     * Display a single document
     */
    public function show($slug)
    {
        $document = KnowledgeDocument::where('slug', $slug)
            ->published()
            ->with(['category', 'tags', 'relatedDocuments' => function ($query) {
                $query->published()->limit(5);
            }])
            ->firstOrFail();
        
        // Increment view count
        $document->increment('view_count');
        
        // Log analytics
        KnowledgeAnalytic::logView($document, [
            'referrer' => request()->header('referer'),
        ]);
        
        // Get breadcrumbs
        $breadcrumbs = [];
        if ($document->category) {
            $breadcrumbs = $this->getCategoryBreadcrumbs($document->category);
        }
        $breadcrumbs[] = [
            'name' => $document->title,
            'url' => null,
        ];
        
        // Get feedback stats
        $feedbackStats = Cache::remember("knowledge_feedback_{$document->id}", 300, function () use ($document) {
            return [
                'helpful' => $document->feedback()->helpful()->count(),
                'not_helpful' => $document->feedback()->notHelpful()->count(),
            ];
        });
        
        return view('knowledge.show', compact('document', 'breadcrumbs', 'feedbackStats'));
    }
    
    /**
     * Search documents
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $categoryId = $request->get('category');
        $tags = $request->get('tags', []);
        
        if (empty($query) && empty($categoryId) && empty($tags)) {
            return redirect()->route('knowledge.index');
        }
        
        $filters = [
            'category_id' => $categoryId,
            'tags' => $tags,
            'status' => 'published',
        ];
        
        $documents = $this->knowledgeService->search($query, $filters);
        
        // Log search
        if (!empty($query)) {
            KnowledgeAnalytic::logSearch($query, $documents->toArray());
        }
        
        $categories = KnowledgeCategory::ordered()->get();
        $allTags = KnowledgeTag::orderBy('name')->get();
        
        return view('knowledge.search', compact(
            'documents',
            'query',
            'categoryId',
            'tags',
            'categories',
            'allTags'
        ));
    }
    
    /**
     * Show documents by tag
     */
    public function tag($slug)
    {
        $tag = KnowledgeTag::where('slug', $slug)->firstOrFail();
        
        $documents = $tag->documents()
            ->published()
            ->with(['category', 'tags'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('knowledge.tag', compact('tag', 'documents'));
    }
    
    /**
     * Submit feedback for a document
     */
    public function feedback(Request $request, $slug)
    {
        $request->validate([
            'helpful' => 'required|boolean',
            'comment' => 'nullable|string|max:1000',
        ]);
        
        $document = KnowledgeDocument::where('slug', $slug)->firstOrFail();
        
        // Check if user already submitted feedback
        $sessionId = session()->getId();
        $existingFeedback = KnowledgeFeedback::where('document_id', $document->id)
            ->where(function ($query) use ($sessionId) {
                $query->where('session_id', $sessionId);
                if (auth()->check()) {
                    $query->orWhere('user_id', auth()->id());
                }
            })
            ->first();
        
        if ($existingFeedback) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted feedback for this document.',
            ], 422);
        }
        
        // Create feedback
        KnowledgeFeedback::create([
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'session_id' => $sessionId,
            'is_helpful' => $request->helpful,
            'comment' => $request->comment,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        // Update document counters
        if ($request->helpful) {
            $document->increment('helpful_count');
        } else {
            $document->increment('not_helpful_count');
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Thank you for your feedback!',
        ]);
    }
    
    /**
     * Get category breadcrumbs
     */
    protected function getCategoryBreadcrumbs(KnowledgeCategory $category): array
    {
        $breadcrumbs = [];
        $current = $category;
        
        while ($current) {
            array_unshift($breadcrumbs, [
                'name' => $current->name,
                'url' => route('knowledge.category', $current->slug),
            ]);
            $current = $current->parent;
        }
        
        array_unshift($breadcrumbs, [
            'name' => 'Knowledge Base',
            'url' => route('knowledge.index'),
        ]);
        
        return $breadcrumbs;
    }
}