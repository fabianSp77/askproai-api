<?php

namespace App\Filament\Admin\Resources\EnhancedCallResource\Pages;

use App\Filament\Admin\Resources\EnhancedCallResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEnhancedCall extends CreateRecord
{
    protected static string $resource = EnhancedCallResource::class;
    
    // protected static string $view = 'filament.admin.resources.enhanced-call-resource.pages.create-enhanced-call';
}