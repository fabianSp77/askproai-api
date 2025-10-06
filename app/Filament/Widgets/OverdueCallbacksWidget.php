<?php

namespace App\Filament\Widgets;

use App\Models\CallbackRequest;
use App\Models\Staff;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

class OverdueCallbacksWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = '🚨 Überfällige Rückrufe';

    protected static ?string $pollingInterval = '30s';

    /**
     * Get the table configuration
     */
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (CallbackRequest $record): string =>
                        $record->service?->name ?? 'Keine Dienstleistung'
                    ),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Telefon')
                    ->copyable()
                    ->copyMessage('Telefonnummer kopiert')
                    ->icon('heroicon-m-phone')
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priorität')
                    ->colors([
                        'secondary' => CallbackRequest::PRIORITY_NORMAL,
                        'warning' => CallbackRequest::PRIORITY_HIGH,
                        'danger' => CallbackRequest::PRIORITY_URGENT,
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        CallbackRequest::PRIORITY_NORMAL => 'Normal',
                        CallbackRequest::PRIORITY_HIGH => 'Hoch',
                        CallbackRequest::PRIORITY_URGENT => 'Dringend',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Überfällig seit')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (CallbackRequest $record): string =>
                        $record->expires_at?->diffForHumans() ?? '-'
                    )
                    ->color('danger')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->badge()
                    ->color('primary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Zugewiesen an')
                    ->default('Nicht zugewiesen')
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => CallbackRequest::STATUS_PENDING,
                        'info' => CallbackRequest::STATUS_ASSIGNED,
                        'primary' => CallbackRequest::STATUS_CONTACTED,
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        CallbackRequest::STATUS_PENDING => 'Ausstehend',
                        CallbackRequest::STATUS_ASSIGNED => 'Zugewiesen',
                        CallbackRequest::STATUS_CONTACTED => 'Kontaktiert',
                        default => $state,
                    })
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('assign')
                    ->label('Zuweisen')
                    ->icon('heroicon-m-user-plus')
                    ->color('primary')
                    ->form([
                        \Filament\Forms\Components\Select::make('staff_id')
                            ->label('Mitarbeiter')
                            ->options(Staff::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (CallbackRequest $record, array $data): void {
                        $staff = Staff::findOrFail($data['staff_id']);
                        $record->assign($staff);
                        Cache::forget('overdue_callbacks_query');

                        \Filament\Notifications\Notification::make()
                            ->title('Rückruf zugewiesen')
                            ->success()
                            ->body("Rückruf wurde {$staff->name} zugewiesen.")
                            ->send();
                    })
                    ->visible(fn (CallbackRequest $record) =>
                        $record->status !== CallbackRequest::STATUS_CONTACTED
                    ),

                Tables\Actions\Action::make('mark_contacted')
                    ->label('Kontaktiert')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Als kontaktiert markieren?')
                    ->modalDescription('Bestätigen Sie, dass der Kunde kontaktiert wurde.')
                    ->action(function (CallbackRequest $record): void {
                        $record->markContacted();
                        Cache::forget('overdue_callbacks_query');

                        \Filament\Notifications\Notification::make()
                            ->title('Als kontaktiert markiert')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (CallbackRequest $record) =>
                        in_array($record->status, [
                            CallbackRequest::STATUS_PENDING,
                            CallbackRequest::STATUS_ASSIGNED
                        ])
                    ),

                Tables\Actions\Action::make('escalate')
                    ->label('Eskalieren')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('danger')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Grund der Eskalation')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                        \Filament\Forms\Components\Select::make('escalate_to')
                            ->label('Eskalieren an (optional)')
                            ->options(Staff::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (CallbackRequest $record, array $data): void {
                        $record->escalate(
                            $data['reason'],
                            $data['escalate_to'] ?? null
                        );
                        Cache::forget('overdue_callbacks_query');

                        \Filament\Notifications\Notification::make()
                            ->title('Rückruf eskaliert')
                            ->warning()
                            ->body('Der Rückruf wurde eskaliert.')
                            ->send();
                    }),
            ])
            ->recordUrl(
                fn (CallbackRequest $record): string =>
                    route('filament.admin.resources.callback-requests.view', ['record' => $record])
            )
            ->defaultSort('expires_at', 'asc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->striped()
            ->emptyStateHeading('Keine überfälligen Rückrufe')
            ->emptyStateDescription('Großartig! Alle Rückrufe wurden rechtzeitig bearbeitet.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    /**
     * Get the base query for overdue callbacks
     *
     * NOTE: Cannot cache Builder instances - return fresh query with eager loading
     */
    protected function getTableQuery(): Builder
    {
        return CallbackRequest::query()
            ->with(['branch', 'service', 'assignedTo', 'customer'])
            ->overdue()
            ->orderBy('priority', 'desc')
            ->orderBy('expires_at', 'asc')
            ->limit(100); // Limit to prevent memory exhaustion
    }

    /**
     * Get the widget heading with count badge
     */
    protected function getHeading(): string
    {
        $count = Cache::remember(
            'overdue_callbacks_count',
            now()->addMinutes(5),
            fn () => CallbackRequest::overdue()->count()
        );

        return "🚨 Überfällige Rückrufe ({$count})";
    }

    /**
     * Can view the widget
     */
    public static function canView(): bool
    {
        return true;
    }
}
