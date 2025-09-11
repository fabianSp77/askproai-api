<?php

namespace App\Filament\Admin\Resources\IntegrationResource\Pages;

use App\Filament\Admin\Resources\IntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Concerns\InteractsWithInfolist;

class ViewIntegration extends ViewRecord
{
    use InteractsWithInfolist;

    protected static string $resource = IntegrationResource::class;

    // protected static string $view = 'filament.admin.resources.integration-resource.view';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}