<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

class TransactionHistory extends Component
{
    use WithPagination;
    
    public $perPage = 20;
    public $search = '';
    public $filterType = '';
    public $filterDateFrom;
    public $filterDateTo;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // Stats for header
    public $totalSpent = 0;
    public $totalTopups = 0;
    public $transactionCount = 0;
    
    // Infinite scroll
    public $hasMorePages = true;
    public $page = 1;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => ''],
        'filterDateFrom' => ['except' => ''],
        'filterDateTo' => ['except' => '']
    ];
    
    public function mount()
    {
        $this->loadStats();
        $this->filterDateFrom = now()->subMonth()->format('Y-m-d');
        $this->filterDateTo = now()->format('Y-m-d');
    }
    
    /**
     * Load transaction statistics
     */
    public function loadStats()
    {
        $tenant = Auth::user()->tenant;
        
        // Cache stats for 60 seconds
        $stats = Cache::remember(
            "transaction.stats.{$tenant->id}",
            60,
            function () use ($tenant) {
                return [
                    'spent' => Transaction::where('tenant_id', $tenant->id)
                        ->where('type', 'usage')
                        ->sum('amount_cents') * -1,
                    'topups' => Transaction::where('tenant_id', $tenant->id)
                        ->where('type', 'topup')
                        ->sum('amount_cents'),
                    'count' => Transaction::where('tenant_id', $tenant->id)->count()
                ];
            }
        );
        
        $this->totalSpent = $stats['spent'];
        $this->totalTopups = $stats['topups'];
        $this->transactionCount = $stats['count'];
    }
    
    /**
     * Get paginated transactions with filters
     */
    public function getTransactionsProperty()
    {
        $tenant = Auth::user()->tenant;
        
        return Transaction::query()
            ->where('tenant_id', $tenant->id)
            ->when($this->search, function (Builder $query) {
                $query->where(function ($q) {
                    $q->where('description', 'like', '%' . $this->search . '%')
                      ->orWhere('reference', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterType, function (Builder $query) {
                $query->where('type', $this->filterType);
            })
            ->when($this->filterDateFrom, function (Builder $query) {
                $query->whereDate('created_at', '>=', $this->filterDateFrom);
            })
            ->when($this->filterDateTo, function (Builder $query) {
                $query->whereDate('created_at', '<=', $this->filterDateTo);
            })
            ->with(['call:id,duration_seconds', 'appointment:id,start_time'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }
    
    /**
     * Load more for infinite scroll
     */
    public function loadMore()
    {
        if ($this->hasMorePages) {
            $this->perPage += 20;
        }
    }
    
    /**
     * Sort by field
     */
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage();
    }
    
    /**
     * Export transactions to CSV
     */
    public function exportCsv()
    {
        $tenant = Auth::user()->tenant;
        
        $transactions = Transaction::where('tenant_id', $tenant->id)
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn($q) => $q->whereDate('created_at', '<=', $this->filterDateTo))
            ->orderBy('created_at', 'desc')
            ->get();
        
        $csv = "Datum,Typ,Beschreibung,Betrag,Guthaben danach\n";
        
        foreach ($transactions as $transaction) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s\n",
                $transaction->created_at->format('d.m.Y H:i'),
                $this->getTransactionTypeLabel($transaction->type),
                $transaction->description,
                number_format($transaction->amount_cents / 100, 2, ',', '.') . ' €',
                number_format($transaction->balance_after_cents / 100, 2, ',', '.') . ' €'
            );
        }
        
        return response()->streamDownload(
            function () use ($csv) {
                echo $csv;
            },
            'transaktionen_' . now()->format('Y-m-d') . '.csv',
            [
                'Content-Type' => 'text/csv',
                'Content-Encoding' => 'UTF-8',
            ]
        );
    }
    
    /**
     * Download invoice for topup transaction
     */
    public function downloadInvoice($transactionId)
    {
        $transaction = Transaction::where('tenant_id', Auth::user()->tenant->id)
            ->where('id', $transactionId)
            ->where('type', 'topup')
            ->firstOrFail();
        
        // Queue invoice generation
        dispatch(new \App\Jobs\GenerateInvoice($transaction));
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Rechnung wird erstellt und per E-Mail zugestellt'
        ]);
    }
    
    /**
     * Get transaction type label
     */
    private function getTransactionTypeLabel($type)
    {
        return match($type) {
            'topup' => 'Aufladung',
            'usage' => 'Verbrauch',
            'refund' => 'Rückerstattung',
            'adjustment' => 'Anpassung',
            default => $type
        };
    }
    
    /**
     * Reset filters
     */
    public function resetFilters()
    {
        $this->reset(['search', 'filterType', 'filterDateFrom', 'filterDateTo']);
        $this->filterDateFrom = now()->subMonth()->format('Y-m-d');
        $this->filterDateTo = now()->format('Y-m-d');
        $this->resetPage();
    }
    
    /**
     * Updated hook for search
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function render()
    {
        return view('livewire.customer.transaction-history', [
            'transactions' => $this->transactions
        ]);
    }
}