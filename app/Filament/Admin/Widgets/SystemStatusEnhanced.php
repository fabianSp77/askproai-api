<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Filament\Admin\Widgets\Traits\HasLoadingStates;

class SystemStatusEnhanced extends Widget
{
    use HasLoadingStates;
    
    protected static string $view = 'filament.admin.widgets.system-status-enhanced';

    protected static ?int $sort = 8;

    protected int | string | array $columnSpan = 1;
    
    protected static ?string $pollingInterval = '60s';
    
    public array $statuses = [];
    
    public function mount(): void
    {
        $this->checkSystemStatus();
    }
    
    public function checkSystemStatus(): void
    {
        $this->withErrorHandling(function () {
            $this->statuses = Cache::remember('system_status', 300, function () {
                return [
                    'database' => $this->checkDatabase(),
                    'redis' => $this->checkRedis(),
                    'horizon' => $this->checkHorizon(),
                    'calcom' => $this->checkCalcom(),
                    'retell' => $this->checkRetell(),
                    'storage' => $this->checkStorage(),
                ];
            });
        });
    }
    
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $pendingMigrations = count(app('migrator')->getMigrationFiles(database_path('migrations'))) - 
                                count(app('migrator')->getRepository()->getRan());
            
            return [
                'status' => $pendingMigrations > 0 ? 'warning' : 'online',
                'message' => $pendingMigrations > 0 ? "$pendingMigrations ausstehende Migrationen" : 'Verbunden',
                'icon' => 'heroicon-o-circle-stack',
                'details' => [
                    'driver' => config('database.default'),
                    'pending_migrations' => $pendingMigrations,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'message' => 'Verbindung fehlgeschlagen',
                'icon' => 'heroicon-o-circle-stack',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }
    
    private function checkRedis(): array
    {
        try {
            $redis = app('redis')->connection();
            $redis->ping();
            
            return [
                'status' => 'online',
                'message' => 'Verbunden',
                'icon' => 'heroicon-o-server-stack',
                'details' => [
                    'queue_size' => $redis->llen('queues:default'),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'message' => 'Nicht verfügbar',
                'icon' => 'heroicon-o-server-stack',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }
    
    private function checkHorizon(): array
    {
        try {
            $horizon = app('Laravel\Horizon\Contracts\MasterSupervisorRepository');
            $masters = $horizon->all();
            
            return [
                'status' => !empty($masters) ? 'online' : 'offline',
                'message' => !empty($masters) ? 'Läuft' : 'Gestoppt',
                'icon' => 'heroicon-o-queue-list',
                'details' => [
                    'supervisors' => count($masters),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'message' => 'Nicht installiert',
                'icon' => 'heroicon-o-queue-list',
                'details' => []
            ];
        }
    }
    
    private function checkCalcom(): array
    {
        try {
            $apiKey = config('services.calcom.api_key');
            if (empty($apiKey)) {
                return [
                    'status' => 'warning',
                    'message' => 'Kein API-Key',
                    'icon' => 'heroicon-o-calendar',
                    'details' => []
                ];
            }
            
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->get('https://api.cal.com/v1/me');
            
            return [
                'status' => $response->successful() ? 'online' : 'offline',
                'message' => $response->successful() ? 'Verbunden' : 'Fehler: ' . $response->status(),
                'icon' => 'heroicon-o-calendar',
                'details' => []
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'message' => 'Nicht erreichbar',
                'icon' => 'heroicon-o-calendar',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }
    
    private function checkRetell(): array
    {
        try {
            $apiKey = config('services.retell.api_key');
            if (empty($apiKey)) {
                return [
                    'status' => 'warning',
                    'message' => 'Kein API-Key',
                    'icon' => 'heroicon-o-phone',
                    'details' => []
                ];
            }
            
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->get('https://api.retellai.com/v1/agents');
            
            return [
                'status' => $response->successful() ? 'online' : 'offline',
                'message' => $response->successful() ? 'Verbunden' : 'Fehler: ' . $response->status(),
                'icon' => 'heroicon-o-phone',
                'details' => []
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'message' => 'Nicht erreichbar',
                'icon' => 'heroicon-o-phone',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }
    
    private function checkStorage(): array
    {
        try {
            $path = storage_path();
            $free = disk_free_space($path);
            $total = disk_total_space($path);
            $used = $total - $free;
            $percentage = round(($used / $total) * 100, 2);
            
            return [
                'status' => $percentage > 90 ? 'warning' : 'online',
                'message' => $percentage . '% belegt',
                'icon' => 'heroicon-o-server',
                'details' => [
                    'free' => $this->formatBytes($free),
                    'total' => $this->formatBytes($total),
                    'percentage' => $percentage,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'message' => 'Fehler',
                'icon' => 'heroicon-o-server',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }
    
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    public function poll(): void
    {
        $this->checkSystemStatus();
    }
}