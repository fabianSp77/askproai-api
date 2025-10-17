<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceStaffAssignmentResource\Pages;
use App\Models\ServiceStaffAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Service Staff Assignment Resource
 *
 * Allows admins to assign staff members to specific services with
 * priority ordering. Used by the service_staff assignment model.
 */
class ServiceStaffAssignmentResource extends Resource
{
    protected static ?string $model = ServiceStaffAssignment::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Mitarbeiter-Zuordnung';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Service-Mitarbeiter';
    protected static ?string $modelLabel = 'Service-Mitarbeiter-Zuordnung';
    protected static ?string $pluralModelLabel = 'Service-Mitarbeiter-Zuordnungen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Zuordnung')
                    ->description('Weisen Sie einen Mitarbeiter einem Service zu')
                    ->schema([
                        Grid::make(3)->schema([
                            Forms\Components\Select::make('company_id')
                                ->label('Firma')
                                ->relationship('company', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(fn (callable $set) => $set('service_id', null))
                                ->helperText('Wählen Sie zuerst die Firma'),

                            Forms\Components\Select::make('service_id')
                                ->label('Service')
                                ->options(function (callable $get) {
                                    $companyId = $get('company_id');
                                    if (!$companyId) {
                                        return [];
                                    }
                                    return \App\Models\Service::where('company_id', $companyId)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->required()
                                ->searchable()
                                ->helperText('Welchen Service kann der Mitarbeiter durchführen?'),

                            Forms\Components\Select::make('staff_id')
                                ->label('Mitarbeiter')
                                ->options(function (callable $get) {
                                    $companyId = $get('company_id');
                                    if (!$companyId) {
                                        return [];
                                    }
                                    return \App\Models\Staff::where('company_id', $companyId)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->required()
                                ->searchable()
                                ->helperText('Welcher Mitarbeiter ist qualifiziert?'),
                        ]),
                    ]),

                Section::make('Priorität und Status')
                    ->description('Legen Sie die Priorität und den Status fest')
                    ->schema([
                        Grid::make(3)->schema([
                            Forms\Components\TextInput::make('priority_order')
                                ->label('Priorität')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(999)
                                ->helperText('Niedrigere Zahl = höhere Priorität (0 = höchste)'),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Aktiv')
                                ->default(true)
                                ->required()
                                ->helperText('Nur aktive Zuordnungen werden verwendet'),

                            Forms\Components\Placeholder::make('priority_explanation')
                                ->label('💡 Prioritäts-Tipp')
                                ->content('Bei mehreren qualifizierten Mitarbeitern wird der mit der niedrigsten Prioritätszahl zuerst zugeordnet.'),
                        ]),
                    ])->columns(3),

                Section::make('Zeitliche Gültigkeit (optional)')
                    ->description('Legen Sie fest, wann diese Zuordnung gültig ist')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('effective_from')
                                ->label('Gültig ab')
                                ->helperText('Leer = sofort gültig'),

                            Forms\Components\DatePicker::make('effective_until')
                                ->label('Gültig bis')
                                ->helperText('Leer = unbegrenzt gültig'),
                        ]),
                    ])->collapsible()->collapsed(),

                Section::make('ℹ️ Hinweis')
                    ->description('Wichtige Informationen')
                    ->schema([
                        Forms\Components\Placeholder::make('usage_info')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="space-y-2 text-sm">
                                    <div class="p-3 bg-amber-50 rounded-lg">
                                        <strong>⚠️ Nur für "Service-Staff"-Modell:</strong>
                                        <p class="mt-1 text-gray-600">Diese Zuordnungen werden nur verwendet, wenn die Firma das Modell "🎓 Nur Qualifizierte" (service_staff) konfiguriert hat.</p>
                                        <p class="mt-1 text-xs text-gray-500">→ Prüfen Sie die "Firmen-Konfiguration" im Menü</p>
                                    </div>
                                    <div class="p-3 bg-blue-50 rounded-lg">
                                        <strong>📋 Beispiel-Szenario (Friseur):</strong>
                                        <p class="mt-1 text-gray-600">Service: "Herrenschnitt" → Mitarbeiter: Max (Priorität 0), Anna (Priorität 1)</p>
                                        <p class="mt-1 text-xs text-gray-500">Bei einer Buchung wird zuerst Max versucht, falls nicht verfügbar dann Anna.</p>
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

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ServiceStaffAssignment $record): string =>
                        $record->service->description ?? ''
                    ),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('priority_order')
                    ->label('Priorität')
                    ->sortable()
                    ->colors([
                        'success' => 0,
                        'primary' => fn ($state) => $state > 0 && $state <= 5,
                        'warning' => fn ($state) => $state > 5,
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('effective_from')
                    ->label('Gültig ab')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('effective_until')
                    ->label('Gültig bis')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->label('Firma')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('service')
                    ->label('Service')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('staff')
                    ->label('Mitarbeiter')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload(),

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
            ->defaultSort('priority_order', 'asc')
            ->emptyStateHeading('Keine Zuordnungen vorhanden')
            ->emptyStateDescription('Weisen Sie Mitarbeiter zu Services zu, um das "Nur Qualifizierte"-Modell zu verwenden.')
            ->emptyStateIcon('heroicon-o-user-plus');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceStaffAssignments::route('/'),
            'create' => Pages\CreateServiceStaffAssignment::route('/create'),
            'edit' => Pages\EditServiceStaffAssignment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::getModel()::where('is_active', true)->count();
            return $count > 0 ? (string) $count : null;
        } catch (QueryException $exception) {
            // Table doesn't exist yet - log and return null instead of crashing
            if ($exception->getCode() !== '42S02') {
                throw $exception;
            }

            Log::warning('[ServiceStaffAssignmentResource] Table not found. Returning null for badge.', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
