<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

/**
 * Base model class for all tenant-scoped models
 * Automatically applies tenant filtering and ensures data isolation
 */
abstract class TenantModel extends Model
{
    use HasTenantScope;
    
    /**
     * Get the name of the tenant identifier column
     */
    public function getTenantColumn(): string
    {
        return 'company_id';
    }
    
    /**
     * Ensure company_id is always in fillable
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // Add company_id to fillable if not already present
        if (!in_array('company_id', $this->fillable)) {
            $this->fillable[] = 'company_id';
        }
    }
}