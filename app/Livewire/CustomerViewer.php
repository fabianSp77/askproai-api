<?php

namespace App\Livewire;

use App\Models\Customer;
use Livewire\Component;

class CustomerViewer extends Component
{
    public $customerId;
    public Customer $customer;
    public $activeTab = 'overview';
    
    public function mount($customerId)
    {
        $this->customerId = $customerId;
        $this->customer = Customer::with([
            'appointments',
            'calls'
        ])->findOrFail($customerId);
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function render()
    {
        return view('livewire.customer-viewer', [
            'customer' => $this->customer,
            'activeTab' => $this->activeTab,
        ]);
    }
}