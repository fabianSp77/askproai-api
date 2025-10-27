<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationQueueResource\Pages;
use App\Models\NotificationQueue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;

class NotificationQueueResource extends Resource
{
    protected static ?string $model = NotificationQueue::class;

    /**
     * Resource disabled - notification_queue table doesn't exist in Sept 21 database backup
     * TODO: Re-enable when database is fully restored
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return false; // Prevents all access to this resource
    }

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Benachrichtigungen';

    protected static ?string $navigationLabel = 'Warteschlange';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled(),

                        Forms\Components\MorphToSelect::make('notifiable')
                            ->label('Empfänger')
                            ->types([
                                Forms\Components\MorphToSelect\Type::make('App\Models\Customer')
                                    ->titleAttribute('full_name'),
                                Forms\Components\MorphToSelect\Type::make('App\Models\Staff')
                                    ->titleAttribute('name'),
                            ])
                            ->searchable()
                            ->disabled(),

                        Forms\Components\Select::make('channel')
                            ->label('Kanal')
                            ->options([
                                'email' => 'E-Mail',
                                'sms' => 'SMS',
                                'whatsapp' => 'WhatsApp',
                                'push' => 'Push-Benachrichtigung'
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('type')
                            ->label('Typ')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Ausstehend',
                                'processing' => 'In Bearbeitung',
                                'sent' => 'Gesendet',
                                'delivered' => 'Zugestellt',
                                'opened' => 'Geöffnet',
                                'clicked' => 'Geklickt',
                                'failed' => 'Fehlgeschlagen',
                                'bounced' => 'Zurückgewiesen'
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('priority')
                            ->label('Priorität')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('attempts')
                            ->label('Versuche')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Geplant für')
                            ->displayFormat('d.m.Y H:i'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Inhalt')
                    ->schema([
                        Forms\Components\KeyValue::make('data')
                            ->label('Daten')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('recipient')
                            ->label('Empfänger-Details')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('Tracking')
                    ->schema([
                        Forms\Components\DateTimePicker::make('sent_at')
                            ->label('Gesendet am')
                            ->displayFormat('d.m.Y H:i')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('Zugestellt am')
                            ->displayFormat('d.m.Y H:i')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('opened_at')
                            ->label('Geöffnet am')
                            ->displayFormat('d.m.Y H:i')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('clicked_at')
                            ->label('Geklickt am')
                            ->displayFormat('d.m.Y H:i')
                            ->disabled(),

                        Forms\Components\TextInput::make('provider_message_id')
                            ->label('Provider Message ID')
                            ->disabled(),

                        Forms\Components\TextInput::make('cost')
                            ->label('Kosten')
                            ->prefix('€')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Fehlerinformationen')
                    ->schema([
                        Forms\Components\Textarea::make('error_message')
                            ->label('Fehlermeldung')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (NotificationQueue $record): bool => $record->status === 'failed')
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(10),

                Tables\Columns\TextColumn::make('notifiable.name')
                    ->label('Empfänger')
                    ->searchable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\BadgeColumn::make('channel')
                    ->label('Kanal')
                    ->colors([
                        'primary' => 'email',
                        'success' => 'whatsapp',
                        'warning' => 'sms',
                        'danger' => 'push',
                    ]),

                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'processing',
                        'primary' => 'sent',
                        'success' => 'delivered',
                        'info' => 'opened',
                        'success' => 'clicked',
                        'danger' => 'failed',
                        'danger' => 'bounced',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-arrow-path' => 'processing',
                        'heroicon-o-paper-airplane' => 'sent',
                        'heroicon-o-check-circle' => 'delivered',
                        'heroicon-o-envelope-open' => 'opened',
                        'heroicon-o-cursor-arrow-rays' => 'clicked',
                        'heroicon-o-x-circle' => 'failed',
                        'heroicon-o-exclamation-triangle' => 'bounced',
                    ]),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 2 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'secondary'
                    }),

                Tables\Columns\TextColumn::make('attempts')
                    ->label('Versuche')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Geplant')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Gesendet')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cost')
                    ->label('Kosten')
                    ->money('EUR')
                    ->alignRight()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'processing' => 'In Bearbeitung',
                        'sent' => 'Gesendet',
                        'delivered' => 'Zugestellt',
                        'opened' => 'Geöffnet',
                        'clicked' => 'Geklickt',
                        'failed' => 'Fehlgeschlagen',
                        'bounced' => 'Zurückgewiesen'
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('channel')
                    ->label('Kanal')
                    ->options([
                        'email' => 'E-Mail',
                        'sms' => 'SMS',
                        'whatsapp' => 'WhatsApp',
                        'push' => 'Push-Benachrichtigung'
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'confirmation' => 'Bestätigung',
                        'reminder' => 'Erinnerung',
                        'cancellation' => 'Stornierung',
                        'rescheduled' => 'Verschiebung',
                        'marketing' => 'Marketing',
                        'system' => 'System'
                    ]),

                Tables\Filters\Filter::make('failed')
                    ->label('Nur Fehlgeschlagene')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'failed')),

                Tables\Filters\Filter::make('scheduled')
                    ->label('Geplante')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('scheduled_at')->where('scheduled_at', '>', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('retry')
                    ->label('Wiederholen')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (NotificationQueue $record): void {
                        $record->update([
                            'status' => 'pending',
                            'attempts' => 0,
                            'error_message' => null,
                            'scheduled_at' => now()
                        ]);
                    })
                    ->visible(fn (NotificationQueue $record): bool => $record->status === 'failed')
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('cancel')
                    ->label('Abbrechen')
                    ->icon('heroicon-o-x-mark')
                    ->action(fn (NotificationQueue $record) => $record->update(['status' => 'cancelled']))
                    ->visible(fn (NotificationQueue $record): bool => in_array($record->status, ['pending', 'scheduled']))
                    ->color('danger')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('retry')
                        ->label('Alle wiederholen')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each(function ($record) {
                                if ($record->status === 'failed') {
                                    $record->update([
                                        'status' => 'pending',
                                        'attempts' => 0,
                                        'error_message' => null,
                                        'scheduled_at' => now()
                                    ]);
                                }
                            });
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('cancel')
                        ->label('Alle abbrechen')
                        ->icon('heroicon-o-x-mark')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each(function ($record) {
                                if (in_array($record->status, ['pending', 'scheduled'])) {
                                    $record->update(['status' => 'cancelled']);
                                }
                            });
                        })
                        ->color('danger')
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationQueues::route('/'),
            'view' => Pages\ViewNotificationQueue::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['notifiable']);
    }

    public static function getNavigationBadge(): ?string
    {
        // SECURITY FIX (SEC-002): Secure company-scoped badge count
        $user = auth()->user();

        if (!$user || !$user->company_id) {
            return null;
        }

        try {
            // Count only pending/processing notifications for current company
            $count = static::getModel()::where('company_id', $user->company_id)
                ->whereIn('status', ['pending', 'processing'])
                ->count();

            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            // Gracefully handle missing tables during database restoration
            \Log::warning('Navigation badge error in NotificationQueueResource: ' . $e->getMessage());
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        try {
            $count = (int) static::getNavigationBadge();

            // Color coding based on pending count
            return match (true) {
                $count === 0 => null,
                $count < 10 => 'info',
                $count < 50 => 'warning',
                default => 'danger',
            };
        } catch (\Exception $e) {
            // Gracefully handle errors
            \Log::warning('Navigation badge color error in NotificationQueueResource: ' . $e->getMessage());
            return null;
        }
    }
}