<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingHour extends Model
{
    protected $fillable = [
        'staff_id',
        'branch_id',
        'company_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
        'break_start',
        'break_end',
        'is_available_online',
        'slot_duration',
        'buffer_time'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available_online' => 'boolean',
        'slot_duration' => 'integer',
        'buffer_time' => 'integer'
    ];

    protected $attributes = [
        'is_active' => true,
        'is_available_online' => true,
        'slot_duration' => 30,
        'buffer_time' => 0
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
