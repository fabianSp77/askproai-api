<?php

namespace App\Filament\Admin\Resources\RetellAgentResource\Pages;

use App\Filament\Admin\Resources\RetellAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;

class ViewRetellAgent extends ViewRecord
{
    protected static string $resource = RetellAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('test')
                ->label('Test Agent')
                ->icon('heroicon-o-play')
                ->color('success')
                ->action(fn () => redirect()->route('filament.admin.pages.ai-call-center', [
                    'test_agent_id' => $this->record->retell_agent_id,
                ])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Agent Details')
                    ->tabs([
                        Tabs\Tab::make('Overview')
                            ->schema([
                                Section::make('Basic Information')
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Agent Name')
                                            ->size('lg')
                                            ->weight('bold'),
                                        TextEntry::make('retell_agent_id')
                                            ->label('Retell Agent ID')
                                            ->copyable(),
                                        TextEntry::make('type')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => RetellAgent::getTypes()[$state] ?? $state),
                                        TextEntry::make('language')
                                            ->formatStateUsing(fn ($state) => RetellAgent::getSupportedLanguages()[$state] ?? $state),
                                        TextEntry::make('description')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('Status & Configuration')
                                    ->schema([
                                        IconEntry::make('is_active')
                                            ->label('Active')
                                            ->boolean(),
                                        IconEntry::make('is_default')
                                            ->label('Default Agent')
                                            ->boolean(),
                                        IconEntry::make('is_test_agent')
                                            ->label('Test Agent')
                                            ->boolean(),
                                        TextEntry::make('priority')
                                            ->numeric(),
                                    ])
                                    ->columns(4),
                            ]),

                        Tabs\Tab::make('Performance')
                            ->schema([
                                Section::make('Performance Metrics')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextEntry::make('total_calls')
                                                    ->label('Total Calls')
                                                    ->numeric()
                                                    ->size('lg'),
                                                TextEntry::make('successful_calls')
                                                    ->label('Successful Calls')
                                                    ->numeric()
                                                    ->size('lg'),
                                                TextEntry::make('success_rate')
                                                    ->label('Success Rate')
                                                    ->formatStateUsing(fn ($record) => $record->success_rate . '%')
                                                    ->color(fn ($record) => match(true) {
                                                        $record->success_rate >= 80 => 'success',
                                                        $record->success_rate >= 60 => 'warning',
                                                        default => 'danger',
                                                    })
                                                    ->size('lg'),
                                                TextEntry::make('average_duration')
                                                    ->label('Average Duration')
                                                    ->formatStateUsing(fn ($state) => gmdate('i:s', $state))
                                                    ->size('lg'),
                                            ]),
                                        TextEntry::make('satisfaction_score')
                                            ->label('Customer Satisfaction Score')
                                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '/5' : 'N/A')
                                            ->color(fn ($state) => match(true) {
                                                $state >= 4.5 => 'success',
                                                $state >= 3.5 => 'warning',
                                                $state !== null => 'danger',
                                                default => 'gray',
                                            }),
                                    ]),
                            ]),

                        Tabs\Tab::make('Capabilities')
                            ->schema([
                                Section::make('Agent Capabilities')
                                    ->schema([
                                        TextEntry::make('capabilities')
                                            ->label('Enabled Capabilities')
                                            ->listWithLineBreaks()
                                            ->bulleted()
                                            ->formatStateUsing(fn ($state) => array_map(
                                                fn($cap) => ucfirst(str_replace('_', ' ', $cap)),
                                                $state ?? []
                                            )),
                                    ]),
                            ]),

                        Tabs\Tab::make('Configuration')
                            ->schema([
                                Section::make('Voice Settings')
                                    ->schema([
                                        KeyValueEntry::make('voice_settings')
                                            ->label('Voice Configuration'),
                                    ])
                                    ->collapsed(),

                                Section::make('Prompt Settings')
                                    ->schema([
                                        KeyValueEntry::make('prompt_settings')
                                            ->label('Custom Prompts'),
                                    ])
                                    ->collapsed(),

                                Section::make('Integration Settings')
                                    ->schema([
                                        KeyValueEntry::make('integration_settings')
                                            ->label('Integration Configuration'),
                                    ])
                                    ->collapsed(),
                            ]),
                    ]),
            ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            RetellAgentResource\Widgets\AgentCallHistory::class,
        ];
    }
}