<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use App\Services\Stripe\EnhancedStripeInvoiceService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;
    
    public ?string $invoiceMode = 'manual';
    public ?array $usageStats = null;
    public bool $statsLoaded = false;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Rechnungstyp')
                    ->description('Wählen Sie, wie die Rechnung erstellt werden soll')
                    ->schema([
                        Radio::make('invoice_mode')
                            ->label('Erstellungsmodus')
                            ->options([
                                'manual' => 'Manuell erstellen',
                                'usage' => 'Aus Nutzungsdaten generieren',
                            ])
                            ->default('manual')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $this->invoiceMode = $state;
                                if ($state === 'manual') {
                                    $set('period_start', null);
                                    $set('period_end', null);
                                }
                            }),
                    ]),
                    
                Section::make('Zeitraum')
                    ->description('Wählen Sie den Abrechnungszeitraum für die Nutzungsdaten')
                    ->visible(fn (Get $get) => $get('invoice_mode') === 'usage')
                    ->schema([
                        Select::make('company_id')
                            ->label('Unternehmen')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive(),
                            
                        DatePicker::make('period_start')
                            ->label('Von')
                            ->required()
                            ->reactive(),
                            
                        DatePicker::make('period_end')
                            ->label('Bis')
                            ->required()
                            ->reactive(),
                            
                        \Filament\Forms\Components\Actions::make([
                            \Filament\Forms\Components\Actions\Action::make('load_usage')
                                ->label('Nutzungsdaten laden')
                                ->icon('heroicon-o-arrow-path')
                                ->action(function (Get $get, Set $set) {
                                    if ($get('company_id') && $get('period_start') && $get('period_end')) {
                                        // Store the current form state
                                        $set('invoice_mode', 'usage');
                                        
                                        $this->loadUsageStats(
                                            $get('company_id'), 
                                            $get('period_start'), 
                                            $get('period_end')
                                        );
                                        
                                        Notification::make()
                                            ->title('Daten geladen')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Bitte alle Felder ausfüllen')
                                            ->warning()
                                            ->send();
                                    }
                                })
                                ->visible(fn (Get $get) => 
                                    $get('company_id') && 
                                    $get('period_start') && 
                                    $get('period_end')
                                ),
                        ]),
                    ]),
                    
                Section::make('Nutzungsvorschau')
                    ->description('Übersicht der Anrufdaten und Kosten')
                    ->visible(fn (Get $get) => $get('invoice_mode') === 'usage' && $this->statsLoaded)
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('usage_stats')
                            ->content(function () {
                                if (!$this->usageStats) {
                                    return 'Keine Daten geladen';
                                }
                                
                                if (isset($this->usageStats['error'])) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="text-red-600">Fehler: ' . $this->usageStats['error'] . '</div>'
                                    );
                                }
                                
                                if (!($this->usageStats['has_pricing'] ?? false)) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="text-yellow-600">Kein Preismodell gefunden</div>'
                                    );
                                }
                                
                                $stats = $this->usageStats;
                                $html = '<div class="space-y-4">';
                                
                                // Period info
                                if (isset($this->data['period_start']) && isset($this->data['period_end'])) {
                                    $html .= '<div class="bg-indigo-50 p-4 rounded">';
                                    $html .= '<h4 class="font-semibold mb-2">Abrechnungszeitraum</h4>';
                                    $html .= '<p class="text-sm">' . \Carbon\Carbon::parse($this->data['period_start'])->format('d.m.Y') . ' - ' . \Carbon\Carbon::parse($this->data['period_end'])->format('d.m.Y') . '</p>';
                                    $html .= '</div>';
                                }
                                
                                // Pricing info
                                $html .= '<div class="bg-gray-50 p-4 rounded">';
                                $html .= '<h4 class="font-semibold mb-2">Preismodell</h4>';
                                $html .= '<div class="grid grid-cols-3 gap-4 text-sm">';
                                $html .= '<div>Grundgebühr: €' . number_format($stats['pricing']['monthly_base_fee'] ?? 0, 2, ',', '.') . '/Monat</div>';
                                $html .= '<div>Inklusiv-Minuten: ' . ($stats['pricing']['included_minutes'] ?? 0) . '</div>';
                                $html .= '<div>Minutenpreis: €' . number_format($stats['pricing']['price_per_minute'] ?? 0, 4, ',', '.') . '</div>';
                                $html .= '</div></div>';
                                
                                // Usage info
                                $html .= '<div class="bg-blue-50 p-4 rounded">';
                                $html .= '<h4 class="font-semibold mb-2">Nutzung</h4>';
                                $html .= '<div class="grid grid-cols-2 gap-4 text-sm">';
                                $html .= '<div>Anrufe: ' . ($stats['usage']['total_calls'] ?? 0) . '</div>';
                                $html .= '<div>Gesamtminuten: ' . number_format($stats['usage']['total_minutes'] ?? 0, 1, ',', '.') . '</div>';
                                $html .= '<div>Inklusiv genutzt: ' . number_format($stats['usage']['included_minutes_used'] ?? 0, 1, ',', '.') . '</div>';
                                $html .= '<div class="text-red-600">Berechenbare Minuten: ' . number_format($stats['usage']['billable_minutes'] ?? 0, 1, ',', '.') . '</div>';
                                $html .= '</div></div>';
                                
                                // Cost preview
                                $monthlyBaseFee = $stats['pricing']['monthly_base_fee'] ?? 0;
                                $billableMinutes = $stats['usage']['billable_minutes'] ?? 0;
                                $pricePerMinute = $stats['pricing']['price_per_minute'] ?? 0;
                                
                                // Calculate pro-rated base fee if period dates are available
                                $baseFee = $monthlyBaseFee;
                                if (isset($this->data['period_start']) && isset($this->data['period_end'])) {
                                    $start = \Carbon\Carbon::parse($this->data['period_start']);
                                    $end = \Carbon\Carbon::parse($this->data['period_end']);
                                    $days = $start->diffInDays($end) + 1;
                                    $monthDays = 30; // Average month
                                    $baseFee = round(($monthlyBaseFee / $monthDays) * $days, 2);
                                }
                                
                                $usageCost = $billableMinutes * $pricePerMinute;
                                $subtotal = $baseFee + $usageCost;
                                
                                $html .= '<div class="bg-green-50 p-4 rounded">';
                                $html .= '<h4 class="font-semibold mb-2">Kostenvorschau</h4>';
                                $html .= '<div class="space-y-1 text-sm">';
                                $html .= '<div class="flex justify-between"><span>Grundgebühr:</span><span>€' . number_format($baseFee, 2, ',', '.') . '</span></div>';
                                if ($billableMinutes > 0) {
                                    $html .= '<div class="flex justify-between"><span>Zusätzliche Minuten:</span><span>€' . number_format($usageCost, 2, ',', '.') . '</span></div>';
                                }
                                $html .= '<div class="flex justify-between font-semibold border-t pt-1"><span>Zwischensumme:</span><span>€' . number_format($subtotal, 2, ',', '.') . '</span></div>';
                                $html .= '</div></div>';
                                
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            }),
                    ]),
                    
                // Action button to create invoice from usage
                Section::make()
                    ->visible(fn (Get $get) => 
                        $get('invoice_mode') === 'usage' && 
                        $this->usageStats !== null && 
                        ($this->usageStats['has_pricing'] ?? false)
                    )
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('ready_to_create')
                            ->content('Die Nutzungsdaten wurden geladen. Klicken Sie auf "Erstellen", um die Rechnung zu generieren.'),
                    ]),
                    
                // Standard form fields for manual mode
                Section::make()
                    ->visible(fn (Get $get) => $get('invoice_mode') === 'manual')
                    ->schema(InvoiceResource::getFormSchema()),
            ]);
    }
    
    protected function loadUsageStats(string $companyId, string $periodStart, string $periodEnd): void
    {
        try {
            $company = Company::find($companyId);
            if (!$company) {
                $this->usageStats = ['error' => 'Unternehmen nicht gefunden'];
                return;
            }
            
            // Set company context for tenant scope
            app()->instance('current_company_id', $company->id);
            
            $service = new EnhancedStripeInvoiceService();
            $this->usageStats = $service->getUsageStatistics(
                $company,
                Carbon::parse($periodStart),
                Carbon::parse($periodEnd)
            );
            
            // Mark as loaded
            $this->statsLoaded = true;
            
            // Don't refresh the entire form, just update the state
            $this->invoiceMode = 'usage';
            
        } catch (\Exception $e) {
            $this->usageStats = [
                'error' => $e->getMessage(),
                'has_pricing' => false,
                'debug' => [
                    'company_id' => $companyId,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ]
            ];
            $this->statsLoaded = true;
        }
    }
    
    protected function handleRecordCreation(array $data): Model
    {
        $service = new EnhancedStripeInvoiceService();
        $company = Company::find($data['company_id']);
        
        if (!$company) {
            throw new \Exception('Unternehmen nicht gefunden');
        }
        
        // Check if usage-based invoice
        if ($data['invoice_mode'] === 'usage') {
            // Create usage-based invoice
            $invoice = $service->createUsageBasedInvoice(
                $company,
                Carbon::parse($data['period_start']),
                Carbon::parse($data['period_end']),
                [
                    'branch_id' => $data['branch_id'] ?? null,
                    'invoice_date' => $data['invoice_date'] ?? now(),
                    'due_date' => $data['due_date'] ?? now()->addDays(30),
                    'payment_terms' => $data['payment_terms'] ?? 'net30',
                ]
            );
            
            return $invoice;
        }
        
        // For manual mode, proceed with normal creation
        return parent::handleRecordCreation($data);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Add creation_mode to data
        $data['creation_mode'] = $data['invoice_mode'] ?? 'manual';
        
        // Only for manual mode
        if (($data['invoice_mode'] ?? 'manual') === 'manual' && empty($data['invoice_number'])) {
            $service = new EnhancedStripeInvoiceService();
            $company = Company::find($data['company_id']);
            
            if ($company) {
                // Create draft invoice to get number
                $draftInvoice = $service->createDraftInvoice($company, [
                    'branch_id' => $data['branch_id'] ?? null,
                    'invoice_date' => $data['invoice_date'] ?? now(),
                    'due_date' => $data['due_date'] ?? now()->addDays(30),
                    'period_start' => $data['period_start'] ?? null,
                    'period_end' => $data['period_end'] ?? null,
                    'creation_mode' => 'manual',
                ]);
                
                $data['invoice_number'] = $draftInvoice->invoice_number;
                
                // Delete the draft as we'll create it properly through Filament
                $draftInvoice->delete();
            }
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Rechnung erstellt')
            ->body('Die Rechnung wurde erfolgreich als Entwurf erstellt.')
            ->success()
            ->send();
    }
}