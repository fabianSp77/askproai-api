<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyAssignmentConfigResource\Pages;
use App\Models\CompanyAssignmentConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

/**
 * Company Assignment Configuration Resource
 *
 * Allows admins to configure which staff assignment business model
 * each company uses (any_staff or service_staff).
 */
class CompanyAssignmentConfigResource extends Resource
{
    protected static ?string $model = CompanyAssignmentConfig::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Mitarbeiter-Zuordnung';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Firmen-Konfiguration';
    protected static ?string $modelLabel = 'Zuordnungsmodell';
    protected static ?string $pluralModelLabel = 'Zuordnungsmodelle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Firma und Modell')
                    ->description('Wählen Sie das Zuordnungsmodell für die Firma')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Firma')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->unique(ignoreRecord: true)
                            ->helperText('Jede Firma kann nur eine aktive Konfiguration haben'),

                        Forms\Components\Select::make('assignment_model')
                            ->label('Zuordnungsmodell')
                            ->options([
                                'any_staff' => '🎯 Egal wer - Erster verfügbarer Mitarbeiter',
                                'service_staff' => '🎓 Nur Qualifizierte - Service-spezifische Mitarbeiter',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Bestimmt, wie Mitarbeiter zu Terminen zugeordnet werden'),

                        Forms\Components\Select::make('fallback_model')
                            ->label('Fallback-Modell (optional)')
                            ->options([
                                'any_staff' => '🎯 Egal wer',
                                'service_staff' => '🎓 Nur Qualifizierte',
                            ])
                            ->native(false)
                            ->helperText('Wird verwendet, wenn das primäre Modell keinen Mitarbeiter findet'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->required()
                            ->helperText('Nur aktive Konfigurationen werden verwendet'),
                    ])->columns(2),

                Section::make('Erweiterte Einstellungen')
                    ->description('Optional: Zusätzliche Metadaten (JSON-Format)')
                    ->schema([
                        Forms\Components\Textarea::make('config_metadata')
                            ->label('Konfigurationsmetadaten')
                            ->rows(4)
                            ->helperText('z.B.: {"assignment_timeout": 30, "priority_override": true}')
                            ->columnSpanFull(),
                    ])->collapsible()->collapsed(),

                Section::make('ℹ️ Modell-Erklärung')
                    ->description('Welches Modell ist das Richtige?')
                    ->schema([
                        Forms\Components\Placeholder::make('model_explanation')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="space-y-3 text-sm">
                                    <div class="p-3 bg-blue-50 rounded-lg">
                                        <strong>🎯 Egal wer (any_staff):</strong>
                                        <p class="mt-1 text-gray-600">Für Firmen, bei denen jeder Mitarbeiter jeden Service durchführen kann. Der erste verfügbare Mitarbeiter wird zugeordnet.</p>
                                        <p class="mt-1 text-xs text-gray-500">Beispiel: Allgemeine Beratungsstellen, Call-Center</p>
                                    </div>
                                    <div class="p-3 bg-green-50 rounded-lg">
                                        <strong>🎓 Nur Qualifizierte (service_staff):</strong>
                                        <p class="mt-1 text-gray-600">Für Firmen mit Service-spezifischen Qualifikationen. Nur qualifizierte Mitarbeiter werden für Services zugeordnet.</p>
                                        <p class="mt-1 text-xs text-gray-500">Beispiel: Friseure (nicht jeder kann jede Dienstleistung), Werkstätten, medizinische Praxen</p>
                                        <p class="mt-1 text-xs font-semibold text-green-700">⚠️ Benötigt Konfiguration in "Service-Mitarbeiter-Zuordnungen"</p>
                                    </div>
                                </div>
                            ')),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('assignment_model')
                    ->label('Modell')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'any_staff' => '🎯 Egal wer',
                        'service_staff' => '🎓 Nur Qualifizierte',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'any_staff',
                        'success' => 'service_staff',
                    ]),

                Tables\Columns\BadgeColumn::make('fallback_model')
                    ->label('Fallback')
                    ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                        'any_staff' => '🎯 Egal wer',
                        'service_staff' => '🎓 Nur Qualifizierte',
                        default => $state,
                    } : '-')
                    ->colors([
                        'gray' => fn ($state) => $state === null,
                        'warning' => fn ($state) => $state !== null,
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('assignment_model')
                    ->label('Modell')
                    ->options([
                        'any_staff' => '🎯 Egal wer',
                        'service_staff' => '🎓 Nur Qualifizierte',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->placeholder('Alle anzeigen')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Keine Konfigurationen vorhanden')
            ->emptyStateDescription('Erstellen Sie eine Konfiguration, um festzulegen, wie Mitarbeiter zu Terminen zugeordnet werden.')
            ->emptyStateIcon('heroicon-o-cog-6-tooth');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanyAssignmentConfigs::route('/'),
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
