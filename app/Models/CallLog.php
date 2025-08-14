<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    protected $fillable = [
        'call_id',
        'caller_number',
        'start_time',
        'end_time',
        'duration',
        'transcript',
        'intent',
        'extracted_data',
        'appointment_id',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}
