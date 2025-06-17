<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Filament\Admin\Resources\AppointmentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;
    
    public function getTitle(): string
    {
        $appointment = $this->record;
        
        return sprintf(
            'Termin: %s - %s',
            $appointment->customer?->name ?? 'Unbekannt',
            $appointment->starts_at?->format('d.m.Y H:i') ?? 'Kein Datum'
        );
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten')
                ->icon('heroicon-o-pencil'),
                
            Actions\Action::make('sync_calcom')
                ->label('Mit Cal.com synchronisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->calcom_booking_id || $record->calcom_v2_booking_id)
                ->action(function ($record) {
                    // Sync single appointment
                    \Filament\Notifications\Notification::make()
                        ->title('Synchronisation gestartet')
                        ->body('Der Termin wird mit Cal.com synchronisiert.')
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('complete')
                ->label('Abschließen')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                ->action(fn ($record) => $record->update(['status' => 'completed'])),
                
            Actions\Action::make('cancel')
                ->label('Absagen')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                ->action(fn ($record) => $record->update(['status' => 'cancelled'])),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Header Section mit wichtigsten Infos
                Section::make()
                    ->schema([
                        Split::make([
                            Group::make([
                                TextEntry::make('formatted_datetime')
                                    ->label('Termin')
                                    ->state(function ($record) {
                                        return $record->starts_at->format('l, d. F Y') . ' um ' . $record->starts_at->format('H:i') . ' Uhr';
                                    })
                                    ->icon('heroicon-o-calendar-days')
                                    ->iconColor('primary')
                                    ->weight('bold')
                                    ->size('lg'),
                                    
                                TextEntry::make('duration_display')
                                    ->label('Dauer')
                                    ->state(function ($record) {
                                        if ($record->starts_at && $record->ends_at) {
                                            $duration = $record->starts_at->diffInMinutes($record->ends_at);
                                            return $duration . ' Minuten';
                                        }
                                        return $record->service?->duration ? $record->service->duration . ' Minuten' : '—';
                                    })
                                    ->icon('heroicon-o-clock')
                                    ->color('gray'),
                            ]),
                            
                            Group::make([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        'no_show' => 'gray',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => 'Ausstehend',
                                        'confirmed' => 'Bestätigt',
                                        'completed' => 'Abgeschlossen',
                                        'cancelled' => 'Abgesagt',
                                        'no_show' => 'Nicht erschienen',
                                        default => $state,
                                    }),
                                    
                                IconEntry::make('is_synced')
                                    ->label('Cal.com Sync')
                                    ->state(fn ($record) => $record->calcom_booking_id || $record->calcom_v2_booking_id)
                                    ->boolean()
                                    ->trueColor('success')
                                    ->falseColor('gray')
                                    ->trueIcon('heroicon-o-cloud-arrow-down')
                                    ->falseIcon('heroicon-o-cloud'),
                            ]),
                        ])->from('md'),
                    ]),
                    
                Grid::make(2)
                    ->schema([
                        Section::make('Kundendaten')
                            ->icon('heroicon-o-user')
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Name')
                                    ->default('—')
                                    ->icon('heroicon-o-user')
                                    ->copyable()
                                    ->url(fn ($record) => $record->customer ? 
                                        \App\Filament\Admin\Resources\CustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                                    
                                TextEntry::make('customer.email')
                                    ->label('E-Mail')
                                    ->default('—')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->url(fn ($state) => $state ? "mailto:$state" : null),
                                    
                                TextEntry::make('customer.phone')
                                    ->label('Telefon')
                                    ->default('—')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->url(fn ($state) => $state ? "tel:$state" : null),
                                    
                                TextEntry::make('customer.notes')
                                    ->label('Kundennotizen')
                                    ->default('—')
                                    ->icon('heroicon-o-document-text')
                                    ->columnSpanFull()
                                    ->limit(100),
                            ]),
                            
                        Section::make('Leistungsdetails')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                TextEntry::make('service.name')
                                    ->label('Leistung')
                                    ->default('—')
                                    ->weight('bold')
                                    ->icon('heroicon-o-sparkles'),
                                    
                                TextEntry::make('staff.name')
                                    ->label('Mitarbeiter')
                                    ->default('—')
                                    ->icon('heroicon-o-user-circle')
                                    ->url(fn ($record) => $record->staff ? 
                                        \App\Filament\Admin\Resources\StaffResource::getUrl('view', ['record' => $record->staff]) : null),
                                    
                                TextEntry::make('branch.name')
                                    ->label('Filiale')
                                    ->default('—')
                                    ->icon('heroicon-o-building-storefront'),
                                    
                                TextEntry::make('price')
                                    ->label('Preis')
                                    ->money('EUR', divideBy: 100)
                                    ->icon('heroicon-o-currency-euro')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                    ]),
                    
                // Timeline Section für Appointment-Historie
                Section::make('Termin-Timeline')
                    ->icon('heroicon-o-clock')
                    ->description('Chronologischer Verlauf aller Ereignisse')
                    ->schema([
                        ViewEntry::make('timeline')
                            ->view('filament.resources.appointment-resource.appointment-timeline')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                    
                // Cal.com Integration Details
                Section::make('Cal.com Integration')
                    ->icon('heroicon-o-calendar')
                    ->description('Details zur Kalendersynchronisation')
                    ->visible(fn ($record) => $record->calcom_booking_id || $record->calcom_v2_booking_id)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('calcom_booking_id')
                                    ->label('Cal.com Booking ID')
                                    ->default('—')
                                    ->copyable()
                                    ->badge()
                                    ->color('info'),
                                    
                                TextEntry::make('calcom_v2_booking_id')
                                    ->label('Cal.com V2 Booking ID')
                                    ->default('—')
                                    ->copyable()
                                    ->badge()
                                    ->color('info'),
                                    
                                TextEntry::make('external_id')
                                    ->label('External ID')
                                    ->default('—')
                                    ->copyable(),
                                    
                                TextEntry::make('calcom_event_type_id')
                                    ->label('Event Type ID')
                                    ->default('—'),
                                    
                                TextEntry::make('calcomEventType.name')
                                    ->label('Event Type')
                                    ->default('—')
                                    ->badge()
                                    ->color('primary'),
                                    
                                TextEntry::make('meta.calcom_sync.last_synced_at')
                                    ->label('Letzte Synchronisation')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->default('Noch nicht synchronisiert'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                // Verknüpfter Anruf
                Section::make('Verknüpfter Anruf')
                    ->icon('heroicon-o-phone')
                    ->description('Details zum zugehörigen Anruf')
                    ->visible(fn ($record) => $record->call_id)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('call.call_id')
                                    ->label('Anruf-ID')
                                    ->copyable()
                                    ->url(fn ($record) => $record->call ? 
                                        \App\Filament\Admin\Resources\CallResource::getUrl('view', ['record' => $record->call]) : null),
                                    
                                TextEntry::make('call.duration_formatted')
                                    ->label('Anrufdauer')
                                    ->state(fn ($record) => $record->call ? 
                                        gmdate('i:s', $record->call->duration_sec) : '—'),
                                    
                                TextEntry::make('call.analysis.sentiment')
                                    ->label('Stimmung')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'positive' => 'success',
                                        'negative' => 'danger',
                                        'neutral' => 'gray',
                                        default => 'gray',
                                    }),
                                    
                                TextEntry::make('call.created_at')
                                    ->label('Anruf am')
                                    ->dateTime('d.m.Y H:i'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('Notizen')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('notes')
                            ->label(false)
                            ->default('Keine Notizen vorhanden')
                            ->prose()
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(fn ($record) => empty($record->notes)),
                    
                // Reminder Status
                Section::make('Erinnerungen')
                    ->icon('heroicon-o-bell')
                    ->description('Status der versendeten Erinnerungen')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                IconEntry::make('reminder_24h_sent')
                                    ->label('24h Erinnerung')
                                    ->state(fn ($record) => !is_null($record->reminder_24h_sent_at))
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                                    
                                IconEntry::make('reminder_2h_sent')
                                    ->label('2h Erinnerung')
                                    ->state(fn ($record) => !is_null($record->reminder_2h_sent_at))
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                                    
                                IconEntry::make('reminder_30m_sent')
                                    ->label('30min Erinnerung')
                                    ->state(fn ($record) => !is_null($record->reminder_30m_sent_at))
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('Metadaten')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Termin-ID')
                                    ->copyable()
                                    ->badge()
                                    ->color('gray'),
                                    
                                TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->icon('heroicon-o-plus-circle'),
                                    
                                TextEntry::make('updated_at')
                                    ->label('Zuletzt geändert')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->icon('heroicon-o-pencil'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}