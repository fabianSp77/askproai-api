<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait SmartLoader
{
    /**
     * Loading profiles for different use cases
     */
    protected static array $loadingProfiles = [
        'minimal' => [],
        'standard' => [],
        'full' => [],
        'counts' => [],
    ];
    
    /**
     * Track loaded relationships to prevent duplicate loading
     */
    protected array $loadedRelations = [];
    
    /**
     * Boot the smart loader trait
     */
    public static function bootSmartLoader(): void
    {
        // Initialize with empty array if not already set
        static::addGlobalScope('smart-loader', function (Builder $builder) {
            // This ensures smart loading is available for all queries
        });
    }
    
    /**
     * Load a specific profile
     */
    public function scopeWithProfile(Builder $query, string $profile = 'standard'): Builder
    {
        $relations = static::getProfileRelations($profile);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query;
    }
    
    /**
     * Load minimal data (just the model)
     */
    public function scopeWithMinimal(Builder $query): Builder
    {
        return $query->withProfile('minimal');
    }
    
    /**
     * Load standard relationships
     */
    public function scopeWithStandard(Builder $query): Builder
    {
        return $query->withProfile('standard');
    }
    
    /**
     * Load all relationships
     */
    public function scopeWithFull(Builder $query): Builder
    {
        return $query->withProfile('full');
    }
    
    /**
     * Load with relationship counts only
     */
    public function scopeWithCounts(Builder $query): Builder
    {
        $countRelations = static::getProfileRelations('counts');
        
        if (!empty($countRelations)) {
            $query->withCount($countRelations);
        }
        
        return $query;
    }
    
    /**
     * Smart loading based on the fields being accessed
     */
    public function scopeWithSmart(Builder $query, array $requestedFields = []): Builder
    {
        $relations = $this->detectRequiredRelations($requestedFields);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query;
    }
    
    /**
     * Load relationships if not already loaded
     */
    public function loadMissing($relations): self
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }
        
        $relations = collect($relations)->filter(function ($relation) {
            return !$this->relationLoaded($relation) && !in_array($relation, $this->loadedRelations);
        })->toArray();
        
        if (!empty($relations)) {
            $this->load($relations);
            $this->loadedRelations = array_merge($this->loadedRelations, $relations);
        }
        
        return $this;
    }
    
    /**
     * Conditionally load relationships based on a condition
     */
    public function loadWhen(bool $condition, $relations): self
    {
        if ($condition) {
            $this->loadMissing($relations);
        }
        
        return $this;
    }
    
    /**
     * Load relationship with a limit
     */
    public function loadLimited(string $relation, int $limit = 10): self
    {
        $this->load([
            $relation => function ($query) use ($limit) {
                $query->limit($limit);
            }
        ]);
        
        return $this;
    }
    
    /**
     * Load recent items from a relationship
     */
    public function loadRecent(string $relation, int $days = 7, int $limit = null): self
    {
        $this->load([
            $relation => function ($query) use ($days, $limit) {
                $query->where('created_at', '>=', now()->subDays($days));
                
                if ($limit) {
                    $query->limit($limit);
                }
                
                $query->orderBy('created_at', 'desc');
            }
        ]);
        
        return $this;
    }
    
    /**
     * Get profile relations
     */
    protected static function getProfileRelations(string $profile): array
    {
        return static::$loadingProfiles[$profile] ?? [];
    }
    
    /**
     * Detect required relations based on requested fields
     */
    protected function detectRequiredRelations(array $fields): array
    {
        $relations = [];
        
        foreach ($fields as $field) {
            if (Str::contains($field, '.')) {
                $parts = explode('.', $field);
                $relation = $parts[0];
                
                // Check if this is a valid relationship
                if (method_exists($this, $relation)) {
                    $relations[] = $relation;
                }
            }
        }
        
        return array_unique($relations);
    }
    
    /**
     * Define loading profile for the model
     */
    public static function defineLoadingProfile(string $profile, array $relations): void
    {
        static::$loadingProfiles[$profile] = $relations;
    }
    
    /**
     * Get all available profiles
     */
    public static function getLoadingProfiles(): array
    {
        return array_keys(static::$loadingProfiles);
    }
    
    /**
     * Check if a relation should be counted instead of loaded
     */
    protected function shouldCount(string $relation): bool
    {
        // Relations that are typically better counted than loaded
        $countableRelations = ['comments', 'likes', 'views', 'votes', 'followers'];
        
        return in_array($relation, $countableRelations) || Str::endsWith($relation, 'Count');
    }
    
    /**
     * Optimize query for API response
     */
    public function scopeForApi(Builder $query, array $includes = []): Builder
    {
        // Start with minimal profile
        $query->withProfile('minimal');
        
        // Add requested includes
        if (!empty($includes)) {
            $validIncludes = array_intersect($includes, $this->getAllowedIncludes());
            
            if (!empty($validIncludes)) {
                $query->with($validIncludes);
            }
        }
        
        // Add counts for collection relationships
        $countRelations = $this->getCountableRelations();
        if (!empty($countRelations)) {
            $query->withCount($countRelations);
        }
        
        return $query;
    }
    
    /**
     * Get allowed includes for API
     */
    protected function getAllowedIncludes(): array
    {
        // Override in model to specify allowed includes
        return [];
    }
    
    /**
     * Get relations that should be counted
     */
    protected function getCountableRelations(): array
    {
        // Override in model to specify countable relations
        return [];
    }
    
    /**
     * Load for detailed view
     */
    public function loadForDetailView(): self
    {
        $profile = static::getProfileRelations('full');
        
        if (!empty($profile)) {
            $this->loadMissing($profile);
        }
        
        return $this;
    }
    
    /**
     * Load for list view
     */
    public function scopeForListView(Builder $query): Builder
    {
        return $query->withProfile('minimal')->withCounts();
    }
    
    /**
     * Prevent N+1 by checking if we're in a loop
     */
    public function getAttribute($key)
    {
        // Check if we're accessing a relationship that isn't loaded
        if ($this->isRelationMethod($key) && !$this->relationLoaded($key)) {
            // Log potential N+1 query in development
            if (config('app.debug')) {
                logger()->warning("Potential N+1 detected: Accessing unloaded relation '{$key}' on " . get_class($this));
            }
        }
        
        return parent::getAttribute($key);
    }
    
    /**
     * Check if a key is a relationship
     */
    protected function isRelationMethod($key)
    {
        return method_exists($this, $key) && 
               !method_exists(self::class, $key) && 
               !Str::startsWith($key, 'get') && 
               !Str::endsWith($key, 'Attribute');
    }
    
    /**
     * Check if the given key is a relationship method on the model.
     * Required by Eloquent Model
     */
    public function isRelation($key)
    {
        return $this->isRelationMethod($key);
    }
    
    /**
     * Create a query builder with smart defaults
     */
    public static function smart(): Builder
    {
        return static::query()->withProfile('standard');
    }
    
    /**
     * Chunk with proper eager loading
     */
    public function scopeSmartChunk(Builder $query, int $count, callable $callback, string $profile = 'standard'): bool
    {
        return $query->withProfile($profile)->chunk($count, $callback);
    }
    
    /**
     * Cursor with proper eager loading
     */
    public function scopeSmartCursor(Builder $query, string $profile = 'standard'): \Illuminate\Support\LazyCollection
    {
        return $query->withProfile($profile)->cursor();
    }
}