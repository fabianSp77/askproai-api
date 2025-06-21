<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MCPMetric extends Model
{
    use HasFactory;

    protected $table = 'mcp_metrics';

    protected $fillable = [
        'service',
        'operation',
        'status',
        'response_time',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'response_time' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to filter by service
     */
    public function scopeForService($query, string $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Scope to filter by time range
     */
    public function scopeInTimeRange($query, string $range)
    {
        $hours = match ($range) {
            '5m' => 0.083,
            '15m' => 0.25,
            '30m' => 0.5,
            '1h' => 1,
            '6h' => 6,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 1,
        };
        
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to filter successful operations
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope to filter failed operations
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Get formatted response time
     */
    public function getFormattedResponseTimeAttribute(): string
    {
        if ($this->response_time < 1000) {
            return round($this->response_time, 2) . ' ms';
        }
        
        return round($this->response_time / 1000, 2) . ' s';
    }

    /**
     * Check if metric indicates a slow operation
     */
    public function getIsSlowAttribute(): bool
    {
        $thresholds = config('mcp-monitoring.thresholds.response_time', []);
        return $this->response_time > ($thresholds['acceptable'] ?? 1000);
    }

    /**
     * Get severity level based on response time and status
     */
    public function getSeverityAttribute(): string
    {
        if ($this->status === 'error') {
            return 'critical';
        }

        $thresholds = config('mcp-monitoring.thresholds.response_time', []);
        
        if ($this->response_time <= ($thresholds['excellent'] ?? 100)) {
            return 'excellent';
        } elseif ($this->response_time <= ($thresholds['good'] ?? 500)) {
            return 'good';
        } elseif ($this->response_time <= ($thresholds['acceptable'] ?? 1000)) {
            return 'warning';
        }
        
        return 'critical';
    }
}