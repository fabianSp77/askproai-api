<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceFeeTemplateResource\Pages;
use App\Models\ServiceFeeTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceFeeTemplateResource extends Resource
{
    protected static ?string $model = ServiceFeeTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Preiskatalog';

    protected static ?string $modelLabel = 'Preiskatalog-Vorlage';

    protected static ?string $pluralModelLabel = 'Preiskatalog-Vorlagen';

    protected static ?string $navigationGroup = 'Abrechnung';

    protected static ?int $navigationSort = 23;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grunddaten')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('code')
                                ->label('Code')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50)
                                ->placeholder('z.B. CHANGE_FLOW')
                                ->helperText('Eindeutiger interner Bezeichner'),

                            Forms\Components\TextInput::make('name')
                                ->label('Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('z.B. Call Flow Änderung'),
                        ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->maxLength(2000)
                            ->placeholder('Beschreibung für Kunden und interne Nutzung...'),
                    ]),

                Forms\Components\Section::make('Kategorisierung')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('category')
                                ->label('Kategorie')
                                ->options(ServiceFeeTemplate::getCategoryOptions())
                                ->required()
                                ->native(false),

                            Forms\Components\TextInput::make('subcategory')
                                ->label('Unterkategorie')
                                ->maxLength(50)
                                ->placeholder('z.B. flow, config, agent'),
                        ]),
                    ]),

                Forms\Components\Section::make('Preisgestaltung')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('default_price')
                                ->label('Standard-Preis')
                                ->numeric()
                                ->prefix('EUR')
                                ->required()
                                ->minValue(0)
                                ->step(0.01),

                            Forms\Components\Select::make('pricing_type')
                                ->label('Abrechnungsart')
                                ->options(ServiceFeeTemplate::getPricingTypeOptions())
                                ->default(ServiceFeeTemplate::PRICING_ONE_TIME)
                                ->required()
                                ->native(false)
                                ->reactive(),

                            Forms\Components\TextInput::make('unit_name')
                                ->label('Einheitsname')
                                ->maxLength(50)
                                ->placeholder('z.B. GB, Ticket')
                                ->visible(fn (Forms\Get $get) => $get('pricing_type') === ServiceFeeTemplate::PRICING_PER_UNIT),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('min_price')
                                ->label('Mindestpreis')
                                ->numeric()
                                ->prefix('EUR')
                                ->step(0.01)
                                ->helperText('Optional: Minimum bei Verhandlungen'),

                            Forms\Components\TextInput::make('max_price')
                                ->label('Höchstpreis')
                                ->numeric()
                                ->prefix('EUR')
                                ->step(0.01)
                                ->helperText('Optional: Maximum bei individuellen Anpassungen'),
                        ]),
                    ]),

                Forms\Components\Section::make('Optionen')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Toggle::make('is_negotiable')
                                ->label('Preis verhandelbar')
                                ->default(true)
                                ->helperText('Kundenindividuelle Preise möglich'),

                            Forms\Components\Toggle::make('requires_approval')
                                ->label('Freigabe erforderlich')
                                ->default(false)
                                ->helperText('Erfordert Genehmigung vor Abrechnung'),

                            Forms\Components\Toggle::make('is_featured')
                                ->label('Hervorgehoben')
                                ->default(false)
                                ->helperText('Prominent in Listen anzeigen'),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('sort_order')
                                ->label('Sortierung')
                                ->numeric()
                                ->default(0)
                                ->helperText('Kleinere Zahlen zuerst'),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Aktiv')
                                ->default(true)
                                ->helperText('Inaktive Vorlagen werden ausgeblendet'),
                        ]),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Kategorie')
                    ->colors(ServiceFeeTemplate::getCategoryColors())
                    ->formatStateUsing(fn ($state) => ServiceFeeTemplate::getCategoryOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('default_price')
                    ->label('Preis')
                    ->money('EUR', locale: 'de')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('pricing_type')
                    ->label('Art')
                    ->colors([
                        'success' => ServiceFeeTemplate::PRICING_ONE_TIME,
                        'warning' => ServiceFeeTemplate::PRICING_MONTHLY,
                        'info' => ServiceFeeTemplate::PRICING_YEARLY,
                        'primary' => ServiceFeeTemplate::PRICING_PER_HOUR,
                        'gray' => ServiceFeeTemplate::PRICING_PER_UNIT,
                    ])
                    ->formatStateUsing(fn ($state) => ServiceFeeTemplate::getPricingTypeOptions()[$state] ?? $state),

                Tables\Columns\IconColumn::make('is_negotiable')
                    ->label('Verhandelbar')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options(ServiceFeeTemplate::getCategoryOptions()),

                Tables\Filters\SelectFilter::make('pricing_type')
                    ->label('Abrechnungsart')
                    ->options(ServiceFeeTemplate::getPricingTypeOptions()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Hervorgehoben'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplizieren')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (ServiceFeeTemplate $record) {
                        $new = $record->replicate();
                        $new->code = $record->code . '_COPY';
                        $new->name = $record->name . ' (Kopie)';
                        $new->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Vorlage dupliziert')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('category')
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceFeeTemplates::route('/'),
            'create' => Pages\CreateServiceFeeTemplate::route('/create'),
            'edit' => Pages\EditServiceFeeTemplate::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_active', true)->count();
    }
}
