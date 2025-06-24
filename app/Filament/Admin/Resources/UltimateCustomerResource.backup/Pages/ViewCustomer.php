<?php

namespace App\Filament\Admin\Resources\UltimateCustomerResource\Pages;

use App\Filament\Admin\Resources\UltimateCustomerResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = UltimateCustomerResource::class;

    protected static string $view = 'filament.admin.pages.ultra-customer-view';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('book_appointment')
                ->label('Book Appointment')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->size('lg')
                ->url(fn () => route('filament.admin.resources.ultimate-appointments.create', [
                    'customer_id' => $this->record->id
                ])),

            Actions\Action::make('send_message')
                ->label('Send Message')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('info')
                ->modalHeading('Send Message to Customer')
                ->modalDescription('Send an SMS or WhatsApp message to this customer.')
                ->form([
                    \Filament\Forms\Components\Select::make('channel')
                        ->label('Channel')
                        ->options([
                            'sms' => 'SMS',
                            'whatsapp' => 'WhatsApp',
                            'email' => 'Email',
                        ])
                        ->default('sms')
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('message')
                        ->label('Message')
                        ->required()
                        ->rows(4)
                        ->placeholder('Type your message here...'),
                ])
                ->action(function (array $data) {
                    // Send message logic
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Message sent')
                        ->body("Message sent via {$data['channel']} to {$this->record->name}")
                        ->send();
                }),

            Actions\Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading('Export Customer Data')
                ->modalDescription('Export this customer\'s data for GDPR compliance or backup.')
                ->modalSubmitActionLabel('Export')
                ->action(function () {
                    // Export logic would go here
                    return response()->streamDownload(
                        function () {
                            echo json_encode($this->record->toArray(), JSON_PRETTY_PRINT);
                        },
                        "customer-{$this->record->id}-export.json"
                    );
                }),

            Actions\EditAction::make()
                ->size('lg'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Customer Overview')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Full Name')
                                    ->size('lg')
                                    ->weight(FontWeight::Bold),
                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->default('No email'),
                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),
                            ]),

                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('customer_type')
                                    ->label('Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'vip' => 'warning',
                                        'premium' => 'primary',
                                        'business' => 'info',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'blocked' => 'danger',
                                        default => 'gray',
                                    }),
                                Infolists\Components\IconEntry::make('is_vip')
                                    ->label('VIP Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-star')
                                    ->falseIcon('heroicon-o-star')
                                    ->trueColor('warning')
                                    ->falseColor('gray'),
                            ]),

                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Customer Since')
                                    ->date()
                                    ->icon('heroicon-o-calendar'),
                                Infolists\Components\TextEntry::make('preferred_language')
                                    ->label('Language')
                                    ->badge(),
                                Infolists\Components\IconEntry::make('marketing_consent')
                                    ->label('Marketing Consent')
                                    ->boolean(),
                            ]),
                        ]),
                    ]),

                Infolists\Components\Section::make('Customer Analytics')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Infolists\Components\ViewEntry::make('customer_analytics')
                            ->label(false)
                            ->view('filament.admin.components.customer-analytics-dashboard'),
                    ]),

                Infolists\Components\Section::make('Appointment History')
                    ->icon('heroicon-o-calendar')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\ViewEntry::make('appointment_history')
                            ->label(false)
                            ->view('filament.admin.components.customer-appointment-list'),
                    ]),

                Infolists\Components\Section::make('Call History')
                    ->icon('heroicon-o-phone')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Infolists\Components\ViewEntry::make('call_history')
                            ->label(false)
                            ->view('filament.admin.components.customer-call-list'),
                    ]),

                Infolists\Components\Section::make('Personal Information')
                    ->icon('heroicon-o-identification')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('birthday')
                                ->label('Birthday')
                                ->date()
                                ->icon('heroicon-o-cake')
                                ->default('Not provided'),
                            Infolists\Components\TextEntry::make('age')
                                ->label('Age')
                                ->getStateUsing(fn () => $this->record->birthday 
                                    ? $this->record->birthday->age . ' years'
                                    : 'Unknown'),
                        ]),

                        Infolists\Components\Grid::make(1)->schema([
                            Infolists\Components\TextEntry::make('full_address')
                                ->label('Address')
                                ->icon('heroicon-o-map-pin')
                                ->getStateUsing(function () {
                                    $parts = array_filter([
                                        $this->record->address_line_1,
                                        $this->record->address_line_2,
                                        $this->record->city,
                                        $this->record->postal_code,
                                        $this->record->state,
                                        $this->record->country,
                                    ]);
                                    return $parts ? implode(', ', $parts) : 'No address provided';
                                }),
                        ]),
                    ]),

                Infolists\Components\Section::make('Notes & Tags')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Internal Notes')
                            ->prose()
                            ->columnSpanFull()
                            ->default('No notes'),
                        
                        Infolists\Components\TextEntry::make('tags')
                            ->label('Tags')
                            ->badge()
                            ->separator(',')
                            ->columnSpanFull()
                            ->default([]),

                        Infolists\Components\KeyValueEntry::make('custom_fields')
                            ->label('Custom Fields')
                            ->columnSpanFull()
                            ->default([]),
                    ]),

                Infolists\Components\Section::make('Activity Summary')
                    ->icon('heroicon-o-chart-pie')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('appointment_count')
                                ->label('Total Appointments')
                                ->numeric()
                                ->size('lg')
                                ->weight(FontWeight::Bold)
                                ->color('primary'),
                            Infolists\Components\TextEntry::make('completed_appointments')
                                ->label('Completed')
                                ->getStateUsing(fn () => $this->record->appointments()
                                    ->where('status', 'completed')->count())
                                ->numeric()
                                ->color('success'),
                            Infolists\Components\TextEntry::make('no_show_count')
                                ->label('No Shows')
                                ->numeric()
                                ->color('danger'),
                            Infolists\Components\TextEntry::make('cancellation_count')
                                ->label('Cancellations')
                                ->getStateUsing(fn () => $this->record->appointments()
                                    ->where('status', 'cancelled')->count())
                                ->numeric()
                                ->color('warning'),
                        ]),

                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('total_spent')
                                ->label('Total Revenue')
                                ->money('EUR')
                                ->size('lg')
                                ->weight(FontWeight::Bold)
                                ->color('success'),
                            Infolists\Components\TextEntry::make('average_spent')
                                ->label('Average per Visit')
                                ->getStateUsing(fn () => $this->record->appointment_count > 0
                                    ? $this->record->total_spent / $this->record->appointment_count
                                    : 0)
                                ->money('EUR'),
                            Infolists\Components\TextEntry::make('lifetime_value')
                                ->label('Customer LTV')
                                ->getStateUsing(fn () => $this->record->total_spent * 1.5) // Simple LTV calculation
                                ->money('EUR')
                                ->color('primary'),
                        ]),
                    ]),
            ]);
    }
}