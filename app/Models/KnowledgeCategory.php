<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'description',
        'parent_id',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'parent_id');
    }

    /**
     * Get the child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(KnowledgeCategory::class, 'parent_id');
    }

    /**
     * Get all documents in this category
     */
    public function documents(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class, 'category_id');
    }

    /**
     * Get published documents count
     */
    public function getPublishedDocumentsCountAttribute(): int
    {
        return $this->documents()->published()->count();
    }

    /**
     * Get all descendants (recursive children)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors (recursive parents)
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * Get the URL for the category
     */
    public function getUrlAttribute(): string
    {
        return route('knowledge.category', $this->slug);
    }

    /**
     * Get breadcrumb trail
     */
    public function getBreadcrumbsAttribute(): array
    {
        $breadcrumbs = [];
        $category = $this;
        
        while ($category) {
            array_unshift($breadcrumbs, [
                'name' => $category->name,
                'url' => $category->url,
            ]);
            $category = $category->parent;
        }
        
        return $breadcrumbs;
    }

    /**
     * Scope for ordered categories
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Scope for root categories (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Check if category has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get the full path (including ancestors)
     */
    public function getFullPathAttribute(): string
    {
        $path = [];
        $category = $this;
        
        while ($category) {
            array_unshift($path, $category->slug);
            $category = $category->parent;
        }
        
        return implode('/', $path);
    }
}