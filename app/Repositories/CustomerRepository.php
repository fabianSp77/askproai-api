<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

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
        
        return $this->model
            ->where('phone', $normalizedPhone)
            ->orWhere('phone', $phone)
            ->first();
    }

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model
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
     * Get customers with appointments
     */
    public function getWithAppointments(): Collection
    {
        return $this->model
            ->has('appointments')
            ->with(['appointments' => function ($query) {
                $query->orderBy('starts_at', 'desc')->limit(10);
            }])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get customers by branch
     */
    public function getByBranch(int $branchId): Collection
    {
        return $this->model
            ->whereHas('appointments', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->with(['appointments' => function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->orderBy('starts_at', 'desc');
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get customers with no-shows
     */
    public function getWithNoShows(int $minNoShows = 1): Collection
    {
        return $this->model
            ->whereHas('appointments', function ($query) {
                $query->where('status', 'no_show');
            }, '>=', $minNoShows)
            ->withCount(['appointments as no_show_count' => function ($query) {
                $query->where('status', 'no_show');
            }])
            ->orderBy('no_show_count', 'desc')
            ->get();
    }

    /**
     * Get top customers by appointment count
     */
    public function getTopCustomers(int $limit = 10): Collection
    {
        return $this->model
            ->withCount('appointments')
            ->orderBy('appointments_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search customers
     */
    public function search(string $term): Collection
    {
        $normalizedTerm = preg_replace('/[^0-9+]/', '', $term);
        
        return $this->model
            ->where(function ($query) use ($term, $normalizedTerm) {
                $query->where('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%")
                      ->orWhere('phone', 'like', "%{$term}%");
                      
                if ($normalizedTerm) {
                    $query->orWhere('phone', 'like', "%{$normalizedTerm}%");
                }
            })
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    /**
     * Get customer statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->count(),
            'with_email' => $this->model->whereNotNull('email')->count(),
            'with_phone' => $this->model->whereNotNull('phone')->count(),
            'active' => $this->model->has('appointments')->count(),
            'new_this_month' => $this->model->where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    /**
     * Get customers by tag
     */
    public function getByTag(string $tag): Collection
    {
        return $this->model
            ->whereJsonContains('tags', $tag)
            ->orderBy('name')
            ->get();
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
     * Get birthday customers
     */
    public function getBirthdayCustomers(\Carbon\Carbon $date = null): Collection
    {
        $date = $date ?? now();
        
        return $this->model
            ->whereMonth('birthdate', $date->month)
            ->whereDay('birthdate', $date->day)
            ->orderBy('name')
            ->get();
    }
}