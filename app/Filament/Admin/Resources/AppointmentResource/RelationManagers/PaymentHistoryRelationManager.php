<?php

namespace App\Filament\Admin\Resources\AppointmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SilentlyDiscardingAttributes;

class PaymentHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $title = 'Zahlungsverlauf';
    protected static ?string $modelLabel = 'Zahlung';
    protected static ?string $pluralModelLabel = 'Zahlungen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Betrag')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->step(0.01),
                    
                Forms\Components\Select::make('payment_method')
                    ->label('Zahlungsmethode')
                    ->options([
                        'cash' => 'Bar',
                        'card' => 'Karte',
                        'bank_transfer' => 'Überweisung',
                        'paypal' => 'PayPal',
                        'invoice' => 'Rechnung',
                    ])
                    ->required(),
                    
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'completed' => 'Abgeschlossen',
                        'failed' => 'Fehlgeschlagen',
                        'refunded' => 'Erstattet',
                        'partially_refunded' => 'Teilweise erstattet',
                    ])
                    ->default('pending')
                    ->required(),
                    
                Forms\Components\TextInput::make('transaction_id')
                    ->label('Transaktions-ID')
                    ->maxLength(255),
                    
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Bezahlt am')
                    ->native(false),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('Notizen')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR', divideBy: 100)
                    ->weight('bold')
                    ->color(fn ($record) => match($record->status) {
                        'completed' => 'success',
                        'refunded' => 'danger',
                        'partially_refunded' => 'warning',
                        default => null,
                    }),
                    
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Zahlungsmethode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'card' => 'info',
                        'bank_transfer' => 'warning',
                        'paypal' => 'primary',
                        'invoice' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Bar',
                        'card' => 'Karte',
                        'bank_transfer' => 'Überweisung',
                        'paypal' => 'PayPal',
                        'invoice' => 'Rechnung',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'danger',
                        'partially_refunded' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Ausstehend',
                        'completed' => 'Abgeschlossen',
                        'failed' => 'Fehlgeschlagen',
                        'refunded' => 'Erstattet',
                        'partially_refunded' => 'Teilweise erstattet',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaktions-ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Bezahlt am')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'completed' => 'Abgeschlossen',
                        'failed' => 'Fehlgeschlagen',
                        'refunded' => 'Erstattet',
                        'partially_refunded' => 'Teilweise erstattet',
                    ]),
                    
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Zahlungsmethode')
                    ->options([
                        'cash' => 'Bar',
                        'card' => 'Karte',
                        'bank_transfer' => 'Überweisung',
                        'paypal' => 'PayPal',
                        'invoice' => 'Rechnung',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Zahlung erfassen'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('refund')
                    ->label('Erstatten')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'completed')
                    ->action(fn ($record) => $record->update(['status' => 'refunded'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Zahlungen erfasst')
            ->emptyStateDescription('Erfassen Sie Zahlungen für diesen Termin.');
    }
    
    public function isReadOnly(): bool
    {
        return false;
    }
}