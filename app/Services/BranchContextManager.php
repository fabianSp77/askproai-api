<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;

/**
 * Manages branch context across the application
 * Provides persistent branch selection and access control
 */
class BranchContextManager
{
    const SESSION_KEY = 'current_branch_id';
    const CACHE_PREFIX = 'branch_context:';
    const CACHE_TTL = 3600; // 1 hour
    
    /**
     * Get the current branch for the authenticated user
     */
    public function getCurrentBranch(): ?Branch
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }
        
        // Check session for stored branch ID
        $branchId = Session::get(self::SESSION_KEY);
        
        if ($branchId) {
            // Validate user still has access to this branch
            $branch = $this->getUserBranch($user, $branchId);
            if ($branch) {
                return $branch;
            }
            
            // Invalid branch in session, clear it
            Session::forget(self::SESSION_KEY);
        }
        
        // No valid branch in session, get user's primary branch
        return $this->getUserPrimaryBranch($user);
    }
    
    /**
     * Set the current branch for the authenticated user
     */
    public function setCurrentBranch(?string $branchId): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        
        // Special case: null means "All Branches" view
        if ($branchId === null) {
            Session::put(self::SESSION_KEY, null);
            return true;
        }
        
        // Validate user has access to this branch
        $branch = $this->getUserBranch($user, $branchId);
        if (!$branch) {
            return false;
        }
        
        // Store in session
        Session::put(self::SESSION_KEY, $branchId);
        
        // Broadcast event for real-time UI updates
        event(new \App\Events\BranchContextChanged($user, $branch));
        
        return true;
    }
    
    /**
     * Get all branches accessible by the user
     */
    public function getBranchesForUser(?User $user = null): Collection
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return collect();
        }
        
        // Cache user's branches for performance
        $cacheKey = self::CACHE_PREFIX . 'user_branches:' . $user->id;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            // Super admin sees all branches
            if ($user->hasRole(['super_admin', 'Super Admin'])) {
                return Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->with('company')
                    ->where('active', true)
                    ->orderBy('name')
                    ->get();
            }
            
            // Company admin sees all company branches
            if ($user->hasRole(['admin', 'Admin']) && $user->company_id) {
                return Branch::where('company_id', $user->company_id)
                    ->where('active', true)
                    ->orderBy('name')
                    ->get();
            }
            
            // Staff sees assigned branches
            if ($user->staff) {
                return $user->staff->branches()
                    ->where('active', true)
                    ->orderBy('name')
                    ->get();
            }
            
            // Branch manager sees their branch
            if ($user->hasRole('branch_manager') && $user->branch_id) {
                return Branch::where('id', $user->branch_id)
                    ->where('active', true)
                    ->get();
            }
            
            return collect();
        });
    }
    
    /**
     * Check if user can access a specific branch
     */
    public function canAccessBranch(?User $user, string $branchId): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }
        
        $branches = $this->getBranchesForUser($user);
        return $branches->contains('id', $branchId);
    }
    
    /**
     * Get user's primary/home branch
     */
    public function getUserPrimaryBranch(User $user): ?Branch
    {
        // Staff member with home branch
        if ($user->staff && $user->staff->home_branch_id) {
            $branch = Branch::find($user->staff->home_branch_id);
            if ($branch && $branch->active) {
                return $branch;
            }
        }
        
        // User with direct branch assignment
        if ($user->branch_id) {
            $branch = Branch::find($user->branch_id);
            if ($branch && $branch->active) {
                return $branch;
            }
        }
        
        // Fall back to first accessible branch
        $branches = $this->getBranchesForUser($user);
        return $branches->first();
    }
    
    /**
     * Get branch for user with validation
     */
    protected function getUserBranch(User $user, string $branchId): ?Branch
    {
        if (!$this->canAccessBranch($user, $branchId)) {
            return null;
        }
        
        return Branch::where('id', $branchId)
            ->where('active', true)
            ->first();
    }
    
    /**
     * Clear branch context cache for a user
     */
    public function clearUserCache(User $user): void
    {
        $cacheKey = self::CACHE_PREFIX . 'user_branches:' . $user->id;
        Cache::forget($cacheKey);
    }
    
    /**
     * Get branch context for API requests
     */
    public function getBranchFromRequest(\Illuminate\Http\Request $request): ?Branch
    {
        // Check header first
        $branchId = $request->header('X-Branch-ID');
        
        // Check query parameter
        if (!$branchId) {
            $branchId = $request->query('branch_id');
        }
        
        // Check request body
        if (!$branchId) {
            $branchId = $request->input('branch_id');
        }
        
        if ($branchId && $this->canAccessBranch(auth()->user(), $branchId)) {
            return Branch::find($branchId);
        }
        
        // Fall back to current session branch
        return $this->getCurrentBranch();
    }
    
    /**
     * Apply branch context to a query builder
     */
    public function applyBranchContext($query, ?string $branchId = null)
    {
        $branchId = $branchId ?? Session::get(self::SESSION_KEY);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query;
    }
    
    /**
     * Check if currently viewing all branches
     */
    public function isAllBranchesView(): bool
    {
        return Session::get(self::SESSION_KEY) === null && auth()->check();
    }
    
    /**
     * Get branch selector options for forms
     */
    public function getBranchOptions(): array
    {
        $branches = $this->getBranchesForUser();
        
        $options = [];
        
        // Add "All Branches" option if user has access to multiple branches
        if ($branches->count() > 1) {
            $options[''] = '🏢 Alle Filialen';
        }
        
        foreach ($branches as $branch) {
            $options[$branch->id] = $branch->name;
        }
        
        return $options;
    }
}