<?php

namespace App\Filament\Resources\WorkingHourResource\Pages;

use App\Filament\Resources\WorkingHourResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Fieldset;
use Filament\Support\Enums\FontWeight;

class ViewWorkingHour extends ViewRecord
{
    protected static string $resource = WorkingHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten')
                ->icon('heroicon-m-pencil-square'),
            Actions\DeleteAction::make()
                ->label('Löschen')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Split::make([
                    Section::make('Arbeitszeit Details')
                        ->description('Grundlegende Informationen zur Arbeitszeit')
                        ->icon('heroicon-o-clock')
                        ->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('staff.name')
                                    ->label('Mitarbeiter')
                                    ->icon('heroicon-m-user')
                                    ->weight(FontWeight::Bold)
                                    ,

                                TextEntry::make('day_name')
                                    ->label('Wochentag')
                                    ->icon('heroicon-m-calendar')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('time_range')
                                    ->label('Arbeitszeit')
                                    ->icon('heroicon-m-clock')
                                    ->badge()
                                    ->color('success'),

                                IconEntry::make('is_active')
                                    ->label('Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ]),

                            Fieldset::make('Zeiten')
                                ->schema([
                                    TextEntry::make('start')
                                        ->label('Beginn')
                                        ->icon('heroicon-m-arrow-right-circle')
                                        ->dateTime('H:i'),

                                    TextEntry::make('end')
                                        ->label('Ende')
                                        ->icon('heroicon-m-arrow-left-circle')
                                        ->dateTime('H:i'),

                                    TextEntry::make('break_start')
                                        ->label('Pause von')
                                        ->icon('heroicon-m-pause')
                                        ->dateTime('H:i')
                                        ->placeholder('Keine Pause'),

                                    TextEntry::make('break_end')
                                        ->label('Pause bis')
                                        ->icon('heroicon-m-play')
                                        ->dateTime('H:i')
                                        ->placeholder('Keine Pause'),
                                ])
                                ->columns(2),

                            Fieldset::make('Zusätzliche Informationen')
                                ->schema([
                                    TextEntry::make('title')
                                        ->label('Titel')
                                        ->placeholder('Kein Titel')
                                        ,

                                    TextEntry::make('description')
                                        ->label('Beschreibung')
                                        ->placeholder('Keine Beschreibung')
                                        ->columnSpanFull()
                                        ->markdown(),

                                    TextEntry::make('timezone')
                                        ->label('Zeitzone')
                                        ->icon('heroicon-m-globe-alt')
                                        ->badge()
                                        ->color('gray'),

                                    IconEntry::make('is_recurring')
                                        ->label('Wiederkehrend')
                                        ->boolean()
                                        ->trueIcon('heroicon-o-arrow-path')
                                        ->falseIcon('heroicon-o-calendar-days'),

                                    TextEntry::make('valid_from')
                                        ->label('Gültig von')
                                        ->date('d.m.Y')
                                        ->placeholder('Unbegrenzt'),

                                    TextEntry::make('valid_until')
                                        ->label('Gültig bis')
                                        ->date('d.m.Y')
                                        ->placeholder('Unbegrenzt'),
                                ])
                                ->columns(2),
                        ]),

                    Section::make('Zuordnungen')
                        ->description('Unternehmens- und Filialdaten')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            TextEntry::make('company.name')
                                ->label('Unternehmen')
                                ->icon('heroicon-m-building-office-2')
                                ->weight(FontWeight::Bold)
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('branch.name')
                                ->label('Filiale')
                                ->icon('heroicon-m-building-storefront')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('staff.email')
                                ->label('Mitarbeiter E-Mail')
                                ->icon('heroicon-m-envelope')
                                
                                ->url(fn ($state) => "mailto:$state"),

                            TextEntry::make('staff.phone')
                                ->label('Mitarbeiter Telefon')
                                ->icon('heroicon-m-phone')
                                
                                ->url(fn ($state) => "tel:$state"),

                            TextEntry::make('staff.calcom_user_id')
                                ->label('Cal.com User ID')
                                ->icon('heroicon-m-calendar-days')
                                ->placeholder('Nicht verknüpft')
                                ,
                        ])
                        ->grow(false),
                ])
                    ->from('md')
                    ->columnSpanFull(),

                Section::make('Cal.com Integration')
                    ->description('Synchronisationsstatus mit Cal.com')
                    ->icon('heroicon-o-calendar')
                    ->collapsed()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('calcom_availability_id')
                                ->label('Availability ID')
                                ->placeholder('Nicht synchronisiert')
                                ,

                            TextEntry::make('calcom_schedule_id')
                                ->label('Schedule ID')
                                ->placeholder('Nicht synchronisiert')
                                ,

                            TextEntry::make('external_sync_at')
                                ->label('Letzte Synchronisation')
                                ->dateTime('d.m.Y H:i:s')
                                ->placeholder('Nie synchronisiert')
                                ->badge()
                                ->color(fn ($state) => $state ? 'success' : 'warning'),
                        ]),
                    ]),

                Section::make('System Information')
                    ->description('Erstellungs- und Änderungsdaten')
                    ->icon('heroicon-o-information-circle')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')
                                ->label('Erstellt am')
                                ->dateTime('d.m.Y H:i:s')
                                ->icon('heroicon-m-calendar'),

                            TextEntry::make('updated_at')
                                ->label('Zuletzt geändert')
                                ->dateTime('d.m.Y H:i:s')
                                ->icon('heroicon-m-pencil'),
                        ]),
                    ]),
            ]);
    }
}