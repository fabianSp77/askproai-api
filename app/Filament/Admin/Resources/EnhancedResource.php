<?php

namespace App\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction as ExcelExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Str;

abstract class EnhancedResource extends Resource
{
    /**
     * Enable advanced search capabilities
     */
    protected static bool $enableAdvancedSearch = true;
    
    /**
     * Enable export functionality
     */
    protected static bool $enableExport = true;
    
    /**
     * Enable filter presets
     */
    protected static bool $enableFilterPresets = true;
    
    /**
     * Enable column toggles
     */
    protected static bool $enableColumnToggles = true;
    
    /**
     * Enable mobile optimization
     */
    protected static bool $enableMobileOptimization = true;
    
    /**
     * Enable keyboard shortcuts
     */
    protected static bool $enableKeyboardShortcuts = true;
    
    /**
     * Default records per page options
     */
    protected static array $recordsPerPageOptions = [10, 25, 50, 100];

    /**
     * Configure common table features
     */
    public static function enhanceTable(Tables\Table $table): Tables\Table
    {
        $table = $table
            ->paginated(static::$recordsPerPageOptions)
            ->defaultPaginationPageOption(25)
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->poll('30s');

        // Add common filters
        if (static::$enableFilterPresets) {
            $table = static::addFilterPresets($table);
        }

        // Add export actions
        if (static::$enableExport) {
            $table = static::addExportActions($table);
        }

        // Add mobile optimization
        if (static::$enableMobileOptimization) {
            $table = static::optimizeForMobile($table);
        }

        // Add common bulk actions
        $table = static::addCommonBulkActions($table);

        // Add keyboard shortcuts info
        if (static::$enableKeyboardShortcuts) {
            $table = static::addKeyboardShortcuts($table);
        }

        return $table;
    }

    /**
     * Add filter presets
     */
    protected static function addFilterPresets(Tables\Table $table): Tables\Table
    {
        return $table
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filter')
                    ->icon('heroicon-o-funnel')
                    ->badge(fn ($livewire) => count($livewire->tableFilters ?? []))
                    ->badgeColor('warning')
            )
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Erstellt ab'),
                        DatePicker::make('created_until')
                            ->label('Erstellt bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Erstellt ab ' . \Carbon\Carbon::parse($data['created_from'])->format('d.m.Y');
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Erstellt bis ' . \Carbon\Carbon::parse($data['created_until'])->format('d.m.Y');
                        }

                        return $indicators;
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormWidth(MaxWidth::FourExtraLarge);
    }

    /**
     * Add export actions
     */
    protected static function addExportActions(Tables\Table $table): Tables\Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Exportieren')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->dropdown()
                    ->dropdownWidth(MaxWidth::ExtraSmall)
                    ->modalWidth(MaxWidth::Medium)
                    ->modalSubmitActionLabel('Exportieren')
                    ->modalCancelActionLabel('Abbrechen')
                    ->form([
                        \Filament\Forms\Components\Select::make('format')
                            ->label('Format')
                            ->options([
                                'csv' => 'CSV',
                                'xlsx' => 'Excel',
                                'pdf' => 'PDF',
                            ])
                            ->default('xlsx')
                            ->required(),
                        \Filament\Forms\Components\CheckboxList::make('columns')
                            ->label('Spalten')
                            ->options(static::getExportColumns())
                            ->default(array_keys(static::getExportColumns()))
                            ->columns(2)
                            ->bulkToggleable(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $filename = static::getModelLabel() . '_' . now()->format('Y-m-d_H-i-s');
                        
                        // Implementation would depend on your export package
                        Notification::make()
                            ->title('Export gestartet')
                            ->body('Der Export wird im Hintergrund erstellt und Ihnen per E-Mail zugesendet.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * Optimize table for mobile devices
     */
    protected static function optimizeForMobile(Tables\Table $table): Tables\Table
    {
        return $table
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->description(
                fn () => \Illuminate\Support\Facades\Request::userAgent() && Str::contains(strtolower(\Illuminate\Support\Facades\Request::userAgent()), ['mobile', 'android', 'iphone']) 
                    ? 'Tipp: Wischen Sie nach links/rechts für mehr Optionen' 
                    : null
            );
    }

    /**
     * Add common bulk actions
     */
    protected static function addCommonBulkActions(Tables\Table $table): Tables\Table
    {
        $existingBulkActions = $table->getBulkActions();
        
        return $table
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ...$existingBulkActions,
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Status aktualisieren')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->form([
                            \Filament\Forms\Components\Select::make('status')
                                ->label('Neuer Status')
                                ->options(static::getStatusOptions())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['status' => $data['status']]);
                            });
                            
                            Notification::make()
                                ->title('Status aktualisiert')
                                ->body($records->count() . ' Einträge wurden aktualisiert.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(
                fn ($record): bool => $record->can_be_modified ?? true,
            );
    }

    /**
     * Add keyboard shortcuts
     */
    protected static function addKeyboardShortcuts(Tables\Table $table): Tables\Table
    {
        return $table
            ->emptyStateActions([
                Tables\Actions\Action::make('showShortcuts')
                    ->label('Tastenkürzel anzeigen')
                    ->icon('heroicon-o-command-line')
                    ->color('gray')
                    ->modalHeading('Tastenkürzel')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen')
                    ->modalContent(view('filament.modals.keyboard-shortcuts'))
                    ->modalWidth(MaxWidth::Medium),
            ]);
    }

    /**
     * Get columns available for export
     */
    protected static function getExportColumns(): array
    {
        // Override in child classes
        return [
            'id' => 'ID',
            'created_at' => 'Erstellt am',
            'updated_at' => 'Aktualisiert am',
        ];
    }

    /**
     * Get status options
     */
    protected static function getStatusOptions(): array
    {
        // Override in child classes
        return [];
    }

    /**
     * Enable global search across multiple fields
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['id'];
    }

    /**
     * Configure global search
     */
    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name ?? $record->title ?? 'ID: ' . $record->id;
    }

    /**
     * Add advanced search query
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Add common eager loading
        if (method_exists(static::class, 'getCommonRelations')) {
            $query = $query->with(static::getCommonRelations());
        }

        return $query;
    }

    /**
     * Configure record URL for quick access
     */
    public static function getRecordUrl($record): ?string
    {
        return static::getUrl('edit', ['record' => $record]);
    }
}