<?php

namespace App\Filament\Admin\Actions;

use App\Models\Company;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class AssignToResellerAction
{
    public static function make(): Action
    {
        return Action::make('assign_to_reseller')
            ->label('Assign to Reseller')
            ->icon('heroicon-o-user-group')
            ->color('primary')
            ->form([
                Select::make('reseller_id')
                    ->label('Select Reseller')
                    ->placeholder('Choose a reseller...')
                    ->options(function () {
                        return Company::query()
                            ->where('company_type', 'reseller')
                            ->where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->required()
                    ->searchable()
                    ->helperText('This company will become a client of the selected reseller'),
            ])
            ->action(function (Company $record, array $data) {
                $reseller = Company::find($data['reseller_id']);
                
                if (!$reseller || $reseller->company_type !== 'reseller') {
                    Notification::make()
                        ->title('Invalid reseller selected')
                        ->danger()
                        ->send();
                    return;
                }

                // Update the company to be a client of the reseller
                $record->update([
                    'parent_company_id' => $reseller->id,
                    'company_type' => 'client',
                ]);

                Notification::make()
                    ->title('Company assigned successfully')
                    ->body("{$record->name} is now a client of {$reseller->name}")
                    ->success()
                    ->send();
            })
            ->visible(function (?Company $record) {
                // Only show for companies that are not already resellers or clients
                if (!$record) {
                    return false;
                }
                
                return $record->company_type !== 'reseller' && 
                       $record->parent_company_id === null &&
                       Company::where('company_type', 'reseller')->where('is_active', true)->exists();
            });
    }
}