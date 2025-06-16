<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseApiController extends Controller
{
    /**
     * Default relationships to include
     */
    protected array $defaultIncludes = [];
    
    /**
     * Available relationships that can be included
     */
    protected array $availableIncludes = [];
    
    /**
     * Fields that can be used for sparse fieldsets
     */
    protected array $allowedFields = [];
    
    /**
     * Default pagination size
     */
    protected int $defaultPerPage = 15;
    
    /**
     * Maximum pagination size
     */
    protected int $maxPerPage = 100;
    
    /**
     * Get includes from request
     */
    protected function getRequestedIncludes(Request $request): array
    {
        $requested = $request->input('include', '');
        $includes = array_filter(explode(',', $requested));
        
        // Only allow available includes
        return array_intersect($includes, $this->availableIncludes);
    }
    
    /**
     * Get fields from request for sparse fieldsets
     */
    protected function getRequestedFields(Request $request): array
    {
        $fields = [];
        
        foreach ($request->input('fields', []) as $type => $fieldList) {
            $requestedFields = array_filter(explode(',', $fieldList));
            
            if (isset($this->allowedFields[$type])) {
                $fields[$type] = array_intersect($requestedFields, $this->allowedFields[$type]);
            }
        }
        
        return $fields;
    }
    
    /**
     * Apply eager loading based on request
     */
    protected function applyEagerLoading($query, Request $request): void
    {
        $includes = array_merge($this->defaultIncludes, $this->getRequestedIncludes($request));
        
        if (!empty($includes)) {
            // Check if model supports smart loading
            $model = $query->getModel();
            
            if (method_exists($model, 'scopeForApi')) {
                $query->forApi($includes);
            } else {
                $query->with($includes);
            }
        }
    }
    
    /**
     * Apply sparse fieldsets
     */
    protected function applySparseFieldsets($query, Request $request): void
    {
        $fields = $this->getRequestedFields($request);
        
        if (!empty($fields)) {
            foreach ($fields as $type => $fieldList) {
                if ($type === 'main') {
                    // Ensure primary key is always included
                    if (!in_array('id', $fieldList)) {
                        $fieldList[] = 'id';
                    }
                    $query->select($fieldList);
                }
            }
        }
    }
    
    /**
     * Get pagination size from request
     */
    protected function getPerPage(Request $request): int
    {
        $perPage = $request->input('per_page', $this->defaultPerPage);
        
        return min(max(1, $perPage), $this->maxPerPage);
    }
    
    /**
     * Format response for list
     */
    protected function respondWithCollection($query, Request $request): JsonResponse
    {
        $this->applyEagerLoading($query, $request);
        $this->applySparseFieldsets($query, $request);
        
        $perPage = $this->getPerPage($request);
        $data = $query->paginate($perPage);
        
        return $this->formatPaginatedResponse($data, $request);
    }
    
    /**
     * Format response for single item
     */
    protected function respondWithItem(Model $item, Request $request): JsonResponse
    {
        $includes = $this->getRequestedIncludes($request);
        
        // Load missing relationships
        if (method_exists($item, 'loadMissing')) {
            $item->loadMissing($includes);
        } else {
            $item->load($includes);
        }
        
        return response()->json([
            'data' => $this->transformItem($item, $request),
        ]);
    }
    
    /**
     * Format paginated response
     */
    protected function formatPaginatedResponse(LengthAwarePaginator $paginator, Request $request): JsonResponse
    {
        $transformed = $paginator->getCollection()->map(function ($item) use ($request) {
            return $this->transformItem($item, $request);
        });
        
        return response()->json([
            'data' => $transformed,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
    
    /**
     * Transform item for response
     */
    protected function transformItem(Model $item, Request $request): array
    {
        // Override in child controllers for custom transformation
        return $item->toArray();
    }
    
    /**
     * Success response
     */
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
    
    /**
     * Error response
     */
    protected function error(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
    
    /**
     * Add query performance metrics to response in debug mode
     */
    protected function addPerformanceMetrics(JsonResponse $response): JsonResponse
    {
        if (config('app.debug')) {
            $queries = \DB::getQueryLog();
            
            $response->headers->set('X-Query-Count', count($queries));
            $response->headers->set('X-Query-Time', array_sum(array_column($queries, 'time')));
            
            // Detect potential N+1 queries
            $n1Count = 0;
            foreach ($queries as $query) {
                if (preg_match('/select .* from .* where .* in \(/i', $query['query'])) {
                    $n1Count++;
                }
            }
            
            if ($n1Count > 0) {
                $response->headers->set('X-N1-Warning', $n1Count);
            }
        }
        
        return $response;
    }
}