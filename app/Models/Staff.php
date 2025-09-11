<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Staff extends Model
{
    use HasUuids, HasFactory;

    /**
     * The relationships that should always be loaded.
     * Prevents N+1 query issues by eager loading common relationships.
     */
    protected $with = ['company', 'branch'];

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'active',
        'is_active',
        'branch_id',
        'home_branch_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function homeBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'home_branch_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_staff')
            ->withPivot('duration_minutes', 'price', 'active')
            ->withTimestamps();
    }
}
