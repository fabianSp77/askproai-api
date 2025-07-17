<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Call;
use Carbon\Carbon;

class CallLiveStatusWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.call-live-status-widget';
    
    protected static ?int $sort = -1;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '5s';
    
    public static function canView(): bool
    {
        return true; // Always show this widget
    }
    
    protected function getViewData(): array
    {
        $company = auth()->user()->company;
        
        if (!$company) {
            return [
                'isLive' => false,
                'lastUpdate' => null,
                'recentCallsCount' => 0,
                'activeCalls' => 0,
                'lastCallTime' => null,
            ];
        }

        // Get the most recent call (bypass scopes for debugging)
        $lastCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('company_id', $company->id)
            ->orderBy('start_timestamp', 'desc')
            ->first();
            
        $lastCallTime = $lastCall ? $lastCall->start_timestamp : null;
        
        // Count calls in the last 5 minutes
        $recentCallsCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('company_id', $company->id)
            ->where('start_timestamp', '>=', now()->subMinutes(5))
            ->count();
            
        // Count active calls (in_progress status)
        $activeCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('company_id', $company->id)
            ->where('call_status', 'in_progress')
            ->count();
            
        // Determine if we're "live" (had calls in last minute)
        $isLive = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('company_id', $company->id)
            ->where('start_timestamp', '>=', now()->subMinute())
            ->exists();

        return [
            'isLive' => $isLive,
            'lastUpdate' => now(),
            'recentCallsCount' => $recentCallsCount,
            'activeCalls' => $activeCalls,
            'lastCallTime' => $lastCallTime,
        ];
    }
}