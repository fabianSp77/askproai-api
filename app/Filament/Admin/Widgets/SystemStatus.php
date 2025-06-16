<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;

class SystemStatus extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'md' => 'full',
        'lg' => 1,
        'xl' => 1,
    ];
    
    protected static bool $isLazy = false;
    
    protected static ?int $sort = 8;
    
    protected function getStats(): array
    {
        $retell = $this->check('https://api.retellai.com');
        $cal = $this->check('https://api.cal.com');

        return [
            Stat::make('Retell.ai', $retell['label'])->color($retell['color'])->icon($retell['icon']),
            Stat::make('Cal.com', $cal['label'])->color($cal['color'])->icon($cal['icon']),
        ];
    }

    private function check(string $url): array
    {
        try {
            $ok = Http::timeout(3)->get($url)->successful();
        } catch (\Throwable $e) {
            return ['label' => 'Fehler', 'color' => 'danger', 'icon' => 'heroicon-o-x-circle'];
        }

        return $ok
            ? ['label' => 'OK', 'color' => 'success', 'icon' => 'heroicon-o-check-circle']
            : ['label' => 'Warnung', 'color' => 'warning', 'icon' => 'heroicon-o-exclamation-triangle'];
    }
}
