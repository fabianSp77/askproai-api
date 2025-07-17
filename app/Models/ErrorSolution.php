<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErrorSolution extends Model
{
    use HasFactory;

    protected $fillable = [
        'error_catalog_id',
        'order',
        'type',
        'title',
        'description',
        'steps',
        'code_snippet',
        'is_automated',
        'automation_script',
        'success_count',
        'failure_count',
        'success_rate',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_automated' => 'boolean',
        'order' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'success_rate' => 'float',
    ];

    /**
     * Get the error catalog that owns the solution.
     */
    public function errorCatalog(): BelongsTo
    {
        return $this->belongsTo(ErrorCatalog::class);
    }

    /**
     * Get the feedback for the solution.
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(ErrorSolutionFeedback::class, 'solution_id');
    }

    /**
     * Get the occurrences that used this solution.
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(ErrorOccurrence::class, 'solution_id');
    }

    /**
     * Record a successful application of this solution.
     */
    public function recordSuccess(): void
    {
        $this->increment('success_count');
        $this->updateSuccessRate();
    }

    /**
     * Record a failed application of this solution.
     */
    public function recordFailure(): void
    {
        $this->increment('failure_count');
        $this->updateSuccessRate();
    }

    /**
     * Update the success rate based on counts.
     */
    public function updateSuccessRate(): void
    {
        $total = $this->success_count + $this->failure_count;
        
        if ($total > 0) {
            $this->update([
                'success_rate' => ($this->success_count / $total) * 100
            ]);
        }
    }

    /**
     * Execute automated solution if available.
     */
    public function executeAutomation(array $context = []): array
    {
        if (!$this->is_automated || !$this->automation_script) {
            return [
                'success' => false,
                'message' => 'This solution is not automated',
            ];
        }

        try {
            // Execute the automation script
            $scriptPath = base_path($this->automation_script);
            
            if (!file_exists($scriptPath)) {
                return [
                    'success' => false,
                    'message' => 'Automation script not found',
                ];
            }

            // Pass context as JSON to the script
            $contextJson = json_encode($context);
            $output = [];
            $returnCode = 0;
            
            exec("php {$scriptPath} '{$contextJson}' 2>&1", $output, $returnCode);
            
            $success = $returnCode === 0;
            
            if ($success) {
                $this->recordSuccess();
            } else {
                $this->recordFailure();
            }
            
            return [
                'success' => $success,
                'output' => implode("\n", $output),
                'return_code' => $returnCode,
            ];
        } catch (\Exception $e) {
            $this->recordFailure();
            
            return [
                'success' => false,
                'message' => 'Automation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get formatted steps for display.
     */
    public function getFormattedSteps(): array
    {
        return array_map(function ($step, $index) {
            return [
                'number' => $index + 1,
                'text' => $step,
            ];
        }, $this->steps, array_keys($this->steps));
    }

    /**
     * Check if solution has positive feedback.
     */
    public function hasPositiveFeedback(): bool
    {
        $total = $this->feedback()->count();
        
        if ($total === 0) {
            return false;
        }
        
        $helpful = $this->feedback()->where('was_helpful', true)->count();
        
        return ($helpful / $total) > 0.7; // 70% threshold
    }

    /**
     * Scope for automated solutions.
     */
    public function scopeAutomated($query)
    {
        return $query->where('is_automated', true);
    }

    /**
     * Scope for manual solutions.
     */
    public function scopeManual($query)
    {
        return $query->where('is_automated', false);
    }

    /**
     * Scope for effective solutions.
     */
    public function scopeEffective($query, float $minSuccessRate = 70.0)
    {
        return $query->where('success_rate', '>=', $minSuccessRate);
    }

    /**
     * Scope for solutions by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}