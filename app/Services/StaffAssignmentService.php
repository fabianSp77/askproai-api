<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\CalcomEventType;
use App\Models\Service;
use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StaffAssignmentService
{
    /**
     * Get available staff for a specific event type and time
     * 
     * @param int $eventTypeId
     * @param \DateTime $dateTime
     * @param int|null $branchId
     * @return Collection
     */
    public function getAvailableStaffForEventType(int $eventTypeId, \DateTime $dateTime, ?int $branchId = null): Collection
    {
        $query = Staff::query()
            ->where('is_active', true)
            ->where('is_bookable', true);
            
        // Filter by branch if specified
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        // Filter by event type assignment
        $query->whereHas('eventTypes', function($q) use ($eventTypeId) {
            $q->where('calcom_event_types.id', $eventTypeId);
        });
        
        // TODO: Add availability checking based on working hours and existing appointments
        
        return $query->get();
    }
    
    /**
     * Get available staff for a service (which maps to event types)
     * 
     * @param int $serviceId
     * @param \DateTime $dateTime
     * @param int|null $branchId
     * @return Collection
     */
    public function getAvailableStaffForService(int $serviceId, \DateTime $dateTime, ?int $branchId = null): Collection
    {
        $service = Service::find($serviceId);
        
        if (!$service || !$service->calcom_event_type_id) {
            Log::warning('Service has no event type mapping', ['service_id' => $serviceId]);
            return collect();
        }
        
        return $this->getAvailableStaffForEventType($service->calcom_event_type_id, $dateTime, $branchId);
    }
    
    /**
     * Validate if a staff member can host an appointment
     * 
     * @param Staff $staff
     * @param CalcomEventType $eventType
     * @param \DateTime $dateTime
     * @return array{valid: bool, reason?: string}
     */
    public function validateStaffAssignment(Staff $staff, CalcomEventType $eventType, \DateTime $dateTime): array
    {
        // Check if staff is active and bookable
        if (!$staff->is_active) {
            return ['valid' => false, 'reason' => 'Staff member is not active'];
        }
        
        if (!$staff->is_bookable) {
            return ['valid' => false, 'reason' => 'Staff member is not bookable'];
        }
        
        // Check if staff can host this event type
        if (!$staff->canHostEventType($eventType->id)) {
            return ['valid' => false, 'reason' => 'Staff member cannot host this event type'];
        }
        
        // TODO: Check availability based on working hours
        // TODO: Check for conflicting appointments
        
        return ['valid' => true];
    }
    
    /**
     * Auto-assign best available staff for an appointment
     * 
     * @param CalcomEventType $eventType
     * @param \DateTime $dateTime
     * @param int|null $branchId
     * @param string $strategy 'round-robin'|'least-busy'|'random'
     * @return Staff|null
     */
    public function autoAssignStaff(
        CalcomEventType $eventType, 
        \DateTime $dateTime, 
        ?int $branchId = null,
        string $strategy = 'round-robin'
    ): ?Staff {
        $availableStaff = $this->getAvailableStaffForEventType($eventType->id, $dateTime, $branchId);
        
        if ($availableStaff->isEmpty()) {
            Log::warning('No available staff for event type', [
                'event_type_id' => $eventType->id,
                'date_time' => $dateTime->format('Y-m-d H:i:s'),
                'branch_id' => $branchId
            ]);
            return null;
        }
        
        switch ($strategy) {
            case 'least-busy':
                return $this->selectLeastBusyStaff($availableStaff, $dateTime);
                
            case 'random':
                return $availableStaff->random();
                
            case 'round-robin':
            default:
                return $this->selectRoundRobinStaff($availableStaff, $eventType);
        }
    }
    
    /**
     * Select staff with least appointments on the given day
     * 
     * @param Collection $staffCollection
     * @param \DateTime $dateTime
     * @return Staff|null
     */
    protected function selectLeastBusyStaff(Collection $staffCollection, \DateTime $dateTime): ?Staff
    {
        $date = $dateTime->format('Y-m-d');
        
        return $staffCollection->sortBy(function($staff) use ($date) {
            return $staff->appointments()
                ->whereDate('starts_at', $date)
                ->count();
        })->first();
    }
    
    /**
     * Select staff using round-robin algorithm
     * 
     * @param Collection $staffCollection
     * @param CalcomEventType $eventType
     * @return Staff|null
     */
    protected function selectRoundRobinStaff(Collection $staffCollection, CalcomEventType $eventType): ?Staff
    {
        // Get the last assigned staff for this event type
        $lastAssignment = \DB::table('appointments')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('services.calcom_event_type_id', $eventType->id)
            ->whereIn('appointments.staff_id', $staffCollection->pluck('id'))
            ->orderBy('appointments.created_at', 'desc')
            ->first();
            
        if (!$lastAssignment) {
            // No previous assignments, return first staff
            return $staffCollection->first();
        }
        
        // Find the next staff in rotation
        $lastStaffIndex = $staffCollection->search(function($staff) use ($lastAssignment) {
            return $staff->id === $lastAssignment->staff_id;
        });
        
        if ($lastStaffIndex === false) {
            return $staffCollection->first();
        }
        
        // Get next staff in rotation
        $nextIndex = ($lastStaffIndex + 1) % $staffCollection->count();
        return $staffCollection->values()->get($nextIndex);
    }
    
    /**
     * Get staff members without any event type assignments
     * 
     * @param int|null $branchId
     * @return Collection
     */
    public function getAdministrativeStaff(?int $branchId = null): Collection
    {
        $query = Staff::query()
            ->where('is_active', true)
            ->whereDoesntHave('eventTypes');
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->get();
    }
    
    /**
     * Sync staff event type assignments from Cal.com
     * 
     * @param CalcomEventType $eventType
     * @param array $hostData Array of host data from Cal.com
     * @return array{assigned: int, removed: int, skipped: int}
     */
    public function syncEventTypeHosts(CalcomEventType $eventType, array $hostData): array
    {
        $stats = ['assigned' => 0, 'removed' => 0, 'skipped' => 0];
        
        // Get current assignments
        $currentStaffIds = \DB::table('staff_event_types')
            ->where('event_type_id', $eventType->id)
            ->pluck('staff_id')
            ->toArray();
            
        $newStaffIds = [];
        
        foreach ($hostData as $host) {
            $staff = $this->findStaffForHost($host, $eventType->company_id);
            
            if (!$staff) {
                $stats['skipped']++;
                continue;
            }
            
            $newStaffIds[] = $staff->id;
            
            // Check if already assigned
            if (in_array($staff->id, $currentStaffIds)) {
                continue;
            }
            
            // Create new assignment
            \DB::table('staff_event_types')->insert([
                'staff_id' => $staff->id,
                'event_type_id' => $eventType->id,
                'calcom_user_id' => $host['id'] ?? null,
                'is_primary' => $host['isPrimary'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $stats['assigned']++;
        }
        
        // Remove outdated assignments
        $toRemove = array_diff($currentStaffIds, $newStaffIds);
        if (!empty($toRemove)) {
            \DB::table('staff_event_types')
                ->where('event_type_id', $eventType->id)
                ->whereIn('staff_id', $toRemove)
                ->delete();
                
            $stats['removed'] = count($toRemove);
        }
        
        return $stats;
    }
    
    /**
     * Find staff member for Cal.com host data
     * 
     * @param array $host
     * @param int $companyId
     * @return Staff|null
     */
    protected function findStaffForHost(array $host, int $companyId): ?Staff
    {
        // Try by Cal.com user ID
        if (isset($host['id'])) {
            $staff = Staff::where('calcom_user_id', $host['id'])->first();
            if ($staff) return $staff;
        }
        
        // Try by email
        if (isset($host['email'])) {
            $staff = Staff::where('email', $host['email'])
                ->whereHas('branch', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->first();
            if ($staff) return $staff;
        }
        
        // Try by name
        if (isset($host['name'])) {
            $staff = Staff::where('name', $host['name'])
                ->whereHas('branch', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->first();
            if ($staff) return $staff;
        }
        
        return null;
    }
}