<?php

namespace App\Filament\Admin\Resources\UltimateAppointmentResource\Pages;

use App\Filament\Admin\Resources\UltimateAppointmentResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Wizard;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = UltimateAppointmentResource::class;

    protected static ?string $title = 'New Appointment';

    protected static string $view = 'filament.admin.pages.ultra-appointment-create';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Customer')
                        ->description('Select or create customer')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Grid::make(2)->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('notes')
                                            ->rows(3)
                                            ->maxLength(1000),
                                    ])
                                    ->createOptionModalHeading('Create New Customer')
                                    ->columnSpan(2),

                                Forms\Components\Placeholder::make('customer_info')
                                    ->label('Customer Details')
                                    ->content(function ($get) {
                                        if (!$customerId = $get('customer_id')) {
                                            return 'Select a customer to view their details';
                                        }
                                        
                                        $customer = \App\Models\Customer::find($customerId);
                                        if (!$customer) {
                                            return 'Customer not found';
                                        }

                                        return view('filament.admin.components.customer-quick-info', [
                                            'customer' => $customer
                                        ]);
                                    })
                                    ->columnSpan(2),
                            ]),
                        ]),

                    Wizard\Step::make('Service & Time')
                        ->description('Choose service and schedule')
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            Grid::make(2)->schema([
                                Forms\Components\Select::make('service_id')
                                    ->label('Service')
                                    ->relationship('service', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $service = \App\Models\Service::find($state);
                                            if ($service) {
                                                $set('duration', $service->duration);
                                                $set('price', $service->price);
                                            }
                                        }
                                    }),

                                Forms\Components\Select::make('branch_id')
                                    ->label('Branch')
                                    ->relationship('branch', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('staff_id', null)),

                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Start Date & Time')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->minutesStep(15)
                                    ->minDate(now())
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state && $duration = $get('duration')) {
                                            $set('ends_at', Carbon::parse($state)->addMinutes($duration));
                                        }
                                    }),

                                Forms\Components\DateTimePicker::make('ends_at')
                                    ->label('End Date & Time')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->minutesStep(15)
                                    ->minDate(fn (Forms\Get $get) => $get('starts_at') ?? now())
                                    ->reactive(),

                                Forms\Components\TextInput::make('duration')
                                    ->label('Duration (minutes)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->suffix('minutes'),

                                Forms\Components\TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->prefix('€')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                        ]),

                    Wizard\Step::make('Staff & Details')
                        ->description('Assign staff and add notes')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Grid::make(2)->schema([
                                Forms\Components\Select::make('staff_id')
                                    ->label('Staff Member')
                                    ->options(function (Forms\Get $get) {
                                        $branchId = $get('branch_id');
                                        $serviceId = $get('service_id');
                                        
                                        if (!$branchId || !$serviceId) {
                                            return [];
                                        }

                                        return \App\Models\Staff::query()
                                            ->where('company_id', session('company_id'))
                                            ->where('branch_id', $branchId)
                                            ->whereHas('services', function ($query) use ($serviceId) {
                                                $query->where('services.id', $serviceId);
                                            })
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Only staff assigned to the selected branch and service are shown'),

                                Forms\Components\Select::make('status')
                                    ->label('Initial Status')
                                    ->options([
                                        'scheduled' => 'Scheduled',
                                        'confirmed' => 'Confirmed',
                                    ])
                                    ->default('scheduled')
                                    ->required(),

                                Forms\Components\Select::make('source')
                                    ->label('Booking Source')
                                    ->options([
                                        'phone' => 'Phone Call',
                                        'online' => 'Online Booking',
                                        'walk-in' => 'Walk-in',
                                        'admin' => 'Admin Created',
                                    ])
                                    ->default('admin')
                                    ->required(),

                                Forms\Components\Toggle::make('send_confirmation')
                                    ->label('Send Confirmation Email')
                                    ->default(true)
                                    ->helperText('Send appointment confirmation to customer'),

                                Forms\Components\Toggle::make('send_reminder')
                                    ->label('Send Reminder')
                                    ->default(true)
                                    ->helperText('Send reminder 24 hours before appointment'),

                                Forms\Components\Toggle::make('is_recurring')
                                    ->label('Recurring Appointment')
                                    ->reactive()
                                    ->helperText('Create a series of appointments'),

                                Forms\Components\Select::make('recurrence_pattern')
                                    ->label('Recurrence Pattern')
                                    ->options([
                                        'daily' => 'Daily',
                                        'weekly' => 'Weekly',
                                        'biweekly' => 'Every 2 Weeks',
                                        'monthly' => 'Monthly',
                                    ])
                                    ->visible(fn (Forms\Get $get) => $get('is_recurring'))
                                    ->required(fn (Forms\Get $get) => $get('is_recurring')),

                                Forms\Components\TextInput::make('recurrence_count')
                                    ->label('Number of Occurrences')
                                    ->numeric()
                                    ->minValue(2)
                                    ->maxValue(52)
                                    ->default(4)
                                    ->visible(fn (Forms\Get $get) => $get('is_recurring'))
                                    ->required(fn (Forms\Get $get) => $get('is_recurring')),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Internal Notes')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->helperText('These notes are only visible to staff'),

                                Forms\Components\Textarea::make('customer_notes')
                                    ->label('Customer Notes')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->helperText('These notes will be visible to the customer'),
                            ]),
                        ]),
                ])
                ->submitAction(Forms\Components\Actions\Action::make('submit')
                    ->label('Create Appointment')
                    ->submit('create')),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('company_id');
        
        // Handle recurring appointments
        if ($data['is_recurring'] ?? false) {
            $this->createRecurringAppointments($data);
        }

        return $data;
    }

    protected function createRecurringAppointments(array $data): void
    {
        $count = $data['recurrence_count'] ?? 4;
        $pattern = $data['recurrence_pattern'] ?? 'weekly';
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);

        // Create the recurring appointments
        for ($i = 1; $i < $count; $i++) {
            $newStartsAt = match($pattern) {
                'daily' => $startsAt->copy()->addDays($i),
                'weekly' => $startsAt->copy()->addWeeks($i),
                'biweekly' => $startsAt->copy()->addWeeks($i * 2),
                'monthly' => $startsAt->copy()->addMonths($i),
                default => $startsAt->copy()->addWeeks($i),
            };

            $newEndsAt = match($pattern) {
                'daily' => $endsAt->copy()->addDays($i),
                'weekly' => $endsAt->copy()->addWeeks($i),
                'biweekly' => $endsAt->copy()->addWeeks($i * 2),
                'monthly' => $endsAt->copy()->addMonths($i),
                default => $endsAt->copy()->addWeeks($i),
            };

            $recurringData = $data;
            $recurringData['starts_at'] = $newStartsAt;
            $recurringData['ends_at'] = $newEndsAt;
            unset($recurringData['is_recurring']);
            unset($recurringData['recurrence_pattern']);
            unset($recurringData['recurrence_count']);

            \App\Models\Appointment::create($recurringData);
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        $notification = Notification::make()
            ->success()
            ->title('Appointment created')
            ->body('The appointment has been successfully scheduled.');

        if ($this->data['is_recurring'] ?? false) {
            $notification->body(
                sprintf(
                    '%d recurring appointments have been created.',
                    $this->data['recurrence_count'] ?? 1
                )
            );
        }

        return $notification->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}