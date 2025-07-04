<?php

namespace App\Traits;

use App\Models\Branch;
use App\Models\Scopes\BranchScope;

/**
 * Trait for models that belong to a branch
 * 
 * Automatically applies branch-based filtering and provides
 * branch relationship and helper methods
 */
trait BelongsToBranch
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToBranch()
    {
        // Add global scope for automatic branch filtering
        static::addGlobalScope(new BranchScope);

        // Auto-set branch_id on creating if not set
        static::creating(function ($model) {
            if (!$model->branch_id && auth()->check()) {
                $branchContext = app(\App\Services\BranchContextManager::class);
                $currentBranch = $branchContext->getCurrentBranch();
                
                if ($currentBranch) {
                    $model->branch_id = $currentBranch->id;
                }
            }
        });
    }

    /**
     * Get the branch that owns the model
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope to filter by specific branch
     */
    public function scopeOfBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Check if model belongs to a specific branch
     */
    public function belongsToBranch($branchId): bool
    {
        return $this->branch_id == $branchId;
    }

    /**
     * Check if current user can access this model based on branch
     */
    public function canBeAccessedByUser($user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }

        // Super admins can access everything
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check if user has access to this branch
        $branchContext = app(\App\Services\BranchContextManager::class);
        return $branchContext->canAccessBranch($user, $this->branch_id);
    }
}