<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCall extends ViewRecord
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-m-pencil-square'),
            Actions\Action::make('back')
                ->label('Zurück zur Liste')
                ->url(CallResource::getUrl('index'))
                ->icon('heroicon-m-arrow-left')
                ->color('gray'),
        ];
    }
}