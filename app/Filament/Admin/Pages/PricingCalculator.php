<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use App\Models\Company;

class PricingCalculator extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    
    protected static ?string $navigationGroup = 'Abrechnung';
    
    protected static ?string $navigationLabel = 'Preiskalkulator';
    
    protected static string $view = 'filament.admin.pages.pricing-calculator';
    
    protected static ?int $navigationSort = 2;
    
    public ?array $data = [];
    
    // Form fields
    public ?int $company_id = null;
    public ?int $estimated_minutes = 100;
    public ?string $package = 'starter';
    public bool $is_small_business = false;
    public ?int $custom_minutes = null;
    public ?float $custom_price = null;
    
    public function mount(): void
    {
        $this->form->fill([
            'estimated_minutes' => 100,
            'package' => 'starter',
            'is_small_business' => false,
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Kalkulations-Parameter')
                    ->description('Wählen Sie die Parameter für die Preisberechnung')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('company_id')
                                    ->label('Bestehendes Unternehmen (optional)')
                                    ->options(Company::pluck('name', 'id'))
                                    ->searchable()
                                    ->placeholder('Neues Unternehmen')
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        if ($state) {
                                            $company = Company::find($state);
                                            $set('is_small_business', $company->is_small_business);
                                        }
                                    }),
                                    
                                Select::make('is_small_business')
                                    ->label('Kleinunternehmer')
                                    ->boolean()
                                    ->default(false)
                                    ->reactive()
                                    ->helperText('Kleinunternehmer nach §19 UStG (0% MwSt)'),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('estimated_minutes')
                                    ->label('Geschätzte Minuten pro Monat')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(10000)
                                    ->step(10)
                                    ->reactive()
                                    ->suffix('Minuten'),
                                    
                                Select::make('package')
                                    ->label('Paket')
                                    ->options([
                                        'starter' => 'Starter (100 Min inkl.)',
                                        'professional' => 'Professional (500 Min inkl.)',
                                        'enterprise' => 'Enterprise (Unbegrenzt)',
                                        'custom' => 'Individuell',
                                    ])
                                    ->required()
                                    ->reactive(),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('custom_minutes')
                                    ->label('Individuelle Inklusiv-Minuten')
                                    ->numeric()
                                    ->visible(fn (Get $get) => $get('package') === 'custom')
                                    ->reactive(),
                                    
                                TextInput::make('custom_price')
                                    ->label('Individueller Grundpreis')
                                    ->numeric()
                                    ->prefix('€')
                                    ->visible(fn (Get $get) => $get('package') === 'custom')
                                    ->reactive(),
                            ]),
                    ]),
                    
            ])
            ->statePath('data');
    }
    
    public function calculate(): void
    {
        $data = $this->form->getState();
        
        // Package details
        $packages = [
            'starter' => [
                'name' => 'Starter',
                'base_price' => 49,
                'included_minutes' => 100,
                'overage_price' => 0.29,
            ],
            'professional' => [
                'name' => 'Professional',
                'base_price' => 149,
                'included_minutes' => 500,
                'overage_price' => 0.19,
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'base_price' => 499,
                'included_minutes' => PHP_INT_MAX,
                'overage_price' => 0,
            ],
            'custom' => [
                'name' => 'Individuell',
                'base_price' => $data['custom_price'] ?? 99,
                'included_minutes' => $data['custom_minutes'] ?? 200,
                'overage_price' => 0.25,
            ],
        ];
        
        $package = $packages[$data['package']];
        $estimatedMinutes = $data['estimated_minutes'];
        $isSmallBusiness = $data['is_small_business'];
        
        // Calculate costs
        $basePrice = $package['base_price'];
        $overageMinutes = max(0, $estimatedMinutes - $package['included_minutes']);
        $overageCost = $overageMinutes * $package['overage_price'];
        $subtotal = $basePrice + $overageCost;
        
        // Tax calculation
        $taxRate = $isSmallBusiness ? 0 : 19;
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;
        
        // ROI calculation
        $hoursSaved = $estimatedMinutes / 60;
        $employeeCostPerHour = 25; // Average employee cost
        $potentialSavings = $hoursSaved * $employeeCostPerHour;
        $roi = $potentialSavings - $total;
        $roiPercentage = $total > 0 ? ($roi / $total) * 100 : 0;
        
        // Update view data
        $this->data = array_merge($data, [
            'package_details' => $package,
            'base_price' => $basePrice,
            'overage_minutes' => $overageMinutes,
            'overage_cost' => $overageCost,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'hours_saved' => $hoursSaved,
            'potential_savings' => $potentialSavings,
            'roi' => $roi,
            'roi_percentage' => $roiPercentage,
        ]);
        
        // Trigger form update
        $this->form->fill($this->data);
    }
    
    public function generateQuote(): void
    {
        if (empty($this->data['total'])) {
            Notification::make()
                ->title('Bitte erst kalkulieren')
                ->warning()
                ->send();
            return;
        }
        
        // TODO: Generate PDF quote
        Notification::make()
            ->title('Angebot erstellt')
            ->body('Das Angebot wurde erfolgreich generiert und kann heruntergeladen werden.')
            ->success()
            ->send();
    }
}