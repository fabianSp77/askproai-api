<?php

namespace App\Filament\Admin\Resources\UltimateCustomerResource\Pages;

use App\Filament\Admin\Resources\UltimateCustomerResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = UltimateCustomerResource::class;

    protected static ?string $title = 'New Customer';

    protected static string $view = 'filament.admin.pages.ultra-customer-create';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Customer Information')
                    ->description('Basic customer details')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Full Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('John Doe')
                                ->autocomplete('name')
                                ->autofocus(),

                            Forms\Components\TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->maxLength(255)
                                ->placeholder('john@example.com')
                                ->autocomplete('email')
                                ->unique(ignoreRecord: true)
                                ->suffixIcon('heroicon-m-envelope'),

                            Forms\Components\TextInput::make('phone')
                                ->label('Phone Number')
                                ->tel()
                                ->required()
                                ->maxLength(255)
                                ->placeholder('+49 123 456789')
                                ->autocomplete('tel')
                                ->suffixIcon('heroicon-m-phone')
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    // Check for existing customer with same phone
                                    if ($state) {
                                        $existing = \App\Models\Customer::where('phone', $state)
                                            ->where('company_id', session('company_id'))
                                            ->first();
                                        
                                        if ($existing) {
                                            Notification::make()
                                                ->warning()
                                                ->title('Duplicate Phone Number')
                                                ->body("A customer with this phone number already exists: {$existing->name}")
                                                ->persistent()
                                                ->send();
                                        }
                                    }
                                }),

                            Forms\Components\DatePicker::make('birthday')
                                ->label('Birthday')
                                ->displayFormat('d/m/Y')
                                ->maxDate(now())
                                ->native(false),
                        ]),
                    ]),

                Section::make('Customer Type & Status')
                    ->description('Classification and preferences')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Grid::make(3)->schema([
                            Forms\Components\Select::make('customer_type')
                                ->label('Customer Type')
                                ->options([
                                    'private' => 'Private',
                                    'business' => 'Business',
                                    'vip' => 'VIP',
                                    'premium' => 'Premium',
                                ])
                                ->default('private')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if (in_array($state, ['vip', 'premium'])) {
                                        $set('is_vip', true);
                                    }
                                }),

                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'blocked' => 'Blocked',
                                ])
                                ->default('active')
                                ->required(),

                            Forms\Components\Toggle::make('is_vip')
                                ->label('VIP Customer')
                                ->helperText('VIP customers get priority treatment')
                                ->reactive(),
                        ]),

                        Forms\Components\Toggle::make('marketing_consent')
                            ->label('Marketing Consent')
                            ->helperText('Customer agrees to receive marketing communications')
                            ->default(false),

                        Forms\Components\Select::make('preferred_language')
                            ->label('Preferred Language')
                            ->options([
                                'de' => 'German',
                                'en' => 'English',
                                'fr' => 'French',
                                'es' => 'Spanish',
                                'it' => 'Italian',
                                'tr' => 'Turkish',
                                'ar' => 'Arabic',
                            ])
                            ->default('de'),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Add tags...')
                            ->suggestions([
                                'regular',
                                'new',
                                'referral',
                                'high-value',
                                'problematic',
                                'family',
                                'corporate',
                            ]),
                    ]),

                Section::make('Address Information')
                    ->description('Customer location details')
                    ->icon('heroicon-o-map-pin')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('address_line_1')
                                ->label('Street Address')
                                ->maxLength(255)
                                ->autocomplete('street-address'),

                            Forms\Components\TextInput::make('address_line_2')
                                ->label('Address Line 2')
                                ->maxLength(255)
                                ->placeholder('Apartment, suite, etc.'),

                            Forms\Components\TextInput::make('city')
                                ->label('City')
                                ->maxLength(255)
                                ->autocomplete('address-level2'),

                            Forms\Components\TextInput::make('postal_code')
                                ->label('Postal Code')
                                ->maxLength(20)
                                ->autocomplete('postal-code'),

                            Forms\Components\Select::make('country')
                                ->label('Country')
                                ->options([
                                    'DE' => 'Germany',
                                    'AT' => 'Austria',
                                    'CH' => 'Switzerland',
                                    'FR' => 'France',
                                    'IT' => 'Italy',
                                    'ES' => 'Spain',
                                    'NL' => 'Netherlands',
                                    'BE' => 'Belgium',
                                ])
                                ->default('DE')
                                ->searchable(),

                            Forms\Components\TextInput::make('state')
                                ->label('State/Province')
                                ->maxLength(255),
                        ]),
                    ]),

                Section::make('Additional Information')
                    ->description('Notes and custom fields')
                    ->icon('heroicon-o-document-text')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(4)
                            ->placeholder('Add any relevant notes about this customer...')
                            ->helperText('These notes are only visible to staff'),

                        Forms\Components\KeyValue::make('custom_fields')
                            ->label('Custom Fields')
                            ->keyLabel('Field Name')
                            ->valueLabel('Value')
                            ->addButtonLabel('Add Custom Field')
                            ->helperText('Add any additional information specific to this customer'),

                        Forms\Components\Select::make('referral_source')
                            ->label('How did they find us?')
                            ->options([
                                'google' => 'Google Search',
                                'social_media' => 'Social Media',
                                'referral' => 'Friend/Family Referral',
                                'walk_in' => 'Walk-in',
                                'advertisement' => 'Advertisement',
                                'website' => 'Company Website',
                                'other' => 'Other',
                            ])
                            ->placeholder('Select source...'),
                    ]),

                Section::make('Initial Appointment')
                    ->description('Optionally create an appointment for this customer')
                    ->icon('heroicon-o-calendar')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('create_appointment')
                            ->label('Create Initial Appointment')
                            ->reactive(),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('appointment_date')
                                    ->label('Appointment Date & Time')
                                    ->minDate(now())
                                    ->required(fn (Forms\Get $get) => $get('create_appointment')),

                                Forms\Components\Select::make('appointment_service')
                                    ->label('Service')
                                    ->options(fn () => \App\Models\Service::where('company_id', session('company_id'))
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(fn (Forms\Get $get) => $get('create_appointment')),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('create_appointment')),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('company_id');
        $data['created_by'] = auth()->id();
        
        // Initialize counters
        $data['appointment_count'] = 0;
        $data['no_show_count'] = 0;
        $data['total_spent'] = 0;

        // Remove appointment data if present
        unset($data['create_appointment']);
        unset($data['appointment_date']);
        unset($data['appointment_service']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Create initial appointment if requested
        if ($this->data['create_appointment'] ?? false) {
            \App\Models\Appointment::create([
                'company_id' => session('company_id'),
                'customer_id' => $this->record->id,
                'service_id' => $this->data['appointment_service'],
                'starts_at' => $this->data['appointment_date'],
                'ends_at' => \Carbon\Carbon::parse($this->data['appointment_date'])->addMinutes(60),
                'status' => 'scheduled',
                'source' => 'admin',
                'notes' => 'Initial appointment created with customer profile',
            ]);

            Notification::make()
                ->success()
                ->title('Appointment Created')
                ->body('An initial appointment has been scheduled for this customer.')
                ->send();
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Customer created')
            ->body('The customer profile has been successfully created.')
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Check for duplicates one more time
        $existingByPhone = static::getModel()::query()
            ->where('company_id', session('company_id'))
            ->where('phone', $data['phone'])
            ->first();

        if ($existingByPhone) {
            Notification::make()
                ->danger()
                ->title('Duplicate Customer')
                ->body("A customer with this phone number already exists: {$existingByPhone->name}")
                ->persistent()
                ->send();

            $this->halt();
        }

        return parent::handleRecordCreation($data);
    }
}