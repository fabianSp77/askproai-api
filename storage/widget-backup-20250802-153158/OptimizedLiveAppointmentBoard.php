<?php

namespace App\Filament\Admin\Widgets;

use App\Repositories\OptimizedAppointmentRepository;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class OptimizedLiveAppointmentBoard extends Widget
{
    protected static string $view = 'filament.admin.widgets.live-appointment-board';
    
    protected int|string|array $columnSpan = 'full';
    
    public $appointments = [];
    public $branches = [];
    public $stats = [];
    
    protected OptimizedAppointmentRepository $repository;
    
    public function mount(): void
    {
        $this->repository = app(OptimizedAppointmentRepository::class);
        $this->loadData();
    }
    
    protected function loadData(): void
    {
        $companyId = auth()->user()->company_id;
        
        // Use cached data with 60 second TTL for dashboard
        $cacheKey = "dashboard:appointments:company:{$companyId}";
        
        $data = Cache::remember($cacheKey, 60, function() use ($companyId) {
            // Get all data with single optimized query
            $appointmentsByBranch = $this->repository->getTodaysAppointmentsByBranch($companyId);
            
            // Get branch information with single query
            $branches = \DB::table('branches')
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->select('id', 'name', 'phone', 'address')
                ->get()
                ->keyBy('id');
            
            // Get stats with single query
            $stats = $this->repository->getAppointmentStats(
                $companyId,
                now()->startOfDay(),
                now()->endOfDay()
            );
            
            // Get staff for all branches with single query
            $staff = \DB::table('staff as s')
                ->join('branches as b', 's.branch_id', '=', 'b.id')
                ->where('b.company_id', $companyId)
                ->where('s.is_active', true)
                ->select('s.id', 's.name', 's.branch_id', 's.avatar_url')
                ->get()
                ->groupBy('branch_id');
            
            return compact('appointmentsByBranch', 'branches', 'stats', 'staff');
        });
        
        // Transform data for view
        $this->branches = $data['branches']->map(function($branch) use ($data) {
            $branchAppointments = $data['appointmentsByBranch']->get($branch->id, collect());
            $branchStaff = $data['staff']->get($branch->id, collect());
            
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'appointment_count' => $branchAppointments->count(),
                'staff_count' => $branchStaff->count(),
                'appointments' => $branchAppointments->map(function($apt) {
                    return [
                        'id' => $apt->id,
                        'time' => Carbon::parse($apt->start_time)->format('H:i'),
                        'customer_name' => $apt->customer_name,
                        'staff_name' => $apt->staff_name,
                        'service_name' => $apt->service_name ?? 'N/A',
                        'status' => $apt->status,
                        'status_color' => $this->getStatusColor($apt->status),
                    ];
                })->values()->toArray(),
                'staff' => $branchStaff->map(function($s) use ($branchAppointments) {
                    $staffAppointments = $branchAppointments->where('staff_id', $s->id);
                    
                    return [
                        'id' => $s->id,
                        'name' => $s->name,
                        'avatar_url' => $s->avatar_url,
                        'appointment_count' => $staffAppointments->count(),
                        'is_busy' => $this->isStaffBusy($staffAppointments),
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();
        
        $this->stats = [
            'total_appointments' => $data['stats']['total'],
            'completed' => $data['stats']['completed'],
            'in_progress' => $this->calculateInProgress($data['appointmentsByBranch']),
            'upcoming' => $this->calculateUpcoming($data['appointmentsByBranch']),
        ];
    }
    
    protected function isStaffBusy($staffAppointments): bool
    {
        $now = now();
        
        return $staffAppointments->contains(function($apt) use ($now) {
            $start = Carbon::parse($apt->start_time);
            $end = Carbon::parse($apt->end_time);
            
            return $now->between($start, $end) && $apt->status === 'in_progress';
        });
    }
    
    protected function calculateInProgress($appointmentsByBranch): int
    {
        $now = now();
        $count = 0;
        
        foreach ($appointmentsByBranch as $appointments) {
            foreach ($appointments as $apt) {
                $start = Carbon::parse($apt->start_time);
                $end = Carbon::parse($apt->end_time);
                
                if ($now->between($start, $end) && $apt->status !== 'cancelled') {
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    protected function calculateUpcoming($appointmentsByBranch): int
    {
        $now = now();
        $count = 0;
        
        foreach ($appointmentsByBranch as $appointments) {
            foreach ($appointments as $apt) {
                if (Carbon::parse($apt->start_time)->gt($now) && $apt->status !== 'cancelled') {
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    protected function getStatusColor(string $status): string
    {
        return match($status) {
            'scheduled' => 'primary',
            'confirmed' => 'success',
            'in_progress' => 'warning',
            'completed' => 'gray',
            'cancelled' => 'danger',
            'no_show' => 'danger',
            default => 'secondary'
        };
    }
    
    // Polling for real-time updates (every 30 seconds)
    public function getPollingInterval(): ?string
    {
        return '30s';
    }
}