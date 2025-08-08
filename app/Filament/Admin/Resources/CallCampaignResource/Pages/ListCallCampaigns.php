<?php

namespace App\Filament\Admin\Resources\CallCampaignResource\Pages;

use App\Filament\Admin\Resources\CallCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCallCampaigns extends ListRecords
{
    protected static string $resource = CallCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Kampagne'),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->badge($this->getModel()::count()),
                
            'active' => Tab::make('Aktiv')
                ->modifyQueryUsing(fn (Builder $query) => $query->active())
                ->badge($this->getModel()::active()->count()),
                
            'scheduled' => Tab::make('Geplant')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'scheduled'))
                ->badge($this->getModel()::where('status', 'scheduled')->count()),
                
            'completed' => Tab::make('Abgeschlossen')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge($this->getModel()::where('status', 'completed')->count()),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            CallCampaignResource\Widgets\CallCampaignStats::class,
        ];
    }
}