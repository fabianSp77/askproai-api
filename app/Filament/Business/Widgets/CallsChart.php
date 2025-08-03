<?php

namespace App\Filament\Business\Widgets;

use App\Models\Call;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CallsChart extends ChartWidget
{
    protected static ?string $heading = 'Calls This Week';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $callsQuery = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        
        // Apply role-based filtering
        if ($user->hasRole('company_staff')) {
            $callsQuery->where('assigned_to', $user->id);
        } elseif ($user->hasRole('company_manager')) {
            $teamIds = $user->teamMembers()->pluck('id')->push($user->id);
            $callsQuery->whereIn('assigned_to', $teamIds);
        }
        
        $data = [];
        $labels = [];
        
        for ($i = 0; $i < 7; $i++) {
            $date = now()->startOfWeek()->addDays($i);
            $labels[] = $date->format('D');
            $data[] = (clone $callsQuery)->whereDate('created_at', $date)->count();
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Calls',
                    'data' => $data,
                    'backgroundColor' => '#3B82F6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}