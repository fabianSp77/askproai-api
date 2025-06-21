<?php

namespace App\Filament\Admin\Pages;

use App\Models\Company;
use App\Services\TaxService;
use App\Services\DatevExportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaxConfiguration extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Abrechnung';
    protected static ?string $navigationLabel = 'Steuer & Compliance';
    protected static ?int $navigationSort = 50;
    protected static string $view = 'filament.admin.pages.tax-configuration';

    public ?array $companyData = [];
    public ?array $taxRates = [];
    public ?array $datevConfig = [];
    public ?array $thresholdStatus = null;

    public function mount(): void
    {
        $company = auth()->user()->company ?? Company::first();
        
        $this->companyData = [
            'tax_number' => $company->tax_number,
            'vat_id' => $company->vat_id,
            'is_small_business' => $company->is_small_business,
            'invoice_prefix' => $company->invoice_prefix,
            'payment_terms' => $company->payment_terms,
        ];

        $this->loadTaxRates();
        $this->loadDatevConfig();
        $this->checkThresholdStatus();
    }

    protected function loadTaxRates(): void
    {
        $company = auth()->user()->company ?? Company::first();
        
        $this->taxRates = DB::table('tax_rates')
            ->where('company_id', $company->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('rate', 'desc')
            ->get()
            ->map(fn($rate) => (array) $rate)
            ->toArray();
    }

    protected function loadDatevConfig(): void
    {
        $company = auth()->user()->company ?? Company::first();
        
        $config = DB::table('datev_configurations')
            ->where('company_id', $company->id)
            ->first();

        if ($config) {
            $this->datevConfig = (array) $config;
            $this->datevConfig['account_mapping'] = json_decode($config->account_mapping ?? '{}', true);
        } else {
            $this->datevConfig = [
                'consultant_number' => '',
                'client_number' => '',
                'export_format' => 'EXTF',
                'account_mapping' => [
                    'revenue_account' => '8400',
                    'debitor_account' => '10000',
                    'tax_19_account' => '1576',
                    'tax_7_account' => '1571',
                ],
            ];
        }
    }

    protected function checkThresholdStatus(): void
    {
        $company = auth()->user()->company ?? Company::first();
        $taxService = app(TaxService::class);
        $this->thresholdStatus = $taxService->checkSmallBusinessThreshold($company);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tax Configuration')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Grundeinstellungen')
                            ->schema([
                                Forms\Components\Section::make('Steuerinformationen')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('companyData.tax_number')
                                                    ->label('Steuernummer')
                                                    ->placeholder('123/456/78901')
                                                    ->helperText('Ihre Steuernummer vom Finanzamt'),
                                                    
                                                Forms\Components\TextInput::make('companyData.vat_id')
                                                    ->label('USt-IdNr.')
                                                    ->placeholder('DE123456789')
                                                    ->helperText('Für innergemeinschaftliche Lieferungen')
                                                    ->suffixAction(
                                                        Forms\Components\Actions\Action::make('validate')
                                                            ->label('Prüfen')
                                                            ->action(fn () => $this->validateVatId())
                                                    ),
                                            ]),
                                            
                                        Forms\Components\Toggle::make('companyData.is_small_business')
                                            ->label('Kleinunternehmer nach §19 UStG')
                                            ->helperText('Keine Umsatzsteuer bei Umsatz < 22.000€')
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state) => $this->updateSmallBusinessStatus($state)),
                                            
                                        Forms\Components\Section::make('Schwellenwert-Status')
                                            ->visible(fn () => $this->thresholdStatus !== null)
                                            ->schema([
                                                Forms\Components\Placeholder::make('threshold_info')
                                                    ->content(fn () => $this->getThresholdStatusHtml()),
                                            ]),
                                    ]),
                                    
                                Forms\Components\Section::make('Rechnungseinstellungen')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('companyData.invoice_prefix')
                                                    ->label('Rechnungspräfix')
                                                    ->placeholder('INV')
                                                    ->maxLength(5),
                                                    
                                                Forms\Components\Select::make('companyData.payment_terms')
                                                    ->label('Zahlungsbedingungen')
                                                    ->options([
                                                        'due_on_receipt' => 'Sofort fällig',
                                                        'net15' => 'Netto 15 Tage',
                                                        'net30' => 'Netto 30 Tage',
                                                        'net60' => 'Netto 60 Tage',
                                                    ]),
                                            ]),
                                    ]),
                            ]),
                            
                        Forms\Components\Tabs\Tab::make('Steuersätze')
                            ->schema([
                                Forms\Components\Section::make('Konfigurierte Steuersätze')
                                    ->schema([
                                        Forms\Components\Repeater::make('taxRates')
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Bezeichnung')
                                                    ->required(),
                                                    
                                                Forms\Components\TextInput::make('rate')
                                                    ->label('Steuersatz (%)')
                                                    ->numeric()
                                                    ->required()
                                                    ->suffix('%'),
                                                    
                                                Forms\Components\Toggle::make('is_default')
                                                    ->label('Standard'),
                                                    
                                                Forms\Components\TextInput::make('stripe_tax_rate_id')
                                                    ->label('Stripe Tax Rate ID')
                                                    ->disabled(),
                                            ])
                                            ->columns(4)
                                            ->defaultItems(0)
                                            ->addActionLabel('Steuersatz hinzufügen'),
                                    ]),
                            ]),
                            
                        Forms\Components\Tabs\Tab::make('DATEV Export')
                            ->schema([
                                Forms\Components\Section::make('DATEV Konfiguration')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('datevConfig.consultant_number')
                                                    ->label('Beraternummer')
                                                    ->numeric()
                                                    ->maxLength(7),
                                                    
                                                Forms\Components\TextInput::make('datevConfig.client_number')
                                                    ->label('Mandantennummer')
                                                    ->numeric()
                                                    ->maxLength(5),
                                                    
                                                Forms\Components\Select::make('datevConfig.export_format')
                                                    ->label('Export-Format')
                                                    ->options([
                                                        'EXTF' => 'EXTF (Erweitert)',
                                                        'CSV' => 'CSV',
                                                    ]),
                                            ]),
                                            
                                        Forms\Components\Section::make('Konten-Zuordnung')
                                            ->schema([
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('datevConfig.account_mapping.revenue_account')
                                                            ->label('Erlöskonto')
                                                            ->default('8400'),
                                                            
                                                        Forms\Components\TextInput::make('datevConfig.account_mapping.debitor_account')
                                                            ->label('Debitorenkonto')
                                                            ->default('10000'),
                                                            
                                                        Forms\Components\TextInput::make('datevConfig.account_mapping.tax_19_account')
                                                            ->label('Vorsteuerkonto 19%')
                                                            ->default('1576'),
                                                            
                                                        Forms\Components\TextInput::make('datevConfig.account_mapping.tax_7_account')
                                                            ->label('Vorsteuerkonto 7%')
                                                            ->default('1571'),
                                                    ]),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->statePath('data')
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Speichern')
                ->action('save'),
                
            Action::make('exportDatev')
                ->label('DATEV Export')
                ->action('exportDatev')
                ->color('secondary'),
                
            Action::make('checkThreshold')
                ->label('Schwellenwerte prüfen')
                ->action('checkThreshold')
                ->color('warning'),
        ];
    }

    public function save(): void
    {
        $company = auth()->user()->company ?? Company::first();

        // Update company data
        $company->update($this->companyData);

        // Save tax rates
        $this->saveTaxRates();

        // Save DATEV config
        $this->saveDatevConfig();

        Notification::make()
            ->title('Steuereinstellungen gespeichert')
            ->success()
            ->send();
    }

    protected function saveTaxRates(): void
    {
        $company = auth()->user()->company ?? Company::first();

        // Delete existing rates not in the list
        $existingIds = collect($this->taxRates)->pluck('id')->filter();
        DB::table('tax_rates')
            ->where('company_id', $company->id)
            ->whereNotIn('id', $existingIds)
            ->delete();

        foreach ($this->taxRates as $rate) {
            if (isset($rate['id'])) {
                DB::table('tax_rates')
                    ->where('id', $rate['id'])
                    ->update([
                        'name' => $rate['name'],
                        'rate' => $rate['rate'],
                        'is_default' => $rate['is_default'] ?? false,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('tax_rates')->insert([
                    'company_id' => $company->id,
                    'name' => $rate['name'],
                    'rate' => $rate['rate'],
                    'is_default' => $rate['is_default'] ?? false,
                    'valid_from' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    protected function saveDatevConfig(): void
    {
        $company = auth()->user()->company ?? Company::first();

        DB::table('datev_configurations')->updateOrInsert(
            ['company_id' => $company->id],
            [
                'consultant_number' => $this->datevConfig['consultant_number'] ?? null,
                'client_number' => $this->datevConfig['client_number'] ?? null,
                'export_format' => $this->datevConfig['export_format'] ?? 'EXTF',
                'account_mapping' => json_encode($this->datevConfig['account_mapping'] ?? []),
                'is_active' => true,
                'updated_at' => now(),
            ]
        );
    }

    public function validateVatId(): void
    {
        $vatId = $this->companyData['vat_id'] ?? '';
        
        if (empty($vatId)) {
            Notification::make()
                ->title('Bitte USt-IdNr. eingeben')
                ->warning()
                ->send();
            return;
        }

        $taxService = app(TaxService::class);
        $result = $taxService->validateVatId($vatId);

        if ($result['valid']) {
            Notification::make()
                ->title('USt-IdNr. gültig')
                ->body("Firmenname: " . ($result['name'] ?? 'N/A'))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('USt-IdNr. ungültig')
                ->body($result['error'] ?? 'Validierung fehlgeschlagen')
                ->danger()
                ->send();
        }
    }

    public function updateSmallBusinessStatus(bool $isSmallBusiness): void
    {
        if (!$isSmallBusiness && $this->thresholdStatus && $this->thresholdStatus['current_revenue'] > 0) {
            Notification::make()
                ->title('Hinweis')
                ->body('Bitte beachten Sie die steuerlichen Auswirkungen beim Wechsel zur Regelbesteuerung.')
                ->warning()
                ->send();
        }
    }

    public function checkThreshold(): void
    {
        $this->checkThresholdStatus();

        if ($this->thresholdStatus['action_required']) {
            Notification::make()
                ->title('Aktion erforderlich')
                ->body($this->thresholdStatus['message'])
                ->danger()
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title('Schwellenwerte geprüft')
                ->body($this->thresholdStatus['message'] ?? 'Alles im grünen Bereich')
                ->success()
                ->send();
        }
    }

    public function exportDatev(): void
    {
        $company = auth()->user()->company ?? Company::first();
        
        // Simple export for current month
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        try {
            $datevExport = app(DatevExportService::class);
            $export = $datevExport->exportInvoices(
                $company,
                $startDate,
                $endDate,
                $this->datevConfig['export_format'] ?? 'EXTF'
            );

            // In production, this would trigger a download
            Notification::make()
                ->title('DATEV Export erstellt')
                ->body("Format: {$export['format']}, Buchungen: {$export['bookings_count']}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Export fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getThresholdStatusHtml(): string
    {
        if (!$this->thresholdStatus) {
            return '';
        }

        $status = $this->thresholdStatus;
        $currentRevenue = number_format($status['current_revenue'], 2, ',', '.') . ' €';
        $previousRevenue = number_format($status['previous_revenue'], 2, ',', '.') . ' €';
        
        $color = match($status['threshold_status']) {
            'exceeded' => 'danger',
            'warning' => 'warning',
            default => 'success',
        };

        $icon = match($status['threshold_status']) {
            'exceeded' => '❌',
            'warning' => '⚠️',
            default => '✅',
        };

        return <<<HTML
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">{$icon}</span>
                    <span class="text-{$color}-600 font-semibold">{$status['message']}</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Aktuelles Jahr</p>
                        <p class="text-lg font-semibold">{$currentRevenue}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Vorjahr</p>
                        <p class="text-lg font-semibold">{$previousRevenue}</p>
                    </div>
                </div>
            </div>
        HTML;
    }
}