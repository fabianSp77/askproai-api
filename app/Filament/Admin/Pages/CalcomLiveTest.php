<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\CalcomV2Service;
use App\Jobs\SyncCalcomBookingsJob;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CalcomLiveTest extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'System & Überwachung';
    protected static ?string $navigationLabel = 'Cal.com Live Test';
    protected static ?int $navigationSort = 11;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    
    protected static string $view = 'filament.admin.pages.calcom-live-test';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $company = auth()->user()->company;
        $this->form->fill([
            'company_id' => $company->id,
            'api_key' => $company->calcom_api_key ?? config('services.calcom.api_key'),
            'test_date' => Carbon::tomorrow()->format('Y-m-d'),
            'event_type_id' => 1,
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('company_id')
                                    ->label('Company ID')
                                    ->disabled(),
                                    
                                TextInput::make('api_key')
                                    ->label('API Key')
                                    ->password()
                                    ->revealable()
                                    ->disabled(),
                            ]),
                    ]),
                    
                Section::make('Test Functions')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('test_date')
                                    ->label('Test Date (for availability)')
                                    ->required(),
                                    
                                TextInput::make('event_type_id')
                                    ->label('Event Type ID')
                                    ->numeric()
                                    ->default(1),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_connection')
                ->label('Test Connection')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function () {
                    $this->testConnection();
                }),
                
            Action::make('get_bookings')
                ->label('Get Bookings')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->action(function () {
                    $this->getBookings();
                }),
                
            Action::make('check_availability')
                ->label('Check Availability')
                ->icon('heroicon-o-clock')
                ->color('success')
                ->action(function () {
                    $this->checkAvailability();
                }),
                
            Action::make('sync_now')
                ->label('Sync Now')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $this->syncNow();
                }),
        ];
    }
    
    private function testConnection(): void
    {
        try {
            $apiKey = $this->data['api_key'];
            $service = new CalcomV2Service($apiKey);
            
            // Test v2 API
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'cal-api-version' => '2024-08-13',
            ])->get('https://api.cal.com/v2/me');
            
            if ($response->successful()) {
                Notification::make()
                    ->title('Connection Successful')
                    ->body('Connected to Cal.com API v2')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Connection Failed')
                    ->body('Status: ' . $response->status() . ' - ' . $response->body())
                    ->danger()
                    ->send();
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Connection Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    private function getBookings(): void
    {
        try {
            $service = new CalcomV2Service($this->data['api_key']);
            $result = $service->getBookings(['limit' => 10]);
            
            if ($result['success']) {
                $count = count($result['data']['bookings']);
                $message = "Retrieved {$count} bookings\n\n";
                
                foreach ($result['data']['bookings'] as $index => $booking) {
                    if ($index >= 3) break; // Show first 3
                    $message .= "• {$booking['title']} - {$booking['status']} ({$booking['start']})\n";
                }
                
                Notification::make()
                    ->title('Bookings Retrieved')
                    ->body($message)
                    ->success()
                    ->duration(10000)
                    ->send();
            } else {
                Notification::make()
                    ->title('Failed to Get Bookings')
                    ->body($result['error'])
                    ->danger()
                    ->send();
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    private function checkAvailability(): void
    {
        try {
            $service = new CalcomV2Service($this->data['api_key']);
            $result = $service->checkAvailability(
                $this->data['event_type_id'],
                $this->data['test_date'],
                'Europe/Berlin'
            );
            
            if ($result['success']) {
                $slots = $result['data']['slots'] ?? [];
                $count = count($slots);
                $message = "Found {$count} available slots for {$this->data['test_date']}\n\n";
                
                foreach (array_slice($slots, 0, 5) as $slot) {
                    $time = Carbon::parse($slot)->format('H:i');
                    $message .= "• {$time}\n";
                }
                
                Notification::make()
                    ->title('Availability Check')
                    ->body($message)
                    ->success()
                    ->duration(10000)
                    ->send();
            } else {
                Notification::make()
                    ->title('Availability Check Failed')
                    ->body($result['error'])
                    ->danger()
                    ->send();
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    private function syncNow(): void
    {
        try {
            $company = auth()->user()->company;
            $beforeCount = Appointment::whereNotNull('calcom_v2_booking_id')->count();
            
            // Dispatch sync job
            SyncCalcomBookingsJob::dispatch($company, $this->data['api_key']);
            
            // Wait a moment for the job to process
            sleep(2);
            
            $afterCount = Appointment::whereNotNull('calcom_v2_booking_id')->count();
            $synced = $afterCount - $beforeCount;
            
            Cache::put('last_calcom_sync_' . $company->id, now(), 3600);
            
            Notification::make()
                ->title('Sync Completed')
                ->body("Synced {$synced} new appointments. Total: {$afterCount}")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Sync Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function getTestResults(): array
    {
        return [
            'appointments_synced' => Appointment::whereNotNull('calcom_v2_booking_id')->count(),
            'last_sync' => Cache::get('last_calcom_sync_' . auth()->user()->company_id),
            'api_configured' => !empty($this->data['api_key']),
            'webhook_configured' => !empty(config('services.calcom.webhook_secret')),
        ];
    }
}