<?php

namespace App\Services\Booking;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Services\CalcomV2Service;
use App\Services\ConflictDetectionService;
use App\Traits\TransactionalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for managing group bookings
 * Handles appointments for multiple customers at the same time
 */
class GroupBookingService
{
    use TransactionalService;

    protected CalcomV2Service $calcomService;
    protected ConflictDetectionService $conflictService;

    public function __construct(
        CalcomV2Service $calcomService,
        ConflictDetectionService $conflictService
    ) {
        $this->calcomService = $calcomService;
        $this->conflictService = $conflictService;
    }

    /**
     * Create a group booking for multiple customers
     * 
     * @param array $data {
     *   customers: array<{id?: int, name: string, email?: string, phone: string}>,
     *   branch_id: int,
     *   staff_id?: int,
     *   service_id: int,
     *   starts_at: string,
     *   duration_minutes?: int,
     *   notes?: string,
     *   max_participants?: int,
     *   price_per_person?: float,
     *   group_discount?: float
     * }
     * @return array{success: bool, group_booking_id?: string, appointments?: array, errors?: array}
     */
    public function createGroupBooking(array $data): array
    {
        $startTime = microtime(true);
        $context = [
            'customer_count' => count($data['customers']),
            'service_id' => $data['service_id'],
            'operation' => 'create_group_booking'
        ];

        try {
            return $this->executeInTransaction(function () use ($data) {
                // Validate inputs
                $this->validateGroupBookingData($data);

                // Generate group booking ID
                $groupBookingId = $this->generateGroupBookingId();

                // Get service details
                $service = Service::findOrFail($data['service_id']);
                $duration = $data['duration_minutes'] ?? $service->duration;

                // Calculate times
                $startsAt = Carbon::parse($data['starts_at']);
                $endsAt = $startsAt->copy()->addMinutes($duration);

                // Check capacity and conflicts
                $capacityCheck = $this->checkGroupCapacity($data);
                if (!$capacityCheck['available']) {
                    return [
                        'success' => false,
                        'errors' => [$capacityCheck['message']]
                    ];
                }

                // Process customers (find existing or create new)
                $customers = $this->processCustomers($data['customers'], $data['company_id'] ?? null);

                // Calculate pricing
                $pricing = $this->calculateGroupPricing(
                    count($customers),
                    $data['price_per_person'] ?? $service->price,
                    $data['group_discount'] ?? 0
                );

                // Create appointments for each customer
                $appointments = [];
                $errors = [];

                foreach ($customers as $index => $customer) {
                    try {
                        $appointment = $this->createGroupAppointment(
                            $customer,
                            $data,
                            $groupBookingId,
                            $startsAt,
                            $endsAt,
                            $pricing['price_per_person'],
                            $index === 0 // First appointment is the parent
                        );
                        $appointments[] = $appointment;

                        // Try to sync with Cal.com (only for primary appointment)
                        if ($index === 0 && !empty($data['calcom_event_type_id'])) {
                            try {
                                $this->syncGroupWithCalcom($appointment, $data, $customers);
                            } catch (\Exception $e) {
                                Log::warning('Failed to sync group booking with Cal.com', [
                                    'group_booking_id' => $groupBookingId,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        $errors[] = [
                            'customer' => $customer->name,
                            'error' => $e->getMessage()
                        ];
                    }
                }

                if (!empty($errors)) {
                    throw new \Exception('Some appointments could not be created: ' . json_encode($errors));
                }

                $this->logTransactionMetrics('create_group_booking', $startTime, true, [
                    'group_booking_id' => $groupBookingId,
                    'appointments_created' => count($appointments)
                ]);

                return [
                    'success' => true,
                    'group_booking_id' => $groupBookingId,
                    'appointments' => $appointments,
                    'pricing' => $pricing
                ];

            }, $context, 3);

        } catch (\Throwable $e) {
            $this->logTransactionMetrics('create_group_booking', $startTime, false, [
                'error' => $e->getMessage()
            ]);

            Log::error('Failed to create group booking', array_merge($context, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]));

            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Add participant to existing group booking
     */
    public function addParticipant(string $groupBookingId, array $customerData): array
    {
        try {
            return $this->executeInTransaction(function () use ($groupBookingId, $customerData) {
                // Get existing group appointments
                $existingAppointments = Appointment::where('group_booking_id', $groupBookingId)
                    ->where('status', '!=', 'cancelled')
                    ->get();

                if ($existingAppointments->isEmpty()) {
                    throw new \Exception('Group booking not found');
                }

                // Get reference appointment
                $referenceAppointment = $existingAppointments->first();

                // Check if group is full
                $service = Service::find($referenceAppointment->service_id);
                $maxParticipants = $service->max_participants ?? 10;

                if ($existingAppointments->count() >= $maxParticipants) {
                    return [
                        'success' => false,
                        'errors' => ['Group is already full']
                    ];
                }

                // Process customer
                $customer = $this->processCustomers([$customerData], $referenceAppointment->company_id)[0];

                // Check if customer already in group
                if ($existingAppointments->where('customer_id', $customer->id)->isNotEmpty()) {
                    return [
                        'success' => false,
                        'errors' => ['Customer already in this group']
                    ];
                }

                // Create appointment
                $appointment = Appointment::create([
                    'company_id' => $referenceAppointment->company_id,
                    'customer_id' => $customer->id,
                    'branch_id' => $referenceAppointment->branch_id,
                    'staff_id' => $referenceAppointment->staff_id,
                    'service_id' => $referenceAppointment->service_id,
                    'starts_at' => $referenceAppointment->starts_at,
                    'ends_at' => $referenceAppointment->ends_at,
                    'status' => $referenceAppointment->status,
                    'booking_type' => 'group',
                    'group_booking_id' => $groupBookingId,
                    'price' => $referenceAppointment->price,
                    'metadata' => [
                        'group_booking_id' => $groupBookingId,
                        'added_after_creation' => true,
                        'added_at' => now()
                    ]
                ]);

                return [
                    'success' => true,
                    'appointment' => $appointment,
                    'group_size' => $existingAppointments->count() + 1
                ];
            });

        } catch (\Exception $e) {
            Log::error('Failed to add participant to group', [
                'group_booking_id' => $groupBookingId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Remove participant from group booking
     */
    public function removeParticipant(string $groupBookingId, int $customerId, string $reason = null): array
    {
        try {
            $appointment = Appointment::where('group_booking_id', $groupBookingId)
                ->where('customer_id', $customerId)
                ->where('status', '!=', 'cancelled')
                ->first();

            if (!$appointment) {
                return [
                    'success' => false,
                    'errors' => ['Participant not found in group']
                ];
            }

            // Check if this is the last participant
            $remainingCount = Appointment::where('group_booking_id', $groupBookingId)
                ->where('status', '!=', 'cancelled')
                ->where('id', '!=', $appointment->id)
                ->count();

            if ($remainingCount === 0) {
                return [
                    'success' => false,
                    'errors' => ['Cannot remove last participant. Cancel entire group instead.']
                ];
            }

            // Cancel appointment
            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason ?? 'Removed from group'
            ]);

            return [
                'success' => true,
                'remaining_participants' => $remainingCount
            ];

        } catch (\Exception $e) {
            Log::error('Failed to remove participant from group', [
                'group_booking_id' => $groupBookingId,
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Cancel entire group booking
     */
    public function cancelGroupBooking(string $groupBookingId, string $reason = null): array
    {
        try {
            return $this->executeInTransaction(function () use ($groupBookingId, $reason) {
                $appointments = Appointment::where('group_booking_id', $groupBookingId)
                    ->where('status', '!=', 'cancelled')
                    ->get();

                if ($appointments->isEmpty()) {
                    return [
                        'success' => false,
                        'errors' => ['Group booking not found or already cancelled']
                    ];
                }

                $cancelled = [];
                foreach ($appointments as $appointment) {
                    $appointment->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancellation_reason' => $reason ?? 'Group booking cancelled'
                    ]);
                    $cancelled[] = $appointment;

                    // Cancel Cal.com booking if exists
                    if ($appointment->calcom_booking_id) {
                        try {
                            $this->calcomService->cancelBooking(
                                $appointment->calcom_booking_id,
                                $reason
                            );
                        } catch (\Exception $e) {
                            Log::warning('Failed to cancel Cal.com booking', [
                                'appointment_id' => $appointment->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                return [
                    'success' => true,
                    'cancelled_count' => count($cancelled),
                    'appointments' => $cancelled
                ];
            });

        } catch (\Exception $e) {
            Log::error('Failed to cancel group booking', [
                'group_booking_id' => $groupBookingId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Check group capacity and availability
     */
    protected function checkGroupCapacity(array $data): array
    {
        $service = Service::find($data['service_id']);
        if (!$service) {
            return ['available' => false, 'message' => 'Service not found'];
        }

        // Check maximum participants
        $maxParticipants = $data['max_participants'] ?? $service->max_participants ?? 10;
        if (count($data['customers']) > $maxParticipants) {
            return [
                'available' => false,
                'message' => "Group size exceeds maximum of {$maxParticipants} participants"
            ];
        }

        // Check if service allows group bookings
        if ($service->group_booking_enabled === false) {
            return [
                'available' => false,
                'message' => 'This service does not allow group bookings'
            ];
        }

        // Check staff availability for group size
        if (!empty($data['staff_id'])) {
            $startsAt = Carbon::parse($data['starts_at']);
            $duration = $data['duration_minutes'] ?? $service->duration;
            $endsAt = $startsAt->copy()->addMinutes($duration);

            $hasConflict = $this->conflictService->checkConflict(
                $data['staff_id'],
                $startsAt,
                $endsAt
            );

            if ($hasConflict) {
                return [
                    'available' => false,
                    'message' => 'Time slot not available'
                ];
            }
        }

        return ['available' => true];
    }

    /**
     * Process customers - find existing or create new
     */
    protected function processCustomers(array $customersData, ?int $companyId = null): array
    {
        $customers = [];
        $companyId = $companyId ?? auth()->user()->company_id;

        foreach ($customersData as $customerData) {
            if (isset($customerData['id'])) {
                // Existing customer
                $customer = Customer::find($customerData['id']);
                if (!$customer) {
                    throw new \Exception("Customer ID {$customerData['id']} not found");
                }
            } else {
                // Find or create customer
                $customer = Customer::where('company_id', $companyId)
                    ->where('phone', $customerData['phone'])
                    ->first();

                if (!$customer) {
                    $customer = Customer::create([
                        'company_id' => $companyId,
                        'name' => $customerData['name'],
                        'email' => $customerData['email'] ?? null,
                        'phone' => $customerData['phone'],
                        'notes' => 'Added via group booking'
                    ]);
                }
            }

            $customers[] = $customer;
        }

        return $customers;
    }

    /**
     * Calculate group pricing with discounts
     */
    protected function calculateGroupPricing(int $groupSize, float $basePrice, float $groupDiscount): array
    {
        $totalBeforeDiscount = $groupSize * $basePrice;
        $discountAmount = 0;
        $pricePerPerson = $basePrice;

        if ($groupDiscount > 0) {
            // Percentage discount
            $discountAmount = $totalBeforeDiscount * ($groupDiscount / 100);
            $totalAfterDiscount = $totalBeforeDiscount - $discountAmount;
            $pricePerPerson = $totalAfterDiscount / $groupSize;
        } else {
            // Volume-based discount tiers
            if ($groupSize >= 10) {
                $groupDiscount = 20;
            } elseif ($groupSize >= 5) {
                $groupDiscount = 15;
            } elseif ($groupSize >= 3) {
                $groupDiscount = 10;
            }

            if ($groupDiscount > 0) {
                $discountAmount = $totalBeforeDiscount * ($groupDiscount / 100);
                $totalAfterDiscount = $totalBeforeDiscount - $discountAmount;
                $pricePerPerson = $totalAfterDiscount / $groupSize;
            }
        }

        return [
            'group_size' => $groupSize,
            'base_price' => $basePrice,
            'price_per_person' => round($pricePerPerson, 2),
            'total_before_discount' => round($totalBeforeDiscount, 2),
            'discount_percentage' => $groupDiscount,
            'discount_amount' => round($discountAmount, 2),
            'total_after_discount' => round($totalBeforeDiscount - $discountAmount, 2)
        ];
    }

    /**
     * Create individual appointment for group member
     */
    protected function createGroupAppointment(
        Customer $customer,
        array $data,
        string $groupBookingId,
        Carbon $startsAt,
        Carbon $endsAt,
        float $price,
        bool $isParent = false
    ): Appointment {
        $appointmentData = [
            'company_id' => $data['company_id'] ?? auth()->user()->company_id,
            'customer_id' => $customer->id,
            'branch_id' => $data['branch_id'],
            'staff_id' => $data['staff_id'] ?? null,
            'service_id' => $data['service_id'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $data['auto_confirm'] ?? false ? 'confirmed' : 'scheduled',
            'booking_type' => 'group',
            'group_booking_id' => $groupBookingId,
            'price' => $price,
            'notes' => $data['notes'] ?? null,
            'source' => $data['source'] ?? 'group_booking',
            'metadata' => [
                'group_booking_id' => $groupBookingId,
                'is_group_parent' => $isParent,
                'group_size' => count($data['customers'])
            ]
        ];

        // If not parent, reference the parent appointment
        if (!$isParent) {
            $parentAppointment = Appointment::where('group_booking_id', $groupBookingId)
                ->whereJsonContains('metadata->is_group_parent', true)
                ->first();
            
            if ($parentAppointment) {
                $appointmentData['parent_appointment_id'] = $parentAppointment->id;
            }
        }

        return Appointment::create($appointmentData);
    }

    /**
     * Sync group booking with Cal.com
     */
    protected function syncGroupWithCalcom(Appointment $appointment, array $data, array $customers): void
    {
        if (empty($data['calcom_event_type_id'])) {
            return;
        }

        // Create a single Cal.com booking with all participants
        $attendees = array_map(function($customer) {
            return [
                'name' => $customer->name,
                'email' => $customer->email ?? 'noreply@askproai.de',
                'phone' => $customer->phone
            ];
        }, $customers);

        $primaryCustomer = $customers[0];

        $calcomBooking = $this->calcomService->createBooking([
            'eventTypeId' => $data['calcom_event_type_id'],
            'start' => $appointment->starts_at->toIso8601String(),
            'responses' => [
                'name' => $primaryCustomer->name,
                'email' => $primaryCustomer->email ?? 'noreply@askproai.de',
                'phone' => $primaryCustomer->phone,
                'guests' => array_slice($attendees, 1) // Additional guests
            ],
            'metadata' => [
                'appointment_id' => $appointment->id,
                'group_booking_id' => $appointment->group_booking_id,
                'is_group_booking' => true,
                'participant_count' => count($customers)
            ],
        ]);

        if (isset($calcomBooking['data']['id'])) {
            // Update all appointments in the group with the same Cal.com booking ID
            Appointment::where('group_booking_id', $appointment->group_booking_id)
                ->update([
                    'calcom_booking_id' => $calcomBooking['data']['id'],
                    'calcom_event_type_id' => $data['calcom_event_type_id']
                ]);
        }
    }

    /**
     * Generate unique group booking ID
     */
    protected function generateGroupBookingId(): string
    {
        return 'GB-' . strtoupper(Str::random(8)) . '-' . time();
    }

    /**
     * Validate group booking data
     */
    protected function validateGroupBookingData(array $data): void
    {
        if (empty($data['customers']) || !is_array($data['customers'])) {
            throw new \InvalidArgumentException('Customers array is required');
        }

        if (count($data['customers']) < 2) {
            throw new \InvalidArgumentException('Group booking requires at least 2 customers');
        }

        $required = ['branch_id', 'service_id', 'starts_at'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }

        // Validate each customer
        foreach ($data['customers'] as $index => $customer) {
            if (empty($customer['name'])) {
                throw new \InvalidArgumentException("Customer name is required (index {$index})");
            }
            if (empty($customer['phone']) && empty($customer['id'])) {
                throw new \InvalidArgumentException("Customer phone or ID is required (index {$index})");
            }
        }
    }

    /**
     * Get group booking details
     */
    public function getGroupBooking(string $groupBookingId): array
    {
        $appointments = Appointment::where('group_booking_id', $groupBookingId)
            ->with(['customer', 'staff', 'service', 'branch'])
            ->get();

        if ($appointments->isEmpty()) {
            return [
                'success' => false,
                'errors' => ['Group booking not found']
            ];
        }

        $activeAppointments = $appointments->where('status', '!=', 'cancelled');
        $parentAppointment = $appointments->firstWhere('metadata.is_group_parent', true) ?? $appointments->first();

        return [
            'success' => true,
            'group_booking_id' => $groupBookingId,
            'participants' => $activeAppointments->count(),
            'total_participants' => $appointments->count(),
            'status' => $parentAppointment->status,
            'starts_at' => $parentAppointment->starts_at,
            'ends_at' => $parentAppointment->ends_at,
            'service' => $parentAppointment->service,
            'staff' => $parentAppointment->staff,
            'branch' => $parentAppointment->branch,
            'appointments' => $appointments,
            'total_price' => $activeAppointments->sum('price')
        ];
    }
}