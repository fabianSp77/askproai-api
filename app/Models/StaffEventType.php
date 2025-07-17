<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StaffEventType extends Pivot
{
    protected $table = 'staff_event_types';

    protected $fillable = [
        'staff_id',
        'calcom_event_type_id',
        'calcom_user_id',
        'is_primary',
        'custom_duration',
        'custom_price',
        'availability_override',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'custom_duration' => 'integer',
        'custom_price' => 'decimal:2',
        'availability_override' => 'array',
    ];

    /**
     * Get the staff member
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the event type
     */
    public function eventType()
    {
        return $this->belongsTo(CalcomEventType::class, 'calcom_event_type_id');
    }
}