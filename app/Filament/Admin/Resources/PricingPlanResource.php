<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PricingPlanResource\Pages;
use App\Models\PricingPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;

class PricingPlanResource extends Resource
{
    protected static ?string $model = PricingPlan::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';
    protected static ?string $navigationLabel = 'Preismodelle';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'pricing-plans';
    
    protected static ?string $recordTitleAttribute = 'name';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Grundinformationen')
                    ->description('Basis-Einstellungen für das Preismodell')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                    $set('slug', \Str::slug($state))
                                ),
                            
                            Forms\Components\TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                        ]),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Grid::make(3)->schema([
                            Forms\Components\Select::make('billing_type')
                                ->label('Abrechnungstyp')
                                ->options([
                                    'prepaid' => 'Prepaid (Vorauszahlung)',
                                    'postpaid' => 'Postpaid (Nachzahlung)',
                                    'hybrid' => 'Hybrid (Kombination)'
                                ])
                                ->required()
                                ->default('prepaid')
                                ->reactive()
                                ->helperText('Bestimmt, wie die Abrechnung erfolgt'),
                            
                            Forms\Components\Toggle::make('is_active')
                                ->label('Aktiv')
                                ->default(true)
                                ->helperText('Nur aktive Pläne können zugewiesen werden'),
                            
                            Forms\Components\Toggle::make('is_default')
                                ->label('Standard-Plan')
                                ->helperText('Wird automatisch neuen Tenants zugewiesen')
                                ->reactive()
                                ->afterStateUpdated(function ($state, $record) {
                                    if ($state && $record) {
                                        // Deaktiviere andere Standard-Pläne
                                        PricingPlan::where('id', '!=', $record->id)
                                            ->update(['is_default' => false]);
                                        
                                        Notification::make()
                                            ->title('Standard-Plan geändert')
                                            ->success()
                                            ->send();
                                    }
                                }),
                        ]),
                    ]),
                
                Section::make('Preisgestaltung')
                    ->description('Definieren Sie die Kosten für verschiedene Services')
                    ->schema([
                        Grid::make(3)->schema([
                            Forms\Components\TextInput::make('price_per_minute_cents')
                                ->label('Preis pro Minute (Cents)')
                                ->numeric()
                                ->required()
                                ->default(42)
                                ->suffix('Cents')
                                ->helperText('42 Cents = 0,42 €'),
                            
                            Forms\Components\TextInput::make('price_per_call_cents')
                                ->label('Preis pro Anruf (Cents)')
                                ->numeric()
                                ->required()
                                ->default(10)
                                ->suffix('Cents'),
                            
                            Forms\Components\TextInput::make('price_per_appointment_cents')
                                ->label('Preis pro Termin (Cents)')
                                ->numeric()
                                ->required()
                                ->default(100)
                                ->suffix('Cents'),
                        ]),
                        
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('setup_fee_cents')
                                ->label('Einrichtungsgebühr (Cents)')
                                ->numeric()
                                ->default(0)
                                ->suffix('Cents'),
                            
                            Forms\Components\TextInput::make('billing_increment_seconds')
                                ->label('Abrechnungstakt (Sekunden)')
                                ->numeric()
                                ->default(1)
                                ->suffix('Sek.')
                                ->helperText('1 = Sekundengenau, 60 = Minutengenau'),
                        ]),
                    ]),
                
                Section::make('Paket-Optionen')
                    ->description('Für Paket-basierte Abrechnungsmodelle')
                    ->visible(fn (Forms\Get $get) => in_array($get('billing_type'), ['postpaid', 'hybrid']))
                    ->schema([
                        Grid::make(3)->schema([
                            Forms\Components\TextInput::make('included_minutes')
                                ->label('Inklusive Minuten')
                                ->numeric()
                                ->default(0)
                                ->suffix('Min.')
                                ->helperText('Anzahl der im Paket enthaltenen Minuten'),
                            
                            Forms\Components\TextInput::make('monthly_fee')
                                ->label('Monatliche Grundgebühr')
                                ->numeric()
                                ->default(0)
                                ->suffix('€'),
                            
                            Forms\Components\TextInput::make('overage_rate_cents')
                                ->label('Überschreitungspreis (Cents/Min)')
                                ->numeric()
                                ->suffix('Cents')
                                ->helperText('Preis für Minuten über dem Paket'),
                        ]),
                    ]),
                
                Section::make('Mengenrabatte')
                    ->description('Automatische Rabatte bei hohem Volumen')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('volume_discount_percent')
                                ->label('Rabatt (%)')
                                ->numeric()
                                ->default(0)
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100),
                            
                            Forms\Components\TextInput::make('volume_threshold_minutes')
                                ->label('Ab Minuten')
                                ->numeric()
                                ->default(0)
                                ->suffix('Min.')
                                ->helperText('Rabatt gilt ab dieser Minutenanzahl'),
                        ]),
                    ]),
                
                Section::make('Features')
                    ->description('Zusätzliche Features und Einstellungen')
                    ->schema([
                        Forms\Components\KeyValue::make('features')
                            ->label('Features')
                            ->keyLabel('Feature')
                            ->valueLabel('Wert')
                            ->addButtonLabel('Feature hinzufügen')
                            ->reorderable()
                            ->helperText('Zusätzliche Features als Key-Value Paare'),
                    ]),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),
                
                Tables\Columns\BadgeColumn::make('billing_type')
                    ->label('Typ')
                    ->colors([
                        'primary' => 'prepaid',
                        'success' => 'postpaid',
                        'warning' => 'hybrid',
                    ]),
                
                Tables\Columns\TextColumn::make('price_per_minute_cents')
                    ->label('€/Min')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' €')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('monthly_fee')
                    ->label('Grundgebühr')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state, 2) . ' €' : '-')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('included_minutes')
                    ->label('Inkl. Min.')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state) : '-')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Standard')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                
                Tables\Columns\TextColumn::make('tenants_count')
                    ->label('Tenants')
                    ->counts('tenants')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('billing_type')
                    ->label('Abrechnungstyp')
                    ->options([
                        'prepaid' => 'Prepaid',
                        'postpaid' => 'Postpaid',
                        'hybrid' => 'Hybrid',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Standard'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplizieren')
                    ->icon('heroicon-o-document-duplicate')
                    ->requiresConfirmation()
                    ->action(function (PricingPlan $record) {
                        $newPlan = $record->replicate();
                        $newPlan->name = $record->name . ' (Kopie)';
                        $newPlan->slug = \Str::slug($newPlan->name);
                        $newPlan->is_default = false;
                        $newPlan->save();
                        
                        Notification::make()
                            ->title('Preisplan dupliziert')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->before(function ($records) {
                            // Verhindere Löschen von Plänen mit Tenants
                            foreach ($records as $record) {
                                if ($record->tenants()->count() > 0) {
                                    Notification::make()
                                        ->title('Löschen nicht möglich')
                                        ->body("Plan '{$record->name}' wird von Tenants verwendet")
                                        ->danger()
                                        ->send();
                                    
                                    return false;
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
    }
    
    public static function getRelations(): array
    {
        return [
            // TenantRelationManager könnte hier hinzugefügt werden
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPricingPlans::route('/'),
            'create' => Pages\CreatePricingPlan::route('/create'),
            'view' => Pages\ViewPricingPlan::route('/{record}'),
            'edit' => Pages\EditPricingPlan::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}