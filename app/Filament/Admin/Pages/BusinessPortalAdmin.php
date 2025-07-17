<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;
use App\Models\PortalUser;
use App\Models\PrepaidBalance;
use App\Models\BalanceTransaction;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class BusinessPortalAdmin extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = '🏢 Kundenverwaltung';
    protected static ?string $navigationGroup = 'Multi-Company Management';
    protected static ?int $navigationSort = -10;
    protected static string $view = 'filament.admin.pages.business-portal-admin';
    
    public ?int $selectedCompanyId = null;
    public ?array $companyStats = null;
    public ?array $recentTransactions = null;
    
    // Form properties for balance adjustment
    public string $adjustmentType = 'credit';
    public string $adjustmentAmount = '';
    public string $adjustmentDescription = 'Manuelle Anpassung durch Admin';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        return $user->hasRole('Super Admin');
    }
    
    public function mount(): void
    {
        // Check if we should open a specific company portal
        if (request()->has('open_company')) {
            $companyId = request()->get('open_company');
            $this->selectedCompanyId = intval($companyId);
            
            // Auto-open the portal after page loads
            $this->dispatch('auto-open-portal', ['companyId' => $companyId]);
        }
        
        $this->loadCompanyData();
    }
    
    protected function getFormSchema(): array
    {
        return [
            Select::make('selectedCompanyId')
                ->label('Firma auswählen')
                ->options(Company::pluck('name', 'id'))
                ->searchable()
                ->reactive()
                ->afterStateUpdated(fn () => $this->loadCompanyData()),
        ];
    }
    
    public function loadCompanyData(): void
    {
        if (!$this->selectedCompanyId) {
            $this->companyStats = null;
            $this->recentTransactions = null;
            return;
        }
        
        $company = Company::find($this->selectedCompanyId);
        if (!$company) {
            return;
        }
        
        // Load prepaid balance
        $balance = PrepaidBalance::where('company_id', $company->id)->first();
        
        // Load statistics
        $this->companyStats = [
            'company' => $company,
            'balance' => $balance,
            'effective_balance' => $balance ? $balance->getEffectiveBalance() : 0,
            'portal_users' => PortalUser::where('company_id', $company->id)->count(),
            'monthly_usage' => $this->getMonthlyUsage($company->id),
            'last_topup' => $this->getLastTopup($company->id),
        ];
        
        // Load recent transactions
        $this->recentTransactions = BalanceTransaction::where('company_id', $company->id)
            ->latest()
            ->limit(10)
            ->get()
            ->toArray();
    }
    
    protected function getMonthlyUsage(int $companyId): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        
        $stats = DB::table('calls')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0)
            ->select(
                DB::raw('COUNT(*) as call_count'),
                DB::raw('SUM(duration_sec) / 60 as total_minutes')
            )
            ->first();
        
        $charges = DB::table('balance_transactions')
            ->where('company_id', $companyId)
            ->where('type', 'debit')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        
        return [
            'calls' => $stats->call_count ?? 0,
            'minutes' => round($stats->total_minutes ?? 0, 0),
            'charges' => $charges,
        ];
    }
    
    protected function getLastTopup(int $companyId): ?array
    {
        $topup = BalanceTransaction::where('company_id', $companyId)
            ->where('type', 'topup')
            ->latest()
            ->first();
        
        if (!$topup) {
            return null;
        }
        
        return [
            'date' => $topup->created_at,
            'amount' => $topup->amount,
            'status' => 'completed',
        ];
    }
    
    public function openCustomerPortal(): void
    {
        if (!$this->selectedCompanyId) {
            Notification::make()
                ->title('Keine Firma ausgewählt')
                ->danger()
                ->send();
            return;
        }
        
        // Generate a temporary access token for admin
        $token = $this->generateAdminAccessToken($this->selectedCompanyId);
        
        // Try Livewire redirect first
        try {
            $this->redirect('/business/admin-access?token=' . $token);
        } catch (\Exception $e) {
            // Fallback: Use JavaScript redirect via dispatch
            $this->dispatch('redirect-to-portal', [
                'url' => '/business/admin-access?token=' . $token
            ]);
        }
    }
    
    public function openPortalForCompany(int $companyId): void
    {
        $this->selectedCompanyId = $companyId;
        
        // Generate token and redirect
        $token = $this->generateAdminAccessToken($companyId);
        
        // Try Livewire redirect first
        try {
            $this->redirect('/business/admin-access?token=' . $token);
        } catch (\Exception $e) {
            // Fallback: Use JavaScript redirect via dispatch
            $this->dispatch('redirect-to-portal', [
                'url' => '/business/admin-access?token=' . $token
            ]);
        }
    }
    
    public function adjustBalance(): void
    {
        if (!$this->selectedCompanyId) {
            return;
        }
        
        $this->dispatch('open-modal', id: 'adjust-balance-modal');
    }
    
    public function processBalanceAdjustment(): void
    {
        $company = Company::find($this->selectedCompanyId);
        if (!$company) {
            return;
        }
        
        $balance = PrepaidBalance::firstOrCreate(
            ['company_id' => $company->id],
            [
                'balance' => 0,
                'reserved_balance' => 0,
                'low_balance_threshold' => 20.00,
            ]
        );
        
        $amount = floatval($this->adjustmentAmount);
        $type = $this->adjustmentType;
        $description = $this->adjustmentDescription ?: 'Manuelle Anpassung durch Admin';
        
        try {
            DB::transaction(function () use ($balance, $amount, $type, $description) {
                if ($type === 'credit') {
                    $balance->addBalance($amount, $description, 'admin_adjustment');
                } else {
                    $balance->deductBalance($amount, $description, 'admin_adjustment');
                }
            });
            
            Notification::make()
                ->title('Guthaben angepasst')
                ->success()
                ->send();
            
            $this->loadCompanyData();
            
            // Reset form
            $this->adjustmentAmount = '';
            $this->adjustmentDescription = 'Manuelle Anpassung durch Admin';
            
            $this->dispatch('close-modal', id: 'adjust-balance-modal');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Anpassen des Guthabens')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function generateAdminAccessToken(int $companyId): string
    {
        // Create a temporary token for admin access
        $token = bin2hex(random_bytes(32));
        
        // Store in cache for 15 minutes
        cache()->put('admin_portal_access_' . $token, [
            'admin_id' => auth()->id(),
            'company_id' => $companyId,
            'created_at' => now(),
            'redirect_to' => '/business/dashboard', // Explicitly set redirect
        ], now()->addMinutes(15));
        
        return $token;
    }
    
    public function getAllCompaniesData(): array
    {
        return Company::with(['prepaidBalance', 'portalUsers'])
            ->get()
            ->map(function ($company) {
                $balance = $company->prepaidBalance;
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'balance' => $balance?->balance ?? 0,
                    'reserved_balance' => $balance?->reserved_balance ?? 0,
                    'effective_balance' => $balance ? $balance->getEffectiveBalance() : 0,
                    'portal_users' => $company->portalUsers->count(),
                    'created_at' => $company->created_at,
                ];
            })
            ->toArray();
    }
}