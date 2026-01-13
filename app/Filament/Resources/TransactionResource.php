<?php

namespace App\Filament\Resources;

use Filament\Facades\Filament;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    /**
     * Resource disabled - transactions table doesn't exist in Sept 21 database backup
     * TODO: Re-enable when database is fully restored
     */
    public static function shouldRegisterNavigation(): bool
    {
        // ✅ Super admin can see all resources
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }


    public static function canViewAny(): bool
    {
        // ✅ Super admin can access all resources
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }


    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Transaktionen';
    protected static ?string $navigationGroup = 'Abrechnung';
    protected static ?int $navigationSort = 27;
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
            ->poll('30s')
            ->striped();
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Transaktionsdetails')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('id')
                                ->label('Transaktions-ID')
                                ->badge(),
                            
                            Infolists\Components\TextEntry::make('type')
                                ->label('Typ')
                                ->formatStateUsing(fn ($state) => match($state) {
                                    'topup' => 'Aufladung',
                                    'usage' => 'Verbrauch',
                                    'refund' => 'Erstattung',
                                    'adjustment' => 'Anpassung',
                                    'bonus' => 'Bonus',
                                    'fee' => 'Gebühr',
                                    default => $state ?? '-',
                                })
                                ->badge()
                                ->color(fn ($state) => match($state) {
                                    'topup' => 'success',
                                    'usage' => 'danger',
                                    'refund' => 'warning',
                                    'adjustment' => 'info',
                                    'bonus' => 'primary',
                                    'fee' => 'gray',
                                    default => 'gray',
                                }),
                            
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Datum & Zeit')
                                ->dateTime('d.m.Y H:i:s'),
                        ]),
                        
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('amount_cents')
                                ->label('Betrag')
                                ->formatStateUsing(function ($state) {
                                    $prefix = $state > 0 ? '+' : '';
                                    return $prefix . number_format(($state ?? 0) / 100, 2) . ' €';
                                })
                                ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                                ->weight('bold'),
                            
                            Infolists\Components\TextEntry::make('balance_before_cents')
                                ->label('Saldo vorher')
                                ->formatStateUsing(fn ($state) => number_format(($state ?? 0) / 100, 2) . ' €'),
                            
                            Infolists\Components\TextEntry::make('balance_after_cents')
                                ->label('Saldo nachher')
                                ->formatStateUsing(fn ($state) => number_format(($state ?? 0) / 100, 2) . ' €')
                                ->color(fn ($state) => $state < 0 ? 'danger' : ($state < 1000 ? 'warning' : 'success'))
                                ->weight('semibold'),
                        ]),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Beschreibung')
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label('Tenant')
                            ->url(fn ($record) => ($record && $record->tenant) ? route('filament.admin.resources.tenants.view', $record->tenant) : null),
                    ]),
                
                Infolists\Components\Section::make('Verknüpfungen')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('call_id')
                                ->label('Anruf')
                                ->visible(fn ($record) => $record && $record->call_id)
                                ->formatStateUsing(fn ($state) => "ID: {$state}")
                                ->url(fn ($record) => ($record && $record->call_id) ? route('filament.admin.resources.calls.view', $record->call_id) : null)
                                ->icon('heroicon-o-phone'),
                            
                            Infolists\Components\TextEntry::make('appointment_id')
                                ->label('Termin')
                                ->visible(fn ($record) => $record && $record->appointment_id)
                                ->formatStateUsing(fn ($state) => "ID: {$state}")
                                ->url(fn ($record) => ($record && $record->appointment_id) ? route('filament.admin.resources.appointments.view', $record->appointment_id) : null)
                                ->icon('heroicon-o-calendar'),
                            
                            Infolists\Components\TextEntry::make('topup_id')
                                ->label('Aufladung')
                                ->visible(fn ($record) => $record && $record->topup_id)
                                ->formatStateUsing(fn ($state) => "ID: {$state}")
                                ->url(fn ($record) => ($record && $record->topup_id) ? route('filament.admin.resources.balance-topups.view', $record->topup_id) : null)
                                ->icon('heroicon-o-credit-card'),
                        ]),
                    ])
                    ->visible(fn ($record) => $record && ($record->call_id || $record->appointment_id || $record->topup_id)),
                
                Infolists\Components\Section::make('Systemdaten')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Erstellt am')
                                ->dateTime('d.m.Y H:i:s'),
                            
                            Infolists\Components\TextEntry::make('updated_at')
                                ->label('Aktualisiert am')
                                ->dateTime('d.m.Y H:i:s'),
                        ]),
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }
    
    public static function getWidgets(): array
    {
        return [
            // TransactionResource\Widgets\TransactionStats::class,
        ];
    }
}