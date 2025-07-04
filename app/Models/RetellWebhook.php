<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetellWebhook extends Model
{
    protected $table = 'webhook_events';
    
    protected $fillable = ['event_type', 'event_id', 'call_id', 'payload', 'provider', 'idempotency_key'];
    protected $casts    = ['payload' => 'array'];
    
    /**
     * Scope to get only Retell webhooks
     */
    public function scopeRetell($query)
    {
        return $query->where('provider', 'retell');
    }
}
