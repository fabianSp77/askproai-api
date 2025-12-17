<?php

namespace App\Filament\Resources\UserInvitationResource\Pages;

use App\Filament\Resources\UserInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserInvitation extends EditRecord
{
    protected static string $resource = UserInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
