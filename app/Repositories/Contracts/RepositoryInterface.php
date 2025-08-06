<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    /**
     * Get all records (deprecated for large datasets)
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated records
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find record by ID
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Find record by ID or throw exception
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Find records by criteria (paginated)
     */
    public function findBy(array $criteria, array $columns = ['*'], int $perPage = 50): LengthAwarePaginator;

    /**
     * Find single record by criteria
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model;

    /**
     * Create new record
     */
    public function create(array $data): Model;

    /**
     * Update record
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete record
     */
    public function delete(int $id): bool;

    /**
     * Count records
     */
    public function count(array $criteria = []): int;

    /**
     * Check if record exists
     */
    public function exists(array $criteria): bool;

    /**
     * Get records with relationships
     */
    public function with(array $relations): self;

    /**
     * Apply criteria
     */
    public function pushCriteria($criteria): self;

    /**
     * Reset criteria
     */
    public function resetCriteria(): self;
}