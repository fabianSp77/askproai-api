<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetellWebhook extends Model
{
    protected $fillable = ['event_type', 'call_id', 'payload'];
    protected $casts    = ['payload' => 'array'];
}
