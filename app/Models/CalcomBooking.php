<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalcomBooking extends Model
{
    protected $fillable = ['calcom_uid', 'appointment_id', 'status', 'raw_payload'];

    protected $casts = ['raw_payload' => 'array'];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
