<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;
    protected Builder $query;
    protected array $criteria = [];
    protected array $with = [];
    protected string $loadingProfile = 'standard';
    protected array $withCounts = [];
    protected bool $preventN1 = true;

    public function __construct()
    {
        $this->makeModel();
        $this->boot();
    }

    /**
     * Specify Model class name
     */
    abstract public function model(): string;

    /**
     * Boot method for child repositories
     */
    protected function boot(): void
    {
        // Override in child classes if needed
    }

    /**
     * Make model instance
     */
    protected function makeModel(): void
    {
        $model = app($this->model());

        if (!$model instanceof Model) {
            throw new \Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        $this->model = $model;
        $this->resetQuery();
    }

    /**
     * Reset query builder
     */
    protected function resetQuery(): void
    {
        $this->query = $this->model->newQuery();
        
        // Apply loading profile if model supports it
        if (method_exists($this->model, 'scopeWithProfile')) {
            $this->query->withProfile($this->loadingProfile);
        } elseif (!empty($this->with)) {
            $this->query->with($this->with);
        }
        
        // Apply counts
        if (!empty($this->withCounts)) {
            $this->query->withCount($this->withCounts);
        }
        
        $this->applyCriteria();
    }

    /**
     * Apply criteria to query
     */
    protected function applyCriteria(): void
    {
        foreach ($this->criteria as $criteria) {
            if (is_callable($criteria)) {
                $criteria($this->query);
            }
        }
    }

    /**
     * Get all records (with memory safety warning)
     * 
     * @deprecated Use paginate(), chunk(), or allSafe() for large datasets
     */
    public function all(array $columns = ['*']): Collection
    {
        // Log warning for potential memory issues in debug mode
        if (config('app.debug')) {
            logger()->warning('Repository::all() called - consider pagination for large datasets', [
                'repository' => get_class($this),
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ]);
        }
        
        $result = $this->query->get($columns);
        $this->resetQuery();
        return $result;
    }
    
    /**
     * Get all records with automatic memory safety (chunked internally)
     */
    public function allSafe(array $columns = ['*'], int $chunkSize = 1000): Collection
    {
        $results = collect();
        
        $this->query->chunk($chunkSize, function ($chunk) use (&$results) {
            $results = $results->merge($chunk);
        });
        
        $this->resetQuery();
        return $results;
    }

    /**
     * Get paginated records
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $result = $this->query->paginate($perPage, $columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Find record by ID
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        $result = $this->query->find($id, $columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Find record by ID or throw exception
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        $result = $this->query->findOrFail($id, $columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Find records by criteria (paginated)
     */
    public function findBy(array $criteria, array $columns = ['*'], int $perPage = 50): LengthAwarePaginator
    {
        $this->applyConditions($criteria);
        return $this->paginate($perPage, $columns);
    }
    
    /**
     * Find records by criteria (all results - use carefully)
     */
    public function findByAll(array $criteria, array $columns = ['*']): Collection
    {
        $this->applyConditions($criteria);
        return $this->allSafe($columns);
    }

    /**
     * Find single record by criteria
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model
    {
        $this->applyConditions($criteria);
        $result = $this->query->first($columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Create new record
     */
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            return $this->model->create($data);
        });
    }

    /**
     * Update record
     */
    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $record = $this->findOrFail($id);
            return $record->update($data);
        });
    }

    /**
     * Delete record
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $record = $this->findOrFail($id);
            return $record->delete();
        });
    }

    /**
     * Count records
     */
    public function count(array $criteria = []): int
    {
        if (!empty($criteria)) {
            $this->applyConditions($criteria);
        }
        
        $count = $this->query->count();
        $this->resetQuery();
        return $count;
    }

    /**
     * Check if record exists
     */
    public function exists(array $criteria): bool
    {
        $this->applyConditions($criteria);
        $exists = $this->query->exists();
        $this->resetQuery();
        return $exists;
    }

    /**
     * Get records with relationships
     */
    public function with(array $relations): self
    {
        $this->with = array_merge($this->with, $relations);
        $this->query->with($relations);
        return $this;
    }

    /**
     * Apply criteria
     */
    public function pushCriteria($criteria): self
    {
        if (!is_callable($criteria)) {
            throw new \InvalidArgumentException('Criteria must be callable');
        }
        
        $this->criteria[] = $criteria;
        $criteria($this->query);
        return $this;
    }

    /**
     * Reset criteria
     */
    public function resetCriteria(): self
    {
        $this->criteria = [];
        $this->resetQuery();
        return $this;
    }

    /**
     * Apply conditions array
     */
    protected function applyConditions(array $conditions): void
    {
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $this->query->whereIn($field, $value);
            } else {
                $this->query->where($field, $value);
            }
        }
    }

    /**
     * Order by column
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Limit results
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Get first record
     */
    public function first(array $columns = ['*']): ?Model
    {
        $result = $this->query->first($columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Get last record
     */
    public function last(array $columns = ['*']): ?Model
    {
        $result = $this->query->latest()->first($columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Update or create record
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return DB::transaction(function () use ($attributes, $values) {
            return $this->model->updateOrCreate($attributes, $values);
        });
    }

    /**
     * Delete multiple records
     */
    public function deleteWhere(array $criteria): int
    {
        return DB::transaction(function () use ($criteria) {
            $this->applyConditions($criteria);
            $deleted = $this->query->delete();
            $this->resetQuery();
            return $deleted;
        });
    }

    /**
     * Get query builder
     */
    public function query(): Builder
    {
        return $this->query;
    }
    
    /**
     * Set loading profile
     */
    public function withProfile(string $profile): self
    {
        $this->loadingProfile = $profile;
        $this->resetQuery();
        return $this;
    }
    
    /**
     * Use minimal loading
     */
    public function minimal(): self
    {
        return $this->withProfile('minimal');
    }
    
    /**
     * Use standard loading
     */
    public function standard(): self
    {
        return $this->withProfile('standard');
    }
    
    /**
     * Use full loading
     */
    public function full(): self
    {
        return $this->withProfile('full');
    }
    
    /**
     * Add relationship counts
     */
    public function withCount($relations): self
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }
        
        $this->withCounts = array_merge($this->withCounts, (array) $relations);
        $this->query->withCount($relations);
        return $this;
    }
    
    /**
     * Get for list view (optimized with pagination)
     */
    public function forList(array $columns = ['*'], int $perPage = 50): LengthAwarePaginator
    {
        $this->minimal();
        
        if (method_exists($this->model, 'getCountableRelations')) {
            $countable = $this->model->getCountableRelations();
            if (!empty($countable)) {
                $this->withCount($countable);
            }
        }
        
        return $this->paginate($perPage, $columns);
    }
    
    /**
     * Get for list view (all records - use carefully)
     */
    public function forListAll(array $columns = ['*']): Collection
    {
        $this->minimal();
        
        if (method_exists($this->model, 'getCountableRelations')) {
            $countable = $this->model->getCountableRelations();
            if (!empty($countable)) {
                $this->withCount($countable);
            }
        }
        
        return $this->allSafe($columns);
    }
    
    /**
     * Get for detail view (with all relations)
     */
    public function forDetail(int $id): ?Model
    {
        $this->full();
        return $this->find($id);
    }
    
    /**
     * Get for API response (paginated)
     */
    public function forApi(array $includes = [], int $perPage = 50): LengthAwarePaginator
    {
        if (method_exists($this->model, 'scopeForApi')) {
            $this->query->forApi($includes);
        } else {
            $this->minimal();
            if (!empty($includes)) {
                $this->with($includes);
            }
        }
        
        return $this->paginate($perPage);
    }
    
    /**
     * Get for API response (all records - use for exports only)
     */
    public function forApiAll(array $includes = []): Collection
    {
        if (method_exists($this->model, 'scopeForApi')) {
            $this->query->forApi($includes);
        } else {
            $this->minimal();
            if (!empty($includes)) {
                $this->with($includes);
            }
        }
        
        return $this->allSafe();
    }
    
    /**
     * Chunk with optimal loading
     */
    public function chunk(int $count, callable $callback, string $profile = 'standard'): bool
    {
        $this->withProfile($profile);
        $result = $this->query->chunk($count, $callback);
        $this->resetQuery();
        return $result;
    }
    
    /**
     * Process large datasets in chunks with memory monitoring
     */
    public function chunkSafe(int $count, callable $callback, string $profile = 'standard'): bool
    {
        $this->withProfile($profile);
        $processedCount = 0;
        $startMemory = memory_get_usage();
        
        $result = $this->query->chunk($count, function ($chunk) use ($callback, &$processedCount, $startMemory) {
            $beforeMemory = memory_get_usage();
            $callback($chunk);
            $afterMemory = memory_get_usage();
            
            $processedCount += $chunk->count();
            
            // Log memory usage for monitoring
            if (config('app.debug')) {
                logger()->debug('Chunk processed', [
                    'repository' => get_class($this),
                    'chunk_size' => $chunk->count(),
                    'total_processed' => $processedCount,
                    'memory_before' => round($beforeMemory / 1024 / 1024, 2) . 'MB',
                    'memory_after' => round($afterMemory / 1024 / 1024, 2) . 'MB',
                    'memory_delta' => round(($afterMemory - $beforeMemory) / 1024 / 1024, 2) . 'MB',
                    'total_memory_delta' => round(($afterMemory - $startMemory) / 1024 / 1024, 2) . 'MB'
                ]);
            }
        });
        
        $this->resetQuery();
        return $result;
    }
    
    /**
     * Prevent N+1 queries in development
     */
    public function detectN1(): self
    {
        if (config('app.debug') && $this->preventN1) {
            DB::listen(function ($query) {
                if (preg_match('/select .* from .* where .* in \(/i', $query->sql)) {
                    logger()->warning('Potential N+1 detected in repository', [
                        'repository' => get_class($this),
                        'query' => $query->sql,
                        'bindings' => $query->bindings,
                    ]);
                }
            });
        }
        
        return $this;
    }
    
    /**
     * Get only specific fields with relations
     */
    public function select(array $fields): self
    {
        // Ensure primary key is always selected
        if (!in_array('id', $fields) && !in_array($this->model->getKeyName(), $fields)) {
            $fields[] = $this->model->getKeyName();
        }
        
        $this->query->select($fields);
        return $this;
    }
}