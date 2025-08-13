<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class AskproaiOverview extends Widget
{
    protected static string $view = 'filament.widgets.askproai-overview';
    protected static ?int $sort = 1;
    
    protected function getViewData(): array
    {
        return [
            'todayCalls' => Call::whereDate('created_at', today())->count(),
            'todayAppointments' => Appointment::whereDate('start_time', today())->count(),
            'totalCustomers' => Customer::count(),
            'weeklyGrowth' => $this->calculateWeeklyGrowth(),
        ];
    }
    
    private function calculateWeeklyGrowth(): float
    {
        $thisWeek = Call::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $lastWeek = Call::whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count();
        
        if ($lastWeek == 0) return 100;
        return round((($thisWeek - $lastWeek) / $lastWeek) * 100, 1);
    }
}