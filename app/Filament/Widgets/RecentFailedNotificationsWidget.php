<?php

namespace App\Filament\Widgets;

use App\Models\NotificationQueue;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentFailedNotificationsWidget extends BaseWidget
{
    protected static ?int $sort = 11;

    protected static ?string $heading = 'Neueste fehlgeschlagene Benachrichtigungen';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $companyId = auth()->user()->company_id;

        // SECURITY FIX (SEC-003): Direct company_id filtering
        return $table
            ->query(
                NotificationQueue::query()
                    ->where('company_id', $companyId)
                    ->where('status', 'failed')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('channel')
                    ->label('Kanal')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'email' => '📧 E-Mail',
                        'sms' => '📱 SMS',
                        'whatsapp' => '💬 WhatsApp',
                        'push' => '🔔 Push',
                        default => ucfirst($state),
                    })
                    ->color('info'),

                Tables\Columns\TextColumn::make('recipient')
                    ->label('Empfänger')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Fehlermeldung')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('retry_count')
                    ->label('Versuche')
                    ->badge()
                    ->color(fn (int $state): string => $state >= 3 ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('notificationConfiguration.event_type')
                    ->label('Event-Typ')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since(),

                Tables\Columns\TextColumn::make('failed_at')
                    ->label('Fehlgeschlagen')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('retry')
                    ->label('Erneut versuchen')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (NotificationQueue $record) {
                        $record->update([
                            'status' => 'pending',
                            'retry_count' => $record->retry_count + 1,
                            'error_message' => null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Benachrichtigung erneut eingereiht')
                            ->body('Die Benachrichtigung wird erneut versucht.')
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->paginated([5, 10])
            ->poll('30s');
    }

    public function getColumnSpan(): string | array | int
    {
        return 'full';
    }
}
