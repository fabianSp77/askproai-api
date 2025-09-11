<?php

namespace App\Livewire;

use App\Models\BalanceTopup;
use Livewire\Component;
use Filament\Notifications\Notification;

class BalanceTopupViewer extends Component
{
    public $topupId;
    public BalanceTopup $topup;
    
    public function mount($topupId)
    {
        $this->topupId = $topupId;
        $this->topup = BalanceTopup::with(['tenant', 'initiatedBy'])->findOrFail($topupId);
    }
    
    public function approveTopup()
    {
        if (!in_array($this->topup->status, ['pending', 'processing'])) {
            Notification::make()
                ->title('Aktion nicht möglich')
                ->body('Diese Aufladung kann nicht mehr genehmigt werden')
                ->warning()
                ->send();
            return;
        }
        
        $this->topup->markAsSucceeded();
        
        Notification::make()
            ->title('Aufladung genehmigt')
            ->body("Aufladung #{$this->topup->id} wurde erfolgreich genehmigt")
            ->success()
            ->send();
        
        $this->topup->refresh();
    }
    
    public function rejectTopup($reason)
    {
        if (!in_array($this->topup->status, ['pending', 'processing'])) {
            Notification::make()
                ->title('Aktion nicht möglich')
                ->body('Diese Aufladung kann nicht mehr abgelehnt werden')
                ->warning()
                ->send();
            return;
        }
        
        $this->topup->markAsFailed($reason);
        
        Notification::make()
            ->title('Aufladung abgelehnt')
            ->body("Aufladung #{$this->topup->id} wurde abgelehnt")
            ->warning()
            ->send();
        
        $this->topup->refresh();
    }
    
    public function getStatusColorProperty()
    {
        return match($this->topup->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'succeeded' => 'success',
            'failed' => 'danger',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }
    
    public function getStatusLabelProperty()
    {
        return match($this->topup->status) {
            'pending' => 'Ausstehend',
            'processing' => 'In Bearbeitung',
            'succeeded' => 'Erfolgreich',
            'failed' => 'Fehlgeschlagen',
            'cancelled' => 'Abgebrochen',
            default => $this->topup->status,
        };
    }
    
    public function getPaymentMethodLabelProperty()
    {
        return match($this->topup->payment_method) {
            'stripe' => 'Stripe',
            'bank_transfer' => 'Überweisung',
            'manual' => 'Manuell',
            'bonus' => 'Bonus',
            'trial' => 'Test',
            default => $this->topup->payment_method,
        };
    }
    
    public function render()
    {
        return view('livewire.balance-topup-viewer');
    }
}