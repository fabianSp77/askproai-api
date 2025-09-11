<?php

namespace App\Livewire;

use App\Models\Transaction;
use Livewire\Component;

class ViewTransactionComponent extends Component
{
    public Transaction $transaction;
    
    public function mount($id)
    {
        $this->transaction = Transaction::with(['tenant', 'call', 'appointment', 'topup'])->findOrFail($id);
    }
    
    public function render()
    {
        return view('livewire.view-transaction-component');
    }
}