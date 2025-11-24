<?php

namespace App\Filament\Resources\UserInvitationResource\Pages;

use App\Filament\Resources\UserInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserInvitations extends ListRecords
{
    protected static string $resource = UserInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
