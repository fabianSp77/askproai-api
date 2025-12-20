<?php

namespace App\Filament\Resources\ServiceCaseResource\Pages;

use App\Filament\Resources\ServiceCaseResource;
use App\Models\ServiceCase;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewServiceCase extends ViewRecord
{
    protected static string $resource = ServiceCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('mark_resolved')
                ->label('Als gelöst markieren')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (ServiceCase $record) => $record->isOpen())
                ->action(function (ServiceCase $record) {
                    $record->update(['status' => ServiceCase::STATUS_RESOLVED]);
                    Notification::make()
                        ->title('Case als gelöst markiert')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('reopen')
                ->label('Wieder öffnen')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (ServiceCase $record) => $record->isClosed())
                ->action(function (ServiceCase $record) {
                    $record->update(['status' => ServiceCase::STATUS_OPEN]);
                    Notification::make()
                        ->title('Case wieder geöffnet')
                        ->warning()
                        ->send();
                }),
            Actions\Action::make('resend_output')
                ->label('Output erneut senden')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (ServiceCase $record) => $record->output_status === ServiceCase::OUTPUT_FAILED)
                ->action(function (ServiceCase $record) {
                    $record->update(['output_status' => ServiceCase::OUTPUT_PENDING]);
                    // Trigger resend via event or job
                    Notification::make()
                        ->title('Output wird erneut gesendet')
                        ->success()
                        ->send();
                }),
        ];
    }
}
