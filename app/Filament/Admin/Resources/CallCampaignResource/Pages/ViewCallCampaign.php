<?php

namespace App\Filament\Admin\Resources\CallCampaignResource\Pages;

use App\Filament\Admin\Resources\CallCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\IconEntry;

class ViewCallCampaign extends ViewRecord
{
    protected static string $resource = CallCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => in_array($this->getRecord()->status, ['draft', 'paused'])),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Kampagnen Übersicht')
                    ->schema([
                        Split::make([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Kampagnen Name')
                                        ->size('lg')
                                        ->weight('bold'),
                                        
                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->color(fn (string $state): string => match($state) {
                                            'draft' => 'gray',
                                            'scheduled' => 'warning',
                                            'running' => 'primary',
                                            'paused' => 'info',
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            default => 'gray',
                                        }),
                                ]),
                                
                            Grid::make(3)
                                ->schema([
                                    TextEntry::make('total_targets')
                                        ->label('Gesamt Ziele')
                                        ->numeric(),
                                        
                                    TextEntry::make('calls_completed')
                                        ->label('Erfolgreich')
                                        ->numeric()
                                        ->color('success'),
                                        
                                    TextEntry::make('calls_failed')
                                        ->label('Fehlgeschlagen')
                                        ->numeric()
                                        ->color('danger'),
                                ]),
                        ]),
                        
                        TextEntry::make('description')
                            ->label('Beschreibung')
                            ->columnSpanFull(),
                    ]),
                    
                Section::make('Fortschritt')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('completion_percentage')
                                    ->label('Fortschritt')
                                    ->formatStateUsing(fn ($state) => $state . '%')
                                    ->size('lg')
                                    ->weight('bold'),
                                    
                                TextEntry::make('success_rate')
                                    ->label('Erfolgsrate')
                                    ->formatStateUsing(fn ($state) => $state . '%')
                                    ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 40 ? 'warning' : 'danger')),
                                    
                                TextEntry::make('started_at')
                                    ->label('Gestartet')
                                    ->dateTime('d.m.Y H:i'),
                                    
                                TextEntry::make('completed_at')
                                    ->label('Abgeschlossen')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch laufend'),
                            ]),
                    ]),
                    
                Section::make('Konfiguration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('target_type')
                                    ->label('Zielgruppen Typ')
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'leads' => 'Sales Leads',
                                        'appointments' => 'Terminbestätigungen',
                                        'follow_up' => 'Nachfass-Anrufe',
                                        'survey' => 'Umfragen',
                                        'custom_list' => 'Eigene Liste',
                                        default => ucfirst($state)
                                    }),
                                    
                                TextEntry::make('agent.name')
                                    ->label('AI Agent'),
                                    
                                TextEntry::make('schedule_type')
                                    ->label('Zeitplan')
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'immediate' => 'Sofort',
                                        'scheduled' => 'Geplant',
                                        'recurring' => 'Wiederkehrend',
                                        default => ucfirst($state)
                                    }),
                                    
                                TextEntry::make('scheduled_at')
                                    ->label('Geplante Zeit')
                                    ->dateTime('d.m.Y H:i')
                                    ->visible(fn ($record) => $record->schedule_type !== 'immediate'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            CallCampaignResource\Widgets\CampaignCallsChart::class,
        ];
    }
}