<?php

namespace App\Filament\Admin\Widgets;

use App\Models\RetellAgent;
use App\Models\Call;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SystemHealthWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        $activeAgents = RetellAgent::where('name', 'like', 'Online:%')->count();
        $totalAgents = RetellAgent::count();
        
        $todayCalls = Call::whereDate('created_at', Carbon::today())->count();
        $weekCalls = Call::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        
        try {
            DB::connection()->getPdo();
            $dbStatus = 'Connected';
            $dbColor = 'success';
        } catch (\Exception $e) {
            $dbStatus = 'Error';
            $dbColor = 'danger';
        }
        
        $lastCall = Call::latest()->first();
        $lastActivity = $lastCall ? $lastCall->created_at->diffForHumans() : 'No activity';
        
        return [
            Stat::make('Active AI Agents', $activeAgents . ' / ' . $totalAgents)
                ->description($activeAgents > 0 ? 'Agents online' : 'No agents online')
                ->descriptionIcon($activeAgents > 0 ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($activeAgents > 0 ? 'success' : 'warning')
                ->chart([7, 6, 8, 5, 9, 8, $activeAgents]),
            
            Stat::make('Calls Today', $todayCalls)
                ->description($weekCalls . ' this week')
                ->descriptionIcon('heroicon-m-phone-arrow-up-right')
                ->color('info')
                ->chart($this->getCallsChart()),
            
            Stat::make('Database Status', $dbStatus)
                ->description('Last activity: ' . $lastActivity)
                ->descriptionIcon($dbColor === 'success' ? 'heroicon-m-server' : 'heroicon-m-exclamation-triangle')
                ->color($dbColor),
        ];
    }
    
    protected function getCallsChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $count = Call::whereDate('created_at', Carbon::today()->subDays($i))->count();
            $data[] = $count;
        }
        return $data;
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }
}