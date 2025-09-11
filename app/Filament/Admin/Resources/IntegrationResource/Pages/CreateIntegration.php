<?php

namespace App\Filament\Admin\Resources\IntegrationResource\Pages;

use App\Filament\Admin\Resources\IntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegration extends CreateRecord
{
    protected static string $resource = IntegrationResource::class;
    
    // protected static string $view = 'filament.admin.resources.integration-resource.pages.create-integration';
}
