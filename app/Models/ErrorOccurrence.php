<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorOccurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'error_catalog_id',
        'company_id',
        'user_id',
        'environment',
        'context',
        'stack_trace',
        'request_url',
        'request_method',
        'ip_address',
        'user_agent',
        'was_resolved',
        'resolved_at',
        'resolution_time',
        'solution_id',
    ];

    protected $casts = [
        'context' => 'array',
        'was_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'resolution_time' => 'integer',
    ];

    /**
     * Get the error catalog for this occurrence.
     */
    public function errorCatalog(): BelongsTo
    {
        return $this->belongsTo(ErrorCatalog::class);
    }

    /**
     * Get the company for this occurrence.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user for this occurrence.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the solution used for this occurrence.
     */
    public function solution(): BelongsTo
    {
        return $this->belongsTo(ErrorSolution::class);
    }

    /**
     * Mark the occurrence as resolved.
     */
    public function markAsResolved(?int $solutionId = null): void
    {
        $resolvedAt = now();
        $resolutionTime = $resolvedAt->diffInSeconds($this->created_at);
        
        $this->update([
            'was_resolved' => true,
            'resolved_at' => $resolvedAt,
            'resolution_time' => $resolutionTime,
            'solution_id' => $solutionId,
        ]);
        
        // Update the error catalog's average resolution time
        $this->errorCatalog->updateAverageResolutionTime();
        
        // If a solution was used, update its success count
        if ($solutionId) {
            $this->solution->recordSuccess();
        }
    }

    /**
     * Get formatted stack trace.
     */
    public function getFormattedStackTrace(): string
    {
        if (!$this->stack_trace) {
            return '';
        }
        
        // Format stack trace for better readability
        $lines = explode("\n", $this->stack_trace);
        $formatted = [];
        
        foreach ($lines as $index => $line) {
            $formatted[] = sprintf("#%d %s", $index, trim($line));
        }
        
        return implode("\n", $formatted);
    }

    /**
     * Get truncated stack trace for display.
     */
    public function getTruncatedStackTrace(int $lines = 5): string
    {
        if (!$this->stack_trace) {
            return '';
        }
        
        $stackLines = explode("\n", $this->stack_trace);
        $truncated = array_slice($stackLines, 0, $lines);
        
        return implode("\n", $truncated) . (count($stackLines) > $lines ? "\n..." : '');
    }

    /**
     * Get resolution time in human-readable format.
     */
    public function getResolutionTimeForHumans(): ?string
    {
        if (!$this->resolution_time) {
            return null;
        }
        
        $minutes = floor($this->resolution_time / 60);
        $seconds = $this->resolution_time % 60;
        
        if ($minutes > 0) {
            return sprintf('%d min %d sec', $minutes, $seconds);
        }
        
        return sprintf('%d sec', $seconds);
    }

    /**
     * Scope for unresolved occurrences.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('was_resolved', false);
    }

    /**
     * Scope for resolved occurrences.
     */
    public function scopeResolved($query)
    {
        return $query->where('was_resolved', true);
    }

    /**
     * Scope for occurrences by environment.
     */
    public function scopeByEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Scope for recent occurrences.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for occurrences by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}