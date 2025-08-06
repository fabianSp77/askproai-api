<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return Customer::class;
    }

    /**
     * Find customer by phone number
     */
    public function findByPhone(string $phone): ?Customer
    {
        // Normalize phone number (remove spaces, dashes, etc.)
        $normalizedPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        return $this->model->newQuery()
            ->where(function($query) use ($normalizedPhone, $phone) {
                $query->where('phone', $normalizedPhone)
                      ->orWhere('phone', $phone);
            })
            ->first();
    }

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->newQuery()
            ->where('email', strtolower($email))
            ->first();
    }

    /**
     * Find or create customer
     */
    public function findOrCreate(array $data): Customer
    {
        // Try to find by phone first
        if (!empty($data['phone'])) {
            $customer = $this->findByPhone($data['phone']);
            if ($customer) {
                // Update existing customer with new data
                $customer->update(array_filter([
                    'name' => $data['name'] ?? $customer->name,
                    'email' => $data['email'] ?? $customer->email,
                ]));
                return $customer;
            }
        }
        
        // Try to find by email
        if (!empty($data['email'])) {
            $customer = $this->findByEmail($data['email']);
            if ($customer) {
                // Update phone if provided
                if (!empty($data['phone'])) {
                    $customer->update(['phone' => $data['phone']]);
                }
                return $customer;
            }
        }
        
        // Create new customer
        return $this->create($data);
    }

    /**
     * Get customers with appointments (paginated)
     */
    public function getWithAppointments(int $perPage = 100): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->has('appointments')
            ->with(['appointments' => function ($query) {
                $query->orderBy('starts_at', 'desc')->limit(10);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    
    /**
     * Process customers with appointments in chunks
     */
    public function processCustomersWithAppointments(callable $processor): bool
    {
        return $this->pushCriteria(function ($query) {
            $query->has('appointments')
                  ->with(['appointments' => function ($query) {
                      $query->orderBy('starts_at', 'desc')->limit(10);
                  }])
                  ->orderBy('created_at', 'desc');
        })->chunkSafe(200, $processor);
    }

    /**
     * Get customers by branch (paginated)
     */
    public function getByBranch(string|int $branchId, int $perPage = 100): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->whereHas('appointments', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->with(['appointments' => function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->orderBy('starts_at', 'desc');
            }])
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get customers with no-shows (paginated)
     */
    public function getWithNoShows(int $minNoShows = 1, int $perPage = 100): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->whereHas('appointments', function ($query) {
                $query->where('status', 'no_show');
            }, '>=', $minNoShows)
            ->withCount(['appointments as no_show_count' => function ($query) {
                $query->where('status', 'no_show');
            }])
            ->orderBy('no_show_count', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get top customers by appointment count (with limit for safety)
     */
    public function getTopCustomers(int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->withCount('appointments')
            ->orderBy('appointments_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search customers (with limit for safety)
     */
    public function search(string $term, int $limit = 50): Collection
    {
        $normalizedTerm = preg_replace('/[^0-9+]/', '', $term);
        
        return $this->model->newQuery()
            ->where(function ($query) use ($term, $normalizedTerm) {
                $query->where('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%")
                      ->orWhere('phone', 'like', "%{$term}%");
                      
                if ($normalizedTerm) {
                    $query->orWhere('phone', 'like', "%{$normalizedTerm}%");
                }
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get customer statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->newQuery()->count(),
            'with_email' => $this->model->newQuery()->whereNotNull('email')->count(),
            'with_phone' => $this->model->newQuery()->whereNotNull('phone')->count(),
            'active' => $this->model->newQuery()->has('appointments')->count(),
            'new_this_month' => $this->model->newQuery()->where('created_at', '>=', now()->startOfMonth())->count()
        ];
    }

    /**
     * Get customers by tag (paginated)
     */
    public function getByTag(string $tag, int $perPage = 100): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->whereJsonContains('tags', $tag)
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Add tag to customer
     */
    public function addTag(int $customerId, string $tag): bool
    {
        $customer = $this->find($customerId);
        if (!$customer) {
            return false;
        }
        
        $tags = $customer->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $customer->update(['tags' => $tags]);
        }
        
        return true;
    }

    /**
     * Remove tag from customer
     */
    public function removeTag(int $customerId, string $tag): bool
    {
        $customer = $this->find($customerId);
        if (!$customer) {
            return false;
        }
        
        $tags = $customer->tags ?? [];
        $tags = array_values(array_diff($tags, [$tag]));
        $customer->update(['tags' => $tags]);
        
        return true;
    }

    /**
     * Get birthday customers (limited result for safety)
     */
    public function getBirthdayCustomers(\Carbon\Carbon $date = null, int $limit = 100): Collection
    {
        $date = $date ?? now();
        
        return $this->model->newQuery()
            ->whereMonth('birthdate', $date->month)
            ->whereDay('birthdate', $date->day)
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }
}