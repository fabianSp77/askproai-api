<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Repositories\AppointmentRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\CustomerCreated;
use App\Events\CustomerMerged;
use App\Services\CacheService;

class CustomerService
{
    protected CustomerRepository $customerRepository;
    protected AppointmentRepository $appointmentRepository;
    protected CacheService $cacheService;

    public function __construct(
        CustomerRepository $customerRepository,
        AppointmentRepository $appointmentRepository,
        CacheService $cacheService
    ) {
        $this->customerRepository = $customerRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * Find customer by phone with caching
     */
    public function findByPhone(string $phone, int $companyId): ?Customer
    {
        return $this->cacheService->getCustomerByPhone($phone, $companyId, function () use ($phone) {
            return $this->customerRepository->findByPhone($phone);
        });
    }

    /**
     * Create new customer
     */
    public function create(array $data): Customer
    {
        // Check for duplicates
        if (!empty($data['phone'])) {
            $existing = $this->customerRepository->findByPhone($data['phone']);
            if ($existing) {
                throw new \Exception('Customer with this phone number already exists');
            }
        }

        if (!empty($data['email'])) {
            $existing = $this->customerRepository->findByEmail($data['email']);
            if ($existing) {
                throw new \Exception('Customer with this email already exists');
            }
        }

        // Normalize data
        $data = $this->normalizeCustomerData($data);

        // Create customer
        $customer = $this->customerRepository->create($data);

        // Clear cache for this customer's phone number
        if ($customer->phone) {
            $this->cacheService->clearCustomerCache($customer->phone);
        }

        // Fire event
        event(new CustomerCreated($customer));

        return $customer;
    }

    /**
     * Update customer
     */
    public function update(int $customerId, array $data): Customer
    {
        $customer = $this->customerRepository->findOrFail($customerId);

        // Check for conflicts
        if (!empty($data['phone']) && $data['phone'] !== $customer->phone) {
            $existing = $this->customerRepository->findByPhone($data['phone']);
            if ($existing && $existing->id !== $customerId) {
                throw new \Exception('Phone number already in use by another customer');
            }
        }

        if (!empty($data['email']) && $data['email'] !== $customer->email) {
            $existing = $this->customerRepository->findByEmail($data['email']);
            if ($existing && $existing->id !== $customerId) {
                throw new \Exception('Email already in use by another customer');
            }
        }

        // Normalize data
        $data = $this->normalizeCustomerData($data);

        // Update customer
        $this->customerRepository->update($customerId, $data);

        // Clear cache for old and new phone numbers
        if ($customer->phone) {
            $this->cacheService->clearCustomerCache($customer->phone);
        }
        if (!empty($data['phone']) && $data['phone'] !== $customer->phone) {
            $this->cacheService->clearCustomerCache($data['phone']);
        }

        return $customer->fresh();
    }

    /**
     * Merge duplicate customers
     */
    public function mergeDuplicates(int $primaryId, int $duplicateId): Customer
    {
        return DB::transaction(function () use ($primaryId, $duplicateId) {
            $primary = $this->customerRepository->findOrFail($primaryId);
            $duplicate = $this->customerRepository->findOrFail($duplicateId);

            // Merge data (primary takes precedence)
            $mergedData = [
                'phone' => $primary->phone ?: $duplicate->phone,
                'email' => $primary->email ?: $duplicate->email,
                'address' => $primary->address ?: $duplicate->address,
                'birthdate' => $primary->birthdate ?: $duplicate->birthdate,
                'notes' => $this->mergeNotes($primary->notes, $duplicate->notes),
                'tags' => array_unique(array_merge($primary->tags ?? [], $duplicate->tags ?? [])),
            ];

            // Update primary customer
            $this->customerRepository->update($primaryId, $mergedData);

            // Move all appointments to primary customer
            $this->appointmentRepository->pushCriteria(function ($query) use ($duplicateId, $primaryId) {
                $query->where('customer_id', $duplicateId)
                      ->update(['customer_id' => $primaryId]);
            });

            // Move all calls to primary customer
            DB::table('calls')
                ->where('customer_id', $duplicateId)
                ->update(['customer_id' => $primaryId]);

            // Update no-show count
            $primary->increment('no_show_count', $duplicate->no_show_count);

            // Delete duplicate
            $this->customerRepository->delete($duplicateId);

            // Fire event
            event(new CustomerMerged($primary->fresh(), $duplicate));

            return $primary->fresh();
        });
    }

    /**
     * Get customer history
     */
    public function getHistory(int $customerId): array
    {
        $customer = $this->customerRepository->findOrFail($customerId);
        
        // Get appointments
        $appointments = $this->appointmentRepository
            ->with(['staff', 'service', 'branch'])
            ->findBy(['customer_id' => $customerId])
            ->sortByDesc('starts_at');

        // Get calls
        $calls = DB::table('calls')
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get activity log
        $activities = DB::table('activity_log')
            ->where('subject_type', Customer::class)
            ->where('subject_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return [
            'customer' => $customer,
            'appointments' => $appointments,
            'calls' => $calls,
            'activities' => $activities,
            'statistics' => [
                'total_appointments' => $appointments->count(),
                'completed_appointments' => $appointments->where('status', 'completed')->count(),
                'no_shows' => $appointments->where('status', 'no_show')->count(),
                'total_spent' => $appointments->where('status', 'completed')->sum('price'),
                'average_appointment_value' => $appointments->where('status', 'completed')->avg('price'),
                'last_appointment' => $appointments->first()?->starts_at,
                'next_appointment' => $appointments->where('starts_at', '>', now())->first()?->starts_at,
            ],
        ];
    }

    /**
     * Search for potential duplicates
     */
    public function findPotentialDuplicates(int $customerId): Collection
    {
        $customer = $this->customerRepository->findOrFail($customerId);
        $duplicates = collect();

        // Search by similar phone
        if ($customer->phone) {
            $phoneVariants = $this->generatePhoneVariants($customer->phone);
            foreach ($phoneVariants as $variant) {
                $found = $this->customerRepository->findBy(['phone' => $variant]);
                $duplicates = $duplicates->merge($found);
            }
        }

        // Search by similar name
        if ($customer->name) {
            $nameParts = explode(' ', $customer->name);
            foreach ($nameParts as $part) {
                if (strlen($part) > 3) {
                    $found = DB::table('customers')
                        ->where('name', 'like', "%{$part}%")
                        ->where('id', '!=', $customerId)
                        ->where('company_id', $customer->company_id)
                        ->get();
                    $duplicates = $duplicates->merge($found);
                }
            }
        }

        // Search by email domain
        if ($customer->email) {
            $domain = substr(strrchr($customer->email, "@"), 1);
            $found = DB::table('customers')
                ->where('email', 'like', "%@{$domain}")
                ->where('id', '!=', $customerId)
                ->where('company_id', $customer->company_id)
                ->get();
            $duplicates = $duplicates->merge($found);
        }

        return $duplicates->unique('id')->values();
    }

    /**
     * Block customer
     */
    public function block(int $customerId, string $reason): void
    {
        $this->customerRepository->update($customerId, [
            'is_blocked' => true,
            'blocked_at' => now(),
            'block_reason' => $reason,
        ]);

        // Cancel all future appointments
        $futureAppointments = $this->appointmentRepository
            ->findBy([
                'customer_id' => $customerId,
                'status' => 'scheduled',
            ])
            ->filter(fn($apt) => $apt->starts_at->isFuture());

        foreach ($futureAppointments as $appointment) {
            app(AppointmentService::class)->cancel(
                $appointment->id, 
                'Customer blocked: ' . $reason
            );
        }
    }

    /**
     * Unblock customer
     */
    public function unblock(int $customerId): void
    {
        $this->customerRepository->update($customerId, [
            'is_blocked' => false,
            'blocked_at' => null,
            'block_reason' => null,
        ]);
    }

    /**
     * Add tag to customer
     */
    public function addTag(int $customerId, string $tag): void
    {
        $this->customerRepository->addTag($customerId, $tag);
    }

    /**
     * Remove tag from customer
     */
    public function removeTag(int $customerId, string $tag): void
    {
        $this->customerRepository->removeTag($customerId, $tag);
    }

    /**
     * Get customers by tag
     */
    public function getByTag(string $tag): Collection
    {
        return $this->customerRepository->getByTag($tag);
    }

    /**
     * Export customer data
     */
    public function export(int $customerId): array
    {
        $history = $this->getHistory($customerId);
        
        return [
            'customer' => $history['customer']->toArray(),
            'appointments' => $history['appointments']->toArray(),
            'calls' => $history['calls']->toArray(),
            'statistics' => $history['statistics'],
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Normalize customer data
     */
    protected function normalizeCustomerData(array $data): array
    {
        // Normalize phone number
        if (!empty($data['phone'])) {
            $data['phone'] = preg_replace('/[^0-9+]/', '', $data['phone']);
        }

        // Normalize email
        if (!empty($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        // Ensure company_id
        if (!isset($data['company_id'])) {
            $data['company_id'] = auth()->user()?->company_id;
        }

        return $data;
    }

    /**
     * Generate phone number variants
     */
    protected function generatePhoneVariants(string $phone): array
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        $variants = [$phone, $normalized];

        // Add variants with/without country code
        if (str_starts_with($normalized, '49')) {
            $variants[] = '0' . substr($normalized, 2);
        } elseif (str_starts_with($normalized, '0')) {
            $variants[] = '49' . substr($normalized, 1);
            $variants[] = '+49' . substr($normalized, 1);
        }

        return array_unique($variants);
    }

    /**
     * Merge notes from two customers
     */
    protected function mergeNotes(?string $notes1, ?string $notes2): ?string
    {
        if (!$notes1 && !$notes2) {
            return null;
        }

        if (!$notes1) {
            return $notes2;
        }

        if (!$notes2) {
            return $notes1;
        }

        return $notes1 . "\n\n--- Merged from duplicate ---\n" . $notes2;
    }
}