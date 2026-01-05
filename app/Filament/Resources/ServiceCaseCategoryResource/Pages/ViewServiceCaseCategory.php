<?php

namespace App\Filament\Resources\ServiceCaseCategoryResource\Pages;

use App\Filament\Resources\ServiceCaseCategoryResource;
use App\Filament\Resources\ServiceCaseResource;
use App\Filament\Resources\ServiceOutputConfigurationResource;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

/**
 * State-of-the-Art Category Detail Page
 *
 * Design Principles (2025 Best Practices):
 * - KPI Dashboard Header: Critical metrics at a glance
 * - Progressive Disclosure: Summary first, details on demand
 * - Visual Indicators: Colors, icons, progress bars
 * - Human-Readable: No technical jargon
 * - Actionable: Quick actions always visible
 */
class ViewServiceCaseCategory extends ViewRecord
{
    protected static string $resource = ServiceCaseCategoryResource::class;

    /**
     * Eager load all relations needed for the view
     */
    protected function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        return ServiceCaseCategory::with([
            'parent',
            'children',
            'outputConfiguration',
            'company',
        ])->withCount(['cases', 'children'])->findOrFail($key);
    }

    /**
     * Dynamic page title with status emoji
     */
    public function getTitle(): string
    {
        $emoji = $this->record->is_active ? '📂' : '📁';
        return $emoji . ' ' . $this->record->name;
    }

    /**
     * Rich subheading with hierarchy info
     */
    public function getSubheading(): ?string
    {
        $parts = [];

        // Hierarchy info
        if ($this->record->parent) {
            $parts[] = "📍 Unter: {$this->record->parent->name}";
        } else {
            $parts[] = '🏠 Root-Kategorie';
        }

        // Company info
        if ($this->record->company) {
            $parts[] = "🏢 {$this->record->company->name}";
        }

        return implode(' • ', $parts);
    }

    /**
     * Build the Infolist with Dashboard-Style Layout
     */
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                // ═══════════════════════════════════════════════════════════
                // 📊 KPI DASHBOARD HEADER - Wichtigste Zahlen auf einen Blick
                // ═══════════════════════════════════════════════════════════
                $this->getKpiDashboard(),

                // ═══════════════════════════════════════════════════════════
                // MAIN LAYOUT: Sidebar (3) + Content (9)
                // ═══════════════════════════════════════════════════════════
                Components\Grid::make(['default' => 1, 'lg' => 12])
                    ->schema([
                        // SIDEBAR
                        Components\Group::make([
                            $this->getQuickInfoCard(),
                            $this->getOutputConfigCard(),
                            $this->getHierarchyCard(),
                        ])->columnSpan(['default' => 'full', 'lg' => 3]),

                        // MAIN CONTENT TABS
                        Components\Tabs::make('CategoryTabs')
                            ->tabs([
                                $this->getOverviewTab(),
                                $this->getAiMatchingTab(),
                                $this->getSlaTab(),
                                $this->getCasesTab(),
                            ])
                            ->persistTabInQueryString('tab')
                            ->contained(false)
                            ->columnSpan(['default' => 'full', 'lg' => 9]),
                    ]),
            ]);
    }

    /**
     * 📊 KPI Dashboard Header - 4 große Statistik-Karten
     */
    protected function getKpiDashboard(): Components\Section
    {
        $casesCount = $this->record->cases_count ?? 0;
        $childrenCount = $this->record->children_count ?? 0;
        $hasAi = !empty($this->record->intent_keywords) && $this->record->confidence_threshold !== null;
        $hasOutput = $this->record->outputConfiguration !== null;

        return Components\Section::make()
            ->schema([
                Components\Grid::make(['default' => 2, 'md' => 4])
                    ->schema([
                        // KPI 1: Cases
                        Components\Group::make([
                            Components\TextEntry::make('kpi_cases')
                                ->label('')
                                ->getStateUsing(fn () => new HtmlString("
                                    <div class='text-center p-4 bg-blue-50 dark:bg-blue-950 rounded-xl border-2 border-blue-200 dark:border-blue-800'>
                                        <div class='text-3xl font-bold text-blue-600 dark:text-blue-400'>{$casesCount}</div>
                                        <div class='text-sm text-blue-700 dark:text-blue-300 font-medium'>Cases gesamt</div>
                                        <div class='text-xs text-blue-500 dark:text-blue-400 mt-1'><span aria-hidden='true'>📋</span> In dieser Kategorie</div>
                                    </div>
                                "))->html(),
                        ]),

                        // KPI 2: Sub-Kategorien
                        Components\Group::make([
                            Components\TextEntry::make('kpi_children')
                                ->label('')
                                ->getStateUsing(fn () => new HtmlString("
                                    <div class='text-center p-4 bg-purple-50 dark:bg-purple-950 rounded-xl border-2 border-purple-200 dark:border-purple-800'>
                                        <div class='text-3xl font-bold text-purple-600 dark:text-purple-400'>{$childrenCount}</div>
                                        <div class='text-sm text-purple-700 dark:text-purple-300 font-medium'>Sub-Kategorien</div>
                                        <div class='text-xs text-purple-500 dark:text-purple-400 mt-1'><span aria-hidden='true'>📁</span> Untergeordnet</div>
                                    </div>
                                "))->html(),
                        ]),

                        // KPI 3: KI-Status
                        Components\Group::make([
                            Components\TextEntry::make('kpi_ai')
                                ->label('')
                                ->getStateUsing(fn () => new HtmlString($hasAi
                                    ? "<div class='text-center p-4 bg-green-50 dark:bg-green-950 rounded-xl border-2 border-green-200 dark:border-green-800'>
                                        <div class='text-3xl font-bold text-green-600 dark:text-green-400' aria-hidden='true'>✓</div>
                                        <div class='text-sm text-green-700 dark:text-green-300 font-medium'>KI aktiv</div>
                                        <div class='text-xs text-green-500 dark:text-green-400 mt-1'><span aria-hidden='true'>🤖</span> Auto-Kategorisierung</div>
                                    </div>"
                                    : "<div class='text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 border-gray-200 dark:border-gray-700'>
                                        <div class='text-3xl font-bold text-gray-400 dark:text-gray-500' aria-hidden='true'>—</div>
                                        <div class='text-sm text-gray-600 dark:text-gray-400 font-medium'>KI inaktiv</div>
                                        <div class='text-xs text-gray-500 dark:text-gray-500 mt-1'><span aria-hidden='true'>⚙️</span> Nicht konfiguriert</div>
                                    </div>"
                                ))->html(),
                        ]),

                        // KPI 4: Output-Status
                        Components\Group::make([
                            Components\TextEntry::make('kpi_output')
                                ->label('')
                                ->getStateUsing(fn () => new HtmlString($hasOutput
                                    ? "<div class='text-center p-4 bg-emerald-50 dark:bg-emerald-950 rounded-xl border-2 border-emerald-200 dark:border-emerald-800'>
                                        <div class='text-3xl font-bold text-emerald-600 dark:text-emerald-400' aria-hidden='true'>✓</div>
                                        <div class='text-sm text-emerald-700 dark:text-emerald-300 font-medium'>Ausgabe aktiv</div>
                                        <div class='text-xs text-emerald-500 dark:text-emerald-400 mt-1'><span aria-hidden='true'>📤</span> " . e($this->record->outputConfiguration->name) . "</div>
                                    </div>"
                                    : "<div class='text-center p-4 bg-orange-50 dark:bg-orange-950 rounded-xl border-2 border-orange-200 dark:border-orange-800'>
                                        <div class='text-3xl font-bold text-orange-500 dark:text-orange-400' aria-hidden='true'>⚠</div>
                                        <div class='text-sm text-orange-700 dark:text-orange-300 font-medium'>Keine Ausgabe</div>
                                        <div class='text-xs text-orange-500 dark:text-orange-400 mt-1'><span aria-hidden='true'>📭</span> Nicht zugestellt</div>
                                    </div>"
                                ))->html(),
                        ]),
                    ]),
            ])
            ->extraAttributes(['class' => 'mb-6']);
    }

    /**
     * 📋 Sidebar: Quick Info Card
     */
    protected function getQuickInfoCard(): Components\Section
    {
        $typeConfig = match ($this->record->default_case_type) {
            ServiceCase::TYPE_INCIDENT => ['label' => 'Störung', 'color' => 'danger', 'icon' => '🔴', 'desc' => 'Technische Probleme'],
            ServiceCase::TYPE_REQUEST => ['label' => 'Anfrage', 'color' => 'warning', 'icon' => '🟡', 'desc' => 'Service-Anfragen'],
            ServiceCase::TYPE_INQUIRY => ['label' => 'Anliegen', 'color' => 'info', 'icon' => '🔵', 'desc' => 'Allgemeine Fragen'],
            default => ['label' => 'Nicht gesetzt', 'color' => 'gray', 'icon' => '⚪', 'desc' => 'Kein Standard-Typ'],
        };

        $prioConfig = match ($this->record->default_priority) {
            ServiceCase::PRIORITY_CRITICAL => ['label' => 'Kritisch', 'color' => 'danger', 'icon' => '🔥'],
            ServiceCase::PRIORITY_HIGH => ['label' => 'Hoch', 'color' => 'warning', 'icon' => '⬆️'],
            ServiceCase::PRIORITY_NORMAL => ['label' => 'Normal', 'color' => 'primary', 'icon' => '➡️'],
            ServiceCase::PRIORITY_LOW => ['label' => 'Niedrig', 'color' => 'gray', 'icon' => '⬇️'],
            default => ['label' => 'Nicht gesetzt', 'color' => 'gray', 'icon' => '—'],
        };

        return Components\Section::make('Klassifizierung')
            ->icon('heroicon-o-tag')
            ->schema([
                // Status Badge
                Components\TextEntry::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ? '✓ Aktiv' : '✗ Inaktiv')
                    ->size(Components\TextEntry\TextEntrySize::Large),

                // Typ mit Erklärung
                Components\TextEntry::make('type_display')
                    ->label('Standard-Typ')
                    ->getStateUsing(fn () => new HtmlString("
                        <div class='flex items-center gap-2'>
                            <span class='text-xl'>{$typeConfig['icon']}</span>
                            <div>
                                <div class='font-semibold text-gray-900 dark:text-gray-100'>{$typeConfig['label']}</div>
                                <div class='text-xs text-gray-500 dark:text-gray-400'>{$typeConfig['desc']}</div>
                            </div>
                        </div>
                    "))->html(),

                // Priorität
                Components\TextEntry::make('priority_display')
                    ->label('Standard-Priorität')
                    ->getStateUsing(fn () => new HtmlString("
                        <div class='flex items-center gap-2'>
                            <span class='text-xl'>{$prioConfig['icon']}</span>
                            <span class='font-semibold text-gray-900 dark:text-gray-100'>{$prioConfig['label']}</span>
                        </div>
                    "))->html(),

                // Sortierung
                Components\TextEntry::make('sort_order')
                    ->label('Sortierung')
                    ->badge()
                    ->color('gray')
                    ->prefix('#')
                    ->helperText('Position in Listen'),
            ]);
        // NOTE: Sidebar cards should NOT be collapsible - critical info must stay visible
    }

    /**
     * 📤 Sidebar: Output Configuration Card
     */
    protected function getOutputConfigCard(): Components\Section
    {
        $output = $this->record->outputConfiguration;

        if (!$output) {
            return Components\Section::make('Ausgabe-Konfiguration')
                ->icon('heroicon-o-paper-airplane')
                ->schema([
                    Components\TextEntry::make('no_output')
                        ->label('')
                        ->getStateUsing(fn () => new HtmlString("
                            <div class='p-4 bg-orange-50 dark:bg-orange-950 rounded-lg border border-orange-200 dark:border-orange-800'>
                                <div class='flex items-center gap-2 text-orange-700 dark:text-orange-300'>
                                    <span class='text-xl' aria-hidden='true'>⚠️</span>
                                    <div>
                                        <div class='font-semibold'>Keine Ausgabe konfiguriert</div>
                                        <div class='text-xs mt-1'>Cases werden nicht automatisch versendet</div>
                                    </div>
                                </div>
                            </div>
                        "))->html(),
                ]);
        }

        $methodIcon = match ($output->output_type ?? $output->delivery_method ?? '') {
            'email' => '📧',
            'webhook' => '🔗',
            'hybrid' => '📧🔗',
            default => '📤',
        };

        $methodLabel = match ($output->output_type ?? $output->delivery_method ?? '') {
            'email' => 'E-Mail',
            'webhook' => 'Webhook',
            'hybrid' => 'E-Mail + Webhook',
            default => 'Unbekannt',
        };

        return Components\Section::make('Ausgabe-Konfiguration')
            ->icon('heroicon-o-paper-airplane')
            ->schema([
                Components\TextEntry::make('output_card')
                    ->label('')
                    ->getStateUsing(fn () => new HtmlString("
                        <div class='p-4 bg-emerald-50 dark:bg-emerald-950 rounded-lg border border-emerald-200 dark:border-emerald-800'>
                            <div class='flex items-center justify-between mb-3'>
                                <span class='text-2xl' aria-hidden='true'>{$methodIcon}</span>
                                <span class='px-2 py-1 text-xs font-semibold bg-emerald-200 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200 rounded'>{$methodLabel}</span>
                            </div>
                            <div class='font-semibold text-emerald-800 dark:text-emerald-200'>" . e($output->name) . "</div>
                            <div class='text-xs text-emerald-600 dark:text-emerald-400 mt-1'>
                                <span aria-hidden='true'>" . ($output->is_active ? '✓' : '✗') . "</span> " . ($output->is_active ? 'Aktiv' : 'Inaktiv') . "
                            </div>
                        </div>
                    "))->html(),

                Components\Actions::make([
                    Components\Actions\Action::make('edit_output')
                        ->label('Konfiguration bearbeiten')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->url(ServiceOutputConfigurationResource::getUrl('edit', ['record' => $output->id]))
                        ->size('sm')
                        ->color('gray'),
                ]),
            ]);
    }

    /**
     * 📁 Sidebar: Hierarchy Card
     */
    protected function getHierarchyCard(): Components\Section
    {
        $children = $this->record->children;

        return Components\Section::make('Hierarchie')
            ->icon('heroicon-o-folder-open')
            ->schema([
                // Parent
                $this->record->parent
                    ? Components\TextEntry::make('parent_link')
                        ->label('Übergeordnet')
                        ->getStateUsing(fn () => new HtmlString("
                            <a href='" . ServiceCaseCategoryResource::getUrl('view', ['record' => $this->record->parent_id]) . "'
                               class='flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2'>
                                <span aria-hidden='true'>📁</span>
                                <span class='font-medium text-primary-600 dark:text-primary-400'>" . e($this->record->parent->name) . "</span>
                                <span class='ml-auto' aria-hidden='true'>→</span>
                            </a>
                        "))->html()
                    : Components\TextEntry::make('is_root')
                        ->label('')
                        ->getStateUsing(fn () => new HtmlString("
                            <div class='flex items-center gap-2 p-2 rounded-lg bg-blue-50 dark:bg-blue-950'>
                                <span aria-hidden='true'>🏠</span>
                                <span class='text-sm text-blue-700 dark:text-blue-300'>Dies ist eine Root-Kategorie</span>
                            </div>
                        "))->html(),

                // Children
                $children->isNotEmpty()
                    ? Components\TextEntry::make('children_list')
                        ->label("Sub-Kategorien ({$children->count()})")
                        ->getStateUsing(fn () => new HtmlString(
                            "<div class='space-y-1'>" .
                            $children->map(fn ($child) => "
                                <a href='" . ServiceCaseCategoryResource::getUrl('view', ['record' => $child->id]) . "'
                                   class='flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2'>
                                    <span aria-hidden='true'>📄</span>
                                    <span class='text-primary-600 dark:text-primary-400'>" . e($child->name) . "</span>
                                    <span class='ml-auto text-xs text-gray-400'>" . ($child->cases_count ?? 0) . " Cases</span>
                                </a>
                            ")->join('') .
                            "</div>"
                        ))->html()
                    : Components\TextEntry::make('no_children')
                        ->label('')
                        ->getStateUsing(fn () => new HtmlString("
                            <div class='text-sm text-gray-500 dark:text-gray-400 italic'>
                                Keine Sub-Kategorien vorhanden
                            </div>
                        "))->html(),
            ]);
    }

    /**
     * 📋 Tab: Übersicht (Dashboard-Style)
     */
    protected function getOverviewTab(): Components\Tabs\Tab
    {
        return Components\Tabs\Tab::make('Übersicht')
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                // Beschreibung
                Components\Section::make('Beschreibung')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        $this->record->description
                            ? Components\TextEntry::make('description')
                                ->label('')
                                ->markdown()
                                ->prose()
                                ->columnSpanFull()
                            : Components\TextEntry::make('no_description')
                                ->label('')
                                ->getStateUsing(fn () => new HtmlString("
                                    <div class='text-gray-500 dark:text-gray-400 italic'>
                                        Keine Beschreibung hinterlegt.
                                        <a href='" . ServiceCaseCategoryResource::getUrl('edit', ['record' => $this->record->id]) . "' class='text-primary-600 hover:underline'>Jetzt hinzufügen →</a>
                                    </div>
                                "))->html(),
                    ])
                    ->collapsible(),
                    // NOTE: Description should ALWAYS be expanded by default (ServiceNow/Jira/Zendesk pattern)

                // Was passiert bei einem neuen Case?
                Components\Section::make('Was passiert bei einem neuen Case?')
                    ->icon('heroicon-o-arrow-path')
                    ->description('Workflow-Vorschau für diese Kategorie')
                    ->schema([
                        Components\TextEntry::make('workflow_preview')
                            ->label('')
                            ->getStateUsing(fn () => $this->buildWorkflowPreview())
                            ->html(),
                    ]),

                // Technische Details
                Components\Section::make('Technische Details')
                    ->icon('heroicon-o-code-bracket')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('slug')
                                    ->label('Slug')
                                    ->badge()
                                    ->color('gray')
                                    ->copyable()
                                    ->copyMessage('Slug kopiert!')
                                    ->helperText('Technischer Identifier'),

                                Components\TextEntry::make('id')
                                    ->label('ID')
                                    ->badge()
                                    ->color('gray')
                                    ->prefix('#'),

                                Components\TextEntry::make('created_at')
                                    ->label('Erstellt')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn () => $this->record->created_at?->diffForHumans()),

                                Components\TextEntry::make('updated_at')
                                    ->label('Aktualisiert')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn () => $this->record->updated_at?->diffForHumans()),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * 🤖 Tab: KI-Matching (Human-Readable)
     */
    protected function getAiMatchingTab(): Components\Tabs\Tab
    {
        $hasKeywords = !empty($this->record->intent_keywords);
        $hasThreshold = $this->record->confidence_threshold !== null;
        $isConfigured = $hasKeywords && $hasThreshold;

        $keywordCount = count($this->record->intent_keywords ?? []);
        $confidence = round(($this->record->confidence_threshold ?? 0) * 100);

        $confidenceLevel = match (true) {
            $confidence >= 80 => ['label' => 'Hoch', 'color' => 'success', 'icon' => '🎯', 'desc' => 'Nur sehr sichere Zuordnungen - weniger False Positives'],
            $confidence >= 50 => ['label' => 'Mittel', 'color' => 'warning', 'icon' => '⚖️', 'desc' => 'Ausgewogene Balance zwischen Genauigkeit und Abdeckung'],
            $confidence > 0 => ['label' => 'Niedrig', 'color' => 'danger', 'icon' => '⚡', 'desc' => 'Viele Zuordnungen, aber auch mehr Fehlzuordnungen möglich'],
            default => ['label' => 'Nicht konfiguriert', 'color' => 'gray', 'icon' => '—', 'desc' => 'KI-Matching ist nicht aktiv'],
        };

        return Components\Tabs\Tab::make('KI-Matching')
            ->icon('heroicon-o-cpu-chip')
            ->badge($isConfigured ? '✓ Aktiv' : null)
            ->badgeColor('success')
            ->schema([
                // Status Overview
                Components\Section::make()
                    ->schema([
                        Components\TextEntry::make('ai_status_card')
                            ->label('')
                            ->getStateUsing(fn () => new HtmlString($isConfigured
                                ? "<div class='p-6 bg-green-50 dark:bg-green-950 rounded-xl border-2 border-green-200 dark:border-green-800'>
                                    <div class='flex items-center gap-4'>
                                        <div class='text-4xl' aria-hidden='true'>🤖</div>
                                        <div>
                                            <div class='text-xl font-bold text-green-800 dark:text-green-200'>KI-Matching ist aktiv</div>
                                            <div class='text-sm text-green-700 dark:text-green-300 mt-1'>
                                                Cases werden automatisch dieser Kategorie zugeordnet, wenn die KI entsprechende Keywords erkennt.
                                            </div>
                                        </div>
                                    </div>
                                </div>"
                                : "<div class='p-6 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 border-gray-200 dark:border-gray-700'>
                                    <div class='flex items-center gap-4'>
                                        <div class='text-4xl' aria-hidden='true'>🔌</div>
                                        <div>
                                            <div class='text-xl font-bold text-gray-700 dark:text-gray-300'>KI-Matching nicht konfiguriert</div>
                                            <div class='text-sm text-gray-600 dark:text-gray-400 mt-1'>
                                                Füge Keywords hinzu und setze einen Confidence-Schwellenwert, um automatische Kategorisierung zu aktivieren.
                                            </div>
                                            <a href='" . ServiceCaseCategoryResource::getUrl('edit', ['record' => $this->record->id]) . "?tab=ki-matching-tab'
                                               class='inline-flex items-center gap-1 mt-3 text-primary-600 hover:underline text-sm font-medium'>
                                                Jetzt konfigurieren →
                                            </a>
                                        </div>
                                    </div>
                                </div>"
                            ))->html(),
                    ]),

                // Confidence Threshold
                $isConfigured ? Components\Section::make('Confidence-Schwellenwert')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->description('Wie sicher muss die KI sein, um diese Kategorie zuzuordnen?')
                    ->schema([
                        Components\TextEntry::make('confidence_visual')
                            ->label('')
                            ->getStateUsing(fn () => new HtmlString("
                                <div class='space-y-4'>
                                    <div class='flex items-center gap-4'>
                                        <div class='text-4xl' aria-hidden='true'>{$confidenceLevel['icon']}</div>
                                        <div>
                                            <div class='text-2xl font-bold text-gray-900 dark:text-gray-100'>{$confidence}%</div>
                                            <div class='text-sm font-medium text-{$confidenceLevel['color']}-600 dark:text-{$confidenceLevel['color']}-400'>{$confidenceLevel['label']}</div>
                                        </div>
                                    </div>

                                    <!-- Progress Bar with ARIA -->
                                    <div class='h-3 w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden'
                                         role='progressbar'
                                         aria-valuenow='{$confidence}'
                                         aria-valuemin='0'
                                         aria-valuemax='100'
                                         aria-label='Confidence-Schwellenwert: {$confidence} Prozent'>
                                        <div class='h-full rounded-full transition-all " . match (true) {
                                            $confidence >= 80 => 'bg-green-500',
                                            $confidence >= 50 => 'bg-yellow-500',
                                            default => 'bg-red-500',
                                        } . "' style='width: {$confidence}%'></div>
                                    </div>

                                    <div class='text-sm text-gray-600 dark:text-gray-400'>
                                        {$confidenceLevel['desc']}
                                    </div>
                                </div>
                            "))->html(),
                    ]) : Components\Group::make(),

                // Keywords
                $hasKeywords ? Components\Section::make("Keywords ({$keywordCount})")
                    ->icon('heroicon-o-hashtag')
                    ->description('Diese Begriffe triggern die Zuordnung zu dieser Kategorie')
                    ->schema([
                        Components\TextEntry::make('keywords_visual')
                            ->label('')
                            ->getStateUsing(fn () => new HtmlString(
                                "<div class='flex flex-wrap gap-2'>" .
                                collect($this->record->intent_keywords)->map(fn ($kw) =>
                                    "<span class='inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200'>
                                        <span class='mr-1' aria-hidden='true'>🔑</span> " . e($kw) . "
                                    </span>"
                                )->join('') .
                                "</div>"
                            ))->html(),
                    ]) : Components\Group::make(),
            ]);
    }

    /**
     * ⏱️ Tab: SLA-Konfiguration (Visual)
     */
    protected function getSlaTab(): Components\Tabs\Tab
    {
        $response = $this->record->sla_response_hours;
        $resolution = $this->record->sla_resolution_hours;
        $hasSla = $response || $resolution;

        return Components\Tabs\Tab::make('SLA')
            ->icon('heroicon-o-clock')
            ->badge($hasSla ? '✓' : null)
            ->badgeColor('success')
            ->schema([
                // SLA Status Card
                Components\Section::make()
                    ->schema([
                        Components\TextEntry::make('sla_status_card')
                            ->label('')
                            ->getStateUsing(fn () => new HtmlString($hasSla
                                ? "<div class='p-6 bg-blue-50 dark:bg-blue-950 rounded-xl border-2 border-blue-200 dark:border-blue-800'>
                                    <div class='flex items-center gap-4'>
                                        <div class='text-4xl' aria-hidden='true'>⏱️</div>
                                        <div>
                                            <div class='text-xl font-bold text-blue-800 dark:text-blue-200'>SLA-Zeiten definiert</div>
                                            <div class='text-sm text-blue-700 dark:text-blue-300 mt-1'>
                                                Diese Zeiten werden für alle Cases in dieser Kategorie angewendet.
                                            </div>
                                        </div>
                                    </div>
                                </div>"
                                : "<div class='p-6 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 border-gray-200 dark:border-gray-700'>
                                    <div class='flex items-center gap-4'>
                                        <div class='text-4xl' aria-hidden='true'>⏳</div>
                                        <div>
                                            <div class='text-xl font-bold text-gray-700 dark:text-gray-300'>Keine SLA-Zeiten definiert</div>
                                            <div class='text-sm text-gray-600 dark:text-gray-400 mt-1'>
                                                Cases in dieser Kategorie haben keine automatischen Zeitvorgaben.
                                            </div>
                                        </div>
                                    </div>
                                </div>"
                            ))->html(),
                    ]),

                // SLA Visual Cards
                $hasSla ? Components\Section::make('Zeitvorgaben')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                // Response Time Card
                                Components\TextEntry::make('response_card')
                                    ->label('')
                                    ->getStateUsing(fn () => new HtmlString("
                                        <div class='p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900 rounded-xl border border-blue-200 dark:border-blue-800'>
                                            <div class='flex items-center gap-2 mb-4'>
                                                <span class='text-2xl' aria-hidden='true'>⏱️</span>
                                                <span class='text-sm font-medium text-blue-700 dark:text-blue-300'>REAKTIONSZEIT</span>
                                            </div>
                                            <div class='text-4xl font-bold text-blue-800 dark:text-blue-200 mb-2'>" . ($response ?? '—') . " <span class='text-lg font-normal'>Stunden</span></div>
                                            <div class='text-sm text-blue-600 dark:text-blue-400'>
                                                Zeit bis zur ersten Antwort oder Bestätigung
                                            </div>
                                        </div>
                                    "))->html(),

                                // Resolution Time Card
                                Components\TextEntry::make('resolution_card')
                                    ->label('')
                                    ->getStateUsing(fn () => new HtmlString("
                                        <div class='p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900 rounded-xl border border-green-200 dark:border-green-800'>
                                            <div class='flex items-center gap-2 mb-4'>
                                                <span class='text-2xl' aria-hidden='true'>🎯</span>
                                                <span class='text-sm font-medium text-green-700 dark:text-green-300'>LÖSUNGSZEIT</span>
                                            </div>
                                            <div class='text-4xl font-bold text-green-800 dark:text-green-200 mb-2'>" . ($resolution ?? '—') . " <span class='text-lg font-normal'>Stunden</span></div>
                                            <div class='text-sm text-green-600 dark:text-green-400'>
                                                Zeit bis zur vollständigen Lösung des Problems
                                            </div>
                                        </div>
                                    "))->html(),
                            ]),
                    ]) : Components\Group::make(),

                // SLA Timeline Visualization
                ($response && $resolution) ? Components\Section::make('Zeitstrahl')
                    ->icon('heroicon-o-arrow-long-right')
                    ->schema([
                        Components\ViewEntry::make('sla_visual')
                            ->label('')
                            ->view('filament.infolist.sla-visual'),
                    ]) : Components\Group::make(),
            ]);
    }

    /**
     * 📋 Tab: Cases
     */
    protected function getCasesTab(): Components\Tabs\Tab
    {
        $count = $this->record->cases_count ?? 0;

        return Components\Tabs\Tab::make('Cases')
            ->icon('heroicon-o-ticket')
            ->badge($count > 0 ? $count : null)
            ->badgeColor('primary')
            ->schema([
                Components\Section::make()
                    ->schema([
                        Components\TextEntry::make('cases_summary')
                            ->label('')
                            ->getStateUsing(fn () => new HtmlString($count > 0
                                ? "<div class='p-6 bg-blue-50 dark:bg-blue-950 rounded-xl border-2 border-blue-200 dark:border-blue-800'>
                                    <div class='flex items-center justify-between'>
                                        <div class='flex items-center gap-4'>
                                            <div class='text-4xl' aria-hidden='true'>📋</div>
                                            <div>
                                                <div class='text-3xl font-bold text-blue-800 dark:text-blue-200'>{$count}</div>
                                                <div class='text-sm text-blue-700 dark:text-blue-300'>Cases in dieser Kategorie</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>"
                                : "<div class='p-6 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 border-gray-200 dark:border-gray-700'>
                                    <div class='flex items-center gap-4'>
                                        <div class='text-4xl' aria-hidden='true'>📭</div>
                                        <div>
                                            <div class='text-xl font-bold text-gray-700 dark:text-gray-300'>Noch keine Cases</div>
                                            <div class='text-sm text-gray-600 dark:text-gray-400 mt-1'>
                                                Es wurden noch keine Cases in dieser Kategorie erstellt.
                                            </div>
                                        </div>
                                    </div>
                                </div>"
                            ))->html(),

                        $count > 0
                            ? Components\Actions::make([
                                Components\Actions\Action::make('view_all_cases')
                                    ->label("Alle {$count} Cases anzeigen")
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->url(ServiceCaseResource::getUrl('index', [
                                        'tableFilters' => [
                                            'category_id' => ['value' => $this->record->id],
                                        ],
                                    ]))
                                    ->color('primary')
                                    ->size('lg'),
                            ])
                            : Components\Group::make(),
                    ]),
            ]);
    }

    /**
     * Build Workflow Preview HTML
     */
    protected function buildWorkflowPreview(): HtmlString
    {
        $type = $this->record->default_case_type
            ? match ($this->record->default_case_type) {
                ServiceCase::TYPE_INCIDENT => '🔴 Störung',
                ServiceCase::TYPE_REQUEST => '🟡 Anfrage',
                ServiceCase::TYPE_INQUIRY => '🔵 Anliegen',
                default => '⚪ Standard',
            }
            : '⚪ Nicht festgelegt';

        $priority = $this->record->default_priority
            ? match ($this->record->default_priority) {
                ServiceCase::PRIORITY_CRITICAL => '🔥 Kritisch',
                ServiceCase::PRIORITY_HIGH => '⬆️ Hoch',
                ServiceCase::PRIORITY_NORMAL => '➡️ Normal',
                ServiceCase::PRIORITY_LOW => '⬇️ Niedrig',
                default => '— Standard',
            }
            : '— Nicht festgelegt';

        $sla = ($this->record->sla_response_hours || $this->record->sla_resolution_hours)
            ? "⏱️ {$this->record->sla_response_hours}h Reaktion / 🎯 {$this->record->sla_resolution_hours}h Lösung"
            : '— Keine SLA-Zeiten';

        $output = $this->record->outputConfiguration
            ? "📤 " . e($this->record->outputConfiguration->name)
            : '⚠️ Keine Ausgabe konfiguriert';

        return new HtmlString("
            <div class='space-y-3'>
                <div class='flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg'>
                    <div class='flex-shrink-0 w-8 h-8 flex items-center justify-center bg-gray-200 dark:bg-gray-700 rounded-full text-sm font-bold'>1</div>
                    <div class='flex-1'>
                        <div class='font-medium text-gray-900 dark:text-gray-100'>Case wird erstellt</div>
                        <div class='text-sm text-gray-600 dark:text-gray-400'>Typ: {$type} • Priorität: {$priority}</div>
                    </div>
                </div>

                <div class='flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg'>
                    <div class='flex-shrink-0 w-8 h-8 flex items-center justify-center bg-gray-200 dark:bg-gray-700 rounded-full text-sm font-bold'>2</div>
                    <div class='flex-1'>
                        <div class='font-medium text-gray-900 dark:text-gray-100'>SLA-Zeiten werden gesetzt</div>
                        <div class='text-sm text-gray-600 dark:text-gray-400'>{$sla}</div>
                    </div>
                </div>

                <div class='flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg'>
                    <div class='flex-shrink-0 w-8 h-8 flex items-center justify-center bg-gray-200 dark:bg-gray-700 rounded-full text-sm font-bold'>3</div>
                    <div class='flex-1'>
                        <div class='font-medium text-gray-900 dark:text-gray-100'>Case wird zugestellt</div>
                        <div class='text-sm text-gray-600 dark:text-gray-400'>{$output}</div>
                    </div>
                </div>
            </div>
        ");
    }

    /**
     * Header Actions - Quick Actions prominent
     */
    protected function getHeaderActions(): array
    {
        return [
            // Primary: Edit
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->label('Bearbeiten'),

            // Quick Actions Group
            Actions\ActionGroup::make([
                Actions\Action::make('create_case')
                    ->label('Neuer Case')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->url(fn () => ServiceCaseResource::getUrl('create', [
                        'category_id' => $this->record->id,
                    ])),

                Actions\Action::make('toggle_active')
                    ->label(fn () => $this->record->is_active ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn () => $this->record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn () => $this->record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function () {
                        $this->record->update(['is_active' => !$this->record->is_active]);
                        $this->refreshFormData(['is_active']);
                    }),

                Actions\Action::make('duplicate')
                    ->label('Duplizieren')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function () {
                        $newRecord = $this->record->replicate();
                        $newRecord->name = $this->record->name . ' (Kopie)';
                        $newRecord->slug = \Illuminate\Support\Str::slug($newRecord->name);
                        $newRecord->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Kategorie dupliziert')
                            ->success()
                            ->send();

                        return redirect(ServiceCaseCategoryResource::getUrl('edit', ['record' => $newRecord->id]));
                    }),

                Actions\DeleteAction::make()
                    ->visible(fn () => $this->record->cases_count === 0 && $this->record->children_count === 0),
            ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->button()
                ->color('gray'),
        ];
    }
}
