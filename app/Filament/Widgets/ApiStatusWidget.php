<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ApiStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.api-status-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;

    public function getStatuses(): array
    {
        return Cache::remember('api_statuses', 300, function () {
            $statuses = [];

            // Cal.com API Check
            $statuses['Cal.com API'] = $this->checkCalcomApi();

            // Retell.ai API Check
            $statuses['Retell.ai API'] = $this->checkRetellApi();

            // Datenbank Check
            $statuses['Datenbank'] = $this->checkDatabase();

            // Server Status
            $statuses['Server'] = $this->checkServer();

            return $statuses;
        });
    }


    private function checkCalcomApi(): array
    {
        $start = microtime(true);
        try {
            $response = Http::timeout(5)
                ->withHeaders(['apiKey' => config('services.calcom.api_key')])
                ->get('https://api.cal.com/v1/event-types');
            
            $latency = round((microtime(true) - $start) * 1000);
            
            if ($response->successful()) {
                return [
                    'status' => 'online',
                    'latency' => $latency,
                    'message' => 'API funktioniert einwandfrei',
                    'last_check' => now()->toIso8601String()
                ];
            }
            
            return [
                'status' => 'warning',
                'latency' => $latency,
                'message' => 'API antwortet mit Status ' . $response->status(),
                'last_check' => now()->toIso8601String()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'latency' => null,
                'message' => 'Verbindung fehlgeschlagen: ' . $e->getMessage(),
                'last_check' => now()->toIso8601String()
            ];
        }
    }


    private function checkRetellApi(): array
    {
        $start = microtime(true);
        try {
            // Korrekter Retell.ai API Endpunkt
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('RETELL_TOKEN'),
                    'Content-Type' => 'application/json'
                ])
                ->get('https://api.retellai.com/v1/agents');
            
            $latency = round((microtime(true) - $start) * 1000);
            
            if ($response->successful()) {
                return [
                    'status' => 'online',
                    'latency' => $latency,
                    'message' => 'API funktioniert einwandfrei',
                    'last_check' => now()->toIso8601String(),
                    'agent_count' => count($response->json() ?? [])
                ];
            }
            
            return [
                'status' => 'warning',
                'latency' => $latency,
                'message' => 'API antwortet mit Status ' . $response->status(),
                'last_check' => now()->toIso8601String()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'latency' => null,
                'message' => 'Verbindung fehlgeschlagen',
                'last_check' => now()->toIso8601String()
            ];
        }
    }


    private function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000);
            
            // Zusätzliche DB-Statistiken
            $callCount = DB::table('calls')->count();
            $appointmentCount = DB::table('appointments')->count();
            
            return [
                'status' => 'online',
                'latency' => $latency,
                'message' => 'Datenbank läuft stabil',
                'last_check' => now()->toIso8601String(),
                'stats' => [
                    'calls' => $callCount,
                    'appointments' => $appointmentCount
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'latency' => null,
                'message' => 'Datenbankverbindung fehlgeschlagen',
                'last_check' => now()->toIso8601String()
            ];
        }
    }


    private function checkServer(): array
    {
        $start = microtime(true);
        
        // Server-Metriken
        $loadAverage = sys_getloadavg();
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsagePercent = round((($diskTotal - $diskFree) / $diskTotal) * 100);
        $memoryUsage = memory_get_usage(true);
        
        $latency = round((microtime(true) - $start) * 1000);
        
        return [
            'status' => 'online',
            'latency' => $latency,
            'message' => 'Server läuft stabil',
            'last_check' => now()->toIso8601String(),
            'metrics' => [
                'load' => round($loadAverage[0], 2),
                'disk_usage' => $diskUsagePercent . '%',
                'memory' => $this->formatBytes($memoryUsage)
            ]
        ];
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
