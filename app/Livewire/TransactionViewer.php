<?php

namespace App\Livewire;

use App\Models\Transaction;
use Livewire\Component;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TransactionViewer extends Component
{
    public Transaction $transaction;
    public array $relatedTransactions = [];
    public bool $showTimeline = false;
    
    public function mount($id)
    {
        try {
            $this->transaction = Transaction::with([
                'tenant',
                'call',
                'appointment',
                'topup'
            ])->findOrFail($id);
            
            // Load related transactions for timeline
            if ($this->transaction->tenant_id) {
                $this->relatedTransactions = Transaction::where('tenant_id', $this->transaction->tenant_id)
                    ->where('id', '!=', $this->transaction->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->toArray();
            }
        } catch (ModelNotFoundException $e) {
            abort(404, 'Transaktion nicht gefunden');
        }
    }
    
    public function toggleTimeline()
    {
        $this->showTimeline = !$this->showTimeline;
    }
    
    public function getFormattedAmount($cents)
    {
        return number_format($cents / 100, 2, ',', '.') . ' €';
    }
    
    public function getTypeLabel($type)
    {
        return match($type) {
            'topup' => 'Aufladung',
            'usage' => 'Verbrauch',
            'refund' => 'Erstattung',
            'adjustment' => 'Anpassung',
            'bonus' => 'Bonus',
            'fee' => 'Gebühr',
            default => ucfirst($type)
        };
    }
    
    public function getTypeColor($type)
    {
        return match($type) {
            'topup' => 'success',
            'usage' => 'danger',
            'refund' => 'warning',
            'adjustment' => 'info',
            'bonus' => 'primary',
            'fee' => 'gray',
            default => 'gray'
        };
    }
    
    public function render()
    {
        return view('livewire.transaction-viewer');
    }
}