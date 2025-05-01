<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    /** mass-assignable Felder */
    protected $fillable = [
        'external_id',
        'transcript',
        'raw',
        'customer_id',   //  ← NEU
    ];

    /** Casts */
    protected $casts = [
        'raw' => 'array',
    ];
}
