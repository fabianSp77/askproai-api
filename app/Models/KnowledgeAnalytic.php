<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'user_id',
        'event_type',
        'event_data',
        'session_id',
        'referrer',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'event_data' => 'array',
    ];

    const EVENT_TYPES = [
        'view' => 'Document Viewed',
        'search' => 'Search Performed',
        'copy_code' => 'Code Copied',
        'execute_code' => 'Code Executed',
        'download' => 'Document Downloaded',
        'print' => 'Document Printed',
        'share' => 'Document Shared',
        'comment' => 'Comment Added',
        'edit' => 'Document Edited',
        'rate' => 'Document Rated',
    ];

    /**
     * Get the document (optional - null for search events)
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    /**
     * Get the user (optional - null for anonymous users)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope for events by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope for events in date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for events by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for events by session
     */
    public function scopeInSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Get human-readable event type
     */
    public function getEventNameAttribute(): string
    {
        return self::EVENT_TYPES[$this->event_type] ?? ucfirst(str_replace('_', ' ', $this->event_type));
    }

    /**
     * Get browser from user agent
     */
    public function getBrowserAttribute(): string
    {
        $userAgent = $this->user_agent;
        
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent)) {
            return 'Chrome';
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent)) {
            return 'Firefox';
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
            return 'Safari';
        } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent)) {
            return 'Edge';
        }
        
        return 'Other';
    }

    /**
     * Get device type from user agent
     */
    public function getDeviceTypeAttribute(): string
    {
        $userAgent = strtolower($this->user_agent);
        
        if (strpos($userAgent, 'mobile') !== false) {
            return 'mobile';
        } elseif (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'tablet';
        }
        
        return 'desktop';
    }

    /**
     * Create a view event
     */
    public static function logView(KnowledgeDocument $document, array $additionalData = []): self
    {
        // Only log user_id if it's an admin user (from users table)
        $userId = null;
        if (auth()->guard('web')->check()) {
            $userId = auth()->guard('web')->id();
        }
        
        return self::create([
            'document_id' => $document->id,
            'user_id' => $userId,
            'event_type' => 'view',
            'event_data' => array_merge($additionalData, [
                'title' => $document->title,
                'category' => $document->category?->name,
                'customer_id' => auth()->guard('customer')->check() ? auth()->guard('customer')->id() : null,
            ]),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a search event
     */
    public static function logSearch(string $query, array $results = []): self
    {
        // Only log user_id if it's an admin user (from users table)
        $userId = null;
        if (auth()->guard('web')->check()) {
            $userId = auth()->guard('web')->id();
        }
        
        return self::create([
            'document_id' => null,
            'user_id' => $userId,
            'event_type' => 'search',
            'event_data' => [
                'query' => $query,
                'results_count' => count($results),
                'result_ids' => array_column($results, 'id'),
                'customer_id' => auth()->guard('customer')->check() ? auth()->guard('customer')->id() : null,
            ],
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}