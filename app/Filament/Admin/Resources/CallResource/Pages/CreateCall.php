<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCall extends CreateRecord
{
    protected static string $resource = CallResource::class;
    
    // protected static string $view = 'filament.admin.resources.call-resource.pages.create-call';
}
