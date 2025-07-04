<?php

namespace App\Filament\Admin\Resources\PrepaidBalanceResource\Pages;

use App\Filament\Admin\Resources\PrepaidBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPrepaidBalances extends ListRecords
{
    protected static string $resource = PrepaidBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            PrepaidBalanceResource\Widgets\PrepaidBalanceStats::class,
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->badge($this->getModel()::count()),
            'low_balance' => Tab::make('Niedriges Guthaben')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('balance - reserved_balance < low_balance_threshold'))
                ->badge($this->getModel()::whereRaw('balance - reserved_balance < low_balance_threshold')->count())
                ->badgeColor('warning'),
            'no_balance' => Tab::make('Kein Guthaben')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('balance', '<=', 0))
                ->badge($this->getModel()::where('balance', '<=', 0)->count())
                ->badgeColor('danger'),
        ];
    }
}