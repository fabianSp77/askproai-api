<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceCaseCategoryResource\Pages;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ServiceCaseCategoryResource extends Resource
{
    protected static ?string $model = ServiceCaseCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Kategorien';
    protected static ?string $modelLabel = 'Kategorie';
    protected static ?string $pluralModelLabel = 'Kategorien';
    protected static ?int $navigationSort = 11;

    /**
     * Only show in navigation when Service Gateway is enabled.
     * @see config/gateway.php 'mode_enabled'
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('gateway.mode_enabled', false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Status Panel (nur auf View-Seite, nicht Edit)
                Forms\Components\ViewField::make('category_status')
                    ->view('filament.forms.components.category-status-panel')
                    ->columnSpanFull()
                    ->dehydrated(false)
                    ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\ViewRecord),

                // Tab-basierte Struktur
                Forms\Components\Tabs::make('Kategorie-Konfiguration')
                    ->tabs([
                        // Tab 1: Basis-Information
                        Forms\Components\Tabs\Tab::make('Basis')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Kategorie-Details')
                                    ->description('Grundlegende Informationen zur Kategorie')
                                    ->icon('heroicon-o-folder')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                                if (!empty($state)) {
                                                    $set('slug', Str::slug($state));
                                                }
                                            })
                                            ->helperText('Der angezeigte Name in Listen und Dropdowns'),

                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->helperText('URL-freundlicher Identifier (wird automatisch generiert)'),

                                        Forms\Components\Select::make('parent_id')
                                            ->label('Übergeordnete Kategorie')
                                            ->relationship('parent', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Leer lassen für Root-Kategorie, oder übergeordnete Kategorie wählen'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Beschreibung')
                                    ->icon('heroicon-o-document-text')
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\Textarea::make('description')
                                            ->label('Interne Beschreibung')
                                            ->rows(4)
                                            ->columnSpanFull()
                                            ->helperText('Wird nur intern angezeigt, nicht für Kunden sichtbar'),
                                    ]),
                            ]),

                        // Tab 2: KI-Matching
                        Forms\Components\Tabs\Tab::make('KI-Matching')
                            ->icon('heroicon-o-cpu-chip')
                            ->badge(fn (Forms\Get $get) => !empty($get('intent_keywords')) ? count($get('intent_keywords')) : null)
                            ->badgeColor('success')
                            ->schema([
                                Forms\Components\Section::make('Intent-Erkennung')
                                    ->description('Konfiguration für automatische Kategorisierung durch KI')
                                    ->icon('heroicon-o-sparkles')
                                    ->schema([
                                        // Intent Matching Preview
                                        Forms\Components\Placeholder::make('intent_explanation')
                                            ->hiddenLabel()
                                            ->content(new HtmlString('
                                                <div class="p-4 bg-info-50 dark:bg-info-950 rounded-lg border border-info-200 dark:border-info-800">
                                                    <div class="flex items-start gap-3">
                                                        <x-heroicon-o-light-bulb class="w-5 h-5 text-info-500 flex-shrink-0 mt-0.5" />
                                                        <div>
                                                            <p class="text-sm font-medium text-info-900 dark:text-info-100">So funktioniert Intent-Matching</p>
                                                            <p class="text-sm text-info-700 dark:text-info-300 mt-1">
                                                                Wenn ein Anrufer sein Anliegen beschreibt, werden die Schlüsselwörter mit dieser Kategorie abgeglichen.
                                                                Bei ausreichender Übereinstimmung (Confidence) wird die Kategorie automatisch zugewiesen.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            '))
                                            ->columnSpanFull(),

                                        Forms\Components\TagsInput::make('intent_keywords')
                                            ->label('Schlüsselwörter')
                                            ->placeholder('z.B. internet, vpn, netzwerk, verbindung')
                                            ->helperText('Begriffe, die auf diese Kategorie hindeuten. Je mehr, desto besser die Erkennung.')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('confidence_threshold')
                                            ->label('Confidence-Schwellenwert')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(1)
                                            ->step(0.05)
                                            ->default(0.5)
                                            ->suffix('%')
                                            ->formatStateUsing(fn ($state) => $state ? round($state * 100) : 50)
                                            ->dehydrateStateUsing(fn ($state) => $state ? $state / 100 : 0.5)
                                            ->live()
                                            ->helperText(fn (Forms\Get $get) => match (true) {
                                                (float)($get('confidence_threshold') ?? 50) / 100 >= 0.8 => '✅ Hoch: Nur sehr sichere Zuordnungen (weniger False Positives)',
                                                (float)($get('confidence_threshold') ?? 50) / 100 >= 0.5 => '⚠️ Mittel: Ausgewogene Balance zwischen Genauigkeit und Abdeckung',
                                                default => '❌ Niedrig: Viele Zuordnungen, aber auch mehr Fehlzuordnungen möglich',
                                            }),
                                    ])->columns(2),
                            ]),

                        // Tab 3: Standard-Werte
                        Forms\Components\Tabs\Tab::make('Standard-Werte')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Case-Defaults')
                                    ->description('Diese Werte werden automatisch auf neue Cases in dieser Kategorie angewendet')
                                    ->icon('heroicon-o-document-duplicate')
                                    ->schema([
                                        Forms\Components\Select::make('default_case_type')
                                            ->label('Standard-Typ')
                                            ->options([
                                                ServiceCase::TYPE_INCIDENT => '🔴 Störung (Incident)',
                                                ServiceCase::TYPE_REQUEST => '🟡 Anfrage (Request)',
                                                ServiceCase::TYPE_INQUIRY => '🔵 Anliegen (Inquiry)',
                                            ])
                                            ->helperText('Störungen = schnelle Reaktion erforderlich, Anfragen = geplante Bearbeitung, Anliegen = informativ'),

                                        Forms\Components\Select::make('default_priority')
                                            ->label('Standard-Priorität')
                                            ->options([
                                                ServiceCase::PRIORITY_CRITICAL => '🔴 Kritisch (Sofort)',
                                                ServiceCase::PRIORITY_HIGH => '🟠 Hoch (< 4 Stunden)',
                                                ServiceCase::PRIORITY_NORMAL => '🔵 Normal (< 8 Stunden)',
                                                ServiceCase::PRIORITY_LOW => '⚪ Niedrig (< 24 Stunden)',
                                            ])
                                            ->helperText('Bestimmt die SLA-Zeiten und Eskalationsregeln'),
                                    ])->columns(2),

                                Forms\Components\Section::make('SLA-Konfiguration')
                                    ->description('Service Level Agreement - Reaktions- und Lösungszeiten')
                                    ->icon('heroicon-o-clock')
                                    ->schema([
                                        // SLA Preview
                                        Forms\Components\Placeholder::make('sla_preview')
                                            ->hiddenLabel()
                                            ->content(function (Forms\Get $get) {
                                                $response = $get('sla_response_hours') ?? 4;
                                                $resolution = $get('sla_resolution_hours') ?? 24;

                                                return new HtmlString("
                                                    <div class='grid grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg'>
                                                        <div class='text-center'>
                                                            <div class='text-2xl font-bold text-primary-600 dark:text-primary-400'>{$response}h</div>
                                                            <div class='text-xs text-gray-500 dark:text-gray-400'>Erste Reaktion</div>
                                                        </div>
                                                        <div class='text-center'>
                                                            <div class='text-2xl font-bold text-success-600 dark:text-success-400'>{$resolution}h</div>
                                                            <div class='text-xs text-gray-500 dark:text-gray-400'>Lösung</div>
                                                        </div>
                                                    </div>
                                                ");
                                            })
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('sla_response_hours')
                                            ->label('Reaktionszeit (Stunden)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(4)
                                            ->suffix('Stunden')
                                            ->live()
                                            ->helperText('Zeit bis zur ersten Antwort/Bestätigung'),

                                        Forms\Components\TextInput::make('sla_resolution_hours')
                                            ->label('Lösungszeit (Stunden)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(24)
                                            ->suffix('Stunden')
                                            ->live()
                                            ->helperText('Zeit bis zur vollständigen Lösung des Problems'),
                                    ])->columns(2),
                            ]),

                        // Tab 4: Ausgabe & Status
                        Forms\Components\Tabs\Tab::make('Ausgabe')
                            ->icon('heroicon-o-paper-airplane')
                            ->schema([
                                Forms\Components\Section::make('Output-Konfiguration')
                                    ->description('Wie werden Cases in dieser Kategorie zugestellt?')
                                    ->icon('heroicon-o-envelope')
                                    ->schema([
                                        Forms\Components\Select::make('output_configuration_id')
                                            ->label('Ausgabe-Methode')
                                            ->relationship('outputConfiguration', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Wähle eine vorkonfigurierte Ausgabe-Methode (Email/Webhook)'),

                                        // Output Config Summary
                                        Forms\Components\Placeholder::make('output_summary')
                                            ->hiddenLabel()
                                            ->visible(fn (Forms\Get $get) => !empty($get('output_configuration_id')))
                                            ->content(function (Forms\Get $get) {
                                                $configId = $get('output_configuration_id');
                                                if (!$configId) {
                                                    return '';
                                                }

                                                $config = ServiceOutputConfiguration::find($configId);
                                                if (!$config) {
                                                    return '';
                                                }

                                                $typeIcon = match ($config->output_type) {
                                                    'email' => '📧',
                                                    'webhook' => '🔗',
                                                    'hybrid' => '📧🔗',
                                                    default => '❓',
                                                };

                                                $typeLabel = match ($config->output_type) {
                                                    'email' => 'E-Mail',
                                                    'webhook' => 'Webhook',
                                                    'hybrid' => 'E-Mail + Webhook',
                                                    default => 'Unbekannt',
                                                };

                                                $status = $config->is_active
                                                    ? '<span class="text-success-600">✓ Aktiv</span>'
                                                    : '<span class="text-danger-600">✗ Inaktiv</span>';

                                                return new HtmlString("
                                                    <div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>
                                                        <div class='flex items-center justify-between'>
                                                            <div class='flex items-center gap-2'>
                                                                <span class='text-lg'>{$typeIcon}</span>
                                                                <span class='font-medium'>{$typeLabel}</span>
                                                            </div>
                                                            <div class='text-sm'>{$status}</div>
                                                        </div>
                                                    </div>
                                                ");
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Status & Sortierung')
                                    ->icon('heroicon-o-adjustments-horizontal')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Kategorie aktiv')
                                            ->default(true)
                                            ->helperText('Inaktive Kategorien erscheinen nicht in Auswahllisten und können nicht für neue Cases verwendet werden'),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label('Sortierung')
                                            ->numeric()
                                            ->default(0)
                                            ->helperText('Niedrigere Zahlen erscheinen weiter oben in Listen'),
                                    ])->columns(2),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ═══════════════════════════════════════════════════════════
                // 📁 KATEGORIE - Name mit Hierarchie-Anzeige
                // ═══════════════════════════════════════════════════════════
                Tables\Columns\TextColumn::make('name')
                    ->label('Kategorie')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon(fn (ServiceCaseCategory $record) =>
                        $record->children()->exists() ? 'heroicon-o-folder' : 'heroicon-o-document')
                    ->description(fn (ServiceCaseCategory $record): ?string =>
                        $record->parent
                            ? "↳ Unter: {$record->parent->name}"
                            : '📂 Root-Kategorie')
                    ->wrap()
                    ->grow(),

                // ═══════════════════════════════════════════════════════════
                // 🏷️ TYP - Badge mit Icon + deutschem Label (SOFORT LESBAR)
                // ═══════════════════════════════════════════════════════════
                Tables\Columns\TextColumn::make('default_case_type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        ServiceCase::TYPE_INCIDENT => 'Störung',
                        ServiceCase::TYPE_REQUEST => 'Anfrage',
                        ServiceCase::TYPE_INQUIRY => 'Anliegen',
                        default => '—',
                    })
                    ->icon(fn (?string $state): ?string => match ($state) {
                        ServiceCase::TYPE_INCIDENT => 'heroicon-o-exclamation-triangle',
                        ServiceCase::TYPE_REQUEST => 'heroicon-o-clipboard-document-list',
                        ServiceCase::TYPE_INQUIRY => 'heroicon-o-question-mark-circle',
                        default => null,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        ServiceCase::TYPE_INCIDENT => 'danger',
                        ServiceCase::TYPE_REQUEST => 'warning',
                        ServiceCase::TYPE_INQUIRY => 'info',
                        default => 'gray',
                    })
                    ->placeholder('Kein Typ')
                    ->alignCenter(),

                // ═══════════════════════════════════════════════════════════
                // ⚡ PRIORITÄT - Farbige Badge mit deutschem Label
                // ═══════════════════════════════════════════════════════════
                Tables\Columns\TextColumn::make('default_priority')
                    ->label('Priorität')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                        ServiceCase::PRIORITY_HIGH => 'Hoch',
                        ServiceCase::PRIORITY_NORMAL => 'Normal',
                        ServiceCase::PRIORITY_LOW => 'Niedrig',
                        default => '—',
                    })
                    ->icon(fn (?string $state): ?string => match ($state) {
                        ServiceCase::PRIORITY_CRITICAL => 'heroicon-o-fire',
                        ServiceCase::PRIORITY_HIGH => 'heroicon-o-arrow-up',
                        ServiceCase::PRIORITY_NORMAL => 'heroicon-o-minus',
                        ServiceCase::PRIORITY_LOW => 'heroicon-o-arrow-down',
                        default => null,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        ServiceCase::PRIORITY_CRITICAL => 'danger',
                        ServiceCase::PRIORITY_HIGH => 'warning',
                        ServiceCase::PRIORITY_NORMAL => 'primary',
                        ServiceCase::PRIORITY_LOW => 'gray',
                        default => 'gray',
                    })
                    ->placeholder('Keine')
                    ->alignCenter()
                    ->visibleFrom('sm'),

                // ═══════════════════════════════════════════════════════════
                // 🤖 KI-STATUS - Text-Badge mit klarer Aussage (nicht nur Icon!)
                // ═══════════════════════════════════════════════════════════
                Tables\Columns\TextColumn::make('ai_status_display')
                    ->label('KI-Matching')
                    ->getStateUsing(function (ServiceCaseCategory $record): string {
                        $hasKeywords = !empty($record->intent_keywords);
                        $hasThreshold = $record->confidence_threshold !== null;

                        if (!$hasKeywords && !$hasThreshold) {
                            return 'Nicht konfiguriert';
                        }

                        $keywordCount = count($record->intent_keywords ?? []);
                        $confidence = round(($record->confidence_threshold ?? 0) * 100);

                        return "Aktiv ({$keywordCount} Keywords, {$confidence}%)";
                    })
                    ->badge()
                    ->icon(fn (ServiceCaseCategory $record): string =>
                        (!empty($record->intent_keywords) && $record->confidence_threshold !== null)
                            ? 'heroicon-o-cpu-chip'
                            : 'heroicon-o-x-mark')
                    ->color(fn (ServiceCaseCategory $record): string => match (true) {
                        empty($record->intent_keywords) || $record->confidence_threshold === null => 'gray',
                        ($record->confidence_threshold ?? 0) >= 0.8 => 'success',
                        ($record->confidence_threshold ?? 0) >= 0.5 => 'warning',
                        default => 'danger',
                    })
                    ->size('sm')
                    ->visibleFrom('lg'),

                // ═══════════════════════════════════════════════════════════
                // ⏱️ SLA - Zwei Zeilen mit klaren Labels
                // ═══════════════════════════════════════════════════════════
                Tables\Columns\TextColumn::make('sla_response_hours')
                    ->label('SLA')
                    ->getStateUsing(function (ServiceCaseCategory $record): HtmlString {
                        $response = $record->sla_response_hours ?? '—';
                        $resolution = $record->sla_resolution_hours ?? '—';

                        return new HtmlString(
                            '<div class="text-xs space-y-0.5">' .
                            '<div class="flex items-center gap-1">' .
                            '<span class="text-blue-600 dark:text-blue-400">⏱</span>' .
                            '<span class="text-gray-700 dark:text-gray-300">' . $response . 'h Reaktion</span>' .
                            '</div>' .
                            '<div class="flex items-center gap-1">' .
                            '<span class="text-green-600 dark:text-green-400">🎯</span>' .
                            '<span class="text-gray-700 dark:text-gray-300">' . $resolution . 'h Lösung</span>' .
                            '</div>' .
                            '</div>'
                        );
                    })
                    ->html()
                    ->visibleFrom('md'),

                // ═══════════════════════════════════════════════════════════
                // 📊 CASES - Anzahl mit klarem Label
                // ═══════════════════════════════════════════════════════════
                Tables\Columns\TextColumn::make('cases_count')
                    ->label('Cases')
                    ->counts('cases')
                    ->sortable()
                    ->getStateUsing(function (ServiceCaseCategory $record): HtmlString {
                        $count = $record->cases_count ?? 0;
                        $color = $count > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400';
                        $icon = $count > 0 ? '📋' : '—';

                        return new HtmlString(
                            '<div class="text-center">' .
                            '<div class="text-lg font-bold ' . $color . '">' . ($count > 0 ? $count : $icon) . '</div>' .
                            ($count > 0 ? '<div class="text-xs text-gray-500">Cases</div>' : '') .
                            '</div>'
                        );
                    })
                    ->html()
                    ->url(fn (ServiceCaseCategory $record): ?string =>
                        $record->cases_count > 0
                            ? ServiceCaseResource::getUrl('index', [
                                'tableFilters' => [
                                    'category_id' => ['value' => $record->id]
                                ]
                            ])
                            : null
                    )
                    ->openUrlInNewTab()
                    ->alignCenter(),

                // ═══════════════════════════════════════════════════════════
                // ✅ STATUS - Toggle mit Label
                // ═══════════════════════════════════════════════════════════
                Tables\Columns\TextColumn::make('status_display')
                    ->label('Status')
                    ->getStateUsing(fn (ServiceCaseCategory $record): string =>
                        $record->is_active ? 'Aktiv' : 'Inaktiv')
                    ->badge()
                    ->color(fn (ServiceCaseCategory $record): string =>
                        $record->is_active ? 'success' : 'gray')
                    ->icon(fn (ServiceCaseCategory $record): string =>
                        $record->is_active ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->alignCenter(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->modifyQueryUsing(fn ($query) => $query
                ->orderBy('parent_id', 'asc')
                ->orderBy('sort_order', 'asc'))
            ->defaultGroup('company.name')
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Firma')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),

                Tables\Filters\Filter::make('root')
                    ->label('Nur Root-Kategorien')
                    ->query(fn ($query) => $query->whereNull('parent_id'))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('default_case_type')
                    ->label('Typ')
                    ->options([
                        ServiceCase::TYPE_INCIDENT => 'Störung',
                        ServiceCase::TYPE_REQUEST => 'Anfrage',
                        ServiceCase::TYPE_INQUIRY => 'Anliegen',
                    ]),
            ])
            // 🎯 Zeile klickbar → direkt zur View Page
            ->recordUrl(fn (ServiceCaseCategory $record): string =>
                static::getUrl('view', ['record' => $record]))

            // 🎯 Ultra-kompakt: ALLE Actions in einem Dropdown (ServiceNow-Style)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->label('Bearbeiten'),
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->label('Details'),
                    Tables\Actions\Action::make('duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->label('Duplizieren')
                        ->color('gray')
                        ->action(function (ServiceCaseCategory $record) {
                            $newCategory = $record->replicate();
                            $newCategory->name = $record->name . ' (Kopie)';
                            $newCategory->slug = Str::slug($newCategory->name);
                            $newCategory->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Kategorie dupliziert')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->before(function (Tables\Actions\DeleteAction $action, ServiceCaseCategory $record) {
                            if ($record->cases()->exists()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Kategorie kann nicht gelöscht werden')
                                    ->body('Es existieren noch Cases in dieser Kategorie.')
                                    ->danger()
                                    ->send();
                                $action->cancel();
                            }
                            if ($record->children()->exists()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Kategorie kann nicht gelöscht werden')
                                    ->body('Es existieren noch Unterkategorien.')
                                    ->danger()
                                    ->send();
                                $action->cancel();
                            }
                        }),
                ])
                    ->iconButton()
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->tooltip('Aktionen')
                    ->size('sm'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->reorderable('sort_order');
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
            'index' => Pages\ListServiceCaseCategories::route('/'),
            'create' => Pages\CreateServiceCaseCategory::route('/create'),
            'view' => Pages\ViewServiceCaseCategory::route('/{record}'),
            'edit' => Pages\EditServiceCaseCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }
}
