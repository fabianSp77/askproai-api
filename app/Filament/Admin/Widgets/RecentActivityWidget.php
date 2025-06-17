<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use App\Models\Call;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class RecentActivityWidget extends Widget
{
    protected static string $view = 'filament.widgets.recent-activity';
    
    protected static ?int $sort = 3;
    
    protected int|string|array $columnSpan = 'full';
    
    public function getRecentActivities(): Collection
    {
        $activities = collect();
        
        // Recent appointments (last 5)
        $recentAppointments = Appointment::with(['customer', 'branch', 'service'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($appointment) {
                return [
                    'type' => 'appointment',
                    'icon' => 'heroicon-o-calendar',
                    'icon_color' => 'primary',
                    'title' => 'New appointment booked',
                    'description' => $appointment->customer?->name . ' - ' . $appointment->service?->name,
                    'meta' => $appointment->branch?->name,
                    'time' => $appointment->created_at->diffForHumans(),
                    'timestamp' => $appointment->created_at,
                ];
            });
        
        // Recent calls (last 5)
        $recentCalls = Call::with(['appointment', 'appointment.customer'])
            ->whereNotNull('ended_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($call) {
                $duration = $call->duration_seconds 
                    ? gmdate('i:s', $call->duration_seconds)
                    : '0:00';
                    
                return [
                    'type' => 'call',
                    'icon' => 'heroicon-o-phone',
                    'icon_color' => 'success',
                    'title' => 'Call completed',
                    'description' => $call->from_number . ' (' . $duration . ')',
                    'meta' => $call->appointment?->customer?->name ?? 'Unknown caller',
                    'time' => $call->created_at->diffForHumans(),
                    'timestamp' => $call->created_at,
                ];
            });
        
        // Merge and sort by timestamp
        return $activities
            ->concat($recentAppointments)
            ->concat($recentCalls)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values();
    }
}