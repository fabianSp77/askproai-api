<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyServicePricingResource\Pages;
use App\Filament\Resources\CompanyServicePricingResource\Widgets;
use App\Models\CompanyServicePricing;
use App\Models\ServiceFeeTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CompanyServicePricingResource extends Resource
{
    protected static ?string $model = CompanyServicePricing::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Kundenpreise';

    protected static ?string $modelLabel = 'Kundenpreis';

    protected static ?string $pluralModelLabel = 'Kundenpreise';

    protected static ?string $navigationGroup = 'Abrechnung';

    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kunde & Service')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Unternehmen')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('template_id')
                            ->label('Service-Vorlage')
                            ->relationship('template', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $template = ServiceFeeTemplate::find($state);
                                    if ($template) {
                                        $set('price', $template->default_price);
                                    }
                                }
                            })
                            ->helperText('Vorlage auswählen oder leer lassen für individuellen Service'),
                    ]),

                Forms\Components\Section::make('Individueller Service')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('custom_code')
                                ->label('Eigener Code')
                                ->maxLength(50)
                                ->placeholder('z.B. CUSTOM_XYZ'),

                            Forms\Components\TextInput::make('custom_name')
                                ->label('Eigener Name')
                                ->maxLength(255)
                                ->placeholder('z.B. Spezial-Integration'),
                        ]),

                        Forms\Components\Textarea::make('custom_description')
                            ->label('Eigene Beschreibung')
                            ->rows(2),
                    ])
                    ->collapsed()
                    ->visible(fn (Forms\Get $get) => !$get('template_id')),

                Forms\Components\Section::make('Preisgestaltung')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('price')
                                ->label('Preis')
                                ->numeric()
                                ->prefix('EUR')
                                ->required()
                                ->minValue(0)
                                ->step(0.01)
                                ->reactive(),

                            Forms\Components\TextInput::make('discount_percentage')
                                ->label('Rabatt %')
                                ->numeric()
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.1)
                                ->reactive(),

                            Forms\Components\Placeholder::make('final_price_display')
                                ->label('Endpreis')
                                ->content(function (Forms\Get $get) {
                                    $price = (float) ($get('price') ?? 0);
                                    $discount = (float) ($get('discount_percentage') ?? 0);
                                    $final = $price * (1 - $discount / 100);
                                    return number_format($final, 2, ',', '.') . ' EUR';
                                }),
                        ]),
                    ]),

                Forms\Components\Section::make('Gültigkeitszeitraum')
                    ->description('Der Preis gilt nur innerhalb des angegebenen Zeitraums')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('effective_from')
                                ->label('Gültig ab')
                                ->required()
                                ->default(now()),

                            Forms\Components\DatePicker::make('effective_until')
                                ->label('Gültig bis')
                                ->helperText('Leer = unbefristet'),
                        ]),
                    ]),

                Forms\Components\Section::make('Vereinbarung')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('contract_reference')
                                ->label('Vertragsnummer')
                                ->maxLength(100)
                                ->placeholder('z.B. VT-2026-001'),

                            Forms\Components\TextInput::make('approved_by_name')
                                ->label('Genehmigt durch')
                                ->maxLength(255)
                                ->placeholder('Name der genehmigenden Person'),
                        ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Interne Notizen')
                            ->rows(3)
                            ->placeholder('Notizen zur Vereinbarung...'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])->collapsed(),
            ]);
    }

    /**
     * Optimize query with eager loading to prevent N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company', 'template']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                // Service-Name mit Beschreibung
                Tables\Columns\TextColumn::make('template.name')
                    ->label('Service')
                    ->formatStateUsing(function ($state, $record) {
                        $name = $state ?? $record->custom_name ?? 'Individueller Service';
                        $type = $record->template?->pricing_type;
                        $typeLabel = match ($type) {
                            'monthly' => '/ Monat',
                            'yearly' => '/ Jahr',
                            'per_hour' => '/ Stunde',
                            'per_unit' => '/ Einheit',
                            default => 'einmalig',
                        };
                        return new HtmlString(
                            '<div class="font-medium">' . e($name) . '</div>' .
                            '<div class="text-xs text-gray-500">' . e($typeLabel) . '</div>'
                        );
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('template', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhere('custom_name', 'like', "%{$search}%");
                    })
                    ->sortable(),

                // Preis mit Rabatt-Info
                Tables\Columns\TextColumn::make('final_price')
                    ->label('Preis')
                    ->formatStateUsing(function ($state, $record) {
                        $finalPrice = number_format($state, 0, ',', '.') . ' €';
                        if ($record->discount_percentage > 0) {
                            $originalPrice = number_format($record->price, 0, ',', '.') . ' €';
                            return new HtmlString(
                                '<div class="font-semibold text-primary-600">' . $finalPrice . '</div>' .
                                '<div class="text-xs text-gray-400 line-through">' . $originalPrice . '</div>' .
                                '<div class="text-xs text-success-600">-' . intval($record->discount_percentage) . '% Rabatt</div>'
                            );
                        }
                        return new HtmlString('<div class="font-semibold">' . $finalPrice . '</div>');
                    })
                    ->sortable(),

                // Gültigkeitszeitraum
                Tables\Columns\TextColumn::make('effective_from')
                    ->label('Gültigkeit')
                    ->formatStateUsing(function ($state, $record) {
                        $from = $state->format('d.m.Y');
                        $until = $record->effective_until?->format('d.m.Y') ?? 'unbefristet';

                        // Status bestimmen
                        $now = now();
                        $isActive = $record->is_active;
                        $isExpired = $record->effective_until && $record->effective_until < $now;
                        $isFuture = $record->effective_from > $now;
                        $expiresSoon = $record->expiresSoon(30);

                        if (!$isActive) {
                            $badge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Deaktiviert</span>';
                        } elseif ($isExpired) {
                            $badge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Abgelaufen</span>';
                        } elseif ($isFuture) {
                            $badge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">Ab ' . $from . '</span>';
                        } elseif ($expiresSoon) {
                            $badge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-warning-100 text-warning-700">Läuft bald ab</span>';
                        } else {
                            $badge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-success-100 text-success-700">Aktiv</span>';
                        }

                        return new HtmlString(
                            '<div class="text-sm">' . $from . ' → ' . $until . '</div>' .
                            '<div class="mt-1">' . $badge . '</div>'
                        );
                    })
                    ->sortable(),

                // Vertragsnummer (optional, kompakt)
                Tables\Columns\TextColumn::make('contract_reference')
                    ->label('Vertrag')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Notizen (Tooltip)
                Tables\Columns\TextColumn::make('notes')
                    ->label('Info')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->notes)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('template_id')
                    ->label('Service')
                    ->relationship('template', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktiv',
                        'expiring' => 'Läuft bald ab',
                        'expired' => 'Abgelaufen',
                        'future' => 'Zukünftig',
                        'inactive' => 'Deaktiviert',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value'] ?? null) {
                            'active' => $query->where('is_active', true)->currentlyValid(),
                            'expiring' => $query->expiringSoon(30),
                            'expired' => $query->whereNotNull('effective_until')->where('effective_until', '<', now()),
                            'future' => $query->where('effective_from', '>', now()),
                            'inactive' => $query->where('is_active', false),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplizieren')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(function ($record) {
                            $new = $record->replicate();
                            $new->effective_from = now();
                            $new->effective_until = null;
                            $new->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Preis dupliziert')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('extend')
                        ->label('Verlängern')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn ($record) => $record->effective_until !== null)
                        ->form([
                            Forms\Components\DatePicker::make('new_until')
                                ->label('Neues Enddatum')
                                ->required()
                                ->default(fn ($record) => $record->effective_until?->addMonths(3)),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update(['effective_until' => $data['new_until']]);

                            \Filament\Notifications\Notification::make()
                                ->title('Laufzeit verlängert')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make(),
                ])->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // Gruppierung nach Unternehmen
            ->groups([
                Group::make('company_id')
                    ->label('Unternehmen')
                    ->titlePrefixedWithLabel(false)
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn ($record) => $record->company?->name ?? 'Unbekannt'),
            ])
            ->defaultGroup('company_id')
            ->defaultSort('company_id')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyServicePricings::route('/'),
            'create' => Pages\CreateCompanyServicePricing::route('/create'),
            'edit' => Pages\EditCompanyServicePricing::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\PricingStatsOverview::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $expiring = static::getModel()::active()->expiringSoon(30)->count();
        return $expiring > 0 ? (string) $expiring : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
