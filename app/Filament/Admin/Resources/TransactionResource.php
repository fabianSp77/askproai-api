<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Carbon\Carbon;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Transaktionen';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'transactions';
    
    protected static ?string $recordTitleAttribute = 'description';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaktionsdetails')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('tenant_id')
                                ->label('Tenant')
                                ->relationship('tenant', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            
                            Forms\Components\Select::make('type')
                                ->label('Typ')
                                ->options([
                                    Transaction::TYPE_TOPUP => 'Aufladung',
                                    Transaction::TYPE_USAGE => 'Verbrauch',
                                    Transaction::TYPE_REFUND => 'Erstattung',
                                    Transaction::TYPE_ADJUSTMENT => 'Anpassung',
                                    Transaction::TYPE_BONUS => 'Bonus',
                                    Transaction::TYPE_FEE => 'Gebühr',
                                ])
                                ->required()
                                ->reactive(),
                        ]),
                        
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('amount_cents')
                                ->label('Betrag (Cents)')
                                ->numeric()
                                ->required()
                                ->helperText('Positiv = Gutschrift, Negativ = Belastung'),
                            
                            Forms\Components\TextInput::make('balance_before_cents')
                                ->label('Saldo vorher (Cents)')
                                ->numeric()
                                ->required()
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('balance_after_cents')
                                ->label('Saldo nachher (Cents)')
                                ->numeric()
                                ->required()
                                ->disabled()
                                ->dehydrated(),
                        ]),
                        
                        Forms\Components\TextInput::make('description')
                            ->label('Beschreibung')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('topup_id')
                                ->label('Aufladungs-ID')
                                ->numeric()
                                ->visible(fn (Forms\Get $get) => $get('type') === Transaction::TYPE_TOPUP),
                            
                            Forms\Components\TextInput::make('call_id')
                                ->label('Anruf-ID')
                                ->numeric()
                                ->visible(fn (Forms\Get $get) => $get('type') === Transaction::TYPE_USAGE),
                            
                            Forms\Components\TextInput::make('appointment_id')
                                ->label('Termin-ID')
                                ->numeric()
                                ->visible(fn (Forms\Get $get) => $get('type') === Transaction::TYPE_USAGE),
                        ]),
                        
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
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->colors([
                        'success' => Transaction::TYPE_TOPUP,
                        'danger' => Transaction::TYPE_USAGE,
                        'warning' => Transaction::TYPE_REFUND,
                        'info' => Transaction::TYPE_ADJUSTMENT,
                        'primary' => Transaction::TYPE_BONUS,
                        'secondary' => Transaction::TYPE_FEE,
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        Transaction::TYPE_TOPUP => 'Aufladung',
                        Transaction::TYPE_USAGE => 'Verbrauch',
                        Transaction::TYPE_REFUND => 'Erstattung',
                        Transaction::TYPE_ADJUSTMENT => 'Anpassung',
                        Transaction::TYPE_BONUS => 'Bonus',
                        Transaction::TYPE_FEE => 'Gebühr',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Betrag')
                    ->formatStateUsing(function ($state) {
                        $prefix = $state > 0 ? '+' : '';
                        $color = $state > 0 ? 'text-green-600' : 'text-red-600';
                        $amount = number_format($state / 100, 2);
                        return "<span class='{$color} font-semibold'>{$prefix}{$amount} €</span>";
                    })
                    ->html()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('balance_after_cents')
                    ->label('Saldo danach')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' €')
                    ->sortable()
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state < 1000 ? 'warning' : 'success')),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->description),
                
                Tables\Columns\IconColumn::make('call_id')
                    ->label('Anruf')
                    ->boolean()
                    ->trueIcon('heroicon-o-phone')
                    ->falseIcon('')
                    ->exists('call'),
                
                Tables\Columns\IconColumn::make('appointment_id')
                    ->label('Termin')
                    ->boolean()
                    ->trueIcon('heroicon-o-calendar')
                    ->falseIcon('')
                    ->exists('appointment'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        Transaction::TYPE_TOPUP => 'Aufladung',
                        Transaction::TYPE_USAGE => 'Verbrauch',
                        Transaction::TYPE_REFUND => 'Erstattung',
                        Transaction::TYPE_ADJUSTMENT => 'Anpassung',
                        Transaction::TYPE_BONUS => 'Bonus',
                        Transaction::TYPE_FEE => 'Gebühr',
                    ])
                    ->multiple(),
                
                Filter::make('credits')
                    ->label('Nur Gutschriften')
                    ->query(fn (Builder $query) => $query->where('amount_cents', '>', 0)),
                
                Filter::make('debits')
                    ->label('Nur Belastungen')
                    ->query(fn (Builder $query) => $query->where('amount_cents', '<', 0)),
                
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Von ' . Carbon::parse($data['created_from'])->format('d.m.Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Bis ' . Carbon::parse($data['created_until'])->format('d.m.Y');
                        }
                        return $indicators;
                    }),
                
                Filter::make('low_balance')
                    ->label('Niedriger Saldo (<10€)')
                    ->query(fn (Builder $query) => $query->where('balance_after_cents', '<', 1000)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('view_related')
                    ->label('Zugehörig')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->modalContent(fn ($record) => view('filament.modals.transaction-relations', ['transaction' => $record]))
                    ->modalHeading('Verknüpfte Datensätze')
                    ->visible(fn ($record) => $record && ($record->call_id || $record->appointment_id || $record->topup_id)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Exportieren'),
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $todayCount = static::getModel()::whereDate('created_at', today())->count();
        return $todayCount > 0 ? $todayCount : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
    
    public static function getWidgets(): array
    {
        return [
            TransactionResource\Widgets\TransactionStats::class,
        ];
    }
}