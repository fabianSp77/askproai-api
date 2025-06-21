<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Company;
use App\Models\Branch;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class TestLivewireDropdown extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'System & Überwachung';
    protected static ?string $navigationLabel = 'Test Livewire Dropdown';
    protected static ?int $navigationSort = 999;
    protected static string $view = 'filament.admin.pages.test-livewire-dropdown';
    
    public ?array $data = [];
    
    public ?int $company_id = null;
    public ?string $branch_id = null;
    public bool $debugMode = true;
    public array $debugLog = [];
    
    public function mount(): void
    {
        $this->form->fill([
            'company_id' => null,
            'branch_id' => null,
        ]);
        
        $this->logDebug('Component mounted');
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Test Reactive Dropdowns')
                    ->description('Testing different approaches for reactive dropdowns')
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->options(Company::pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $this->logDebug("Company changed to: $state");
                                $this->company_id = $state;
                                $this->branch_id = null;
                                $set('branch_id', null);
                                
                                // Force refresh of branch options
                                $this->dispatch('company-changed', companyId: $state);
                            }),
                            
                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                $this->logDebug("Getting branches for company: $companyId");
                                
                                if (!$companyId) {
                                    return [];
                                }
                                
                                $branches = Branch::withoutGlobalScopes()
                                    ->where('company_id', $companyId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                                    ->toArray();
                                    
                                $this->logDebug("Found " . count($branches) . " branches");
                                
                                return $branches;
                            })
                            ->searchable()
                            ->disabled(fn (callable $get) => empty($get('company_id')))
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->logDebug("Branch changed to: $state");
                                $this->branch_id = $state;
                            }),
                    ])
            ])
            ->statePath('data');
    }
    
    private function logDebug(string $message): void
    {
        if ($this->debugMode) {
            $this->debugLog[] = [
                'time' => now()->format('H:i:s.u'),
                'message' => $message,
            ];
        }
    }
    
    public function submit(): void
    {
        $data = $this->form->getState();
        
        Notification::make()
            ->title('Form Submitted')
            ->body("Company: {$data['company_id']}, Branch: {$data['branch_id']}")
            ->success()
            ->send();
    }
    
    public function clearDebugLog(): void
    {
        $this->debugLog = [];
        $this->logDebug('Debug log cleared');
    }
}