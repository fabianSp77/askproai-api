<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AnimatedStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.animated-status-widget';
    protected static ?int $pollingInterval = 15; // Sekunden

    public function getIntegrationStates(): array
    {
        $states = [];

        // Cal.com API
        $lastCheck = now();
        $startCal = microtime(true);
        try {
            $response = Http::timeout(3)->get('https://api.cal.com/v1/bookings', [
                'apiKey' => env('CALCOM_API_KEY')
            ]);
            $latencyCal = round((microtime(true) - $startCal) * 1000); // Antwortzeit in ms
            $states['Cal.com API'] = [
                'status' => $response->successful() ? 'online' : 'offline',
                'last_success' => $response->successful() ? $lastCheck : Cache::get('calcom_last_success'),
                'flowing' => $response->successful(),
                'latency' => $latencyCal,
                'history' => Cache::get('calcom_history', []),
            ];
            if ($response->successful()) {
                Cache::put('calcom_last_success', $lastCheck, 60 * 24);
                $history = Cache::get('calcom_history', []);
                $history[] = $lastCheck->timestamp;
                $history = array_slice($history, -10);
                Cache::put('calcom_history', $history, 60 * 24);
            }
        } catch (\Exception $e) {
            $latencyCal = null;
            $states['Cal.com API'] = [
                'status' => 'offline',
                'last_success' => Cache::get('calcom_last_success'),
                'flowing' => false,
                'latency' => $latencyCal,
                'history' => Cache::get('calcom_history', []),
            ];
        }

        // Retell.ai API (KORREKTE ABFRAGE!)
        $lastCheck = now();
        $startRetell = microtime(true);
        try {
            $response = Http::withToken(env('RETELL_API_KEY'))
                ->timeout(3)
                ->get('https://api.retellai.com/list-agents');
            $latencyRetell = round((microtime(true) - $startRetell) * 1000);
            $states['Retell.ai API'] = [
                'status' => $response->successful() ? 'online' : 'offline',
                'last_success' => $response->successful() ? $lastCheck : Cache::get('retell_last_success'),
                'flowing' => $response->successful(),
                'latency' => $latencyRetell,
                'history' => Cache::get('retell_history', []),
            ];
            if ($response->successful()) {
                Cache::put('retell_last_success', $lastCheck, 60 * 24);
                $history = Cache::get('retell_history', []);
                $history[] = $lastCheck->timestamp;
                $history = array_slice($history, -10);
                Cache::put('retell_history', $history, 60 * 24);
            }
        } catch (\Exception $e) {
            $latencyRetell = null;
            $states['Retell.ai API'] = [
                'status' => 'offline',
                'last_success' => Cache::get('retell_last_success'),
                'flowing' => false,
                'latency' => $latencyRetell,
                'history' => Cache::get('retell_history', []),
            ];
        }

        // Datenbank
        try {
            DB::connection()->getPdo();
            $states['Datenbank'] = [
                'status' => 'online',
                'last_success' => now(),
                'flowing' => true,
                'latency' => null,
                'history' => [],
            ];
        } catch (\Exception $e) {
            $states['Datenbank'] = [
                'status' => 'offline',
                'last_success' => null,
                'flowing' => false,
                'latency' => null,
                'history' => [],
            ];
        }

        // Server (immer online)
        $states['Server'] = [
            'status' => 'online',
            'last_success' => now(),
            'flowing' => true,
            'latency' => null,
            'history' => [],
        ];

        return $states;
    }
}
