<?php

namespace App\Traits;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToCompany(): void
    {
        // Add global scope
        static::addGlobalScope(new CompanyScope);
        
        // Auto-set company_id on create
        static::creating(function (Model $model) {
            if (empty($model->company_id)) {
                $model->company_id = static::getCurrentCompanyId();
            }
            
            // Validate company_id matches current user's company
            if ($model->company_id && $model->company_id !== static::getCurrentCompanyId()) {
                throw new \RuntimeException(
                    'Attempted to create record for different company. Access denied.'
                );
            }
        });
        
        // Prevent updating company_id
        static::updating(function (Model $model) {
            if ($model->isDirty('company_id')) {
                throw new \RuntimeException(
                    'Company ID cannot be changed after creation.'
                );
            }
        });
    }
    
    /**
     * Get current company ID from authenticated user
     */
    protected static function getCurrentCompanyId(): ?int
    {
        // Try multiple sources
        if ($user = Auth::user()) {
            return $user->company_id ?? $user->company()->first()?->id;
        }
        
        // Check request context
        if ($companyId = request()->header('X-Company-ID')) {
            return (int) $companyId;
        }
        
        // Check session
        if ($companyId = session('company_id')) {
            return (int) $companyId;
        }
        
        return null;
    }
    
    /**
     * Scope to company
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->withoutGlobalScope(CompanyScope::class)
            ->where($this->getTable() . '.company_id', $companyId);
    }
    
    /**
     * Scope to current company
     */
    public function scopeCurrentCompany($query)
    {
        $companyId = static::getCurrentCompanyId();
        
        if (!$companyId) {
            // Return empty result if no company context
            return $query->whereRaw('0 = 1');
        }
        
        return $query->forCompany($companyId);
    }
    
    /**
     * Check if model belongs to current company
     */
    public function belongsToCurrentCompany(): bool
    {
        $currentCompanyId = static::getCurrentCompanyId();
        
        return $currentCompanyId && $this->company_id == $currentCompanyId;
    }
    
    /**
     * Company relationship
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}