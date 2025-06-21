<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use App\Services\Stripe\EnhancedStripeInvoiceService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Company;
use Carbon\Carbon;
use App\Services\CacheWarmer;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;
    
    public ?array $usageStats = null;
    protected ?bool $isUsageBased = null;
    
    public function getRecord(): \Illuminate\Database\Eloquent\Model
    {
        // Eager load relationships to prevent N+1 queries
        return parent::getRecord()->load(['company', 'flexibleItems']);
    }
    
    protected function isUsageBasedInvoice(): bool
    {
        // Memoize the check to avoid repeated queries
        if ($this->isUsageBased === null) {
            $this->isUsageBased = CacheWarmer::isUsageBasedInvoice($this->record);
        }
        return $this->isUsageBased;
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Preserve creation mode
        $data['creation_mode'] = $this->record->creation_mode ?? 'manual';
        
        // Load flexible items into the form
        $flexibleItems = $this->record->flexibleItems()->ordered()->get();
        
        if ($flexibleItems->count() > 0) {
            // If we have flexible items, use those instead of the basic items repeater
            $data['items'] = $flexibleItems->map(function ($item) {
                return [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate_id' => $item->tax_rate_id,
                    'period_start' => $item->period_start,
                    'period_end' => $item->period_end,
                ];
            })->toArray();
        }
        
        // If this is a usage-based invoice, load the usage stats from metadata
        if ($this->record->creation_mode === 'usage' && isset($this->record->metadata['usage_stats'])) {
            $this->usageStats = $this->record->metadata['usage_stats'];
        }
        
        return $data;
    }
    
    public function form(Form $form): Form
    {
        // For usage-based invoices, show a special form
        if ($this->isUsageBasedInvoice()) {
            return $form->schema([
                // Usage info section
                Section::make('Nutzungsbasierte Rechnung')
                    ->description('Diese Rechnung wurde aus Nutzungsdaten generiert')
                    ->schema([
                        Placeholder::make('usage_summary')
                            ->content(fn () => new \Illuminate\Support\HtmlString(
                                $this->renderUsageSummary()
                            )),
                            
                        Grid::make(2)
                            ->schema([
                                Toggle::make('allow_manual_edits')
                                    ->label('Manuelle Anpassungen erlauben')
                                    ->helperText('Aktivieren Sie dies, um die generierten Positionen zu bearbeiten')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state) {
                                        if ($state) {
                                            Notification::make()
                                                ->title('Manueller Modus aktiviert')
                                                ->body('Sie können nun die Positionen bearbeiten. Die Änderungen werden beim Speichern übernommen.')
                                                ->warning()
                                                ->send();
                                        }
                                    }),
                                    
                                \Filament\Forms\Components\Actions::make([
                                    \Filament\Forms\Components\Actions\Action::make('refresh_usage')
                                        ->label('Nutzungsdaten aktualisieren')
                                        ->icon('heroicon-o-arrow-path')
                                        ->action(function () {
                                            $this->refreshUsageData();
                                        }),
                                ]),
                            ]),
                    ]),
                    
                // Standard invoice fields
                ...InvoiceResource::getFormSchema(),
                
                // Additional options for usage-based invoices
                Section::make('Erweiterte Optionen')
                    ->collapsed()
                    ->schema([
                        Toggle::make('add_custom_items')
                            ->label('Zusätzliche Positionen hinzufügen')
                            ->helperText('Fügen Sie weitere manuelle Positionen zur Rechnung hinzu')
                            ->reactive(),
                            
                        \Filament\Forms\Components\Repeater::make('custom_items')
                            ->label('Zusätzliche Positionen')
                            ->visible(fn (Get $get) => $get('add_custom_items'))
                            ->schema([
                                TextInput::make('description')
                                    ->label('Beschreibung')
                                    ->required(),
                                TextInput::make('quantity')
                                    ->label('Menge')
                                    ->numeric()
                                    ->default(1),
                                TextInput::make('unit_price')
                                    ->label('Einzelpreis')
                                    ->numeric()
                                    ->prefix('€')
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Position hinzufügen'),
                    ]),
            ]);
        }
        
        // For manual invoices, use the standard form
        return $form->schema(InvoiceResource::getFormSchema());
    }
    
    protected function renderUsageSummary(): string
    {
        if (!$this->usageStats) {
            return '<p class="text-gray-500">Keine Nutzungsdaten verfügbar</p>';
        }
        
        $stats = $this->usageStats;
        $periodStart = $this->record->period_start ? $this->record->period_start->format('d.m.Y') : 'N/A';
        $periodEnd = $this->record->period_end ? $this->record->period_end->format('d.m.Y') : 'N/A';
        
        $html = '<div class="bg-blue-50 p-4 rounded space-y-3">';
        $html .= '<div class="grid grid-cols-2 gap-4 text-sm">';
        $html .= '<div>';
        $html .= '<span class="text-gray-600">Abrechnungszeitraum:</span><br>';
        $html .= '<span class="font-medium">' . $periodStart . ' - ' . $periodEnd . '</span>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<span class="text-gray-600">Preismodell ID:</span><br>';
        $html .= '<span class="font-medium">' . ($stats['pricing_model_id'] ?? 'N/A') . '</span>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<span class="text-gray-600">Gesamtanrufe:</span><br>';
        $html .= '<span class="font-medium">' . ($stats['total_calls'] ?? 0) . '</span>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<span class="text-gray-600">Gesamtminuten:</span><br>';
        $html .= '<span class="font-medium">' . number_format($stats['total_minutes'] ?? 0, 1, ',', '.') . ' Min.</span>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<span class="text-gray-600">Inklusiv-Minuten:</span><br>';
        $html .= '<span class="font-medium">' . ($stats['included_minutes'] ?? 0) . ' Min.</span>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<span class="text-gray-600">Berechenbare Minuten:</span><br>';
        $html .= '<span class="font-medium text-red-600">' . number_format($stats['billable_minutes'] ?? 0, 1, ',', '.') . ' Min.</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    protected function refreshUsageData(): void
    {
        try {
            $service = new EnhancedStripeInvoiceService();
            $company = $this->record->company;
            
            // Use period dates from the invoice, or default to reasonable values
            $periodStart = $this->record->period_start ?: now()->startOfMonth();
            $periodEnd = $this->record->period_end ?: now();
            
            // Get fresh usage statistics
            $stats = $service->getUsageStatistics(
                $company,
                $periodStart,
                $periodEnd
            );
            
            // Update the invoice metadata
            $metadata = $this->record->metadata ?? [];
            $metadata['usage_stats'] = $stats;
            $this->record->update(['metadata' => $metadata]);
            
            // Reload usage stats
            $this->usageStats = $stats;
            
            Notification::make()
                ->title('Nutzungsdaten aktualisiert')
                ->body('Die Nutzungsdaten wurden erfolgreich aktualisiert.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Aktualisieren')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('finalize')
                ->label('Finalisieren & Versenden')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $service = new EnhancedStripeInvoiceService();
                    
                    if ($service->finalizeInvoice($this->record)) {
                        Notification::make()
                            ->title('Rechnung finalisiert')
                            ->body('Die Rechnung wurde finalisiert und an den Kunden versendet.')
                            ->success()
                            ->send();
                            
                        $this->redirect($this->getResource()::getUrl('index'));
                    } else {
                        Notification::make()
                            ->title('Fehler beim Finalisieren')
                            ->body('Die Rechnung konnte nicht finalisiert werden. Bitte prüfen Sie die Logs.')
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Rechnung finalisieren?')
                ->modalDescription('Nach der Finalisierung kann die Rechnung nicht mehr bearbeitet werden.')
                ->modalSubmitActionLabel('Ja, finalisieren')
                ->visible(fn () => $this->record->status === 'draft' && !$this->record->finalized_at),
                
            Actions\Action::make('preview')
                ->label('Vorschau')
                ->icon('heroicon-o-eye')
                ->modalContent(fn () => view('filament.admin.resources.invoice-preview', [
                    'invoice' => $this->record
                ]))
                ->modalHeading('Rechnungsvorschau')
                ->modalWidth('5xl')
                ->modalSubmitAction(false),
                
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'draft' && !$this->record->finalized_at),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Add audit log entry
        $auditLog = $this->record->audit_log ?? [];
        $auditLog[] = [
            'action' => 'updated',
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
            'changes' => array_keys(array_diff_assoc($data, $this->record->toArray())),
        ];
        
        $data['audit_log'] = $auditLog;
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Handle custom items for usage-based invoices
        if ($this->record->creation_mode === 'usage' && isset($this->data['custom_items']) && is_array($this->data['custom_items'])) {
            // Add custom items to flexible items
            $maxSortOrder = $this->record->flexibleItems()->max('sort_order') ?? 0;
            
            foreach ($this->data['custom_items'] as $index => $itemData) {
                $this->record->flexibleItems()->create([
                    'description' => $itemData['description'] ?? '',
                    'quantity' => $itemData['quantity'] ?? 1,
                    'unit' => 'Stück',
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'amount' => ($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0),
                    'tax_rate' => 19,
                    'sort_order' => $maxSortOrder + $index + 1,
                    'type' => 'custom',
                ]);
            }
        }
        
        // Update flexible items from form data only if manual editing is allowed
        if ($this->data['allow_manual_edits'] ?? false) {
            if (isset($this->data['items']) && is_array($this->data['items'])) {
                // Delete existing flexible items
                $this->record->flexibleItems()->delete();
                
                // Create new flexible items from form data
                foreach ($this->data['items'] as $index => $itemData) {
                    $this->record->flexibleItems()->create([
                        'description' => $itemData['description'] ?? '',
                        'quantity' => $itemData['quantity'] ?? 1,
                        'unit' => 'Stück', // Default unit since form doesn't have unit field
                        'unit_price' => $itemData['unit_price'] ?? 0,
                        'amount' => ($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0),
                        'tax_rate' => 19, // Default tax rate
                        'tax_rate_id' => $itemData['tax_rate_id'] ?? null,
                        'period_start' => $itemData['period_start'] ?? null,
                        'period_end' => $itemData['period_end'] ?? null,
                        'sort_order' => $index,
                        'type' => ($this->record->creation_mode === 'usage') ? 'usage' : 'custom',
                    ]);
                }
            }
        }
        
        // Recalculate totals if items changed
        if ($this->record->flexibleItems()->exists()) {
            $taxService = new \App\Services\TaxService();
            $company = $this->record->company;
            
            $subtotal = 0;
            $totalTax = 0;
            
            // Refresh the relationship to get updated items
            $this->record->load('flexibleItems');
            
            foreach ($this->record->flexibleItems as $item) {
                $amount = $item->quantity * $item->unit_price;
                // Since we use TaxService without Tax namespace, use simplified calculation
                $taxRate = $company->is_small_business ? 0 : 19;
                $taxAmount = $amount * ($taxRate / 100);
                
                $subtotal += $amount;
                $totalTax += $taxAmount;
            }
            
            $this->record->update([
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'total' => $subtotal + $totalTax,
            ]);
        }
    }
}