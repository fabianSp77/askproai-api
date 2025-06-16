<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Filament\Admin\Resources\AppointmentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Carbon\Carbon;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make()
                ->label('Bearbeiten')
                ->icon('heroicon-o-pencil'),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Terminübersicht')
                    ->description('Detaillierte Informationen zum Termin')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('starts_at')
                                    ->label('Termin')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-calendar-days')
                                    ->iconColor('primary')
                                    ->weight('bold')
                                    ->size('lg'),
                                    
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
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
                            ]),
                            
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('duration_info')
                                    ->label('Dauer')
                                    ->getStateUsing(fn ($record) => $record->service?->duration ? $record->service->duration . ' Minuten' : '—')
                                    ->icon('heroicon-o-clock'),
                                    
                                Infolists\Components\TextEntry::make('price_info')
                                    ->label('Preis')
                                    ->getStateUsing(fn ($record) => $record->service?->price ? number_format($record->service->price / 100, 2, ',', '.') . ' €' : '—')
                                    ->icon('heroicon-o-currency-euro'),
                            ]),
                        ])->from('md'),
                    ]),
                    
                Infolists\Components\Grid::make(2)
                    ->schema([
                        Infolists\Components\Section::make('Kundendaten')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Name')
                                    ->default('—')
                                    ->icon('heroicon-o-user')
                                    ->copyable(),
                                    
                                Infolists\Components\TextEntry::make('customer.email')
                                    ->label('E-Mail')
                                    ->default('—')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                    
                                Infolists\Components\TextEntry::make('customer.phone')
                                    ->label('Telefon')
                                    ->default('—')
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),
                            ]),
                            
                        Infolists\Components\Section::make('Leistungsdetails')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Infolists\Components\TextEntry::make('service.name')
                                    ->label('Leistung')
                                    ->default('—')
                                    ->weight('bold'),
                                    
                                Infolists\Components\TextEntry::make('staff.name')
                                    ->label('Mitarbeiter')
                                    ->default('—')
                                    ->icon('heroicon-o-user-circle'),
                                    
                                Infolists\Components\TextEntry::make('branch.name')
                                    ->label('Filiale')
                                    ->default('—')
                                    ->icon('heroicon-o-building-storefront'),
                            ]),
                    ]),
                    
                Infolists\Components\Section::make('Notizen')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label(false)
                            ->default('Keine Notizen vorhanden')
                            ->prose(),
                    ])
                    ->collapsed(fn ($record) => empty($record->notes)),
                    
                Infolists\Components\Section::make('Metadaten')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('Termin-ID')
                                    ->copyable(),
                                    
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i'),
                                    
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Zuletzt geändert')
                                    ->dateTime('d.m.Y H:i'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}