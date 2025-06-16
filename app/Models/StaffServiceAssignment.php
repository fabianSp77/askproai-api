<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffServiceAssignment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'staff_id',
        'master_service_id',
        'branch_id',
        'calcom_user_id',
        'availability_rules',
        'active'
    ];

    protected $casts = [
        'availability_rules' => 'array',
        'active' => 'boolean'
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function masterService(): BelongsTo
    {
        return $this->belongsTo(MasterService::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getCalendarIdAttribute(): ?string
    {
        return $this->calcom_user_id ?? $this->staff->calcom_user_id;
    }
}
