<?php

namespace App\Filament\Admin\Resources\DummyCompanyResource\Pages;

use App\Filament\Admin\Resources\DummyCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;

class EditDummyCompany extends EditRecord
{
    protected static string $resource = DummyCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('test')->label('TEST DUMMY FIELD'),
        ];
    }
}
