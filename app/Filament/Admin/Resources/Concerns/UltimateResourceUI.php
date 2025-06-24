<?php

namespace App\Filament\Admin\Resources\Concerns;

use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Support\Facades\FilamentView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\ViewField;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Collection;

trait UltimateResourceUI
{
    // View system properties
    protected static bool $hasMultiView = true;
    protected static array $viewTypes = ['table', 'grid', 'kanban', 'calendar', 'timeline'];
    protected static string $defaultView = 'table';
    
    // Feature flags
    protected static bool $hasInlineEditing = true;
    protected static bool $hasCommandPalette = true;
    protected static bool $hasSmartFiltering = true;
    protected static bool $hasDragAndDrop = true;
    protected static bool $hasKeyboardShortcuts = true;
    protected static bool $hasRealTimeUpdates = true;
    protected static bool $hasBulkActions = true;
    protected static bool $hasDataVisualization = true;
    
    public static function table(Table $table): Table
    {
        $table = parent::table($table);
        
        return $table
            ->poll('10s')
            ->striped()
            ->contentGrid(fn () => self::getContentGrid())
            ->extremePaginationLinks()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->filtersLayout(FiltersLayout::Dropdown)
            ->filtersFormColumns(3)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Smart Filters')
                    ->icon('heroicon-m-funnel')
                    ->badge(fn (Table $table): ?string => 
                        $table->getFilters() ? count($table->getActiveFilters()) : null
                    )
                    ->badgeColor('primary')
            )
            ->modifyQueryUsing(fn (Builder $query) => self::applySmartFilters($query))
            ->after(fn () => self::renderViewSwitcher())
            ->emptyStateHeading('No records found')
            ->emptyStateDescription('Try adjusting your filters or create a new record.')
            ->emptyStateIcon('heroicon-o-magnifying-glass')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create first record')
                    ->icon('heroicon-m-plus')
                    ->button()
                    ->url(static::getUrl('create'))
                    ->visible(fn (): bool => static::canCreate()),
                Action::make('reset_filters')
                    ->label('Reset filters')
                    ->icon('heroicon-m-x-mark')
                    ->color('gray')
                    ->action(fn (Table $table) => $table->resetFilters())
            ])
            ->recordAction(fn () => self::getRecordAction())
            ->recordUrl(null)
            ->pushActions([
                self::getCommandPaletteAction(),
                self::getViewSwitcherAction(),
                self::getKeyboardShortcutsAction(),
            ])
            ->pushBulkActions([
                self::getSmartBulkActions()
            ]);
    }
    
    protected static function getContentGrid(): array
    {
        $view = request()->get('view', static::$defaultView);
        
        return match ($view) {
            'grid' => ['md' => 2, 'xl' => 3, '2xl' => 4],
            'kanban' => ['md' => 1],
            default => ['md' => 1],
        };
    }
    
    protected static function renderViewSwitcher(): HtmlString
    {
        $currentView = request()->get('view', static::$defaultView);
        
        return new HtmlString(<<<HTML
            <div x-data="{ currentView: '$currentView', switchView(view) { window.dispatchEvent(new CustomEvent('switch-view', { detail: { view: view }})); } }" class="fi-ta-view-switcher">
                <style>
                    .fi-ta-view-switcher {
                        position: sticky;
                        top: 0;
                        z-index: 40;
                        background: rgba(255, 255, 255, 0.8);
                        backdrop-filter: blur(12px);
                        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                        margin: -1.5rem -1.5rem 1.5rem;
                        padding: 1rem 1.5rem;
                    }
                    
                    .dark .fi-ta-view-switcher {
                        background: rgba(31, 41, 55, 0.8);
                        border-bottom-color: rgba(255, 255, 255, 0.1);
                    }
                    
                    .view-tabs {
                        display: flex;
                        gap: 0.5rem;
                        align-items: center;
                    }
                    
                    .view-tab {
                        position: relative;
                        padding: 0.5rem 1rem;
                        border-radius: 0.5rem;
                        font-size: 0.875rem;
                        font-weight: 500;
                        color: rgb(107, 114, 128);
                        transition: all 0.2s;
                        cursor: pointer;
                        user-select: none;
                    }
                    
                    .view-tab:hover {
                        background: rgba(0, 0, 0, 0.05);
                        color: rgb(31, 41, 55);
                    }
                    
                    .dark .view-tab:hover {
                        background: rgba(255, 255, 255, 0.1);
                        color: rgb(243, 244, 246);
                    }
                    
                    .view-tab.active {
                        background: rgb(59, 130, 246);
                        color: white;
                        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
                    }
                    
                    .view-tab-icon {
                        width: 1.25rem;
                        height: 1.25rem;
                        margin-right: 0.5rem;
                        display: inline-block;
                        vertical-align: middle;
                    }
                    
                    .keyboard-hint {
                        margin-left: auto;
                        font-size: 0.75rem;
                        color: rgb(156, 163, 175);
                        display: flex;
                        align-items: center;
                        gap: 0.25rem;
                    }
                    
                    .kbd {
                        padding: 0.125rem 0.375rem;
                        background: rgba(0, 0, 0, 0.1);
                        border-radius: 0.25rem;
                        font-family: monospace;
                        font-size: 0.75rem;
                    }
                    
                    .dark .kbd {
                        background: rgba(255, 255, 255, 0.1);
                    }
                </style>
                
                <div class="view-tabs">
                    <button 
                        @click="switchView('table')"
                        :class="currentView === 'table' ? 'active' : ''"
                        class="view-tab"
                        title="Table View (⌘1)"
                    >
                        <svg class="view-tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Table
                    </button>
                    
                    <button 
                        @click="switchView('grid')"
                        :class="currentView === 'grid' ? 'active' : ''"
                        class="view-tab"
                        title="Grid View (⌘2)"
                    >
                        <svg class="view-tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Grid
                    </button>
                    
                    <button 
                        @click="switchView('kanban')"
                        :class="currentView === 'kanban' ? 'active' : ''"
                        class="view-tab"
                        title="Kanban View (⌘3)"
                    >
                        <svg class="view-tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                        Kanban
                    </button>
                    
                    <button 
                        @click="switchView('calendar')"
                        :class="currentView === 'calendar' ? 'active' : ''"
                        class="view-tab"
                        title="Calendar View (⌘4)"
                    >
                        <svg class="view-tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Calendar
                    </button>
                    
                    <button 
                        @click="switchView('timeline')"
                        :class="currentView === 'timeline' ? 'active' : ''"
                        class="view-tab"
                        title="Timeline View (⌘5)"
                    >
                        <svg class="view-tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Timeline
                    </button>
                    
                    <div class="keyboard-hint">
                        <span>Press</span>
                        <kbd class="kbd">⌘K</kbd>
                        <span>for command palette</span>
                    </div>
                </div>
            </div>
        HTML);
    }
    
    protected static function getCommandPaletteAction(): Action
    {
        return Action::make('command_palette')
            ->label('Command Palette')
            ->icon('heroicon-m-command-line')
            ->color('gray')
            ->extraAttributes([
                'x-on:click' => 'openCommandPalette()',
                'title' => 'Command Palette (⌘K)',
            ])
            ->outlined();
    }
    
    protected static function getViewSwitcherAction(): Action
    {
        return Action::make('view_switcher')
            ->label('Views')
            ->icon('heroicon-m-squares-2x2')
            ->color('gray')
            ->extraAttributes([
                'title' => 'Switch View',
            ])
            ->outlined();
    }
    
    protected static function getKeyboardShortcutsAction(): Action
    {
        return Action::make('keyboard_shortcuts')
            ->label('')
            ->icon('heroicon-m-question-mark-circle')
            ->color('gray')
            ->tooltip('Keyboard Shortcuts (?)')
            ->extraAttributes([
                'x-on:click' => 'showKeyboardShortcuts()',
            ])
            ->iconButton();
    }
    
    protected static function getRecordAction(): ?string
    {
        if (static::$hasInlineEditing) {
            return 'inline-edit';
        }
        
        return 'edit';
    }
    
    protected static function getSmartBulkActions(): array
    {
        return [
            BulkAction::make('smart_update')
                ->label('Smart Update')
                ->icon('heroicon-m-pencil-square')
                ->color('primary')
                ->form([
                    ViewField::make('ai_suggestions')
                        ->view('filament.forms.ai-bulk-suggestions')
                        ->label('AI Suggestions'),
                ])
                ->action(function (Collection $records, array $data) {
                    // Smart bulk update logic
                }),
                
            BulkAction::make('export_selected')
                ->label('Export')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Export selected records')
                ->modalSubheading('Choose export format')
                ->form([
                    \Filament\Forms\Components\Radio::make('format')
                        ->label('Export Format')
                        ->options([
                            'csv' => 'CSV',
                            'xlsx' => 'Excel',
                            'pdf' => 'PDF',
                            'json' => 'JSON',
                        ])
                        ->default('csv')
                        ->required(),
                ])
                ->action(function (Collection $records, array $data) {
                    // Export logic
                }),
                
            BulkAction::make('analyze')
                ->label('Analyze')
                ->icon('heroicon-m-chart-bar')
                ->color('info')
                ->action(function (Collection $records) {
                    // Analyze selected records
                }),
        ];
    }
    
    protected static function applySmartFilters(Builder $query): Builder
    {
        $smartFilter = request()->get('smart_filter');
        
        if (!$smartFilter) {
            return $query;
        }
        
        // Natural language processing for smart filters
        $smartFilter = strtolower($smartFilter);
        
        // Date filters
        if (str_contains($smartFilter, 'today')) {
            $query->whereDate('created_at', today());
        } elseif (str_contains($smartFilter, 'yesterday')) {
            $query->whereDate('created_at', today()->subDay());
        } elseif (str_contains($smartFilter, 'this week')) {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif (str_contains($smartFilter, 'last week')) {
            $query->whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ]);
        } elseif (str_contains($smartFilter, 'this month')) {
            $query->whereMonth('created_at', now()->month);
        }
        
        // Status filters
        if (str_contains($smartFilter, 'active')) {
            $query->where('status', 'active');
        } elseif (str_contains($smartFilter, 'pending')) {
            $query->where('status', 'pending');
        } elseif (str_contains($smartFilter, 'completed')) {
            $query->where('status', 'completed');
        }
        
        // Sentiment filters (for calls)
        if (str_contains($smartFilter, 'positive')) {
            $query->whereJsonContains('analysis->sentiment', 'positive');
        } elseif (str_contains($smartFilter, 'negative')) {
            $query->whereJsonContains('analysis->sentiment', 'negative');
        }
        
        return $query;
    }
}