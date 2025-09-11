<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalcomEventType extends Model
{
    protected $fillable = [
        'calcom_event_type_id',
        'calcom_numeric_event_type_id',
        'company_id',
        'branch_id',
        'staff_id',
        'name',
        'slug',
        'team_id',
        'is_team_event',
        'requires_confirmation',
        'duration_minutes',
        'description',
        'price',
        'is_active',
        'last_synced_at',
        'minimum_booking_notice',
        'booking_future_limit',
        'time_slot_interval',
        'buffer_before',
        'buffer_after',
        'locations',
        'custom_fields',
        'max_bookings_per_day',
        'seats_per_time_slot',
        'schedule_id',
        'recurring_config',
        'setup_status',
        'setup_checklist',
        'webhook_settings',
        'calcom_url',
        'booking_limits',
        'metadata'
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
