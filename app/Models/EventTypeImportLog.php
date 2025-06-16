<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventTypeImportLog extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'import_type',
        'total_found',
        'total_imported',
        'total_skipped',
        'total_errors',
        'import_details',
        'error_details',
        'status',
        'started_at',
        'completed_at'
    ];
    
    protected $casts = [
        'import_details' => 'array',
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
    
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Scope für erfolgreiche Imports
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed')->where('total_errors', 0);
    }
    
    /**
     * Scope für fehlgeschlagene Imports
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed')->orWhere('total_errors', '>', 0);
    }
    
    /**
     * Berechne die Import-Dauer
     */
    public function getDurationAttribute()
    {
        if ($this->started_at && $this->completed_at) {
            return $this->completed_at->diffInSeconds($this->started_at);
        }
        
        return null;
    }
    
    /**
     * Formatierte Dauer
     */
    public function getFormattedDurationAttribute()
    {
        $duration = $this->duration;
        
        if (!$duration) {
            return '-';
        }
        
        if ($duration < 60) {
            return $duration . ' Sek.';
        }
        
        return round($duration / 60, 1) . ' Min.';
    }
}