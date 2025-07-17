<?php

namespace App\Filament\Admin\Resources\ErrorCatalogResource\Pages;

use App\Filament\Admin\Resources\ErrorCatalogResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Actions;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ManageErrorSolutions extends ManageRelatedRecords
{
    use InteractsWithRecord;
    
    protected static string $resource = ErrorCatalogResource::class;
    
    protected static string $relationship = 'solutions';
    
    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    
    public static function getNavigationLabel(): string
    {
        return 'Solutions';
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}