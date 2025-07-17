<?php

namespace App\Http\Controllers;

use App\Models\ErrorCatalog;
use App\Models\ErrorTag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ErrorCatalogController extends Controller
{
    /**
     * Display the error catalog search page.
     */
    public function index(Request $request): View
    {
        $query = ErrorCatalog::query()
            ->with(['tags', 'solutions' => function ($q) {
                $q->orderBy('order');
            }, 'preventionTips'])
            ->active()
            ->withCount('solutions');

        // Search functionality
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        // Category filter
        if ($category = $request->get('category')) {
            $query->byCategory($category);
        }

        // Service filter
        if ($service = $request->get('service')) {
            $query->byService($service);
        }

        // Severity filter
        if ($severity = $request->get('severity')) {
            $query->where('severity', $severity);
        }

        // Tag filter
        if ($tag = $request->get('tag')) {
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('slug', $tag);
            });
        }

        // Sort options
        $sortBy = $request->get('sort', 'occurrence_count');
        $sortDirection = $request->get('direction', 'desc');
        
        $validSorts = ['occurrence_count', 'last_occurred_at', 'avg_resolution_time', 'severity'];
        if (in_array($sortBy, $validSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $errors = $query->paginate(20)->withQueryString();

        // Get filter options
        $categories = [
            'AUTH' => 'Authentication',
            'API' => 'API Integration',
            'INTEGRATION' => 'External Integration',
            'DB' => 'Database',
            'QUEUE' => 'Queue/Jobs',
            'UI' => 'User Interface',
        ];

        $services = [
            'retell' => 'Retell.ai',
            'calcom' => 'Cal.com',
            'stripe' => 'Stripe',
            'webhook' => 'Webhook',
            'internal' => 'Internal',
        ];

        $severities = [
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
        ];

        $allTags = ErrorTag::all();
        $totalErrors = ErrorCatalog::count();

        return view('errors.index', compact(
            'errors',
            'categories',
            'allTags',
            'totalErrors'
        ));
    }

    /**
     * Display a single error with its solutions.
     */
    public function show(string $errorCode): View
    {
        $error = ErrorCatalog::where('error_code', $errorCode)
            ->with([
                'solutions' => function ($q) {
                    $q->orderBy('order');
                },
                'preventionTips' => function ($q) {
                    $q->orderBy('order');
                },
                'tags',
                'relatedErrors' => function ($q) {
                    $q->limit(5);
                }
            ])
            ->active()
            ->firstOrFail();

        // Record view (optional - for analytics)
        // $error->increment('view_count');

        // Get similar errors
        $similarErrors = $error->getSimilarErrors(5);

        return view('errors.show', compact('error', 'similarErrors'));
    }

    /**
     * Submit feedback for a solution.
     */
    public function submitFeedback(Request $request, int $solutionId)
    {
        $validated = $request->validate([
            'was_helpful' => 'required|boolean',
            'comment' => 'nullable|string|max:500',
        ]);

        $solution = \App\Models\ErrorSolution::findOrFail($solutionId);

        $solution->feedback()->create([
            'user_id' => auth()->id(),
            'was_helpful' => $validated['was_helpful'],
            'comment' => $validated['comment'] ?? null,
        ]);

        // Update solution success/failure count
        if ($validated['was_helpful']) {
            $solution->recordSuccess();
        } else {
            $solution->recordFailure();
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your feedback!',
        ]);
    }

    /**
     * Get autocomplete suggestions for search.
     */
    public function autocomplete(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $errors = ErrorCatalog::search($query)
            ->active()
            ->limit(10)
            ->get(['error_code', 'title', 'category', 'severity']);

        return response()->json($errors->map(function ($error) {
            return [
                'value' => $error->error_code,
                'label' => $error->error_code . ' - ' . $error->title,
                'category' => $error->category,
                'severity' => $error->severity,
            ];
        }));
    }
}