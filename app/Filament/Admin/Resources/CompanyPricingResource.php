<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CompanyPricingResource\Pages;
use App\Models\CompanyPricing;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CompanyPricingResource extends Resource
{
    protected static ?string $model = CompanyPricing::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';
    
    protected static ?string $navigationGroup = 'Verwaltung';
    
    protected static ?string $modelLabel = 'Preismodell';
    
    protected static ?string $pluralModelLabel = 'Preismodelle';
    
    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Firma & Gültigkeit')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Firma')
                            ->options(function () {
                                $user = Auth::user();
                                if ($user->hasRole('Super Admin')) {
                                    return Company::pluck('name', 'id');
                                }
                                return Company::where('id', $user->company_id)->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->disabled(fn ($record) => $record !== null)
                            ->helperText('Die Firma kann nach der Erstellung nicht mehr geändert werden.'),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('valid_from')
                                    ->label('Gültig ab')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d.m.Y'),
                                    
                                Forms\Components\DatePicker::make('valid_until')
                                    ->label('Gültig bis')
                                    ->native(false)
                                    ->displayFormat('d.m.Y')
                                    ->after('valid_from')
                                    ->helperText('Leer lassen für unbegrenzte Gültigkeit'),
                                    
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true)
                                    ->helperText('Nur aktive Preismodelle werden angewendet'),
                            ]),
                    ])
                    ->columns(1),
                    
                Forms\Components\Section::make('Preisgestaltung')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price_per_minute')
                                    ->label('Preis pro Minute')
                                    ->numeric()
                                    ->required()
                                    ->step(0.0001)
                                    ->minValue(0)
                                    ->prefix('€')
                                    ->helperText('Standard-Minutenpreis (z.B. 0.35 für 35 Cent)'),
                                    
                                Forms\Components\TextInput::make('included_minutes')
                                    ->label('Inkludierte Minuten')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->suffix('Min/Monat')
                                    ->helperText('Freiminuten pro Monat'),
                            ]),
                            
                        Forms\Components\TextInput::make('overage_price_per_minute')
                            ->label('Preis für zusätzliche Minuten')
                            ->numeric()
                            ->step(0.0001)
                            ->minValue(0)
                            ->prefix('€')
                            ->helperText('Preis nach Verbrauch der Inklusivminuten (leer = Standard-Minutenpreis)'),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('setup_fee')
                                    ->label('Einrichtungsgebühr')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->prefix('€')
                                    ->helperText('Einmalige Gebühr bei Aktivierung'),
                                    
                                Forms\Components\TextInput::make('monthly_base_fee')
                                    ->label('Monatliche Grundgebühr')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->prefix('€')
                                    ->helperText('Feste monatliche Gebühr'),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Interne Notizen zu diesem Preismodell'),
                    ])
                    ->collapsible(),
                    
                // Informations-Panel
                Forms\Components\Section::make('Beispielrechnung')
                    ->schema([
                        Forms\Components\Placeholder::make('price_examples')
                            ->label('Preisbeispiele')
                            ->content(function ($get) {
                                $pricePerMinute = $get('price_per_minute') ?? 0;
                                $includedMinutes = $get('included_minutes') ?? 0;
                                $monthlyFee = $get('monthly_base_fee') ?? 0;
                                
                                if (!$pricePerMinute) {
                                    return 'Bitte geben Sie einen Minutenpreis ein.';
                                }
                                
                                $examples = [
                                    50 => 'Wenig-Nutzer (50 Min)',
                                    $includedMinutes => "Genau Inklusive ({$includedMinutes} Min)",
                                    200 => 'Normal-Nutzer (200 Min)',
                                    500 => 'Viel-Nutzer (500 Min)',
                                ];
                                
                                $html = '<div class="space-y-2">';
                                foreach ($examples as $minutes => $label) {
                                    if ($minutes <= $includedMinutes) {
                                        $cost = $monthlyFee;
                                    } else {
                                        $overageMinutes = $minutes - $includedMinutes;
                                        $overagePrice = $get('overage_price_per_minute') ?? $pricePerMinute;
                                        $cost = $monthlyFee + ($overageMinutes * $overagePrice);
                                    }
                                    
                                    $html .= sprintf(
                                        '<div class="flex justify-between"><span>%s:</span><strong>€%.2f</strong></div>',
                                        $label,
                                        $cost
                                    );
                                }
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('price_per_minute')
                    ->label('Minutenpreis')
                    ->money('EUR')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('included_minutes')
                    ->label('Inkl. Minuten')
                    ->suffix(' Min')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                    
                Tables\Columns\TextColumn::make('monthly_base_fee')
                    ->label('Grundgebühr')
                    ->money('EUR')
                    ->alignCenter()
                    ->placeholder('—'),
                    
                Tables\Columns\TextColumn::make('valid_period')
                    ->label('Gültigkeitszeitraum')
                    ->getStateUsing(function ($record) {
                        $from = $record->valid_from->format('d.m.Y');
                        $until = $record->valid_until ? $record->valid_until->format('d.m.Y') : 'unbegrenzt';
                        return "{$from} - {$until}";
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->is_active) return 'gray';
                        if ($record->valid_until && $record->valid_until->isPast()) return 'danger';
                        if ($record->valid_from->isFuture()) return 'warning';
                        return 'success';
                    }),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Firma')
                    ->options(function () {
                        $user = Auth::user();
                        if ($user->hasRole('Super Admin')) {
                            return Company::pluck('name', 'id');
                        }
                        return Company::where('id', $user->company_id)->pluck('name', 'id');
                    })
                    ->searchable(),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
                    
                Tables\Filters\Filter::make('currently_valid')
                    ->label('Aktuell gültig')
                    ->query(fn (Builder $query): Builder => $query->active())
                    ->default(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplizieren')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function ($record) {
                        $newRecord = $record->replicate();
                        $newRecord->valid_from = now();
                        $newRecord->valid_until = null;
                        $newRecord->is_active = false;
                        $newRecord->save();
                        
                        return redirect()->route('filament.admin.resources.company-pricings.edit', $newRecord);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCompanyPricings::route('/'),
            'create' => Pages\CreateCompanyPricing::route('/create'),
            'view' => Pages\ViewCompanyPricing::route('/{record}'),
            'edit' => Pages\EditCompanyPricing::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = Auth::user();
        if (!$user->hasRole('Super Admin') && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        
        return $query;
    }
}