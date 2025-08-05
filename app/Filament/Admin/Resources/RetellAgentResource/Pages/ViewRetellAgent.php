<?php

namespace App\Filament\Admin\Resources\RetellAgentResource\Pages;

use App\Filament\Admin\Resources\RetellAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

class ViewRetellAgent extends ViewRecord
{
    protected static string $resource = RetellAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('sync')
                ->label('Sync from Retell')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->action(fn () => $this->record->syncFromRetell()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Agent Name')
                            ->size('lg')
                            ->weight('bold'),
                        TextEntry::make('agent_id')
                            ->label('Retell Agent ID')
                            ->copyable(),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        TextEntry::make('sync_status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'synced' => 'success',
                                'error' => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('last_synced_at')
                            ->label('Last Synced')
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
}