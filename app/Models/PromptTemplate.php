<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromptTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'content',
        'variables',
        'parent_id',
        'category',
        'version',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'variables' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent template.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PromptTemplate::class, 'parent_id');
    }

    /**
     * Get child templates.
     */
    public function children(): HasMany
    {
        return $this->hasMany(PromptTemplate::class, 'parent_id');
    }

    /**
     * Get all ancestors (parent hierarchy).
     */
    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;
        
        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }
        
        return $ancestors;
    }

    /**
     * Get compiled prompt with variable substitution.
     */
    public function compile(array $variables = []): string
    {
        $content = $this->content;
        
        // Include parent template content if exists
        if ($this->parent) {
            $parentContent = $this->parent->compile($variables);
            $content = str_replace('{{parent}}', $parentContent, $content);
        }
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }
        
        return $content;
    }

    /**
     * Get all variables from this template and its parents.
     */
    public function getAllVariables(): array
    {
        $variables = $this->variables ?? [];
        
        if ($this->parent) {
            $variables = array_merge($this->parent->getAllVariables(), $variables);
        }
        
        return array_unique($variables);
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for templates by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}