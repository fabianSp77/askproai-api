<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;

class SystemStatus extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        try {
        $retell = $this->check(config('services.retellai.base_url'));
        $cal = $this->check(config('services.calcom.base_url'));

        } catch (\Exception $e) {
            \Log::error('SystemStatus Widget Error: ' . $e->getMessage());
            return [
                Stat::make('System Status', 'Fehler')
                    ->description('Widget konnte nicht geladen werden')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Retell', $retell['label'])->color($retell['color']),
            Stat::make('Cal.com', $cal['label'])->color($cal['color']),
        ];
    }

    private function check(?string $url): array
    {
        if (empty($url)) {
            return ['label' => 'Nicht konfiguriert', 'color' => 'warning'];
        }

        try {
            $response = Http::timeout(3)->get($url);
            $ok = $response->successful();

            // Log für Debugging
            \Log::debug('SystemStatus Widget Check', [
                'url' => $url,
                'status' => $response->status(),
                'ok' => $ok,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('SystemStatus Widget Error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return ['label' => 'Fehler', 'color' => 'danger'];
        }

        return $ok
            ? ['label' => 'OK', 'color' => 'success']
            : ['label' => 'HTTP ' . $response->status(), 'color' => 'warning'];
    }
}
