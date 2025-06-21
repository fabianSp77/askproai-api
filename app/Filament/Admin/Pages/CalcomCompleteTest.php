<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\CalcomV2Service;
use App\Jobs\SyncCalcomBookingsJob;
use App\Models\Appointment;
use App\Models\CalcomEventType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CalcomCompleteTest extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'System & Überwachung';
    protected static ?string $navigationLabel = 'Cal.com Complete Test';
    protected static ?int $navigationSort = 12;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    
    protected static string $view = 'filament.admin.pages.calcom-complete-test';
    
    public ?array $data = [];
    public array $testResults = [];
    public ?array $availableSlots = null;
    public ?array $eventTypes = null;
    public ?array $teamMembers = null;
    
    public function mount(): void
    {
        $company = auth()->user()->company;
        $tomorrow = Carbon::tomorrow();
        
        $this->form->fill([
            'api_key' => $company->calcom_api_key ?? config('services.calcom.api_key'),
            'test_date' => $tomorrow->format('Y-m-d'),
            'customer_name' => 'Test Kunde',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+49 151 12345678',
            'notes' => 'Test-Buchung über AskProAI Complete Test',
            'use_v2' => true,
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('API Configuration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('api_key')
                                    ->label('Cal.com API Key')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->helperText('Your Cal.com API key'),
                                    
                                Toggle::make('use_v2')
                                    ->label('Use API v2')
                                    ->default(true)
                                    ->helperText('Toggle between v1 and v2 API'),
                            ]),
                    ]),
                    
                Section::make('Test Booking Data')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('test_date')
                                    ->label('Test Date')
                                    ->required()
                                    ->minDate(now())
                                    ->reactive(),
                                    
                                Select::make('event_type_id')
                                    ->label('Event Type')
                                    ->options(function () {
                                        if ($this->eventTypes) {
                                            return collect($this->eventTypes)->pluck('title', 'id');
                                        }
                                        return [];
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->helperText('First run "Get Event Types" to populate this list'),
                                    
                                Select::make('selected_slot')
                                    ->label('Available Time Slot')
                                    ->options(function () {
                                        if ($this->availableSlots) {
                                            return collect($this->availableSlots)->mapWithKeys(function ($slot) {
                                                $time = Carbon::parse($slot);
                                                return [$slot => $time->format('H:i')];
                                            });
                                        }
                                        return [];
                                    })
                                    ->helperText('First run "Check Availability" to see slots'),
                                    
                                TextInput::make('customer_name')
                                    ->label('Customer Name')
                                    ->required(),
                                    
                                TextInput::make('customer_email')
                                    ->label('Customer Email')
                                    ->email()
                                    ->required(),
                                    
                                TextInput::make('customer_phone')
                                    ->label('Customer Phone')
                                    ->tel(),
                            ]),
                            
                        Textarea::make('notes')
                            ->label('Booking Notes')
                            ->rows(2),
                    ]),
            ])
            ->statePath('data');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_all_tests')
                ->label('Run All Tests')
                ->icon('heroicon-o-play')
                ->color('success')
                ->action(function () {
                    $this->runAllTests();
                }),
        ];
    }
    
    public function getTestActions(): array
    {
        return [
            // Basic Connection Tests
            Action::make('test_connection')
                ->label('1. Test API Connection')
                ->icon('heroicon-o-signal')
                ->action(fn () => $this->testConnection()),
                
            // Event Types & Team
            Action::make('get_event_types')
                ->label('2. Get Event Types')
                ->icon('heroicon-o-rectangle-stack')
                ->action(fn () => $this->getEventTypes()),
                
            Action::make('get_team_members')
                ->label('3. Get Team/Staff')
                ->icon('heroicon-o-user-group')
                ->action(fn () => $this->getTeamMembers()),
                
            // New: Teams & Schedules
            Action::make('get_teams')
                ->label('4. Get Teams')
                ->icon('heroicon-o-building-office')
                ->action(fn () => $this->getTeams()),
                
            Action::make('get_schedules')
                ->label('5. Get Schedules')
                ->icon('heroicon-o-calendar')
                ->action(fn () => $this->getSchedules()),
                
            Action::make('get_webhooks')
                ->label('6. Get Webhooks')
                ->icon('heroicon-o-link')
                ->action(fn () => $this->getWebhooks()),
                
            // Availability
            Action::make('check_availability')
                ->label('7. Check Availability')
                ->icon('heroicon-o-clock')
                ->action(fn () => $this->checkAvailability())
                ->disabled(fn () => empty($this->data['event_type_id'])),
                
            // Booking Operations
            Action::make('create_booking')
                ->label('8. Create Test Booking')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->createBooking())
                ->disabled(fn () => empty($this->data['selected_slot'])),
                
            // Booking Management
            Action::make('get_bookings')
                ->label('9. Get Recent Bookings')
                ->icon('heroicon-o-calendar-days')
                ->action(fn () => $this->getBookings()),
                
            Action::make('update_booking')
                ->label('10. Update Last Booking')
                ->icon('heroicon-o-pencil')
                ->color('warning')
                ->action(fn () => $this->updateBooking()),
                
            Action::make('cancel_booking')
                ->label('11. Cancel Last Booking')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->cancelBooking()),
                
            // Sync & Import
            Action::make('sync_all')
                ->label('12. Full Sync')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(fn () => $this->syncAll()),
        ];
    }
    
    private function testConnection(): void
    {
        $this->testResults['connection'] = [];
        $apiKey = $this->data['api_key'];
        $useV2 = $this->data['use_v2'];
        
        try {
            if ($useV2) {
                // Test v2 API
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'cal-api-version' => '2024-08-13',
                ])->get('https://api.cal.com/v2/me');
                
                if ($response->successful()) {
                    $data = $response->json();
                    $this->testResults['connection']['v2'] = [
                        'status' => 'success',
                        'message' => 'Connected to API v2',
                        'user' => $data['data'] ?? $data
                    ];
                } else {
                    $this->testResults['connection']['v2'] = [
                        'status' => 'error',
                        'message' => 'v2 failed: ' . $response->status(),
                        'error' => $response->body()
                    ];
                }
            } else {
                // Test v1 API
                $response = Http::get("https://api.cal.com/v1/me?apiKey={$apiKey}");
                
                if ($response->successful()) {
                    $data = $response->json();
                    $this->testResults['connection']['v1'] = [
                        'status' => 'success',
                        'message' => 'Connected to API v1',
                        'user' => $data['user'] ?? $data
                    ];
                } else {
                    $this->testResults['connection']['v1'] = [
                        'status' => 'error',
                        'message' => 'v1 failed: ' . $response->status(),
                        'error' => $response->body()
                    ];
                }
            }
            
            $this->showTestResult('Connection Test', $this->testResults['connection']);
            
        } catch (\Exception $e) {
            $this->showError('Connection Test', $e->getMessage());
        }
    }
    
    private function getEventTypes(): void
    {
        try {
            $service = new CalcomV2Service($this->data['api_key']);
            
            if ($this->data['use_v2']) {
                // Try v2 first
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->data['api_key'],
                    'cal-api-version' => '2024-08-13',
                ])->get('https://api.cal.com/v2/event-types');
                
                if ($response->successful()) {
                    $data = $response->json();
                    $this->eventTypes = $data['data'] ?? [];
                } else {
                    // Fallback to v1
                    $eventTypes = $service->getEventTypes();
                    $this->eventTypes = $eventTypes['event_types'] ?? $eventTypes ?? [];
                }
            } else {
                $eventTypes = $service->getEventTypes();
                $this->eventTypes = $eventTypes['event_types'] ?? $eventTypes ?? [];
            }
            
            $this->testResults['event_types'] = [
                'count' => count($this->eventTypes),
                'types' => collect($this->eventTypes)->map(function ($type) {
                    return [
                        'id' => $type['id'],
                        'title' => $type['title'] ?? $type['slug'],
                        'duration' => $type['length'] ?? $type['duration'] ?? 'N/A',
                        'hosts' => $type['hosts'] ?? $type['users'] ?? []
                    ];
                })->toArray()
            ];
            
            // Update form options
            $this->form->fill($this->data);
            
            $this->showTestResult('Event Types', $this->testResults['event_types']);
            
        } catch (\Exception $e) {
            $this->showError('Event Types', $e->getMessage());
        }
    }
    
    private function getTeamMembers(): void
    {
        try {
            $service = new CalcomV2Service($this->data['api_key']);
            
            // Get users/team members
            $users = $service->getUsers();
            $this->teamMembers = $users['users'] ?? $users ?? [];
            
            $this->testResults['team_members'] = [
                'count' => count($this->teamMembers),
                'members' => collect($this->teamMembers)->map(function ($member) {
                    return [
                        'id' => $member['id'],
                        'name' => $member['name'],
                        'email' => $member['email'],
                        'username' => $member['username'] ?? 'N/A'
                    ];
                })->toArray()
            ];
            
            $this->showTestResult('Team Members', $this->testResults['team_members']);
            
        } catch (\Exception $e) {
            $this->showError('Team Members', $e->getMessage());
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
                $this->availableSlots = $result['data']['slots'] ?? [];
                
                $this->testResults['availability'] = [
                    'date' => $this->data['test_date'],
                    'event_type_id' => $this->data['event_type_id'],
                    'count' => count($this->availableSlots),
                    'slots' => collect($this->availableSlots)->map(function ($slot) {
                        return Carbon::parse($slot)->format('H:i');
                    })->toArray()
                ];
                
                // Update form
                $this->form->fill($this->data);
                
                $this->showTestResult('Availability Check', $this->testResults['availability']);
            } else {
                $this->showError('Availability Check', $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->showError('Availability Check', $e->getMessage());
        }
    }
    
    private function createBooking(): void
    {
        try {
            $service = new CalcomV2Service($this->data['api_key']);
            
            // Parse selected slot
            $startTime = Carbon::parse($this->data['selected_slot']);
            $eventType = collect($this->eventTypes)->firstWhere('id', $this->data['event_type_id']);
            $duration = $eventType['length'] ?? 60;
            $endTime = $startTime->copy()->addMinutes($duration);
            
            $customerData = [
                'name' => $this->data['customer_name'],
                'email' => $this->data['customer_email'],
                'phone' => $this->data['customer_phone'] ?? null,
            ];
            
            // Create booking
            $result = $service->bookAppointment(
                $this->data['event_type_id'],
                $startTime->toIso8601String(),
                $endTime->toIso8601String(),
                $customerData,
                $this->data['notes']
            );
            
            if ($result) {
                Cache::put('last_test_booking_id', $result['id'] ?? $result['uid'], 3600);
                
                $this->testResults['create_booking'] = [
                    'status' => 'success',
                    'booking_id' => $result['id'] ?? $result['uid'],
                    'booking_uid' => $result['uid'] ?? null,
                    'start_time' => $startTime->format('Y-m-d H:i'),
                    'customer' => $customerData,
                    'response' => $result
                ];
                
                $this->showTestResult('Booking Created', $this->testResults['create_booking']);
            } else {
                $this->showError('Create Booking', 'Failed to create booking');
            }
            
        } catch (\Exception $e) {
            $this->showError('Create Booking', $e->getMessage());
        }
    }
    
    private function getBookings(): void
    {
        try {
            $service = new CalcomV2Service($this->data['api_key']);
            
            $result = $service->getBookings(['limit' => 10]);
            
            if ($result['success']) {
                $bookings = $result['data']['bookings'];
                
                $this->testResults['bookings'] = [
                    'count' => count($bookings),
                    'bookings' => collect($bookings)->map(function ($booking) {
                        return [
                            'id' => $booking['id'],
                            'title' => $booking['title'],
                            'status' => $booking['status'],
                            'start' => Carbon::parse($booking['start'] ?? $booking['startTime'])->format('Y-m-d H:i'),
                            'attendees' => collect($booking['attendees'] ?? [])->pluck('name')->implode(', ')
                        ];
                    })->take(5)->toArray()
                ];
                
                // Store last booking ID for update/cancel tests
                if (count($bookings) > 0) {
                    Cache::put('last_test_booking_id', $bookings[0]['id'], 3600);
                }
                
                $this->showTestResult('Recent Bookings', $this->testResults['bookings']);
            } else {
                $this->showError('Get Bookings', $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->showError('Get Bookings', $e->getMessage());
        }
    }
    
    private function updateBooking(): void
    {
        try {
            $bookingId = Cache::get('last_test_booking_id');
            
            if (!$bookingId) {
                $this->showError('Update Booking', 'No booking ID found. Create or get bookings first.');
                return;
            }
            
            // For v2 API, update booking
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->data['api_key'],
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->patch("https://api.cal.com/v2/bookings/{$bookingId}", [
                'title' => 'Updated: ' . $this->data['notes'],
                'description' => 'Updated at ' . now()->format('Y-m-d H:i:s')
            ]);
            
            if ($response->successful()) {
                $this->testResults['update_booking'] = [
                    'status' => 'success',
                    'booking_id' => $bookingId,
                    'message' => 'Booking updated successfully',
                    'response' => $response->json()
                ];
                
                $this->showTestResult('Update Booking', $this->testResults['update_booking']);
            } else {
                $this->showError('Update Booking', 'Status ' . $response->status() . ': ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->showError('Update Booking', $e->getMessage());
        }
    }
    
    private function cancelBooking(): void
    {
        try {
            $bookingId = Cache::get('last_test_booking_id');
            
            if (!$bookingId) {
                $this->showError('Cancel Booking', 'No booking ID found. Create or get bookings first.');
                return;
            }
            
            // Cancel booking
            if ($this->data['use_v2']) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->data['api_key'],
                    'cal-api-version' => '2024-08-13',
                ])->delete("https://api.cal.com/v2/bookings/{$bookingId}");
            } else {
                $response = Http::delete("https://api.cal.com/v1/bookings/{$bookingId}?apiKey=" . $this->data['api_key']);
            }
            
            if ($response->successful()) {
                $this->testResults['cancel_booking'] = [
                    'status' => 'success',
                    'booking_id' => $bookingId,
                    'message' => 'Booking cancelled successfully'
                ];
                
                $this->showTestResult('Cancel Booking', $this->testResults['cancel_booking']);
            } else {
                $this->showError('Cancel Booking', 'Status ' . $response->status() . ': ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->showError('Cancel Booking', $e->getMessage());
        }
    }
    
    private function getTeams(): void
    {
        try {
            $service = new CalcomV2Service($this->data['api_key']);
            
            $result = $service->getTeams();
            
            if ($result['success']) {
                $teams = $result['data']['data'] ?? $result['data'] ?? [];
                
                $this->testResults['teams'] = [
                    'count' => count($teams),
                    'teams' => collect($teams)->map(function ($team) {
                        return [
                            'id' => $team['id'],
                            'name' => $team['name'],
                            'slug' => $team['slug'],
                            'members_count' => count($team['members'] ?? [])
                        ];
                    })->toArray()
                ];
                
                // If teams found, get event types for first team
                if (count($teams) > 0) {
                    $firstTeamId = $teams[0]['id'];
                    $teamEventTypes = $service->getTeamEventTypes($firstTeamId);
                    
                    if ($teamEventTypes['success']) {
                        $eventTypes = $teamEventTypes['data']['data'] ?? [];
                        $this->testResults['teams']['first_team_event_types'] = [
                            'team_id' => $firstTeamId,
                            'count' => count($eventTypes),
                            'types' => collect($eventTypes)->map(function ($et) {
                                return [
                                    'id' => $et['id'],
                                    'title' => $et['title'],
                                    'hosts_count' => count($et['hosts'] ?? [])
                                ];
                            })->take(3)->toArray()
                        ];
                    }
                }
                
                $this->showTestResult('Teams', $this->testResults['teams']);
            } else {
                $this->showError('Teams', $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->showError('Teams', $e->getMessage());
        }
    }
    
    private function getSchedules(): void
    {
        try {
            $service = new CalcomV2Service($this->data['api_key']);
            
            $result = $service->getSchedules();
            
            if ($result['success']) {
                $schedules = $result['data']['data'] ?? $result['data'] ?? [];
                
                $this->testResults['schedules'] = [
                    'count' => count($schedules),
                    'schedules' => collect($schedules)->map(function ($schedule) {
                        return [
                            'id' => $schedule['id'],
                            'name' => $schedule['name'],
                            'isDefault' => $schedule['isDefault'] ?? false
                        ];
                    })->toArray()
                ];
                
                $this->showTestResult('Schedules', $this->testResults['schedules']);
            } else {
                $this->showError('Schedules', $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->showError('Schedules', $e->getMessage());
        }
    }
    
    private function getWebhooks(): void
    {
        try {
            $service = new CalcomV2Service($this->data['api_key']);
            
            $result = $service->getWebhooks();
            
            if ($result['success']) {
                $webhooks = $result['data']['data'] ?? $result['data'] ?? [];
                
                $this->testResults['webhooks'] = [
                    'count' => count($webhooks),
                    'webhooks' => collect($webhooks)->map(function ($webhook) {
                        return [
                            'id' => $webhook['id'],
                            'subscriberUrl' => $webhook['subscriberUrl'],
                            'active' => $webhook['active'] ?? false,
                            'triggers' => $webhook['triggers'] ?? []
                        ];
                    })->toArray(),
                    'askproai_webhook_registered' => collect($webhooks)->contains(function ($webhook) {
                        return str_contains($webhook['subscriberUrl'], 'askproai');
                    })
                ];
                
                $this->showTestResult('Webhooks', $this->testResults['webhooks']);
            } else {
                $this->showError('Webhooks', $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->showError('Webhooks', $e->getMessage());
        }
    }
    
    private function syncAll(): void
    {
        try {
            $company = auth()->user()->company;
            $beforeCount = Appointment::whereNotNull('calcom_v2_booking_id')->count();
            
            SyncCalcomBookingsJob::dispatch($company, $this->data['api_key']);
            
            // Wait for job
            sleep(3);
            
            $afterCount = Appointment::whereNotNull('calcom_v2_booking_id')->count();
            $synced = $afterCount - $beforeCount;
            
            $this->testResults['sync'] = [
                'status' => 'success',
                'before_count' => $beforeCount,
                'after_count' => $afterCount,
                'synced' => $synced,
                'message' => "Synced {$synced} new appointments"
            ];
            
            $this->showTestResult('Full Sync', $this->testResults['sync']);
            
        } catch (\Exception $e) {
            $this->showError('Full Sync', $e->getMessage());
        }
    }
    
    private function runAllTests(): void
    {
        $this->testResults = [];
        
        Notification::make()
            ->title('Running All Tests')
            ->body('This may take a few moments...')
            ->info()
            ->send();
            
        // Run tests in sequence
        $this->testConnection();
        sleep(1);
        
        $this->getEventTypes();
        sleep(1);
        
        $this->getTeamMembers();
        sleep(1);
        
        if (!empty($this->data['event_type_id'])) {
            $this->checkAvailability();
            sleep(1);
        }
        
        $this->getBookings();
        
        Notification::make()
            ->title('All Tests Completed')
            ->body('Check the results below')
            ->success()
            ->duration(5000)
            ->send();
    }
    
    private function showTestResult(string $test, array $result): void
    {
        // Store result in testResults array for display
        $this->testResults[$test] = $result;
        
        // Also show notification
        $message = json_encode($result, JSON_PRETTY_PRINT);
        
        Notification::make()
            ->title($test . ' - Result')
            ->body(substr($message, 0, 500) . (strlen($message) > 500 ? '...' : ''))
            ->success()
            ->duration(10000)
            ->send();
    }
    
    private function showError(string $test, string $error): void
    {
        // Store error in testResults array for display
        $this->testResults[$test] = [
            'status' => 'error',
            'message' => $error
        ];
        
        Log::error('Cal.com Test Error', [
            'test' => $test,
            'error' => $error
        ]);
        
        Notification::make()
            ->title($test . ' - Failed')
            ->body($error)
            ->danger()
            ->duration(10000)
            ->send();
    }
}