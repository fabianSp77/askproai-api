<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Services\BranchContextManager;

/**
 * Global scope for automatic branch filtering
 * 
 * Automatically filters queries by the current branch context
 * unless explicitly disabled or viewing "All Branches"
 */
class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        // Skip if no authenticated user
        if (!auth()->check()) {
            return;
        }

        // Skip if already filtering by branch
        if ($this->hasExistingBranchFilter($builder)) {
            return;
        }

        // Get branch context manager
        $branchContext = app(BranchContextManager::class);
        
        // Get current branch from context
        $currentBranch = $branchContext->getCurrentBranch();
        
        // If no specific branch selected (viewing all branches), don't filter
        if (!$currentBranch) {
            // But still apply company-level filtering for regular users
            $user = auth()->user();
            if (!$user->hasRole('super_admin') && $user->company_id) {
                // Get all accessible branch IDs for the user
                $accessibleBranchIds = $branchContext->getBranchesForUser($user)->pluck('id');
                
                if ($accessibleBranchIds->isNotEmpty()) {
                    $builder->whereIn($model->getTable() . '.branch_id', $accessibleBranchIds);
                }
            }
            return;
        }

        // Apply branch filter
        $builder->where($model->getTable() . '.branch_id', $currentBranch->id);
    }

    /**
     * Extend the query builder with custom methods
     */
    public function extend(Builder $builder)
    {
        // Add method to disable branch scope
        $builder->macro('withoutBranchScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        // Add method to filter by specific branch
        $builder->macro('forBranch', function (Builder $builder, $branchId) {
            return $builder->withoutBranchScope()->where('branch_id', $branchId);
        });

        // Add method to get data for all accessible branches
        $builder->macro('forAllBranches', function (Builder $builder) {
            $builder->withoutBranchScope();
            
            $user = auth()->user();
            if (!$user) {
                return $builder;
            }

            // Super admin sees all
            if ($user->hasRole('super_admin')) {
                return $builder;
            }

            // Others see only their accessible branches
            $branchContext = app(BranchContextManager::class);
            $accessibleBranchIds = $branchContext->getBranchesForUser($user)->pluck('id');
            
            if ($accessibleBranchIds->isNotEmpty()) {
                $builder->whereIn('branch_id', $accessibleBranchIds);
            }

            return $builder;
        });
    }

    /**
     * Check if the query already has a branch filter
     */
    protected function hasExistingBranchFilter(Builder $builder): bool
    {
        $query = $builder->getQuery();
        
        // Check regular where clauses
        if ($query->wheres) {
            foreach ($query->wheres as $where) {
                if (isset($where['column']) && str_ends_with($where['column'], '.branch_id')) {
                    return true;
                }
                if (isset($where['column']) && $where['column'] === 'branch_id') {
                    return true;
                }
            }
        }

        return false;
    }
}