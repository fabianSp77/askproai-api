<?php

namespace App\Filament\Resources\CallbackRequestResource\Pages;

use App\Filament\Resources\CallbackRequestResource;
use App\Models\CallbackRequest;
use App\Models\Staff;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;

class ViewCallbackRequest extends ViewRecord
{
    protected static string $resource = CallbackRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assign')
                ->label('Zuweisen')
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->visible(fn (CallbackRequest $record): bool => !$record->assigned_to)
                ->form([
                    Forms\Components\Select::make('staff_id')
                        ->label('Mitarbeiter')
                        ->options(Staff::pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->helperText('Wählen Sie den Mitarbeiter für die Bearbeitung'),
                ])
                ->action(function (CallbackRequest $record, array $data): void {
                    $staff = Staff::find($data['staff_id']);
                    if ($staff) {
                        $record->assign($staff);
                    }
                })
                ->successNotificationTitle('Erfolgreich zugewiesen')
                ->requiresConfirmation(),

            Actions\Action::make('markContacted')
                ->label('Als kontaktiert markieren')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn (CallbackRequest $record): bool =>
                    $record->status === CallbackRequest::STATUS_ASSIGNED
                )
                ->action(fn (CallbackRequest $record) => $record->markContacted())
                ->successNotificationTitle('Als kontaktiert markiert')
                ->requiresConfirmation(),

            Actions\Action::make('markCompleted')
                ->label('Als abgeschlossen markieren')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (CallbackRequest $record): bool =>
                    $record->status === CallbackRequest::STATUS_CONTACTED
                )
                ->form([
                    Forms\Components\Textarea::make('notes')
                        ->label('Abschlussnotizen')
                        ->rows(3)
                        ->helperText('Zusätzliche Informationen zum Abschluss'),
                ])
                ->action(function (CallbackRequest $record, array $data): void {
                    if (!empty($data['notes'])) {
                        $record->notes = ($record->notes ? $record->notes . "\n\n" : '') .
                            '**Abschluss:** ' . $data['notes'];
                    }
                    $record->markCompleted();
                })
                ->successNotificationTitle('Als abgeschlossen markiert')
                ->requiresConfirmation(),

            Actions\Action::make('escalate')
                ->label('Eskalieren')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->visible(fn (CallbackRequest $record): bool =>
                    $record->status !== CallbackRequest::STATUS_COMPLETED
                )
                ->form([
                    Forms\Components\Select::make('reason')
                        ->label('Eskalationsgrund')
                        ->options([
                            'no_response' => 'Keine Antwort',
                            'technical_issue' => 'Technisches Problem',
                            'customer_complaint' => 'Kundenbeschwerde',
                            'urgent_request' => 'Dringende Anfrage',
                            'complex_case' => 'Komplexer Fall',
                            'other' => 'Sonstiges',
                        ])
                        ->required()
                        ->native(false),
                    Forms\Components\Textarea::make('details')
                        ->label('Details')
                        ->rows(3)
                        ->helperText('Zusätzliche Informationen zur Eskalation'),
                ])
                ->action(function (CallbackRequest $record, array $data): void {
                    $reason = $data['reason'];
                    if (!empty($data['details'])) {
                        $reason .= ': ' . $data['details'];
                    }
                    $record->escalate($reason);
                })
                ->successNotificationTitle('Erfolgreich eskaliert')
                ->requiresConfirmation(),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
