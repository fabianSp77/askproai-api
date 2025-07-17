<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErrorCatalog;
use App\Models\ErrorOccurrence;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ErrorCatalogApiController extends Controller
{
    /**
     * Search errors in the catalog.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|min:2',
            'category' => 'nullable|string|in:AUTH,API,INTEGRATION,DB,QUEUE,UI',
            'service' => 'nullable|string',
            'severity' => 'nullable|string|in:critical,high,medium,low',
            'tag' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = ErrorCatalog::query()
            ->with(['tags', 'solutions' => function ($q) {
                $q->orderBy('order')->limit(3);
            }])
            ->active()
            ->withCount('solutions');

        // Search query
        if ($search = $validated['q'] ?? null) {
            $query->search($search);
        }

        // Filters
        if ($category = $validated['category'] ?? null) {
            $query->byCategory($category);
        }

        if ($service = $validated['service'] ?? null) {
            $query->byService($service);
        }

        if ($severity = $validated['severity'] ?? null) {
            $query->where('severity', $severity);
        }

        if ($tag = $validated['tag'] ?? null) {
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('slug', $tag);
            });
        }

        $limit = $validated['limit'] ?? 20;
        $errors = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $errors->items(),
            'meta' => [
                'current_page' => $errors->currentPage(),
                'last_page' => $errors->lastPage(),
                'per_page' => $errors->perPage(),
                'total' => $errors->total(),
            ],
        ]);
    }

    /**
     * Get a specific error by code.
     */
    public function show(string $errorCode): JsonResponse
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
            ])
            ->active()
            ->first();

        if (!$error) {
            return response()->json([
                'success' => false,
                'message' => 'Error not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $error,
        ]);
    }

    /**
     * Report an error occurrence.
     */
    public function reportOccurrence(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'error_code' => 'required|string|exists:error_catalog,error_code',
            'context' => 'nullable|array',
            'stack_trace' => 'nullable|string',
            'request_url' => 'nullable|string|url',
            'request_method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE',
            'environment' => 'nullable|string|in:production,staging,local',
        ]);

        $error = ErrorCatalog::where('error_code', $validated['error_code'])->first();
        
        // Create occurrence record
        $occurrence = ErrorOccurrence::create([
            'error_catalog_id' => $error->id,
            'company_id' => $request->user()?->company_id,
            'user_id' => $request->user()?->id,
            'environment' => $validated['environment'] ?? config('app.env'),
            'context' => $validated['context'] ?? [],
            'stack_trace' => $validated['stack_trace'] ?? null,
            'request_url' => $validated['request_url'] ?? $request->fullUrl(),
            'request_method' => $validated['request_method'] ?? $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Update error statistics
        $error->recordOccurrence();

        return response()->json([
            'success' => true,
            'message' => 'Error occurrence reported successfully',
            'data' => [
                'occurrence_id' => $occurrence->id,
                'error_code' => $error->error_code,
                'solutions_available' => $error->solutions()->count(),
            ],
        ], 201);
    }

    /**
     * Mark an error occurrence as resolved.
     */
    public function markResolved(Request $request, int $occurrenceId): JsonResponse
    {
        $validated = $request->validate([
            'solution_id' => 'nullable|integer|exists:error_solutions,id',
        ]);

        $occurrence = ErrorOccurrence::findOrFail($occurrenceId);
        
        // Check authorization
        if ($occurrence->company_id && $occurrence->company_id !== $request->user()?->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $occurrence->markAsResolved($validated['solution_id'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Error occurrence marked as resolved',
            'data' => [
                'resolution_time' => $occurrence->getResolutionTimeForHumans(),
            ],
        ]);
    }

    /**
     * Auto-detect errors from text/logs.
     */
    public function detect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:10000',
            'include_solutions' => 'nullable|boolean',
        ]);

        $text = $validated['text'];
        $includeSolutions = $validated['include_solutions'] ?? true;

        // Find all auto-detectable errors
        $detectedErrors = ErrorCatalog::query()
            ->active()
            ->where('auto_detectable', true)
            ->whereNotNull('stack_pattern')
            ->get()
            ->filter(function ($error) use ($text) {
                return $error->matchesPattern($text);
            })
            ->values();

        // Load solutions if requested
        if ($includeSolutions && $detectedErrors->isNotEmpty()) {
            $detectedErrors->load(['solutions' => function ($q) {
                $q->orderBy('order')->limit(3);
            }]);
        }

        return response()->json([
            'success' => true,
            'detected_count' => $detectedErrors->count(),
            'data' => $detectedErrors,
        ]);
    }

    /**
     * Get error statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:today,week,month,year',
            'company_id' => 'nullable|integer',
        ]);

        $period = $validated['period'] ?? 'month';
        $companyId = $validated['company_id'] ?? $request->user()?->company_id;

        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        // Base query for occurrences
        $occurrenceQuery = ErrorOccurrence::where('created_at', '>=', $startDate);
        
        if ($companyId) {
            $occurrenceQuery->where('company_id', $companyId);
        }

        // Get statistics
        $stats = [
            'total_errors' => ErrorCatalog::active()->count(),
            'critical_errors' => ErrorCatalog::active()->critical()->count(),
            'occurrences' => [
                'total' => $occurrenceQuery->count(),
                'resolved' => (clone $occurrenceQuery)->resolved()->count(),
                'unresolved' => (clone $occurrenceQuery)->unresolved()->count(),
                'avg_resolution_time' => (clone $occurrenceQuery)->resolved()->avg('resolution_time'),
            ],
            'top_errors' => ErrorCatalog::query()
                ->select('error_catalog.*')
                ->join('error_occurrences', 'error_catalog.id', '=', 'error_occurrences.error_catalog_id')
                ->where('error_occurrences.created_at', '>=', $startDate)
                ->when($companyId, function ($q) use ($companyId) {
                    $q->where('error_occurrences.company_id', $companyId);
                })
                ->groupBy('error_catalog.id')
                ->orderByRaw('COUNT(error_occurrences.id) DESC')
                ->limit(5)
                ->withCount(['occurrences as recent_occurrences' => function ($q) use ($startDate, $companyId) {
                    $q->where('created_at', '>=', $startDate);
                    if ($companyId) {
                        $q->where('company_id', $companyId);
                    }
                }])
                ->get(['error_catalog.*']),
            'by_category' => ErrorOccurrence::query()
                ->select('error_catalog.category', \DB::raw('COUNT(*) as count'))
                ->join('error_catalog', 'error_occurrences.error_catalog_id', '=', 'error_catalog.id')
                ->where('error_occurrences.created_at', '>=', $startDate)
                ->when($companyId, function ($q) use ($companyId) {
                    $q->where('error_occurrences.company_id', $companyId);
                })
                ->groupBy('error_catalog.category')
                ->pluck('count', 'category'),
        ];

        return response()->json([
            'success' => true,
            'period' => $period,
            'start_date' => $startDate->toDateTimeString(),
            'data' => $stats,
        ]);
    }
}