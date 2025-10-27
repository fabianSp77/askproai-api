<?php

namespace App\Filament\Resources\RetellCallSessionResource\Pages;

use App\Filament\Resources\RetellCallSessionResource;
use App\Services\Retell\CallTrackingService;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ViewRetellCallSession extends ViewRecord
{
    protected static string $resource = RetellCallSessionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Call Overview')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('call_id')
                                    ->label('Call ID')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('call_status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'in_progress' => 'warning',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('duration_seconds')
                                    ->label('Duration')
                                    ->getStateUsing(fn ($record) => $record->getDurationSeconds() ? $record->getDurationSeconds() . 's' : 'In progress'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Customer & Context')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Customer')
                                    ->default('Unknown'),
                                Infolists\Components\TextEntry::make('company.name')
                                    ->label('Company')
                                    ->default('-'),
                                Infolists\Components\TextEntry::make('agent_id')
                                    ->label('Agent ID')
                                    ->copyable(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Performance Metrics')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('function_call_count')
                                    ->label('Total Functions'),
                                Infolists\Components\TextEntry::make('error_count')
                                    ->label('Errors')
                                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                                Infolists\Components\TextEntry::make('avg_response_time_ms')
                                    ->label('Avg Response')
                                    ->suffix(' ms'),
                                Infolists\Components\TextEntry::make('max_response_time_ms')
                                    ->label('Max Response')
                                    ->suffix(' ms'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Timeline & Function Calls')
                    ->schema([
                        Infolists\Components\ViewEntry::make('timeline')
                            ->view('filament.resources.retell-call-session.timeline')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions::Action::make('refresh')
            //     ->icon('heroicon-o-arrow-path')
            //     ->action(fn () => $this->refreshFormData()),
        ];
    }
}
