<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Cal.com Branches API Controller
 *
 * Provides branch listing for Cal.com booking widget team/branch selection
 */
class CalcomBranchesController extends Controller
{
    /**
     * Get all branches for the authenticated user's company
     *
     * Response format:
     * [
     *   {
     *     "id": 1,
     *     "name": "Hauptfiliale",
     *     "slug": "hauptfiliale",
     *     "services_count": 12,
     *     "is_default": true,
     *     "address": "Musterstraße 1, 12345 Berlin"
     *   }
     * ]
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'error' => 'User not authenticated or not associated with a company',
                'branches' => []
            ], 401);
        }

        // SEC-001: Authorization check - verify user can view branches
        if (Gate::denies('viewAny', Branch::class)) {
            return response()->json([
                'error' => 'Unauthorized to view branches',
                'branches' => []
            ], 403);
        }

        try {
            // Get branches for the user's company with service counts
            $branches = Branch::where('company_id', $user->company_id)
                ->with('services:id,branch_id,name,is_active')
                ->withCount(['services' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->get()
                ->map(function ($branch) use ($user) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'slug' => $branch->slug ?? \Illuminate\Support\Str::slug($branch->name),
                        'services_count' => $branch->services_count,
                        'is_default' => $branch->id === $user->branch_id,
                        'address' => $this->formatAddress($branch),
                    ];
                });

            return response()->json([
                'success' => true,
                'branches' => $branches,
                'default_branch_id' => $branches->firstWhere('is_default', true)?->id ?? $branches->first()?->id,
                'total_count' => $branches->count(),
            ]);

        } catch (\Exception $e) {
            \Log::error('[CalcomBranches] Failed to fetch branches', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch branches',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'branches' => []
            ], 500);
        }
    }

    /**
     * Format branch address for display
     *
     * @param Branch $branch
     * @return string|null
     */
    protected function formatAddress(Branch $branch): ?string
    {
        $parts = array_filter([
            $branch->street,
            $branch->postal_code,
            $branch->city,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }
}
