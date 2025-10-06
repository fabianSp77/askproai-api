<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BalanceBonusTierResource\Pages;
use App\Filament\Resources\BalanceBonusTierResource\RelationManagers;
use App\Models\BalanceBonusTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Cache;

class BalanceBonusTierResource extends Resource
{
    protected static ?string $model = BalanceBonusTier::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Bonus-Stufen';

    protected static ?string $modelLabel = 'Bonus-Stufe';

    protected static ?string $pluralModelLabel = 'Bonus-Stufen';

    protected static ?string $navigationGroup = 'Abrechnung';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Stufen-Konfiguration')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Tier Name')
                                    ->placeholder('e.g., Bronze, Silver, Gold, Platinum')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                Forms\Components\ColorPicker::make('color')
                                    ->label('Tier Color')
                                    ->default('#3B82F6')
                                    ->helperText('Visual color for this tier'),

                                Forms\Components\Select::make('icon')
                                    ->label('Tier Icon')
                                    ->options([
                                        'trophy' => '🏆 Trophy',
                                        'medal' => '🥇 Medal',
                                        'star' => '⭐ Star',
                                        'crown' => '👑 Crown',
                                        'gem' => '💎 Gem',
                                        'fire' => '🔥 Fire',
                                    ])
                                    ->default('trophy'),
                            ])
                            ->columns(4),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->placeholder('Beschreiben Sie die Vorteile dieser Stufe')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Bonus-Einstellungen')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('min_amount')
                                    ->label('Minimum Amount (€)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('€')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Minimum topup amount for this tier'),

                                Forms\Components\TextInput::make('max_amount')
                                    ->label('Maximum Amount (€)')
                                    ->numeric()
                                    ->prefix('€')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Leave empty for unlimited')
                                    ->placeholder('Unlimited'),

                                Forms\Components\TextInput::make('bonus_percentage')
                                    ->label('Bonus Percentage (%)')
                                    ->required()
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->helperText('Bonus percentage for this tier'),

                                Forms\Components\TextInput::make('fixed_bonus')
                                    ->label('Fixed Bonus (€)')
                                    ->numeric()
                                    ->prefix('€')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Optional fixed bonus amount')
                                    ->placeholder('0.00'),
                            ])
                            ->columns(4),
                    ]),

                Section::make('Gültigkeit & Status')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Forms\Components\DatePicker::make('valid_from')
                                    ->label('Valid From')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection(),

                                Forms\Components\DatePicker::make('valid_until')
                                    ->label('Valid Until')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection()
                                    ->after('valid_from'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Enable or disable this tier'),

                                Forms\Components\Toggle::make('is_promotional')
                                    ->label('Promotional')
                                    ->default(false)
                                    ->inline(false)
                                    ->helperText('Mark as limited-time promotion'),
                            ])
                            ->columns(4),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tier Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon(fn ($record) => match($record->icon ?? 'trophy') {
                        'medal' => 'heroicon-o-star',
                        'star' => 'heroicon-o-star',
                        'crown' => 'heroicon-o-sparkles',
                        'gem' => 'heroicon-o-cube-transparent',
                        'fire' => 'heroicon-o-fire',
                        default => 'heroicon-o-trophy'
                    })
                    ->color(fn ($record) => match(strtolower($record->name)) {
                        'bronze' => 'warning',
                        'silver' => 'gray',
                        'gold' => 'warning',
                        'platinum' => 'primary',
                        'diamond' => 'success',
                        default => 'secondary'
                    }),

                Tables\Columns\TextColumn::make('min_amount')
                    ->label('Min Amount')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('max_amount')
                    ->label('Max Amount')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->placeholder('Unlimited'),

                Tables\Columns\TextColumn::make('bonus_percentage')
                    ->label('Bonus %')
                    ->suffix('%')
                    ->numeric(2)
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 20 => 'success',
                        $state >= 10 => 'warning',
                        $state >= 5 => 'primary',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('fixed_bonus')
                    ->label('Fixed Bonus')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_promotional')
                    ->label('Promo')
                    ->boolean()
                    ->trueIcon('heroicon-o-megaphone')
                    ->falseIcon('')
                    ->trueColor('danger')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Valid From')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : null),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Times Used')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->default(0)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('min_amount', 'asc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->placeholder('All tiers')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                TernaryFilter::make('is_promotional')
                    ->label('Promotional')
                    ->placeholder('All types')
                    ->trueLabel('Promotional only')
                    ->falseLabel('Regular only'),

                Filter::make('valid')
                    ->label('Currently Valid')
                    ->query(fn (Builder $query): Builder => $query
                        ->where(fn ($query) => $query
                            ->whereNull('valid_from')
                            ->orWhere('valid_from', '<=', now()))
                        ->where(fn ($query) => $query
                            ->whereNull('valid_until')
                            ->orWhere('valid_until', '>=', now())))
                    ->toggle(),

                Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('valid_until')
                        ->where('valid_until', '<', now()))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton(),
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function ($record) {
                        $newTier = $record->replicate();
                        $newTier->name = $record->name . ' (Copy)';
                        $newTier->is_active = false;
                        $newTier->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Tier Duplicated')
                            ->success()
                            ->send();
                    })
                    ->iconButton(),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('createStandardTiers')
                    ->label('Standard-Stufen erstellen')
                    ->icon('heroicon-o-sparkles')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Standard-Bonusstufen erstellen')
                    ->modalDescription('Dies erstellt Bronze-, Silber-, Gold- und Platin-Stufen mit Standard-Bonusprozentsätzen.')
                    ->action(function () {
                        $tiers = [
                            ['name' => 'Bronze', 'min_amount' => 10, 'max_amount' => 49.99, 'bonus_percentage' => 5, 'icon' => 'medal'],
                            ['name' => 'Silver', 'min_amount' => 50, 'max_amount' => 99.99, 'bonus_percentage' => 10, 'icon' => 'star'],
                            ['name' => 'Gold', 'min_amount' => 100, 'max_amount' => 249.99, 'bonus_percentage' => 15, 'icon' => 'crown'],
                            ['name' => 'Platinum', 'min_amount' => 250, 'max_amount' => null, 'bonus_percentage' => 20, 'icon' => 'gem'],
                        ];

                        $created = 0;
                        foreach ($tiers as $tier) {
                            if (!BalanceBonusTier::where('name', $tier['name'])->exists()) {
                                BalanceBonusTier::create(array_merge($tier, [
                                    'description' => "{$tier['name']} tier with {$tier['bonus_percentage']}% bonus",
                                    'is_active' => true,
                                ]));
                                $created++;
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Standard Tiers Created')
                            ->body("Created {$created} standard bonus tiers.")
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListBalanceBonusTiers::route('/'),
            'create' => Pages\CreateBalanceBonusTier::route('/create'),
            'edit' => Pages\EditBalanceBonusTier::route('/{record}/edit'),
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
}