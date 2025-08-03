<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LiveAppointmentBoard extends Widget
{
    protected static string $view = 'filament.admin.widgets.live-appointment-board';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;
    
    public array $liveData = [];
    public string $selectedDate;
    public ?int $companyId = null;
    public ?string $selectedBranchId = null;
    
    // Auto-refresh interval in seconds
    public int $refreshInterval = 30;
    
    public function mount(): void
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->companyId = session('filter_company_id') ?? auth()->user()->company_id;
        $this->selectedBranchId = session('filter_branch_id');
        $this->loadLiveData();
    }
    
    public function setDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->loadLiveData();
    }
    
    public function setBranch(?string $branchId): void
    {
        $this->selectedBranchId = $branchId;
        $this->loadLiveData();
    }
    
    public function loadLiveData(): void
    {
        $date = Carbon::parse($this->selectedDate);
        
        $this->liveData = [
            'current_status' => $this->getCurrentDayStatus($date),
            'time_slots' => $this->getTimeSlotOverview($date),
            'upcoming_appointments' => $this->getUpcomingAppointments($date),
            'recent_activities' => $this->getRecentActivities(),
            'staff_availability' => $this->getStaffAvailability($date),
            'alerts' => $this->getActiveAlerts($date),
            'queue_status' => $this->getQueueStatus(),
        ];
    }
    
    protected function getCurrentDayStatus(Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        $now = Carbon::now();
        
        $query = Appointment::whereBetween('starts_at', [$startOfDay, $endOfDay])
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId));
            
        // Status counts
        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        // Currently in progress (appointments happening now)
        $inProgress = (clone $query)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->where('status', 'confirmed')
            ->count();
            
        // Next appointment
        $nextAppointment = (clone $query)
            ->where('starts_at', '>', $now)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('starts_at')
            ->with(['customer', 'staff', 'service'])
            ->first();
            
        // Appointments in next hour
        $nextHour = (clone $query)
            ->whereBetween('starts_at', [$now, $now->copy()->addHour()])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();
            
        // Late arrivals (appointments that should have started but no check-in)
        $lateArrivals = (clone $query)
            ->where('starts_at', '<', $now->copy()->subMinutes(15))
            ->where('starts_at', '>', $now->copy()->subHours(2))
            ->where('status', 'scheduled')
            ->count();
            
        return [
            'total' => array_sum($statusCounts),
            'scheduled' => $statusCounts['scheduled'] ?? 0,
            'confirmed' => $statusCounts['confirmed'] ?? 0,
            'completed' => $statusCounts['completed'] ?? 0,
            'cancelled' => $statusCounts['cancelled'] ?? 0,
            'no_show' => $statusCounts['no_show'] ?? 0,
            'in_progress' => $inProgress,
            'next_hour' => $nextHour,
            'late_arrivals' => $lateArrivals,
            'next_appointment' => $nextAppointment ? [
                'time' => $nextAppointment->starts_at->format('H:i'),
                'customer' => $nextAppointment->customer->name ?? 'Unknown',
                'staff' => $nextAppointment->staff->name ?? 'Unknown',
                'service' => $nextAppointment->service->name ?? 'Unknown',
                'branch' => $nextAppointment->branch->name ?? 'Unknown',
            ] : null,
        ];
    }
    
    protected function getTimeSlotOverview(Carbon $date): array
    {
        $slots = [];
        $startOfDay = $date->copy()->setTime(8, 0); // Business hours start
        $endOfDay = $date->copy()->setTime(20, 0); // Business hours end
        $now = Carbon::now();
        
        for ($hour = 8; $hour < 20; $hour++) {
            $slotStart = $date->copy()->setTime($hour, 0);
            $slotEnd = $date->copy()->setTime($hour, 59, 59);
            
            $appointments = Appointment::whereBetween('starts_at', [$slotStart, $slotEnd])
                ->when($this->companyId, function ($q) {
                    $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
                })
                ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
                ->with(['staff', 'branch'])
                ->get();
                
            // Get unique staff for this hour
            $staffCount = $appointments->pluck('staff_id')->unique()->count();
            
            // Calculate utilization (simplified)
            $maxCapacity = $staffCount * 2; // 2 appointments per hour per staff
            $utilization = $maxCapacity > 0 ? round(($appointments->count() / $maxCapacity) * 100) : 0;
            
            $slots[] = [
                'hour' => $hour,
                'label' => sprintf('%02d:00', $hour),
                'appointments' => $appointments->count(),
                'confirmed' => $appointments->where('status', 'confirmed')->count(),
                'completed' => $appointments->where('status', 'completed')->count(),
                'utilization' => $utilization,
                'is_current' => $now->hour === $hour && $date->isToday(),
                'is_past' => $slotEnd < $now,
            ];
        }
        
        return $slots;
    }
    
    protected function getUpcomingAppointments(Carbon $date): array
    {
        $now = Carbon::now();
        $endOfDay = $date->copy()->endOfDay();
        
        return Appointment::where('starts_at', '>', $now)
            ->where('starts_at', '<=', $endOfDay)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->with(['customer', 'staff', 'service', 'branch'])
            ->orderBy('starts_at')
            ->limit(10)
            ->get()
            ->map(function ($appointment) use ($now) {
                $minutesUntil = $now->diffInMinutes($appointment->starts_at);
                
                return [
                    'id' => $appointment->id,
                    'time' => $appointment->starts_at->format('H:i'),
                    'duration' => $appointment->duration_minutes,
                    'customer' => $appointment->customer->name ?? 'Unknown',
                    'phone' => $appointment->customer->phone ?? '',
                    'staff' => $appointment->staff->name ?? 'Unknown',
                    'service' => $appointment->service->name ?? 'Unknown',
                    'branch' => $appointment->branch->name ?? 'Unknown',
                    'status' => $appointment->status,
                    'minutes_until' => $minutesUntil,
                    'is_soon' => $minutesUntil <= 30,
                    'is_urgent' => $minutesUntil <= 15,
                ];
            })
            ->toArray();
    }
    
    protected function getRecentActivities(): array
    {
        $activities = [];
        
        // Recent bookings (last 30 minutes)
        $recentBookings = Appointment::where('created_at', '>=', Carbon::now()->subMinutes(30))
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->with(['customer', 'staff', 'service'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($appointment) {
                return [
                    'type' => 'booking',
                    'icon' => 'heroicon-o-calendar-days',
                    'color' => 'green',
                    'message' => "New booking: {$appointment->customer->name} with {$appointment->staff->name}",
                    'time' => $appointment->created_at->diffForHumans(),
                    'details' => $appointment->starts_at->format('d.m H:i') . ' - ' . $appointment->service->name,
                ];
            });
            
        // Recent cancellations (last hour)
        $recentCancellations = Appointment::where('updated_at', '>=', Carbon::now()->subHour())
            ->where('status', 'cancelled')
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->with(['customer', 'staff'])
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->map(function ($appointment) {
                return [
                    'type' => 'cancellation',
                    'icon' => 'heroicon-o-x-circle',
                    'color' => 'red',
                    'message' => "Cancellation: {$appointment->customer->name}",
                    'time' => $appointment->updated_at->diffForHumans(),
                    'details' => 'Was scheduled for ' . $appointment->starts_at->format('d.m H:i'),
                ];
            });
            
        // Recent calls
        $recentCalls = Call::where('created_at', '>=', Carbon::now()->subMinutes(30))
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->with(['customer'])
            ->latest()
            ->limit(3)
            ->get()
            ->map(function ($call) {
                return [
                    'type' => 'call',
                    'icon' => 'heroicon-o-phone',
                    'color' => 'blue',
                    'message' => "Incoming call: " . ($call->customer->name ?? 'Unknown caller'),
                    'time' => $call->created_at->diffForHumans(),
                    'details' => $call->duration_sec . 's - ' . ($call->appointment_id ? 'Booked' : 'No booking'),
                ];
            });
            
        // Merge and sort by time
        $activities = collect()
            ->merge($recentBookings)
            ->merge($recentCancellations)
            ->merge($recentCalls)
            ->sortByDesc('time')
            ->take(10)
            ->values()
            ->toArray();
            
        return $activities;
    }
    
    protected function getStaffAvailability(Carbon $date): array
    {
        $branches = Branch::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('id', $this->selectedBranchId))
            ->with(['staff' => function ($q) {
                $q->where('active', true);
            }])
            ->get();
            
        $availability = [];
        
        foreach ($branches as $branch) {
            $branchStaff = [];
            
            foreach ($branch->staff as $staff) {
                // Get appointments for this staff member today
                $appointments = Appointment::where('staff_id', $staff->id)
                    ->whereDate('starts_at', $date)
                    ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
                    ->orderBy('starts_at')
                    ->get();
                    
                // Calculate availability
                $totalSlots = 16; // 8 hours * 2 slots per hour
                $bookedSlots = $appointments->count();
                $availableSlots = $totalSlots - $bookedSlots;
                $utilizationRate = round(($bookedSlots / $totalSlots) * 100);
                
                // Get current status
                $now = Carbon::now();
                $currentAppointment = $appointments
                    ->where('starts_at', '<=', $now)
                    ->where('ends_at', '>=', $now)
                    ->first();
                    
                $nextAppointment = $appointments
                    ->where('starts_at', '>', $now)
                    ->first();
                    
                $branchStaff[] = [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'total_appointments' => $bookedSlots,
                    'available_slots' => $availableSlots,
                    'utilization' => $utilizationRate,
                    'status' => $currentAppointment ? 'busy' : 'available',
                    'current_appointment' => $currentAppointment ? [
                        'customer' => $currentAppointment->customer->name ?? 'Unknown',
                        'ends_at' => $currentAppointment->ends_at->format('H:i'),
                    ] : null,
                    'next_appointment' => $nextAppointment ? [
                        'time' => $nextAppointment->starts_at->format('H:i'),
                        'customer' => $nextAppointment->customer->name ?? 'Unknown',
                    ] : null,
                ];
            }
            
            $availability[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'staff' => $branchStaff,
                'total_staff' => count($branchStaff),
                'available_staff' => collect($branchStaff)->where('status', 'available')->count(),
            ];
        }
        
        return $availability;
    }
    
    protected function getActiveAlerts(Carbon $date): array
    {
        $alerts = [];
        $now = Carbon::now();
        
        // No-shows (appointments that should have started 30+ minutes ago)
        $potentialNoShows = Appointment::where('starts_at', '<', $now->copy()->subMinutes(30))
            ->where('starts_at', '>', $now->copy()->subHours(2))
            ->where('status', 'scheduled')
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->with(['customer', 'staff'])
            ->get();
            
        foreach ($potentialNoShows as $appointment) {
            $alerts[] = [
                'type' => 'no_show',
                'severity' => 'warning',
                'message' => "Potential no-show: {$appointment->customer->name}",
                'details' => "Was scheduled for {$appointment->starts_at->format('H:i')} with {$appointment->staff->name}",
                'action' => 'Contact customer',
                'appointment_id' => $appointment->id,
            ];
        }
        
        // Double bookings
        $doubleBookings = Appointment::whereDate('starts_at', $date)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->groupBy('staff_id', 'starts_at')
            ->havingRaw('COUNT(*) > 1')
            ->select('staff_id', 'starts_at', \DB::raw('COUNT(*) as count'))
            ->get();
            
        foreach ($doubleBookings as $doubleBooking) {
            $alerts[] = [
                'type' => 'double_booking',
                'severity' => 'error',
                'message' => 'Double booking detected',
                'details' => "Staff member has {$doubleBooking->count} appointments at {$doubleBooking->starts_at->format('H:i')}",
                'action' => 'Reschedule appointments',
            ];
        }
        
        // Low availability warning
        $upcomingHours = 3;
        $upcomingAppointments = Appointment::whereBetween('starts_at', [
                $now,
                $now->copy()->addHours($upcomingHours)
            ])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->count();
            
        $maxCapacity = $this->liveData['staff_availability'][0]['total_staff'] ?? 1 * $upcomingHours * 2;
        $utilizationRate = $maxCapacity > 0 ? ($upcomingAppointments / $maxCapacity) * 100 : 0;
        
        if ($utilizationRate > 90) {
            $alerts[] = [
                'type' => 'high_demand',
                'severity' => 'info',
                'message' => 'High demand alert',
                'details' => "Next {$upcomingHours} hours are {$utilizationRate}% booked",
                'action' => 'Consider opening additional slots',
            ];
        }
        
        return $alerts;
    }
    
    protected function getQueueStatus(): array
    {
        // Get current call queue status
        $activeCalls = Call::where('call_status', 'active')
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->count();
            
        $queuedCalls = Call::where('call_status', 'queued')
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->count();
            
        $avgWaitTime = Call::where('call_status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->avg('duration_sec') ?? 0;
            
        return [
            'active_calls' => $activeCalls,
            'queued_calls' => $queuedCalls,
            'avg_wait_time' => round($avgWaitTime),
            'status' => $queuedCalls > 5 ? 'busy' : ($queuedCalls > 0 ? 'normal' : 'idle'),
        ];
    }
    
    public function refresh(): void
    {
        $this->loadLiveData();
    }
    
    public function getBranches(): array
    {
        return Branch::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->pluck('name', 'id')
            ->toArray();
    }
    
    public static function canView(): bool
    {
        return true;
    }
}