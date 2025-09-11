<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class BalanceWidget extends Component
{
    public $balance = 0;
    public $formattedBalance = '0,00 €';
    public $lowBalanceThreshold = 500; // 5€ in cents
    public $isLowBalance = false;
    public $lastUpdated;
    public $autoTopupEnabled = false;
    public $autoTopupThreshold = 1000; // 10€
    public $autoTopupAmount = 5000; // 50€
    
    // Real-time update flag
    public $realtimeEnabled = true;
    
    public function mount()
    {
        $this->loadBalance();
        $this->loadAutoTopupSettings();
    }
    
    public function loadBalance()
    {
        $tenant = Auth::user()->tenant;
        
        // Use cache with short TTL for performance
        $this->balance = Cache::remember(
            "balance.tenant.{$tenant->id}",
            5, // 5 seconds cache
            fn() => $tenant->balance_cents
        );
        
        $this->formattedBalance = $this->formatCurrency($this->balance);
        $this->isLowBalance = $this->balance < $this->lowBalanceThreshold;
        $this->lastUpdated = now()->format('H:i:s');
    }
    
    public function loadAutoTopupSettings()
    {
        $tenant = Auth::user()->tenant;
        $settings = $tenant->settings ?? [];
        
        $this->autoTopupEnabled = $settings['auto_topup_enabled'] ?? false;
        $this->autoTopupThreshold = $settings['auto_topup_threshold'] ?? 1000;
        $this->autoTopupAmount = $settings['auto_topup_amount'] ?? 5000;
    }
    
    /**
     * Listen for balance updates via websocket/SSE
     */
    #[On('balance-updated')]
    public function onBalanceUpdated($newBalance)
    {
        $oldBalance = $this->balance;
        $this->balance = $newBalance;
        $this->formattedBalance = $this->formatCurrency($this->balance);
        $this->isLowBalance = $this->balance < $this->lowBalanceThreshold;
        $this->lastUpdated = now()->format('H:i:s');
        
        // Trigger animations if balance changed
        if ($oldBalance !== $newBalance) {
            $this->dispatch('balance-animation', [
                'oldBalance' => $oldBalance,
                'newBalance' => $newBalance,
                'difference' => $newBalance - $oldBalance
            ]);
        }
        
        // Check for auto-topup trigger
        if ($this->autoTopupEnabled && $this->balance < $this->autoTopupThreshold) {
            $this->triggerAutoTopup();
        }
    }
    
    /**
     * Manual refresh button
     */
    public function refreshBalance()
    {
        Cache::forget("balance.tenant." . Auth::user()->tenant->id);
        $this->loadBalance();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Guthaben aktualisiert'
        ]);
    }
    
    /**
     * Quick topup shortcuts
     */
    public function quickTopup($amount)
    {
        $this->dispatch('open-topup-modal', ['amount' => $amount]);
    }
    
    /**
     * Toggle auto-topup
     */
    public function toggleAutoTopup()
    {
        $tenant = Auth::user()->tenant;
        $settings = $tenant->settings ?? [];
        
        $this->autoTopupEnabled = !$this->autoTopupEnabled;
        $settings['auto_topup_enabled'] = $this->autoTopupEnabled;
        
        $tenant->settings = $settings;
        $tenant->save();
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => $this->autoTopupEnabled 
                ? 'Auto-Aufladung aktiviert' 
                : 'Auto-Aufladung deaktiviert'
        ]);
    }
    
    /**
     * Trigger auto-topup process
     */
    private function triggerAutoTopup()
    {
        // Prevent multiple auto-topups within 5 minutes
        $lockKey = "auto-topup.tenant." . Auth::user()->tenant->id;
        
        if (Cache::has($lockKey)) {
            return;
        }
        
        Cache::put($lockKey, true, 300); // 5 minute lock
        
        // Dispatch auto-topup job
        dispatch(new \App\Jobs\ProcessAutoTopup(
            Auth::user()->tenant,
            $this->autoTopupAmount
        ));
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Auto-Aufladung wird verarbeitet...'
        ]);
    }
    
    /**
     * Format cents to currency
     */
    private function formatCurrency($cents)
    {
        return number_format($cents / 100, 2, ',', '.') . ' €';
    }
    
    /**
     * Polling for browsers without SSE support
     */
    public function pollBalance()
    {
        if (!$this->realtimeEnabled) {
            $this->loadBalance();
        }
    }
    
    public function render()
    {
        return view('livewire.customer.balance-widget');
    }
}