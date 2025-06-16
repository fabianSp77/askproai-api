<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ValidationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'test_type',
        'status',
        'results',
        'tested_at',
        'expires_at'
    ];

    protected $casts = [
        'results' => 'array',
        'tested_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getOverallStatus(): string
    {
        if (!$this->results || !isset($this->results['tests'])) {
            return self::STATUS_ERROR;
        }

        $hasError = false;
        $hasWarning = false;

        foreach ($this->results['tests'] as $test) {
            if ($test['status'] === self::STATUS_ERROR) {
                $hasError = true;
            } elseif ($test['status'] === self::STATUS_WARNING) {
                $hasWarning = true;
            }
        }

        if ($hasError) return self::STATUS_ERROR;
        if ($hasWarning) return self::STATUS_WARNING;
        return self::STATUS_SUCCESS;
    }

    public static function getLatestForEntity(string $entityType, string $entityId): ?self
    {
        return static::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('expires_at', '>', now())
            ->orderBy('tested_at', 'desc')
            ->first();
    }
}
