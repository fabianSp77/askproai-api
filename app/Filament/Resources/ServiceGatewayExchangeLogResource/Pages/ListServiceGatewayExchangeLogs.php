<?php

namespace App\Filament\Resources\ServiceGatewayExchangeLogResource\Pages;

use App\Filament\Resources\ServiceGatewayExchangeLogResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListServiceGatewayExchangeLogs extends ListRecords
{
    protected static string $resource = ServiceGatewayExchangeLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->badge(fn () => $this->getAllTabBadgeCount())
                ->badgeColor('gray'),

            'outbound' => Tab::make('Ausgehend')
                ->modifyQueryUsing(fn (Builder $query) => $query->outbound())
                ->badge(fn () => $this->getModel()::outbound()->count())
                ->badgeColor('primary')
                ->icon('heroicon-o-arrow-up-right'),

            'inbound' => Tab::make('Eingehend')
                ->modifyQueryUsing(fn (Builder $query) => $query->inbound())
                ->badge(fn () => $this->getModel()::inbound()->count())
                ->badgeColor('success')
                ->icon('heroicon-o-arrow-down-left'),

            'failed' => Tab::make('Fehler')
                ->modifyQueryUsing(fn (Builder $query) => $query->failed())
                ->badge(fn () => $this->getModel()::failed()->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-exclamation-triangle'),

            'today' => Tab::make('Heute')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(fn () => $this->getModel()::whereDate('created_at', today())->count())
                ->badgeColor('info')
                ->icon('heroicon-o-calendar'),
        ];
    }

    private function getAllTabBadgeCount(): int
    {
        return $this->getModel()::count();
    }
}
