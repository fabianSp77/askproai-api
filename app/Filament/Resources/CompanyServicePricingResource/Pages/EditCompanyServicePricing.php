<?php

namespace App\Filament\Resources\CompanyServicePricingResource\Pages;

use App\Filament\Resources\CompanyServicePricingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompanyServicePricing extends EditRecord
{
    protected static string $resource = CompanyServicePricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('duplicate')
                ->label('Duplizieren')
                ->icon('heroicon-o-document-duplicate')
                ->action(function () {
                    $new = $this->record->replicate();
                    $new->effective_from = now();
                    $new->effective_until = null;
                    $new->created_by = auth()->id();
                    $new->save();

                    \Filament\Notifications\Notification::make()
                        ->title('Preisvereinbarung dupliziert')
                        ->body('Neue Vereinbarung ab heute erstellt.')
                        ->success()
                        ->send();

                    return redirect($this->getResource()::getUrl('edit', ['record' => $new]));
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
