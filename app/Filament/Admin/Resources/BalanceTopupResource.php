<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BalanceTopupResource\Pages;
use App\Models\BalanceTopup;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class BalanceTopupResource extends Resource
{
    protected static ?string $model = BalanceTopup::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Aufladungen';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'balance-topups';
    
    protected static ?string $recordTitleAttribute = 'id';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Aufladungsdetails')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('tenant_id')
                                ->label('Tenant')
                                ->relationship('tenant', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                    $set('metadata.tenant_balance', Tenant::find($state)?->balance_cents / 100)
                                ),
                            
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => 'Ausstehend',
                                    'processing' => 'In Bearbeitung',
                                    'succeeded' => 'Erfolgreich',
                                    'failed' => 'Fehlgeschlagen',
                                    'cancelled' => 'Abgebrochen',
                                ])
                                ->required()
                                ->disabled(fn ($record) => $record && in_array($record->status, ['succeeded', 'failed']))
                                ->helperText('Erfolgreiche Aufladungen können nicht mehr geändert werden'),
                        ]),
                        
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('Betrag')
                                ->numeric()
                                ->required()
                                ->prefix('€')
                                ->step(0.01)
                                ->minValue(1)
                                ->reactive()
                                ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) => 
                                    $set('metadata.total', ($state ?? 0) + ($get('bonus_amount') ?? 0))
                                ),
                            
                            Forms\Components\TextInput::make('bonus_amount')
                                ->label('Bonus')
                                ->numeric()
                                ->prefix('€')
                                ->step(0.01)
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) => 
                                    $set('metadata.total', ($get('amount') ?? 0) + ($state ?? 0))
                                ),
                            
                            Forms\Components\Select::make('currency')
                                ->label('Währung')
                                ->options([
                                    'EUR' => 'EUR (€)',
                                    'USD' => 'USD ($)',
                                    'GBP' => 'GBP (£)',
                                ])
                                ->default('EUR')
                                ->required(),
                        ]),
                        
                        Forms\Components\TextInput::make('bonus_reason')
                            ->label('Bonus-Grund')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('bonus_amount') > 0)
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Zahlungsinformationen')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('payment_method')
                                ->label('Zahlungsmethode')
                                ->options([
                                    'stripe' => 'Stripe (Kreditkarte)',
                                    'bank_transfer' => 'Banküberweisung',
                                    'manual' => 'Manuelle Aufladung',
                                    'bonus' => 'Bonus/Gutschrift',
                                    'trial' => 'Testguthaben',
                                ])
                                ->required(),
                            
                            Forms\Components\DateTimePicker::make('paid_at')
                                ->label('Zahlungsdatum')
                                ->displayFormat('d.m.Y H:i')
                                ->visible(fn (Forms\Get $get) => $get('status') === 'succeeded'),
                        ]),
                        
                        Forms\Components\TextInput::make('stripe_payment_intent_id')
                            ->label('Stripe Payment Intent ID')
                            ->visible(fn (Forms\Get $get) => $get('payment_method') === 'stripe')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('stripe_checkout_session_id')
                            ->label('Stripe Checkout Session ID')
                            ->visible(fn (Forms\Get $get) => $get('payment_method') === 'stripe')
                            ->disabled(),
                        
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadaten')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable()
                    ->searchable()
                    ->limit(20),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'succeeded',
                        'warning' => 'pending',
                        'info' => 'processing',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Ausstehend',
                        'processing' => 'In Bearbeitung',
                        'succeeded' => 'Erfolgreich',
                        'failed' => 'Fehlgeschlagen',
                        'cancelled' => 'Abgebrochen',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Betrag')
                    ->formatStateUsing(fn ($state, $record) => 
                        number_format($state, 2) . ' ' . $record->currency
                    )
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('bonus_amount')
                    ->label('Bonus')
                    ->formatStateUsing(fn ($state, $record) => 
                        $state > 0 ? '+' . number_format($state, 2) . ' ' . $record->currency : '-'
                    )
                    ->color('success')
                    ->alignEnd(),
                
                Tables\Columns\TextColumn::make('total')
                    ->label('Gesamt')
                    ->getStateUsing(fn ($record) => $record->getTotalAmount())
                    ->formatStateUsing(fn ($state, $record) => 
                        number_format($state, 2) . ' ' . $record->currency
                    )
                    ->alignEnd()
                    ->weight('bold')
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Zahlungsart')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'stripe' => 'Stripe',
                        'bank_transfer' => 'Überweisung',
                        'manual' => 'Manuell',
                        'bonus' => 'Bonus',
                        'trial' => 'Test',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'stripe',
                        'info' => 'bank_transfer',
                        'warning' => 'manual',
                        'success' => 'bonus',
                        'gray' => 'trial',
                    ]),
                
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Zahlungsdatum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Ausstehend'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'processing' => 'In Bearbeitung',
                        'succeeded' => 'Erfolgreich',
                        'failed' => 'Fehlgeschlagen',
                        'cancelled' => 'Abgebrochen',
                    ])
                    ->multiple(),
                
                SelectFilter::make('payment_method')
                    ->label('Zahlungsmethode')
                    ->options([
                        'stripe' => 'Stripe',
                        'bank_transfer' => 'Banküberweisung',
                        'manual' => 'Manuell',
                        'bonus' => 'Bonus',
                        'trial' => 'Test',
                    ]),
                
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !in_array($record->status, ['succeeded', 'failed'])),
                
                Tables\Actions\Action::make('approve')
                    ->label('Genehmigen')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aufladung genehmigen')
                    ->modalDescription('Sind Sie sicher, dass Sie diese Aufladung genehmigen möchten? Das Guthaben wird dem Tenant gutgeschrieben.')
                    ->action(function (BalanceTopup $record) {
                        $record->markAsSucceeded();
                        
                        Notification::make()
                            ->title('Aufladung genehmigt')
                            ->body("Aufladung #{$record->id} wurde erfolgreich genehmigt")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'processing'])),
                
                Tables\Actions\Action::make('reject')
                    ->label('Ablehnen')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('reason')
                            ->label('Grund für Ablehnung')
                            ->required(),
                    ])
                    ->action(function (BalanceTopup $record, array $data) {
                        $record->markAsFailed($data['reason']);
                        
                        Notification::make()
                            ->title('Aufladung abgelehnt')
                            ->body("Aufladung #{$record->id} wurde abgelehnt")
                            ->warning()
                            ->send();
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'processing'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status === 'succeeded') {
                                    Notification::make()
                                        ->title('Löschen nicht möglich')
                                        ->body('Erfolgreiche Aufladungen können nicht gelöscht werden')
                                        ->danger()
                                        ->send();
                                    
                                    return false;
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('10s')
            ->striped();
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
            'index' => Pages\ListBalanceTopups::route('/'),
            'create' => Pages\CreateBalanceTopup::route('/create'),
            'view' => Pages\ViewBalanceTopup::route('/{record}'),
            'edit' => Pages\EditBalanceTopup::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        return $pendingCount > 0 ? $pendingCount : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}