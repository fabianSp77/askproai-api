<?php

namespace App\Filament\Resources\ServiceChangeFeeResource\Pages;

use App\Filament\Resources\ServiceChangeFeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\ServiceChangeFee;

class ViewServiceChangeFee extends ViewRecord
{
    protected static string $resource = ServiceChangeFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->canBeEdited()),

            Actions\Action::make('charge')
                ->label('Abrechnen')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn ($record) => $record->canBeInvoiced())
                ->requiresConfirmation()
                ->action(function () {
                    $feeService = app(\App\Services\Billing\FeeService::class);
                    $transaction = $feeService->chargeServiceChangeFee($this->record, 'balance');

                    if ($transaction) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gebühr abgerechnet')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    }
                }),
        ];
    }
}
