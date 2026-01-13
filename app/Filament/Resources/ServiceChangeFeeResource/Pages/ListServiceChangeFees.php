<?php

namespace App\Filament\Resources\ServiceChangeFeeResource\Pages;

use App\Filament\Resources\ServiceChangeFeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ServiceChangeFee;

class ListServiceChangeFees extends ListRecords
{
    protected static string $resource = ServiceChangeFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle'),
            'pending' => Tab::make('Ausstehend')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ServiceChangeFee::STATUS_PENDING))
                ->badge(ServiceChangeFee::where('status', ServiceChangeFee::STATUS_PENDING)->count())
                ->badgeColor('warning'),
            'invoiced' => Tab::make('Abgerechnet')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ServiceChangeFee::STATUS_INVOICED)),
            'paid' => Tab::make('Bezahlt')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ServiceChangeFee::STATUS_PAID)),
        ];
    }
}
