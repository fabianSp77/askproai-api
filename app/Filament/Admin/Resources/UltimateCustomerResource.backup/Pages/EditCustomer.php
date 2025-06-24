<?php

namespace App\Filament\Admin\Resources\UltimateCustomerResource\Pages;

use App\Filament\Admin\Resources\UltimateCustomerResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Actions;
use Filament\Notifications\Notification;

class EditCustomer extends EditRecord
{
    protected static string $resource = UltimateCustomerResource::class;

    protected static string $view = 'filament.admin.pages.ultra-customer-edit';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('merge')
                ->label('Merge Duplicate')
                ->icon('heroicon-o-arrows-pointing-in')
                ->color('warning')
                ->modalHeading('Merge with Another Customer')
                ->modalDescription('Merge this customer with another customer record. All appointments and history will be combined.')
                ->form([
                    Forms\Components\Select::make('target_customer_id')
                        ->label('Merge Into')
                        ->options(fn () => \App\Models\Customer::where('company_id', session('company_id'))
                            ->where('id', '!=', $this->record->id)
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->helperText('Select the customer to merge this record into'),
                ])
                ->action(function (array $data) {
                    $targetCustomer = \App\Models\Customer::find($data['target_customer_id']);
                    
                    // Merge appointments
                    $this->record->appointments()->update(['customer_id' => $targetCustomer->id]);
                    $this->record->calls()->update(['customer_id' => $targetCustomer->id]);
                    
                    // Update counts
                    $targetCustomer->appointment_count += $this->record->appointment_count;
                    $targetCustomer->no_show_count += $this->record->no_show_count;
                    $targetCustomer->total_spent += $this->record->total_spent;
                    $targetCustomer->save();
                    
                    // Delete current record
                    $this->record->delete();
                    
                    Notification::make()
                        ->success()
                        ->title('Customers merged')
                        ->body("Customer records have been successfully merged into {$targetCustomer->name}")
                        ->send();
                    
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $targetCustomer]));
                }),

            Actions\Action::make('block')
                ->label('Block Customer')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn () => $this->record->status !== 'blocked')
                ->requiresConfirmation()
                ->modalHeading('Block Customer')
                ->modalDescription('Are you sure you want to block this customer? They will not be able to book new appointments.')
                ->form([
                    Forms\Components\Textarea::make('block_reason')
                        ->label('Reason for Blocking')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'blocked',
                        'blocked_at' => now(),
                        'block_reason' => $data['block_reason'],
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Customer blocked')
                        ->send();
                }),

            Actions\Action::make('unblock')
                ->label('Unblock Customer')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'blocked')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'active',
                        'blocked_at' => null,
                        'block_reason' => null,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Customer unblocked')
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->hasRole('admin')),
        ];
    }

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
                                ->maxLength(255),

                            Forms\Components\TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->suffixIcon('heroicon-m-envelope'),

                            Forms\Components\TextInput::make('phone')
                                ->label('Phone Number')
                                ->tel()
                                ->required()
                                ->maxLength(255)
                                ->suffixIcon('heroicon-m-phone')
                                ->unique(ignoreRecord: true),

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
                                ->required()
                                ->disabled(fn () => $this->record->status === 'blocked'),

                            Forms\Components\Toggle::make('is_vip')
                                ->label('VIP Customer')
                                ->helperText('VIP customers get priority treatment'),
                        ]),

                        Forms\Components\Toggle::make('marketing_consent')
                            ->label('Marketing Consent')
                            ->helperText('Customer agrees to receive marketing communications'),

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
                            ]),

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

                Section::make('Statistics')
                    ->description('Customer activity metrics')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Grid::make(4)->schema([
                            Forms\Components\Placeholder::make('appointment_count')
                                ->label('Total Appointments')
                                ->content(fn () => $this->record->appointment_count ?? 0),

                            Forms\Components\Placeholder::make('no_show_count')
                                ->label('No Shows')
                                ->content(fn () => $this->record->no_show_count ?? 0),

                            Forms\Components\Placeholder::make('cancellation_rate')
                                ->label('Cancellation Rate')
                                ->content(function () {
                                    $total = $this->record->appointments()->count();
                                    $cancelled = $this->record->appointments()->where('status', 'cancelled')->count();
                                    return $total > 0 ? round(($cancelled / $total) * 100) . '%' : '0%';
                                }),

                            Forms\Components\Placeholder::make('total_spent')
                                ->label('Total Spent')
                                ->content(fn () => '€' . number_format($this->record->total_spent ?? 0, 2)),
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
                                ->maxLength(255),

                            Forms\Components\TextInput::make('address_line_2')
                                ->label('Address Line 2')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('city')
                                ->label('City')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('postal_code')
                                ->label('Postal Code')
                                ->maxLength(20),

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
                                ->searchable(),

                            Forms\Components\TextInput::make('state')
                                ->label('State/Province')
                                ->maxLength(255),
                        ]),
                    ]),

                Section::make('Additional Information')
                    ->description('Notes and metadata')
                    ->icon('heroicon-o-document-text')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(4)
                            ->helperText('These notes are only visible to staff'),

                        Forms\Components\KeyValue::make('custom_fields')
                            ->label('Custom Fields')
                            ->keyLabel('Field Name')
                            ->valueLabel('Value')
                            ->addButtonLabel('Add Custom Field'),

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
                            ]),
                    ]),

                Section::make('System Information')
                    ->description('Metadata and tracking')
                    ->icon('heroicon-o-cog')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Placeholder::make('created_at')
                                ->label('Customer Since')
                                ->content(fn () => $this->record->created_at->format('M j, Y')),

                            Forms\Components\Placeholder::make('last_appointment')
                                ->label('Last Appointment')
                                ->content(function () {
                                    $lastAppointment = $this->record->appointments()
                                        ->latest('starts_at')
                                        ->first();
                                    return $lastAppointment 
                                        ? $lastAppointment->starts_at->format('M j, Y') 
                                        : 'No appointments yet';
                                }),

                            Forms\Components\Placeholder::make('id')
                                ->label('Customer ID')
                                ->content(fn () => $this->record->id),

                            Forms\Components\Placeholder::make('updated_at')
                                ->label('Last Updated')
                                ->content(fn () => $this->record->updated_at->diffForHumans()),
                        ]),
                    ]),
            ]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Customer updated')
            ->body('The customer profile has been successfully updated.')
            ->send();
    }
}