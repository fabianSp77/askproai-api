<?php

namespace App\Filament\Resources\ServiceFeeTemplateResource\Pages;

use App\Filament\Resources\ServiceFeeTemplateResource;
use App\Models\ServiceFeeTemplate;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListServiceFeeTemplates extends ListRecords
{
    protected static string $resource = ServiceFeeTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->badge(ServiceFeeTemplate::where('is_active', true)->count()),

            'setup' => Tab::make('Einrichtung')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', ServiceFeeTemplate::CATEGORY_SETUP))
                ->badge(ServiceFeeTemplate::where('category', ServiceFeeTemplate::CATEGORY_SETUP)->where('is_active', true)->count())
                ->badgeColor('success'),

            'change' => Tab::make('Änderungen')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', ServiceFeeTemplate::CATEGORY_CHANGE))
                ->badge(ServiceFeeTemplate::where('category', ServiceFeeTemplate::CATEGORY_CHANGE)->where('is_active', true)->count())
                ->badgeColor('warning'),

            'capacity' => Tab::make('Kapazität')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', ServiceFeeTemplate::CATEGORY_CAPACITY))
                ->badge(ServiceFeeTemplate::where('category', ServiceFeeTemplate::CATEGORY_CAPACITY)->where('is_active', true)->count())
                ->badgeColor('primary'),

            'support' => Tab::make('Support')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', ServiceFeeTemplate::CATEGORY_SUPPORT))
                ->badge(ServiceFeeTemplate::where('category', ServiceFeeTemplate::CATEGORY_SUPPORT)->where('is_active', true)->count())
                ->badgeColor('info'),

            'integration' => Tab::make('Integrationen')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', ServiceFeeTemplate::CATEGORY_INTEGRATION))
                ->badge(ServiceFeeTemplate::where('category', ServiceFeeTemplate::CATEGORY_INTEGRATION)->where('is_active', true)->count())
                ->badgeColor('gray'),
        ];
    }
}
