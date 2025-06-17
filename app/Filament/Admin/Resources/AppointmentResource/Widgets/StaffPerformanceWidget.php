<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StaffPerformanceWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.staff-performance';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getStaffPerformance(): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $staffPerformance = Staff::select('staff.id', 'staff.name', 'staff.company_id')
            ->selectRaw('COUNT(DISTINCT appointments.id) as total_appointments')
            ->selectRaw('COUNT(DISTINCT CASE WHEN appointments.status = "completed" THEN appointments.id END) as completed_appointments')
            ->selectRaw('COUNT(DISTINCT CASE WHEN appointments.status = "no_show" THEN appointments.id END) as no_show_appointments')
            ->selectRaw('COUNT(DISTINCT CASE WHEN appointments.status = "cancelled" THEN appointments.id END) as cancelled_appointments')
            ->selectRaw('COALESCE(SUM(appointments.price), 0) as total_revenue')
            ->selectRaw('COALESCE(AVG(appointments.price), 0) as avg_price')
            ->leftJoin('appointments', function($join) use ($startDate, $endDate) {
                $join->on('staff.id', '=', 'appointments.staff_id')
                    ->whereBetween('appointments.starts_at', [$startDate, $endDate]);
            })
            ->groupBy('staff.id', 'staff.name', 'staff.company_id')
            ->orderByDesc('total_appointments')
            ->limit(10)
            ->get();
            
        return $staffPerformance->map(function($staff) {
            $completionRate = $staff->total_appointments > 0 
                ? round(($staff->completed_appointments / $staff->total_appointments) * 100) 
                : 0;
                
            $noShowRate = $staff->total_appointments > 0 
                ? round(($staff->no_show_appointments / $staff->total_appointments) * 100) 
                : 0;
                
            return [
                'name' => $staff->name,
                'total_appointments' => $staff->total_appointments,
                'completed' => $staff->completed_appointments,
                'completion_rate' => $completionRate,
                'no_show_rate' => $noShowRate,
                'cancelled' => $staff->cancelled_appointments,
                'revenue' => $staff->total_revenue / 100,
                'avg_price' => $staff->avg_price / 100,
            ];
        })->toArray();
    }
    
    public function getTopServices(): array
    {
        return DB::table('appointments')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->select('services.name', DB::raw('COUNT(*) as count'), DB::raw('SUM(appointments.price) as revenue'))
            ->whereBetween('appointments.starts_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])
            ->whereIn('appointments.status', ['completed', 'confirmed'])
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function($service) {
                return [
                    'name' => $service->name,
                    'count' => $service->count,
                    'revenue' => $service->revenue / 100,
                ];
            })
            ->toArray();
    }
}