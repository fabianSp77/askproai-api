<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'appointment_id',
        'customer_id',
        'service_id',
        'staff_id',
        'branch_id',
        'company_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'metadata',
        'price',
        'payment_status',
        'payment_method',
        'confirmation_sent_at',
        'reminder_sent_at',
        'cancelled_at',
        'cancellation_reason',
        'source',
        'external_id'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'metadata' => 'array',
        'price' => 'decimal:2',
        'confirmation_sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'pending',
        'source' => 'phone'
    ];
}
