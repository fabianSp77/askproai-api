<?php

namespace App\Filament\Admin\Pages;

use App\Services\CalcomV2Service;
use App\Services\RetellService;
use App\Models\Call;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class DataSync extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Daten synchronisieren';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static ?int $navigationSort = 80;
    protected static string $view = 'filament.admin.pages.data-sync';
    
    public function syncLastWeekCalls(): void
    {
        $this->syncCallsForPeriod(7, 'der letzten Woche');
    }
    
    public function syncTodayCalls(): void
    {
        $this->syncCallsForPeriod(0, 'von heute');
    }
    
    public function syncLastMonthCalls(): void
    {
        $this->syncCallsForPeriod(30, 'des letzten Monats');
    }
    
    private function syncCallsForPeriod(int $days, string $periodName): void
    {
        try {
            $retellService = app(RetellService::class);
            
            $startDate = $days === 0 ? now()->startOfDay() : now()->subDays($days)->startOfDay();
            $endDate = now()->endOfDay();
            
            Notification::make()
                ->title('Anruf-Synchronisation gestartet')
                ->body("Hole Anrufe {$periodName}...")
                ->info()
                ->send();
            
            // Direkt HTTP Request machen, da RetellService keine getCalls Methode hat
            $apiKey = config('services.retell.api_key') ?? config('services.retell.token');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post('https://api.retellai.com/v2/list-calls', [
                'limit' => 100,
                'start_timestamp' => $startDate->timestamp * 1000,
                'end_timestamp' => $endDate->timestamp * 1000,
            ]);
            
            $imported = 0;
            $skipped = 0;
            
            if ($response->successful()) {
                $data = $response->json();
                $calls = $data['calls'] ?? [];
                
                foreach ($calls as $callData) {
                    if (Call::where('retell_call_id', $callData['call_id'])->exists()) {
                        $skipped++;
                        continue;
                    }
                    
                    try {
                        $duration = (($callData['end_timestamp'] ?? 0) - ($callData['start_timestamp'] ?? 0)) / 1000;
                        
                        Call::create([
                            'retell_call_id' => $callData['call_id'],
                            'company_id' => auth()->user()->company_id,
                            'from_number' => $callData['from_number'] ?? null,
                            'to_number' => $callData['to_number'] ?? null,
                            'status' => $callData['status'] ?? 'ended',
                            'start_timestamp' => isset($callData['start_timestamp']) 
                                ? Carbon::createFromTimestampMs($callData['start_timestamp']) 
                                : null,
                            'end_timestamp' => isset($callData['end_timestamp']) 
                                ? Carbon::createFromTimestampMs($callData['end_timestamp']) 
                                : null,
                            'duration_sec' => (int) $duration,
                            'agent_id' => $callData['agent_id'] ?? null,
                            'recording_url' => $callData['recording_url'] ?? null,
                            'transcript' => $callData['transcript'] ?? null,
                            'synced_at' => now(),
                        ]);
                        $imported++;
                    } catch (\Exception $e) {
                        \Log::error('Call import error', [
                            'call_id' => $callData['call_id'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } else {
                throw new \Exception('Retell API Fehler: ' . $response->body());
            }
            
            Notification::make()
                ->title('Synchronisation abgeschlossen')
                ->body("{$imported} Anrufe importiert, {$skipped} übersprungen")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Synchronisation fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function syncUpcomingAppointments(): void
    {
        $this->syncAppointmentsForPeriod('upcoming', 'Zukünftige');
    }
    
    public function syncPastAppointments(): void
    {
        $this->syncAppointmentsForPeriod('past', 'Vergangene');
    }
    
    private function syncAppointmentsForPeriod(string $type, string $typeName): void
    {
        try {
            $calcomService = app(CalcomV2Service::class);
            
            Notification::make()
                ->title('Termin-Synchronisation gestartet')
                ->body("{$typeName} Termine werden abgerufen...")
                ->info()
                ->send();
            
            if ($type === 'upcoming') {
                $startDate = now();
                $endDate = now()->addDays(90);
            } else {
                $startDate = now()->subDays(30);
                $endDate = now();
            }
            
            // Debug: Prüfe was getBookings zurückgibt
            $response = $calcomService->getBookings([
                'startDate' => $startDate->toIso8601String(),
                'endDate' => $endDate->toIso8601String(),
            ]);
            
            // Robuste Fehlerbehandlung
            $bookings = [];
            $count = 0;
            $errorMessage = null;
            
            if ($response === true || $response === false) {
                // Fallback: Wenn die Methode nur boolean zurückgibt
                $errorMessage = 'API returned boolean instead of data structure';
                \Log::error('Cal.com API issue', [
                    'response' => $response,
                    'type' => gettype($response)
                ]);
            } elseif (is_array($response)) {
                if (isset($response['success']) && $response['success'] === true) {
                    if (isset($response['data']['bookings']) && is_array($response['data']['bookings'])) {
                        $bookings = $response['data']['bookings'];
                        $count = count($bookings);
                    } else {
                        $errorMessage = 'Response structure invalid - no bookings array found';
                    }
                } else {
                    $errorMessage = $response['error'] ?? 'API request failed';
                }
            } else {
                $errorMessage = 'Unexpected response type: ' . gettype($response);
            }
            
            if ($errorMessage) {
                throw new \Exception($errorMessage);
            }
            
            Notification::make()
                ->title('Synchronisation abgeschlossen')
                ->body("{$count} {$typeName} Termine gefunden")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            \Log::error('Termin-Sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Synchronisation fehlgeschlagen')
                ->body('Fehler: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}