<?php

namespace App\Filament\Resources\AdminUpdateResource\Pages;

use App\Filament\Resources\AdminUpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminUpdates extends ListRecords
{
    protected static string $resource = AdminUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Neues Update erstellen'),
        ];
    }

    public function getTitle(): string
    {
        return '📋 Admin Updates Portal';
    }

    public function getSubHeading(): string
    {
        return 'Hier werden alle System-Updates und Änderungen gespeichert und verwaltet';
    }
}
