<?php

namespace App\Services\MCP;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use App\Models\AppointmentLock;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * MCP Server for Appointment Management
 * Handles appointment creation, updates, availability checking, and scheduling
 */
class AppointmentMCPServer
{
    protected DatabaseMCPServer $databaseMCP;
    protected CalcomMCPServer $calcomMCP;
    
    public function __construct(
        DatabaseMCPServer $databaseMCP,
        CalcomMCPServer $calcomMCP
    ) {
        $this->databaseMCP = $databaseMCP;
        $this->calcomMCP = $calcomMCP;
    }
    
    /**
     * Get appointment details
     */
    public function getAppointment(int $appointmentId): array
    {
        try {
            $appointment = Appointment::withoutGlobalScopes()
                ->with(['customer', 'staff', 'service', 'branch'])
                ->find($appointmentId);
                
            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Appointment not found',
                    'data' => null
                ];
            }
            
            return [
                'success' => true,
                'data' => [
                    'id' => $appointment->id,
                    'customer' => [
                        'id' => $appointment->customer->id,
                        'name' => $appointment->customer->name,
                        'email' => $appointment->customer->email,
                        'phone' => $appointment->customer->phone
                    ],
                    'staff' => $appointment->staff ? [
                        'id' => $appointment->staff->id,
                        'name' => $appointment->staff->name
                    ] : null,
                    'service' => $appointment->service ? [
                        'id' => $appointment->service->id,
                        'name' => $appointment->service->name,
                        'duration' => $appointment->service->duration,
                        'price' => $appointment->service->price
                    ] : null,
                    'branch' => [
                        'id' => $appointment->branch->id,
                        'name' => $appointment->branch->name
                    ],
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                    'status' => $appointment->status,
                    'price' => $appointment->price,
                    'notes' => $appointment->notes,
                    'calcom_booking_id' => $appointment->calcom_booking_id,
                    'calcom_booking_uid' => $appointment->calcom_booking_uid,
                    'reminder_sent' => $appointment->reminder_sent,
                    'confirmation_sent' => $appointment->confirmation_sent,
                    'created_at' => $appointment->created_at,
                    'updated_at' => $appointment->updated_at
                ]
            ];
        } catch (\Exception $e) {
            Log::error('AppointmentMCP: Failed to get appointment', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get appointment: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Create new appointment
     */
    public function createAppointment(array $data): array
    {
        try {
            DB::beginTransaction();
            
            // Validate required fields
            $required = ['branch_id', 'customer_id', 'start_time', 'end_time'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Missing required field: {$field}");
                }
            }
            
            // Check availability
            $availabilityCheck = $this->checkAvailability(
                $data['branch_id'],
                $data['start_time'],
                $data['end_time'],
                $data['staff_id'] ?? null
            );
            
            if (!$availabilityCheck['available']) {
                DB::rollback();
                return [
                    'success' => false,
                    'message' => 'Time slot not available: ' . $availabilityCheck['reason'],
                    'data' => null
                ];
            }
            
            // Create appointment lock
            $lockId = $this->createAppointmentLock(
                $data['branch_id'],
                $data['start_time'],
                $data['end_time'],
                $data['staff_id'] ?? null
            );
            
            try {
                // Create appointment
                $appointment = Appointment::create([
                    'company_id' => Branch::find($data['branch_id'])->company_id,
                    'branch_id' => $data['branch_id'],
                    'customer_id' => $data['customer_id'],
                    'staff_id' => $data['staff_id'] ?? null,
                    'service_id' => $data['service_id'] ?? null,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'status' => $data['status'] ?? 'scheduled',
                    'price' => $data['price'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'source' => $data['source'] ?? 'mcp',
                    'call_id' => $data['call_id'] ?? null
                ]);
                
                // Create Cal.com booking if configured
                if (!empty($data['create_calcom_booking'])) {
                    $calcomResult = $this->createCalcomBooking($appointment);
                    if ($calcomResult['success']) {
                        $appointment->update([
                            'calcom_booking_id' => $calcomResult['booking_id'],
                            'calcom_booking_uid' => $calcomResult['booking_uid']
                        ]);
                    }
                }
                
                // Release lock
                $this->releaseAppointmentLock($lockId);
                
                DB::commit();
                
                Log::info('AppointmentMCP: Created appointment', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $appointment->customer_id,
                    'branch_id' => $appointment->branch_id
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Appointment created successfully',
                    'data' => $appointment->fresh()->load(['customer', 'staff', 'service', 'branch']),
                    'appointment_id' => $appointment->id
                ];
                
            } catch (\Exception $e) {
                // Release lock on error
                $this->releaseAppointmentLock($lockId);
                throw $e;
            }
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('AppointmentMCP: Failed to create appointment', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to create appointment: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Update appointment
     */
    public function updateAppointment(int $appointmentId, array $data): array
    {
        try {
            $appointment = Appointment::withoutGlobalScopes()->find($appointmentId);
            
            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Appointment not found',
                    'data' => null
                ];
            }
            
            // If changing time, check availability
            if (isset($data['start_time']) || isset($data['end_time'])) {
                $newStart = $data['start_time'] ?? $appointment->start_time;
                $newEnd = $data['end_time'] ?? $appointment->end_time;
                
                $availabilityCheck = $this->checkAvailability(
                    $appointment->branch_id,
                    $newStart,
                    $newEnd,
                    $data['staff_id'] ?? $appointment->staff_id,
                    $appointmentId // Exclude current appointment
                );
                
                if (!$availabilityCheck['available']) {
                    return [
                        'success' => false,
                        'message' => 'New time slot not available: ' . $availabilityCheck['reason'],
                        'data' => null
                    ];
                }
            }
            
            $appointment->update($data);
            
            // Update Cal.com booking if needed
            if ($appointment->calcom_booking_id && (isset($data['start_time']) || isset($data['end_time']))) {
                $this->updateCalcomBooking($appointment);
            }
            
            // Clear cache
            Cache::forget("appointment_{$appointmentId}");
            
            Log::info('AppointmentMCP: Updated appointment', [
                'appointment_id' => $appointmentId,
                'updated_fields' => array_keys($data)
            ]);
            
            return [
                'success' => true,
                'message' => 'Appointment updated successfully',
                'data' => $appointment->fresh()->load(['customer', 'staff', 'service', 'branch'])
            ];
        } catch (\Exception $e) {
            Log::error('AppointmentMCP: Failed to update appointment', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to update appointment: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Cancel appointment
     */
    public function cancelAppointment(int $appointmentId, string $reason = ''): array
    {
        try {
            $appointment = Appointment::withoutGlobalScopes()->find($appointmentId);
            
            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Appointment not found',
                    'data' => null
                ];
            }
            
            $appointment->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now()
            ]);
            
            // Cancel Cal.com booking if exists
            if ($appointment->calcom_booking_id) {
                $this->calcomMCP->cancelBooking([
                    'booking_id' => $appointment->calcom_booking_id,
                    'reason' => $reason
                ]);
            }
            
            Log::info('AppointmentMCP: Cancelled appointment', [
                'appointment_id' => $appointmentId,
                'reason' => $reason
            ]);
            
            return [
                'success' => true,
                'message' => 'Appointment cancelled successfully',
                'data' => $appointment->fresh()
            ];
        } catch (\Exception $e) {
            Log::error('AppointmentMCP: Failed to cancel appointment', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to cancel appointment: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Check availability for time slot
     */
    public function checkAvailability(
        string $branchId, 
        string $startTime, 
        string $endTime, 
        ?int $staffId = null,
        ?int $excludeAppointmentId = null
    ): array {
        try {
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);
            
            // Check branch working hours
            $dayOfWeek = $start->dayOfWeek;
            $workingHours = DB::table('working_hours')
                ->where('branch_id', $branchId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_closed', false)
                ->first();
                
            if (!$workingHours) {
                return [
                    'available' => false,
                    'reason' => 'Branch is closed on this day'
                ];
            }
            
            // Check if time is within working hours
            if ($start->format('H:i:s') < $workingHours->start_time || 
                $end->format('H:i:s') > $workingHours->end_time) {
                return [
                    'available' => false,
                    'reason' => 'Time is outside working hours'
                ];
            }
            
            // Check for conflicting appointments
            $query = Appointment::where('branch_id', $branchId)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->where(function($q) use ($start, $end) {
                    $q->whereBetween('start_time', [$start, $end])
                      ->orWhereBetween('end_time', [$start, $end])
                      ->orWhere(function($q2) use ($start, $end) {
                          $q2->where('start_time', '<=', $start)
                             ->where('end_time', '>=', $end);
                      });
                });
                
            if ($staffId) {
                $query->where('staff_id', $staffId);
            }
            
            if ($excludeAppointmentId) {
                $query->where('id', '!=', $excludeAppointmentId);
            }
            
            if ($query->exists()) {
                return [
                    'available' => false,
                    'reason' => 'Time slot already booked'
                ];
            }
            
            // Check appointment locks
            $lockExists = AppointmentLock::where('branch_id', $branchId)
                ->where('expires_at', '>', now())
                ->where(function($q) use ($start, $end) {
                    $q->whereBetween('start_time', [$start, $end])
                      ->orWhereBetween('end_time', [$start, $end])
                      ->orWhere(function($q2) use ($start, $end) {
                          $q2->where('start_time', '<=', $start)
                             ->where('end_time', '>=', $end);
                      });
                })
                ->when($staffId, function($q) use ($staffId) {
                    $q->where('staff_id', $staffId);
                })
                ->exists();
                
            if ($lockExists) {
                return [
                    'available' => false,
                    'reason' => 'Time slot is being processed by another booking'
                ];
            }
            
            return [
                'available' => true,
                'reason' => null
            ];
            
        } catch (\Exception $e) {
            Log::error('AppointmentMCP: Failed to check availability', [
                'branch_id' => $branchId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'available' => false,
                'reason' => 'Error checking availability: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get appointments for date range
     */
    public function getAppointmentsByDateRange(
        Carbon $startDate, 
        Carbon $endDate, 
        array $filters = []
    ): array {
        try {
            $query = Appointment::withoutGlobalScopes()
                ->with(['customer', 'staff', 'service', 'branch'])
                ->whereBetween('start_time', [$startDate, $endDate]);
                
            // Apply filters
            if (!empty($filters['branch_id'])) {
                $query->where('branch_id', $filters['branch_id']);
            }
            if (!empty($filters['staff_id'])) {
                $query->where('staff_id', $filters['staff_id']);
            }
            if (!empty($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            $appointments = $query->orderBy('start_time')->get();
            
            return [
                'success' => true,
                'data' => $appointments->map(function($apt) {
                    return [
                        'id' => $apt->id,
                        'customer_name' => $apt->customer->name,
                        'staff_name' => $apt->staff->name ?? 'Unassigned',
                        'service_name' => $apt->service->name ?? 'General',
                        'branch_name' => $apt->branch->name,
                        'start_time' => $apt->start_time,
                        'end_time' => $apt->end_time,
                        'status' => $apt->status,
                        'price' => $apt->price
                    ];
                }),
                'count' => $appointments->count()
            ];
        } catch (\Exception $e) {
            Log::error('AppointmentMCP: Failed to get appointments by date range', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get appointments: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Create appointment lock
     */
    protected function createAppointmentLock(
        string $branchId, 
        string $startTime, 
        string $endTime,
        ?int $staffId = null
    ): string {
        $lockId = Str::uuid()->toString();
        
        AppointmentLock::create([
            'id' => $lockId,
            'branch_id' => $branchId,
            'staff_id' => $staffId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'expires_at' => now()->addMinutes(5) // 5 minute lock
        ]);
        
        return $lockId;
    }
    
    /**
     * Release appointment lock
     */
    protected function releaseAppointmentLock(string $lockId): void
    {
        AppointmentLock::where('id', $lockId)->delete();
    }
    
    /**
     * Create Cal.com booking
     */
    protected function createCalcomBooking(Appointment $appointment): array
    {
        try {
            $customer = $appointment->customer;
            $branch = $appointment->branch;
            
            $result = $this->calcomMCP->createBooking([
                'event_type_id' => $branch->calcom_event_type_id,
                'start' => $appointment->start_time,
                'end' => $appointment->end_time,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'notes' => $appointment->notes,
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'branch_id' => $branch->id,
                    'source' => 'askproai'
                ]
            ]);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'booking_id' => $result['data']['id'],
                    'booking_uid' => $result['data']['uid']
                ];
            }
            
            return ['success' => false];
        } catch (\Exception $e) {
            Log::error('AppointmentMCP: Failed to create Cal.com booking', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false];
        }
    }
    
    /**
     * Update Cal.com booking
     */
    protected function updateCalcomBooking(Appointment $appointment): void
    {
        try {
            $this->calcomMCP->updateBooking([
                'booking_id' => $appointment->calcom_booking_id,
                'start' => $appointment->start_time,
                'end' => $appointment->end_time
            ]);
        } catch (\Exception $e) {
            Log::error('AppointmentMCP: Failed to update Cal.com booking', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}