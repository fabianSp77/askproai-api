<?php

namespace App\Filament\Admin\Resources\BalanceTopupResource\Pages;

use App\Filament\Admin\Resources\BalanceTopupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBalanceTopups extends ListRecords
{
    protected static string $resource = BalanceTopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Aufladung'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            BalanceTopupResource\Widgets\BalanceTopupStats::class,
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->icon('heroicon-o-rectangle-stack'),
            
            'pending' => Tab::make('Ausstehend')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'processing' => Tab::make('In Bearbeitung')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processing'))
                ->badge(fn () => $this->getModel()::where('status', 'processing')->count())
                ->badgeColor('info'),
            
            'succeeded' => Tab::make('Erfolgreich')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'succeeded'))
                ->badge(fn () => $this->getModel()::where('status', 'succeeded')->count())
                ->badgeColor('success'),
            
            'failed' => Tab::make('Fehlgeschlagen')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(fn () => $this->getModel()::where('status', 'failed')->count())
                ->badgeColor('danger'),
        ];
    }
}