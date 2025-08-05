<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UnifiedEventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'company_id',
        'service_id',
        'provider',
        'external_id',
        'name',
        'slug',
        'description',
        'duration_minutes',
        'price',
        'provider_data',
        'conflict_data',
        'is_active',
        'assignment_status',
        'import_status',
        'imported_at',
        'assigned_at',
        'calcom_event_type_id',
        'duration',
        'raw_data',
        'last_imported_at'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $casts = [
        'is_active' => 'boolean',
        'provider_data' => 'array',
        'conflict_data' => 'array',
        'imported_at' => 'datetime',
        'assigned_at' => 'datetime',
        'last_imported_at' => 'datetime',
        'price' => 'decimal:2',
        'raw_data' => 'array',
        'duration' => 'integer',
        'duration_minutes' => 'integer',
        'calcom_event_type_id' => 'integer'
    ];

    // Boot method to auto-generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // Relationships
    public function branch()
    {
    return $this->belongsTo(Branch::class, 'branch_id', 'id');        
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    
    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'staff_event_types', 'event_type_id', 'staff_id')
            ->withTimestamps()
            ->withPivot(['is_primary', 'custom_duration', 'custom_price']);
    }

    // Scopes
    public function scopeAssigned($query)
    {
        return $query->where('assignment_status', 'assigned');
    }

    public function scopeUnassigned($query)
    {
        return $query->where('assignment_status', 'unassigned');
    }

    public function scopeDuplicates($query)
    {
        return $query->where('import_status', 'duplicate');
    }

    public function scopePendingReview($query)
    {
        return $query->where('import_status', 'pending_review');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function assignToBranch($branchId)
    {
        $this->update([
            'branch_id' => $branchId,
            'assignment_status' => 'assigned',
            'assigned_at' => now()
        ]);
    }

    public function unassign()
    {
        $this->update([
            'branch_id' => null,
            'assignment_status' => 'unassigned',
            'assigned_at' => null
        ]);
    }

    public function markAsImported()
    {
        $this->update([
            'imported_at' => now(),
            'import_status' => 'success'
        ]);
    }

    public function markAsDuplicate($conflictData)
    {
        $this->update([
            'import_status' => 'duplicate',
            'conflict_data' => $conflictData
        ]);
    }

    // Attribute getters
    public function getDurationAttribute()
    {
        return $this->duration_minutes;
    }

    public function setDurationAttribute($value)
    {
        $this->attributes['duration_minutes'] = $value;
    }

    // Helper methods
    public function isAssigned()
    {
        return $this->assignment_status === 'assigned';
    }

    public function isDuplicate()
    {
        return $this->import_status === 'duplicate';
    }

    public function hasConflicts()
    {
        return !empty($this->conflict_data);
    }

    // Format price for display
    public function getFormattedPriceAttribute()
    {
        return $this->price ? '€ ' . number_format($this->price, 2, ',', '.') : 'Kostenlos';
    }

    // Get metadata from provider_data
    public function getMetadataAttribute()
    {
        return $this->provider_data;
    }

    public function setMetadataAttribute($value)
    {
        $this->provider_data = $value;
    }
}
