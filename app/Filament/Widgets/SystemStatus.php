<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;

class SystemStatus extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $retell = $this->check(config('retellai.base_url'));
        $cal = $this->check(config('services.calcom.base_url'));

        return [
            Stat::make('Retell', $retell['label'])->color($retell['color']),
            Stat::make('Cal.com', $cal['label'])->color($cal['color']),
        ];
    }

    private function check(string $url): array
    {
        try {
            $ok = Http::timeout(3)->get($url)->successful();
        } catch (\Throwable $e) {
            return ['label' => 'Fehler', 'color' => 'danger'];
        }

        return $ok
            ? ['label' => 'OK', 'color' => 'success']
            : ['label' => 'Warnung', 'color' => 'warning'];
    }
}
