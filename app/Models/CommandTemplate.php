<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommandTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'icon',
        'category',
        'description',
        'command_template',
        'parameters',
        'nlp_keywords',
        'shortcut',
        'is_public',
        'is_premium',
        'usage_count',
        'success_rate',
        'avg_execution_time',
        'created_by',
        'company_id',
        'metadata'
    ];

    protected $casts = [
        'parameters' => 'array',
        'nlp_keywords' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
        'is_premium' => 'boolean',
        'usage_count' => 'integer',
        'success_rate' => 'float',
        'avg_execution_time' => 'float'
    ];

    /**
     * Get the user who created this command
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the company this command belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get execution history for this command
     */
    public function executions(): HasMany
    {
        return $this->hasMany(CommandExecution::class);
    }

    /**
     * Users who have favorited this command
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'command_favorites')
            ->withTimestamps();
    }

    /**
     * Workflows using this command
     */
    public function workflows(): BelongsToMany
    {
        return $this->belongsToMany(CommandWorkflow::class, 'workflow_commands')
            ->withPivot('order', 'config')
            ->orderBy('order');
    }

    /**
     * Scope for public commands
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for company-specific commands
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhere('is_public', true);
        });
    }

    /**
     * Search commands by query
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhereJsonContains('nlp_keywords', $search);
        });
    }

    /**
     * Increment usage count
     */
    public function recordUsage($success = true, $executionTime = null)
    {
        $this->increment('usage_count');
        
        if ($executionTime) {
            // Update average execution time
            $totalTime = $this->avg_execution_time * ($this->usage_count - 1) + $executionTime;
            $this->avg_execution_time = $totalTime / $this->usage_count;
        }
        
        if (!is_null($success)) {
            // Update success rate
            $successCount = $this->success_rate * ($this->usage_count - 1) / 100;
            if ($success) $successCount++;
            $this->success_rate = ($successCount / $this->usage_count) * 100;
        }
        
        $this->save();
    }
}