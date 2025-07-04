<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PrepaidBalanceResource\Pages;
use App\Models\PrepaidBalance;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PrepaidBalanceResource extends Resource
{
    protected static ?string $model = PrepaidBalance::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';
    protected static ?string $navigationLabel = 'Prepaid Guthaben';
    protected static ?string $navigationGroup = 'Billing & Portal';
    protected static ?int $navigationSort = 20;
    protected static ?string $modelLabel = 'Prepaid Guthaben';
    protected static ?string $pluralModelLabel = 'Prepaid Guthaben';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->label('Firma')
                    ->relationship('company', 'name')
                    ->required()
                    ->searchable()
                    ->disabled(),
                Forms\Components\TextInput::make('balance')
                    ->label('Guthaben')
                    ->numeric()
                    ->prefix('€')
                    ->disabled(),
                Forms\Components\TextInput::make('reserved_balance')
                    ->label('Reserviertes Guthaben')
                    ->numeric()
                    ->prefix('€')
                    ->disabled(),
                Forms\Components\TextInput::make('low_balance_threshold')
                    ->label('Warnschwelle')
                    ->numeric()
                    ->prefix('€')
                    ->required(),
                Forms\Components\DateTimePicker::make('last_warning_sent_at')
                    ->label('Letzte Warnung gesendet')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('balance')
                    ->label('Guthaben')
                    ->money('EUR')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('reserved_balance')
                    ->label('Reserviert')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('effective_balance')
                    ->label('Verfügbar')
                    ->getStateUsing(fn ($record) => $record->getEffectiveBalance())
                    ->money('EUR')
                    ->sortable()
                    ->color(fn ($state) => $state > 20 ? 'success' : ($state > 0 ? 'warning' : 'danger')),
                TextColumn::make('low_balance_threshold')
                    ->label('Warnschwelle')
                    ->money('EUR')
                    ->toggleable(),
                TextColumn::make('last_topup')
                    ->label('Letzte Aufladung')
                    ->getStateUsing(function ($record) {
                        $lastTopup = $record->transactions()
                            ->where('type', 'credit')
                            ->latest()
                            ->first();
                        return $lastTopup ? $lastTopup->created_at->diffForHumans() : '-';
                    }),
                TextColumn::make('monthly_usage')
                    ->label('Monatliche Nutzung')
                    ->getStateUsing(function ($record) {
                        $usage = $record->transactions()
                            ->where('type', 'debit')
                            ->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->sum('amount');
                        return number_format($usage, 2, ',', '.') . ' €';
                    })
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('low_balance')
                    ->label('Niedriges Guthaben')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('balance - reserved_balance < low_balance_threshold')),
                Tables\Filters\Filter::make('no_balance')
                    ->label('Kein Guthaben')
                    ->query(fn (Builder $query): Builder => $query->where('balance', '<=', 0)),
            ])
            ->actions([
                Action::make('viewPortal')
                    ->label('Portal öffnen')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->action(function (PrepaidBalance $record) {
                        // Generate admin access token
                        $token = bin2hex(random_bytes(32));
                        
                        // Store in cache for 15 minutes
                        cache()->put('admin_portal_access_' . $token, [
                            'admin_id' => auth()->id(),
                            'company_id' => $record->company_id,
                            'created_at' => now(),
                        ], now()->addMinutes(15));
                        
                        // Redirect to business portal
                        return redirect('/business/admin-access?token=' . $token);
                    }),
                Action::make('adjustBalance')
                    ->label('Anpassen')
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        Select::make('type')
                            ->label('Typ')
                            ->options([
                                'credit' => 'Aufladung (+)',
                                'debit' => 'Abzug (-)',
                            ])
                            ->required(),
                        TextInput::make('amount')
                            ->label('Betrag')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->minValue(0.01),
                        Textarea::make('description')
                            ->label('Beschreibung')
                            ->required()
                            ->default('Manuelle Anpassung durch Admin'),
                    ])
                    ->action(function (PrepaidBalance $record, array $data) {
                        try {
                            DB::transaction(function () use ($record, $data) {
                                if ($data['type'] === 'credit') {
                                    $record->addBalance($data['amount'], $data['description'], 'admin_adjustment');
                                } else {
                                    $record->deductBalance($data['amount'], $data['description'], 'admin_adjustment');
                                }
                            });
                            
                            Notification::make()
                                ->title('Guthaben angepasst')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Fehler')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No bulk actions for financial data
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
            'index' => Pages\ListPrepaidBalances::route('/'),
            'view' => Pages\ViewPrepaidBalance::route('/{record}'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company', 'transactions']);
    }
}