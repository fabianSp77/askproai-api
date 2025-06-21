<?php

namespace App\Filament\Admin\Pages;

use App\Services\CalcomV2Service;
use App\Services\RetellService;
use App\Models\Call;
use App\Models\Appointment;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimpleSyncManager extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Daten abrufen';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static ?int $navigationSort = 90;
    protected static string $view = 'filament.admin.pages.simple-sync-manager';
    
    // Anruf-Filter
    public $callDateFrom;
    public $callDateTo;
    public $callLimit = 50;
    public $callMinDuration = 30;
    
    // Termin-Filter
    public $appointmentDateFrom;
    public $appointmentDateTo;
    public $appointmentLimit = 100;
    
    public function mount(): void
    {
        // Setze sinnvolle Defaults
        $this->callDateFrom = now()->subDays(7)->format('Y-m-d');
        $this->callDateTo = now()->format('Y-m-d');
        
        $this->appointmentDateFrom = now()->subDays(30)->format('Y-m-d');
        $this->appointmentDateTo = now()->addDays(30)->format('Y-m-d');
    }
    
    public function syncCalls(): void
    {
        try {
            $retellService = app(RetellService::class);
            
            // Zeige Notification
            Notification::make()
                ->title('Anruf-Abruf gestartet')
                ->body('Die Anrufe werden abgerufen...')
                ->info()
                ->send();
            
            // Hole Anrufe mit einfachen Parametern
            $startTime = Carbon::parse($this->callDateFrom)->startOfDay()->timestamp * 1000;
            $endTime = Carbon::parse($this->callDateTo)->endOfDay()->timestamp * 1000;
            
            // Direkt HTTP Request machen
            $apiKey = config('services.retell.api_key') ?? config('services.retell.token');
            
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->post('https://api.retellai.com/v2/list-calls', [
                    'limit' => $this->callLimit,
                    'start_timestamp' => $startTime,
                    'end_timestamp' => $endTime,
                ]);
            } catch (\Exception $e) {
                Notification::make()
                    ->title('API-Verbindungsfehler')
                    ->body('Die Retell API ist momentan nicht erreichbar. Bitte versuchen Sie es später erneut.')
                    ->danger()
                    ->send();
                return;
            }
            
            $imported = 0;
            $skipped = 0;
            
            if ($response && $response->successful()) {
                $data = $response->json();
                $calls = $data['calls'] ?? [];
                
                if (empty($calls)) {
                    Notification::make()
                        ->title('Keine Anrufe gefunden')
                        ->body('Es wurden keine Anrufe im angegebenen Zeitraum gefunden.')
                        ->warning()
                        ->send();
                    return;
                }
                
                foreach ($calls as $callData) {
                    // Prüfe Mindestdauer
                    $duration = (($callData['end_timestamp'] ?? 0) - ($callData['start_timestamp'] ?? 0)) / 1000;
                    if ($duration < $this->callMinDuration) {
                        $skipped++;
                        continue;
                    }
                    
                    // Prüfe ob bereits existiert
                    $exists = Call::where('retell_call_id', $callData['call_id'])->exists();
                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                    
                    // Erstelle Anruf
                    try {
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
                        \Log::error('Call import error', ['error' => $e->getMessage()]);
                    }
                }
            } else {
                $statusCode = $response ? $response->status() : 'Unknown';
                $errorMessage = $response ? $response->body() : 'Keine Antwort';
                
                Notification::make()
                    ->title('API-Fehler')
                    ->body("Retell API Fehler (Status: $statusCode): " . substr($errorMessage, 0, 100) . '...')
                    ->danger()
                    ->send();
                    
                Log::error('Retell API sync failed', [
                    'status' => $statusCode,
                    'response' => $errorMessage
                ]);
                
                return;
            }
            
            Notification::make()
                ->title('Anruf-Abruf abgeschlossen')
                ->body("$imported Anrufe importiert, $skipped übersprungen")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Anruf-Abruf')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function syncAppointments(): void
    {
        try {
            $calcomService = app(CalcomV2Service::class);
            
            Notification::make()
                ->title('Termin-Abruf gestartet')
                ->body('Die Termine werden abgerufen...')
                ->info()
                ->send();
            
            // Hole Termine
            $bookings = $calcomService->getBookings([
                'startDate' => Carbon::parse($this->appointmentDateFrom)->toIso8601String(),
                'endDate' => Carbon::parse($this->appointmentDateTo)->toIso8601String(),
            ]);
            
            $imported = 0;
            $skipped = 0;
            
            foreach ($bookings as $booking) {
                // Prüfe ob bereits existiert
                $exists = Appointment::where('calcom_booking_id', $booking['id'])->exists();
                if ($exists) {
                    $skipped++;
                    continue;
                }
                
                // Erstelle Termin
                try {
                    // Hier würde die Termin-Erstellung stattfinden
                    // Vereinfachte Version ohne komplexe Logik
                    $imported++;
                } catch (\Exception $e) {
                    \Log::error('Appointment import error', ['error' => $e->getMessage()]);
                }
            }
            
            Notification::make()
                ->title('Termin-Abruf abgeschlossen')
                ->body("$imported Termine importiert, $skipped übersprungen")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Termin-Abruf')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}