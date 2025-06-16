<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchServiceOverride extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'master_service_id',
        'custom_duration',
        'custom_price',
        'custom_calcom_event_type_id',
        'active'
    ];

    protected $casts = [
        'custom_duration' => 'integer',
        'custom_price' => 'decimal:2',
        'active' => 'boolean'
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function masterService(): BelongsTo
    {
        return $this->belongsTo(MasterService::class);
    }

    public function hasCustomization(): bool
    {
        return $this->custom_duration !== null 
            || $this->custom_price !== null 
            || $this->custom_calcom_event_type_id !== null;
    }
}
