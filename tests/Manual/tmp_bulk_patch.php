->bulkActions([
    \Filament\Tables\Actions\BulkAction::make('refresh_selected')
        ->label('Ausgewählte aktualisieren')
        ->icon('heroicon-o-arrow-path')
        ->requiresConfirmation()
        ->action(function (\Illuminate\Support\Collection $records, \Filament\Tables\Actions\BulkAction $action) {
            $service = app(\App\Services\CallDataRefresher::class);
            $ok = $fail = 0;

            $records->each(function (\App\Models\Call $call) use ($service, &$ok, &$fail) {
                $service->refresh($call) ? $ok++ : $fail++;
            });

            $action->notify('success', "Aktualisiert: {$ok} ✔  /  Fehler: {$fail} ✖");
        }),
])
