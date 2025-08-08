<?php

namespace App\Filament\Admin\Resources\CallCampaignResource\Widgets;

use App\Models\RetellAICallCampaign;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CallCampaignStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Gesamt Kampagnen', RetellAICallCampaign::count())
                ->description('Alle erstellten Kampagnen')
                ->icon('heroicon-o-megaphone')
                ->color('primary'),
                
            Stat::make('Aktive Kampagnen', RetellAICallCampaign::active()->count())
                ->description('Laufende oder geplante')
                ->icon('heroicon-o-play-circle')
                ->color('success'),
                
            Stat::make('Geplante Kampagnen', RetellAICallCampaign::where('status', 'scheduled')->count())
                ->description('Warten auf Start')
                ->icon('heroicon-o-clock')
                ->color('warning'),
                
            Stat::make('Abgeschlossene Kampagnen', RetellAICallCampaign::where('status', 'completed')->count())
                ->description('Erfolgreich beendet')
                ->icon('heroicon-o-check-circle')
                ->color('gray'),
        ];
    }
    
    /**
     * Polling interval in seconds (null = no polling)
     */
    protected static ?string $pollingInterval = '30s';
}