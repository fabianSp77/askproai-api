<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Events\TransactionCreated;
use App\Events\TransactionUpdated;
use App\Events\TransactionDeleted;
use App\Jobs\SendTransactionWebhook;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        event(new TransactionCreated($transaction));
        
        // Clear transaction stats cache
        \App\Filament\Admin\Resources\TransactionResource\Widgets\TransactionStats::clearCache();
        
        // Dispatch webhook job if webhooks are configured
        if ($this->hasWebhookSubscribers('transaction.created')) {
            SendTransactionWebhook::dispatch($transaction, 'created')
                ->onQueue('webhooks');
        }
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        // Clear transaction stats cache
        \App\Filament\Admin\Resources\TransactionResource\Widgets\TransactionStats::clearCache();
        
        // Only send webhook if significant fields changed
        $significantFields = ['amount_cents', 'balance_after_cents', 'type', 'description'];
        $hasSignificantChange = false;
        
        foreach ($significantFields as $field) {
            if ($transaction->wasChanged($field)) {
                $hasSignificantChange = true;
                break;
            }
        }
        
        if ($hasSignificantChange && $this->hasWebhookSubscribers('transaction.updated')) {
            SendTransactionWebhook::dispatch($transaction, 'updated')
                ->onQueue('webhooks');
        }
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        // Clear transaction stats cache
        \App\Filament\Admin\Resources\TransactionResource\Widgets\TransactionStats::clearCache();
        
        if ($this->hasWebhookSubscribers('transaction.deleted')) {
            SendTransactionWebhook::dispatch($transaction, 'deleted')
                ->onQueue('webhooks');
        }
    }

    /**
     * Check if there are webhook subscribers for the given event
     */
    protected function hasWebhookSubscribers(string $event): bool
    {
        // Check if webhooks are enabled in config
        if (!config('services.webhooks.enabled', false)) {
            return false;
        }
        
        // Check if there are subscribers for this event
        // In a real implementation, this would check a database table
        // For now, we'll use config
        $webhookUrls = config("services.webhooks.urls.{$event}", []);
        
        return !empty($webhookUrls);
    }
}