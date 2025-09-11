<?php

namespace App\Filament\Reseller\Resources;

use App\Filament\Reseller\Resources\MyCustomersResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MyCustomersResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Meine Kunden';
    
    protected static ?string $modelLabel = 'Kunde';
    
    protected static ?string $pluralModelLabel = 'Kunden';
    
    protected static ?string $navigationGroup = 'Kunden';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kundendaten')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('birthdate')
                            ->label('Geburtsdatum')
                            ->maxDate(now()),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Firmeninformationen')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Firmenname')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vat_id')
                            ->label('USt-IdNr.')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->label('Adresse')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Forms\Components\Section::make('Abrechnungsinformationen')
                    ->schema([
                        Forms\Components\Select::make('pricing_plan_id')
                            ->label('Preisplan')
                            ->relationship('pricingPlan', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('balance_cents')
                            ->label('Guthaben')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01)
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => $state * 100)
                            ->formatStateUsing(fn ($state) => $state / 100),
                        Forms\Components\Toggle::make('auto_topup_enabled')
                            ->label('Auto-Aufladung aktiviert')
                            ->default(false),
                        Forms\Components\TextInput::make('auto_topup_amount')
                            ->label('Auto-Aufladung Betrag')
                            ->numeric()
                            ->prefix('€')
                            ->visible(fn (Forms\Get $get) => $get('auto_topup_enabled')),
                    ])
                    ->columns(2),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Firma')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance_cents')
                    ->label('Guthaben')
                    ->money('EUR', divideBy: 100)
                    ->sortable()
                    ->color(fn ($state) => $state < 500 ? 'danger' : ($state < 2000 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Gesamtumsatz')
                    ->money('EUR', divideBy: 100)
                    ->sortable()
                    ->getStateUsing(function (Model $record) {
                        return $record->transactions()
                            ->where('type', 'usage')
                            ->sum('amount_cents') * -1;
                    }),
                Tables\Columns\IconColumn::make('auto_topup_enabled')
                    ->label('Auto-Topup')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Kunde seit')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pricing_plan')
                    ->label('Preisplan')
                    ->relationship('pricingPlan', 'name'),
                Tables\Filters\Filter::make('low_balance')
                    ->label('Niedriges Guthaben')
                    ->query(fn (Builder $query): Builder => $query->where('balance_cents', '<', 500)),
                Tables\Filters\Filter::make('auto_topup')
                    ->label('Auto-Aufladung aktiv')
                    ->query(fn (Builder $query): Builder => $query->where('auto_topup_enabled', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('add_credit')
                    ->label('Guthaben aufladen')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Betrag in €')
                            ->numeric()
                            ->required()
                            ->minValue(10)
                            ->maxValue(1000)
                            ->step(10)
                            ->default(50),
                        Forms\Components\Textarea::make('note')
                            ->label('Notiz')
                            ->rows(2),
                    ])
                    ->action(function (Model $record, array $data): void {
                        $amountCents = $data['amount'] * 100;
                        $record->addCredit($amountCents, $data['note'] ?? 'Manuelle Aufladung durch Reseller');
                        
                        // Track commission for reseller
                        $reseller = app('current_reseller');
                        $commission = $amountCents * 0.10; // 10% commission on manual topups
                        
                        \App\Models\CommissionLedger::create([
                            'reseller_id' => $reseller->id,
                            'customer_id' => $record->id,
                            'transaction_type' => 'manual_topup',
                            'amount_cents' => $amountCents,
                            'commission_cents' => $commission,
                            'commission_rate' => 10.00,
                            'status' => 'pending',
                        ]);
                    })
                    ->successNotificationTitle('Guthaben erfolgreich aufgeladen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers will be added here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyCustomers::route('/'),
            'create' => Pages\CreateMyCustomer::route('/create'),
            'view' => Pages\ViewMyCustomer::route('/{record}'),
            'edit' => Pages\EditMyCustomer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // CRITICAL: Data isolation - only show customers belonging to current reseller
        $reseller = app('current_reseller');
        
        return parent::getEloquentQuery()
            ->where('reseller_id', $reseller->id);
    }

    public static function canCreate(): bool
    {
        // Resellers can create new customers
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        $reseller = app('current_reseller');
        return static::getModel()::where('reseller_id', $reseller->id)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}