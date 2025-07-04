<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MLJobProgress extends Model
{
    use HasFactory;

    protected $table = 'ml_job_progress';

    protected $fillable = [
        'job_id',
        'job_type',
        'status',
        'total_items',
        'processed_items',
        'progress_percentage',
        'current_step',
        'message',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'progress_percentage' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'running']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public static function startJob($jobType, $totalItems)
    {
        return static::create([
            'job_id' => uniqid($jobType . '_'),
            'job_type' => $jobType,
            'status' => 'pending',
            'total_items' => $totalItems,
            'progress_percentage' => 0,
            'started_at' => now(),
        ]);
    }

    public function updateProgress($processedItems, $message = null, $currentStep = null)
    {
        $this->update([
            'status' => 'running',
            'processed_items' => $processedItems,
            'progress_percentage' => $this->total_items > 0 ? round(($processedItems / $this->total_items) * 100, 2) : 0,
            'message' => $message,
            'current_step' => $currentStep,
        ]);
    }

    public function markAsCompleted($message = null)
    {
        $this->update([
            'status' => 'completed',
            'progress_percentage' => 100,
            'message' => $message,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function getFormattedDurationAttribute()
    {
        if (!$this->started_at) {
            return 'Not started';
        }

        $end = $this->completed_at ?? now();
        return $this->started_at->diffForHumans($end, true);
    }
}